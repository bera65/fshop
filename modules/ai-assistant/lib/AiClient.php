<?php

class AiAssistantClient
{
	public static function isConfigured(): bool
	{
		return trim((string) Settings::get('AI_ASSISTANT_API_KEY')) !== '';
	}

	public static function chat(string $systemPrompt, string $userPrompt, array $options = []): array
	{
		$apiKey = trim((string) Settings::get('AI_ASSISTANT_API_KEY'));

		if ($apiKey === '') {
			return [
				'success' => false,
				'message' => 'API anahtarı tanımlı değil. Modül ayarlarından ekleyin.',
			];
		}

		$baseUrl = rtrim((string) Settings::get('AI_ASSISTANT_BASE_URL'), '/');

		if ($baseUrl === '') {
			$baseUrl = 'https://api.openai.com/v1';
		}

		$model = trim((string) Settings::get('AI_ASSISTANT_MODEL'));

		if ($model === '') {
			$model = 'gpt-4o-mini';
		}

		$maxTokens = (int) ($options['max_tokens'] ?? 0);

		if ($maxTokens <= 0) {
			$maxTokens = (int) Settings::get('AI_ASSISTANT_MAX_TOKENS');
		}

		if ($maxTokens < 256) {
			$maxTokens = 1200;
		}

		if ($maxTokens > 4000) {
			$maxTokens = 4000;
		}

		$temperature = isset($options['temperature'])
			? (float) $options['temperature']
			: 0.4;

		$payload = [
			'model' => $model,
			'messages' => [
				['role' => 'system', 'content' => $systemPrompt],
				['role' => 'user', 'content' => $userPrompt],
			],
			'temperature' => $temperature,
			'max_tokens' => $maxTokens,
		];

		if (!empty($options['json'])) {
			$payload['response_format'] = ['type' => 'json_object'];
		}

		$url = $baseUrl . '/chat/completions';
		$headers = [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $apiKey,
		];

		$provider = (string) Settings::get('AI_ASSISTANT_PROVIDER');

		if ($provider === 'openrouter') {
			$headers[] = 'HTTP-Referer: ' . rtrim((string) Settings::get('DOMAIN'), '/');
			$headers[] = 'X-Title: FShop AI Assistant';
		}

		$response = self::httpPostJson($url, $payload, $headers);

		if (!$response['success']) {
			return $response;
		}

		$body = $response['body'];
		$content = trim((string) ($body['choices'][0]['message']['content'] ?? ''));

		if ($content === '') {
			return [
				'success' => false,
				'message' => 'Yapay zeka boş yanıt döndürdü',
				'raw' => $body,
			];
		}

		return [
			'success' => true,
			'message' => 'Tamam',
			'content' => $content,
			'model' => (string) ($body['model'] ?? $model),
			'usage' => $body['usage'] ?? null,
			'raw' => $body,
		];
	}

	public static function improveProduct(array $fields, string $tone = 'professional', string $lang = 'tr'): array
	{
		$system = 'Sen deneyimli bir Türk e-ticaret SEO ve ürün editörüsün. '
			. 'Mağaza ürün metinlerini iyileştirirsin. Abartılı vaatlerden kaçın. '
			. 'Yanıtını yalnızca geçerli JSON olarak ver.';

		$user = [
			'task' => 'Ürün alanlarını iyileştir',
			'tone' => $tone,
			'language' => $lang,
			'fields' => [
				'product_name' => (string) ($fields['product_name'] ?? ''),
				'short_description' => (string) ($fields['short_description'] ?? ''),
				'description' => (string) ($fields['description'] ?? ''),
				'meta_title' => (string) ($fields['meta_title'] ?? ''),
				'meta_description' => (string) ($fields['meta_description'] ?? ''),
			],
			'instructions' => [
				'product_name: çekici, net, max 80 karakter',
				'short_description: 1-2 cümle, satış odaklı',
				'description: HTML paragraf kullanabilirsin (<p>), özellikler için madde işaretleri',
				'meta_title: SEO başlık, max 60 karakter',
				'meta_description: SEO açıklama, max 155 karakter',
				'Sadece verilen dili kullan',
				'JSON anahtarları: product_name, short_description, description, meta_title, meta_description, notes',
			],
		];

		$result = self::chat($system, json_encode($user, JSON_UNESCAPED_UNICODE), [
			'json' => true,
			'max_tokens' => 2000,
			'temperature' => 0.5,
		]);

		if (empty($result['success'])) {
			$retry = self::chat(
				$system . ' Yalnızca geçerli bir JSON nesnesi döndür.',
				json_encode($user, JSON_UNESCAPED_UNICODE),
				['json' => false, 'max_tokens' => 2000, 'temperature' => 0.5]
			);

			if (!empty($retry['success'])) {
				$result = $retry;
			} else {
				return $result;
			}
		}

		$decoded = self::decodeJsonContent((string) $result['content']);

		if ($decoded === null) {
			return [
				'success' => false,
				'message' => 'Yapay zeka yanıtı JSON olarak çözülemedi',
				'content' => $result['content'],
			];
		}

		return [
			'success' => true,
			'message' => 'Öneriler hazır',
			'suggestions' => [
				'product_name' => trim((string) ($decoded['product_name'] ?? '')),
				'short_description' => trim((string) ($decoded['short_description'] ?? '')),
				'description' => trim((string) ($decoded['description'] ?? '')),
				'meta_title' => trim((string) ($decoded['meta_title'] ?? '')),
				'meta_description' => trim((string) ($decoded['meta_description'] ?? '')),
			],
			'notes' => trim((string) ($decoded['notes'] ?? '')),
			'model' => $result['model'] ?? '',
		];
	}

	public static function summarizeAdminPage(string $pageName, string $pageTitle, string $pageText = ''): array
	{
		if ($pageName === 'dashboard') {
			return self::analyzeDashboard(self::collectDashboardStats());
		}

		$system = 'Sen bir e-ticaret yönetim paneli asistanısın. '
			. 'Türkçe, kısa ve net özetler yaz. Markdown kullanabilirsin (başlık, madde). '
			. 'Abartma; yalnızca verilen bağlama dayan.';

		$user = "Admin paneli sayfasını özetle.\n"
			. "Sayfa kodu: {$pageName}\n"
			. "Sayfa başlığı: {$pageTitle}\n\n"
			. "Şunları kapsasın:\n"
			. "1) Bu sayfanın amacı\n"
			. "2) Görünen önemli bilgiler / alanlar\n"
			. "3) Yönetici için 3 pratik öneri\n\n"
			. "SAYFA METNİ:\n"
			. mb_substr(trim($pageText) !== '' ? $pageText : '(Metin yok — genel açıklama yap)', 0, 12000);

		$result = self::chat($system, $user, [
			'json' => false,
			'max_tokens' => 1600,
			'temperature' => 0.35,
		]);

		if (empty($result['success'])) {
			return $result;
		}

		return [
			'success' => true,
			'message' => 'Özet hazır',
			'analysis' => (string) $result['content'],
			'model' => $result['model'] ?? '',
		];
	}

	/**
	 * @param list<array{id:string,label:string,title:string,description:string,default_title?:string,default_desc?:string}> $pages
	 */
	public static function writeSeoPages(array $pages, string $lang = 'tr'): array
	{
		$siteName = trim((string) Settings::get('SITE_NAME')) ?: 'Mağaza';
		$system = 'Sen deneyimli bir Türk e-ticaret SEO uzmanısın. '
			. 'Mağaza sayfaları için meta title ve description yazarsın. '
			. 'Yanıtını yalnızca geçerli JSON olarak ver.';

		$user = [
			'task' => 'Mağaza SEO meta alanlarını yaz',
			'language' => $lang,
			'site_name' => $siteName,
			'pages' => $pages,
			'instructions' => [
				'Her sayfa için title (max 60 karakter) ve description (max 155 karakter) üret',
				'Boş alanları doldur; dolu alanları iyileştir',
				'Site adını uygun yerlerde kullan',
				'Clickbait ve abartılı vaatten kaçın',
				'JSON formatı: {"pages":{"home":{"title":"...","description":"..."},...},"notes":"..."}',
			],
		];

		$result = self::chat($system, json_encode($user, JSON_UNESCAPED_UNICODE), [
			'json' => true,
			'max_tokens' => 2200,
			'temperature' => 0.45,
		]);

		if (empty($result['success'])) {
			return $result;
		}

		$decoded = self::decodeJsonContent((string) $result['content']);

		if ($decoded === null || !is_array($decoded['pages'] ?? null)) {
			return [
				'success' => false,
				'message' => 'Yapay zeka yanıtı JSON olarak çözülemedi',
				'content' => $result['content'],
			];
		}

		$suggestions = [];

		foreach ($decoded['pages'] as $pageId => $row) {
			if (!is_array($row)) {
				continue;
			}

			$suggestions[(string) $pageId] = [
				'title' => mb_substr(trim((string) ($row['title'] ?? '')), 0, 255),
				'description' => mb_substr(trim((string) ($row['description'] ?? '')), 0, 512),
			];
		}

		if ($suggestions === []) {
			return [
				'success' => false,
				'message' => 'SEO önerisi üretilemedi',
			];
		}

		return [
			'success' => true,
			'message' => 'SEO önerileri hazır',
			'suggestions' => $suggestions,
			'notes' => trim((string) ($decoded['notes'] ?? '')),
			'model' => $result['model'] ?? '',
		];
	}

	public static function writeCmsPage(array $fields, string $tone = 'professional', string $lang = 'tr'): array
	{
		$system = 'Sen deneyimli bir Türk e-ticaret içerik editörüsün. '
			. 'CMS / bilgilendirme sayfaları yazarsın. Abartılı vaatlerden kaçın. '
			. 'Yanıtını yalnızca geçerli JSON olarak ver.';

		$user = [
			'task' => 'CMS sayfası içeriği üret veya iyileştir',
			'tone' => $tone,
			'language' => $lang,
			'fields' => [
				'title' => (string) ($fields['title'] ?? ''),
				'summary' => (string) ($fields['summary'] ?? ''),
				'content' => (string) ($fields['content'] ?? ''),
				'meta_title' => (string) ($fields['meta_title'] ?? ''),
				'meta_description' => (string) ($fields['meta_description'] ?? ''),
				'slug' => (string) ($fields['slug'] ?? ''),
			],
			'hint' => (string) ($fields['hint'] ?? ''),
			'instructions' => [
				'title: net sayfa başlığı',
				'summary: 1-2 cümle kısa özet',
				'content: HTML (<p>, <ul><li>, <h2>) ile düzenli, okunabilir içerik',
				'meta_title: max 60 karakter',
				'meta_description: max 155 karakter',
				'Boş alanları doldur; dolu alanları iyileştir',
				'JSON anahtarları: title, summary, content, meta_title, meta_description, notes',
			],
		];

		$result = self::chat($system, json_encode($user, JSON_UNESCAPED_UNICODE), [
			'json' => true,
			'max_tokens' => 2800,
			'temperature' => 0.5,
		]);

		if (empty($result['success'])) {
			return $result;
		}

		$decoded = self::decodeJsonContent((string) $result['content']);

		if ($decoded === null) {
			return [
				'success' => false,
				'message' => 'Yapay zeka yanıtı JSON olarak çözülemedi',
				'content' => $result['content'],
			];
		}

		return [
			'success' => true,
			'message' => 'CMS önerileri hazır',
			'suggestions' => [
				'title' => trim((string) ($decoded['title'] ?? '')),
				'summary' => trim((string) ($decoded['summary'] ?? '')),
				'content' => trim((string) ($decoded['content'] ?? '')),
				'meta_title' => trim((string) ($decoded['meta_title'] ?? '')),
				'meta_description' => trim((string) ($decoded['meta_description'] ?? '')),
			],
			'notes' => trim((string) ($decoded['notes'] ?? '')),
			'model' => $result['model'] ?? '',
		];
	}

	public static function writeBlogPost(array $fields, string $tone = 'professional', string $lang = 'tr'): array
	{
		$system = 'Sen deneyimli bir Türk e-ticaret blog yazarısın. '
			. 'SEO uyumlu, akıcı ve pratik yazılar üretirsin. Abartılı vaatlerden kaçın. '
			. 'Yanıtını yalnızca geçerli JSON olarak ver.';

		$user = [
			'task' => !empty($fields['editing']) ? 'Mevcut blog yazısını iyileştir' : 'Yeni blog yazısı üret',
			'tone' => $tone,
			'language' => $lang,
			'idea' => (string) ($fields['idea'] ?? ''),
			'fields' => [
				'title' => (string) ($fields['title'] ?? ''),
				'excerpt' => (string) ($fields['excerpt'] ?? ''),
				'content' => (string) ($fields['content'] ?? ''),
				'meta_title' => (string) ($fields['meta_title'] ?? ''),
				'meta_description' => (string) ($fields['meta_description'] ?? ''),
				'slug' => (string) ($fields['slug'] ?? ''),
			],
			'instructions' => [
				'title: çekici blog başlığı',
				'excerpt: 1-2 cümle özet',
				'content: HTML (<p>, <h2>, <ul><li>) ile düzenli, en az 3-5 paragraf',
				'meta_title: max 60 karakter',
				'meta_description: max 155 karakter',
				'slug: opsiyonel, kısa URL slug (küçük harf, tire)',
				'idea boş değilse konuyu ona göre yaz',
				'JSON anahtarları: title, excerpt, content, meta_title, meta_description, slug, notes',
			],
		];

		$result = self::chat($system, json_encode($user, JSON_UNESCAPED_UNICODE), [
			'json' => true,
			'max_tokens' => 3200,
			'temperature' => 0.55,
		]);

		if (empty($result['success'])) {
			return $result;
		}

		$decoded = self::decodeJsonContent((string) $result['content']);

		if ($decoded === null) {
			return [
				'success' => false,
				'message' => 'Yapay zeka yanıtı JSON olarak çözülemedi',
				'content' => $result['content'],
			];
		}

		return [
			'success' => true,
			'message' => 'Blog önerileri hazır',
			'suggestions' => [
				'title' => trim((string) ($decoded['title'] ?? '')),
				'excerpt' => trim((string) ($decoded['excerpt'] ?? '')),
				'content' => trim((string) ($decoded['content'] ?? '')),
				'meta_title' => trim((string) ($decoded['meta_title'] ?? '')),
				'meta_description' => trim((string) ($decoded['meta_description'] ?? '')),
				'slug' => trim((string) ($decoded['slug'] ?? '')),
			],
			'notes' => trim((string) ($decoded['notes'] ?? '')),
			'model' => $result['model'] ?? '',
		];
	}

	/**
	 * Mağaza UI çevirileri: İngilizce kaynaktan hedef dile veya İngilizceyi cilalama.
	 *
	 * @param list<array{key:string,en:string}> $items
	 * @return array{success:bool,message:string,translations?:array<string,string>,english?:array<string,string>}
	 */
	public static function translateUiStrings(array $items, string $targetLang, string $mode = 'translate'): array
	{
		$clean = [];

		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}

			$key = trim((string) ($item['key'] ?? ''));
			$en = trim((string) ($item['en'] ?? ''));

			if ($key === '' || $en === '') {
				continue;
			}

			$clean[] = [
				'key' => $key,
				'en' => $en,
			];

			if (count($clean) >= 40) {
				break;
			}
		}

		if ($clean === []) {
			return [
				'success' => false,
				'message' => 'Çevrilecek metin bulunamadı',
			];
		}

		$targetLang = strtolower(trim($targetLang));
		$mode = $mode === 'polish_en' ? 'polish_en' : 'translate';

		if ($mode === 'polish_en') {
			$system = 'You are an expert ecommerce UX writer. '
				. 'Polish short UI strings into clear, natural British/American English suitable as a translation source. '
				. 'Keep meaning. Fix typos. Do not add marketing fluff. '
				. 'Return ONLY valid JSON.';

			$user = [
				'task' => 'polish_english_ui_strings',
				'items' => $clean,
				'instructions' => [
					'Return JSON object: {"english":{"key":"polished English",...},"notes":"..."}',
					'Keys must match input keys exactly',
					'Keep placeholders like {name}, %s, HTML entities if present',
					'Keep short labels short',
				],
			];
		} else {
			$langName = $targetLang !== '' ? $targetLang : 'tr';
			$system = 'You are a professional ecommerce UI translator. '
				. 'Translate concise storefront UI strings from English into the target language. '
				. 'Keep tone natural for online shopping. Preserve placeholders. '
				. 'Return ONLY valid JSON.';

			$user = [
				'task' => 'translate_ui_strings',
				'source_language' => 'en',
				'target_language' => $langName,
				'items' => $clean,
				'instructions' => [
					'Return JSON object: {"translations":{"key":"translated text",...},"notes":"..."}',
					'Keys must match input keys exactly',
					'Do not leave English unless the word is a brand name',
					'Keep placeholders like {name}, %s unchanged',
					'Be concise; match UI length when possible',
				],
			];
		}

		$result = self::chat($system, json_encode($user, JSON_UNESCAPED_UNICODE), [
			'json' => true,
			'max_tokens' => 3500,
			'temperature' => 0.2,
		]);

		if (empty($result['success'])) {
			return $result;
		}

		$decoded = self::decodeJsonContent((string) $result['content']);

		if ($decoded === null) {
			return [
				'success' => false,
				'message' => 'Yapay zeka yanıtı JSON olarak çözülemedi',
				'content' => $result['content'],
			];
		}

		if ($mode === 'polish_en') {
			$map = is_array($decoded['english'] ?? null) ? $decoded['english'] : [];
			$out = [];

			foreach ($clean as $row) {
				$key = $row['key'];
				$val = trim((string) ($map[$key] ?? ''));

				if ($val !== '') {
					$out[$key] = $val;
				}
			}

			return [
				'success' => true,
				'message' => count($out) . ' İngilizce metin iyileştirildi',
				'english' => $out,
				'notes' => trim((string) ($decoded['notes'] ?? '')),
				'model' => $result['model'] ?? '',
			];
		}

		$map = is_array($decoded['translations'] ?? null) ? $decoded['translations'] : [];
		$out = [];

		foreach ($clean as $row) {
			$key = $row['key'];
			$val = trim((string) ($map[$key] ?? ''));

			if ($val !== '') {
				$out[$key] = $val;
			}
		}

		return [
			'success' => true,
			'message' => count($out) . ' çeviri üretildi',
			'translations' => $out,
			'notes' => trim((string) ($decoded['notes'] ?? '')),
			'model' => $result['model'] ?? '',
		];
	}

	public static function analyzeDashboard(array $stats): array
	{
		$system = 'Sen bir e-ticaret analisti ve büyüme danışmanısın. '
			. 'Türkçe, net ve uygulanabilir öneriler ver. Markdown kullan (başlık, madde). '
			. 'Abartma; verilere dayan.';

		$user = "Aşağıdaki mağaza verilerini analiz et.\n"
			. "Şunları kapsasın:\n"
			. "1) Genel performans özeti\n"
			. "2) Satış ve ciro durumu\n"
			. "3) Çok satan / öne çıkan ürünler\n"
			. "4) Riskler (düşük stok, bekleyen siparişler vb.)\n"
			. "5) Öncelikli 5 aksiyon önerisi\n\n"
			. "VERİLER (JSON):\n"
			. json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

		$result = self::chat($system, $user, [
			'json' => false,
			'max_tokens' => 2200,
			'temperature' => 0.35,
		]);

		if (empty($result['success'])) {
			return $result;
		}

		return [
			'success' => true,
			'message' => 'Analiz hazır',
			'analysis' => (string) $result['content'],
			'model' => $result['model'] ?? '',
		];
	}

	public static function collectDashboardStats(): array
	{
		$cancelled = class_exists('Order') ? Order::STATUS_CANCELLED : 4;
		$base = Admin::getDashboardStats();
		$charts = Admin::getDashboardCharts();

		$topProducts = DB::execute(
			'SELECT od.product_name, od.id_product, SUM(od.qty) AS sold_qty,
				COALESCE(SUM(od.total), 0) AS sold_revenue
			 FROM order_detail od
			 INNER JOIN orders o ON o.id_order = od.id_order
			 WHERE o.status != ?
			   AND o.date_add >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			 GROUP BY od.id_product, od.product_name
			 ORDER BY sold_qty DESC
			 LIMIT 10',
			[$cancelled]
		) ?: [];

		$lowStock = DB::execute(
			'SELECT id_product, product_name, stock, price
			 FROM products
			 WHERE active = 1 AND stock <= 5
			 ORDER BY stock ASC, id_product DESC
			 LIMIT 10'
		) ?: [];

		$recentOrders = DB::execute(
			'SELECT reference, total, status, date_add
			 FROM orders
			 ORDER BY id_order DESC
			 LIMIT 8'
		) ?: [];

		return [
			'generated_at' => date('c'),
			'kpi' => [
				'orders_total' => $base['orders_total'] ?? 0,
				'orders_today' => $base['orders_today'] ?? 0,
				'orders_pending' => $base['orders_pending'] ?? 0,
				'orders_awaiting_shipment' => $base['orders_awaiting_shipment'] ?? 0,
				'products_total' => $base['products_total'] ?? 0,
				'products_low_stock' => $base['products_low_stock'] ?? 0,
				'users_total' => $base['users_total'] ?? 0,
				'users_today' => $base['users_today'] ?? 0,
				'revenue_today' => $base['revenue_today'] ?? 0,
				'revenue_yesterday' => $base['revenue_yesterday'] ?? 0,
				'revenue_month' => $base['revenue_month'] ?? 0,
				'revenue_total' => $base['revenue_total'] ?? 0,
			],
			'top_products_30d' => $topProducts,
			'low_stock_products' => $lowStock,
			'recent_orders' => $recentOrders,
			'daily_14d' => $charts['daily'] ?? [],
			'order_status_breakdown' => $charts['status'] ?? [],
		];
	}

	/** @return array<string, mixed>|null */
	private static function decodeJsonContent(string $content): ?array
	{
		$content = trim($content);

		if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $content, $m)) {
			$content = trim($m[1]);
		}

		$decoded = json_decode($content, true);

		if (is_array($decoded)) {
			return $decoded;
		}

		if (preg_match('/\{.*\}/s', $content, $m)) {
			$decoded = json_decode($m[0], true);

			if (is_array($decoded)) {
				return $decoded;
			}
		}

		return null;
	}

	/** @param array<int, string> $headers */
	private static function httpPostJson(string $url, array $payload, array $headers): array
	{
		$body = json_encode($payload, JSON_UNESCAPED_UNICODE);

		if ($body === false) {
			return ['success' => false, 'message' => 'İstek hazırlanamadı'];
		}

		if (!function_exists('curl_init')) {
			return ['success' => false, 'message' => 'cURL eklentisi gerekli'];
		}

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POSTFIELDS => $body,
			CURLOPT_TIMEOUT => 90,
			CURLOPT_CONNECTTIMEOUT => 15,
		]);

		$raw = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($errno) {
			return [
				'success' => false,
				'message' => 'Bağlantı hatası: ' . $error,
			];
		}

		$decoded = is_string($raw) ? json_decode($raw, true) : null;

		if ($status < 200 || $status >= 300) {
			$apiMessage = '';

			if (is_array($decoded)) {
				$apiMessage = (string) ($decoded['error']['message'] ?? $decoded['message'] ?? '');
			}

			return [
				'success' => false,
				'message' => $apiMessage !== ''
					? ('API hatası (' . $status . '): ' . $apiMessage)
					: ('API hatası (HTTP ' . $status . ')'),
				'raw' => $decoded,
			];
		}

		if (!is_array($decoded)) {
			return [
				'success' => false,
				'message' => 'Geçersiz API yanıtı',
			];
		}

		return [
			'success' => true,
			'body' => $decoded,
		];
	}
}
