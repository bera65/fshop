<?php

class SchemaOrg
{
	/** @return string[] */
	public static function getGlobalScripts(string $pageTitle, string $pageDesc): array
	{
		$scripts = [
			self::encode(self::buildWebPage($pageTitle, $pageDesc)),
			self::encode(self::buildOrganization()),
			self::encode(self::buildWebSite()),
		];

		return array_values(array_filter($scripts));
	}

	/**
	 * @param array<string, mixed> $product
	 * @param array<int, array<string, mixed>> $images
	 * @param array<int, array{name: string, url: string}> $breadcrumb
	 */
	public static function getProductScripts(
		array $product,
		array $images,
		array $breadcrumb,
		string $pageTitle,
		string $pageDesc,
		float $freeShippingMin,
		float $shippingFee
	): array {
		$scripts = [
			self::encode(self::buildProduct($product, $images, $pageDesc, $freeShippingMin, $shippingFee)),
			self::encode(self::buildBreadcrumbList($breadcrumb)),
		];

		$questions = self::getPublishedQuestions((int) $product['id_product']);
		$faq = $questions !== []
			? self::buildFaqFromQuestions($questions)
			: self::buildProductFaq($product, $freeShippingMin, $shippingFee);

		if ($faq === []) {
			$faq = self::buildProductFaq($product, $freeShippingMin, $shippingFee);
		}

		$scripts[] = self::encode($faq);

		return array_values(array_filter($scripts));
	}

	public static function currentUrl(): string
	{
		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
		$uri = $_SERVER['REQUEST_URI'] ?? '/';

		return $scheme . '://' . $host . $uri;
	}

	private static function buildWebPage(string $name, string $description): array
	{
		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$title = trim($name) !== '' ? $name : $siteName;
		$desc = trim($description) !== '' ? $description : $siteName;

		return [
			'@context' => 'https://schema.org',
			'@type' => 'WebPage',
			'url' => self::currentUrl(),
			'name' => $title,
			'description' => self::plainText($desc, 500),
		];
	}

	private static function buildOrganization(): array
	{
		global $domain;

		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$email = trim((string) Settings::get('CONTACT_EMAIL'));
		$phone = trim((string) (Settings::get('CONTACT_PHONE_TEL') ?: Settings::get('CONTACT_PHONE')));

		$org = [
			'@context' => 'https://schema.org',
			'@type' => 'Organization',
			'name' => $siteName,
			'legalName' => $siteName,
			'url' => rtrim($domain, '/') . '/',
			'logo' => SiteAssets::resolveLogoUrl('header'),
		];

		if ($email !== '') {
			$org['email'] = $email;
		}

		$street = self::firstSetting('SCHEMA_ORG_STREET', 'CONTACT_ADDRESS');
		$city = self::firstSetting('SCHEMA_ORG_CITY', 'CONTACT_CITY');
		$postal = self::firstSetting('SCHEMA_ORG_POSTAL', 'POSTAL_CODE');
		$country = self::firstSetting('CONTACT_COUNTRY') ?: 'TR';

		if ($street !== '' || $city !== '') {
			$org['address'] = array_filter([
				'@type' => 'PostalAddress',
				'streetAddress' => $street,
				'addressLocality' => $city,
				'postalCode' => $postal,
				'addressCountry' => $country,
			]);
		}

		if ($phone !== '') {
			$org['contactPoint'] = [[
				'@type' => 'ContactPoint',
				'telephone' => self::formatPhone($phone),
				'contactType' => 'customer service',
				'availableLanguage' => ['tr'],
			]];
		}

		$lat = self::numericCoord(self::firstSetting('SCHEMA_ORG_LAT', 'LATITUDE'));
		$lng = self::numericCoord(self::firstSetting('SCHEMA_ORG_LNG', 'LONGITUDE'));

		if ($lat !== null && $lng !== null) {
			$org['geo'] = [
				'@type' => 'GeoCoordinates',
				'latitude' => $lat,
				'longitude' => $lng,
			];
		}

		$org['openingHoursSpecification'] = [
			'@type' => 'OpeningHoursSpecification',
			'dayOfWeek' => [
				'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
			],
			'opens' => self::hourSpec(self::firstSetting('OPEN_HOUR'), '00:00'),
			'closes' => self::hourSpec(self::firstSetting('CLOSE_HOUR'), '23:59'),
		];

		$sameAs = self::socialLinks();

		if ($sameAs !== []) {
			$org['sameAs'] = $sameAs;
		}

		return $org;
	}

	private static function buildWebSite(): array
	{
		global $domain;

		$siteName = Settings::get('SITE_NAME') ?: 'FShop';

		return [
			'@context' => 'https://schema.org',
			'@type' => 'WebSite',
			'name' => $siteName,
			'alternateName' => [$siteName],
			'url' => rtrim($domain, '/') . '/',
			'potentialAction' => [
				'@type' => 'SearchAction',
				'target' => rtrim($domain, '/') . '/search?q={search_term_string}',
				'query-input' => 'required name=search_term_string',
			],
		];
	}

	/** @param array<string, mixed> $product */
	private static function buildProduct(
		array $product,
		array $images,
		string $pageDesc,
		float $freeShippingMin,
		float $shippingFee
	): array {
		$url = Product::getLink($product);
		$name = (string) $product['product_name'];
		$price = (float) $product['price'];
		$inStock = Product::isInStock($product);
		$description = self::plainText(
			$pageDesc !== '' ? $pageDesc : (string) ($product['short_description'] ?? strip_tags((string) $product['description'])),
			5000
		);
		$shippingValue = ($freeShippingMin > 0 && $price >= $freeShippingMin) ? 0.0 : max(0.0, $shippingFee);

		$imageUrls = [];

		foreach ($images as $image) {
			if (!empty($image['url'])) {
				$imageUrls[] = (string) $image['url'];
			}
		}

		if ($imageUrls === [] && !empty($product['image_url'])) {
			$imageUrls[] = (string) $product['image_url'];
		}

		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'Product',
			'@id' => $url . '#product',
			'productID' => (string) (int) $product['id_product'],
			'url' => $url,
			'name' => $name,
			'description' => $description,
			'category' => (string) ($product['category_name'] ?? ''),
			'brand' => [
				'@type' => 'Brand',
				'name' => (string) ($product['brand_name'] ?? ''),
			],
			'hasMerchantReturnPolicy' => [
				'@type' => 'MerchantReturnPolicy',
				'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
				'merchantReturnDays' => 14,
				'returnMethod' => 'https://schema.org/ReturnByMail',
			],
			'additionalProperty' => [
				[
					'@type' => 'PropertyValue',
					'name' => 'Stok Durumu',
					'value' => $inStock ? 'Stokta' : 'Tükendi',
				],
				[
					'@type' => 'PropertyValue',
					'name' => 'Kargo Ücreti',
					'value' => $shippingValue <= 0 ? 'Ücretsiz' : Tools::displayPrice($shippingValue),
				],
			],
			'offers' => [
				'@type' => 'Offer',
				'url' => $url,
				'price' => number_format($price, 2, '.', ''),
				'priceCurrency' => 'TRY',
				'availability' => $inStock
					? 'https://schema.org/InStock'
					: 'https://schema.org/OutOfStock',
				'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
				'shippingDetails' => [
					'@type' => 'OfferShippingDetails',
					'shippingRate' => [
						'@type' => 'MonetaryAmount',
						'value' => number_format($shippingValue, 2, '.', ''),
						'currency' => 'TRY',
					],
				],
				'seller' => [
					'@type' => 'Organization',
					'name' => Settings::get('SITE_NAME') ?: 'FShop',
				],
			],
		];

		if ($imageUrls !== []) {
			$schema['image'] = count($imageUrls) === 1 ? $imageUrls[0] : $imageUrls;
		}

		$sku = trim((string) ($product['stock_code'] ?? ''));

		if ($sku !== '') {
			$schema['sku'] = $sku;
		}

		$gtin = trim((string) ($product['barcode'] ?? ''));

		if ($gtin !== '') {
			$schema['gtin'] = $gtin;
		}

		$rating = self::getProductRating((int) $product['id_product']);

		if ($rating !== null) {
			$schema['aggregateRating'] = $rating;
		}

		return $schema;
	}

	/** @param array<int, array{name: string, url: string}> $breadcrumb */
	private static function buildBreadcrumbList(array $breadcrumb): array
	{
		global $domain;
		$items = [];
		$position = 1;

		foreach ($breadcrumb as $crumb) {
			$url = trim((string) ($crumb['url'] ?? ''));

			if ($url === '') {
				$url = self::currentUrl();
			} elseif (!preg_match('#^https?://#i', $url)) {
				$url = rtrim($domain, '/') . '/' . ltrim($url, '/');
			}

			$items[] = [
				'@type' => 'ListItem',
				'position' => $position++,
				'name' => (string) ($crumb['name'] ?? ''),
				'item' => $url,
			];
		}

		return [
			'@context' => 'https://schema.org',
			'@type' => 'BreadcrumbList',
			'itemListElement' => $items,
		];
	}

	/** @param array<string, mixed> $product */
	private static function buildProductFaq(array $product, float $freeShippingMin, float $shippingFee): array
	{
		$name = (string) $product['product_name'];
		$priceLabel = Tools::displayPrice((float) $product['price']);
		$inStock = Product::isInStock($product);
		$shippingLabel = ($freeShippingMin > 0 && (float) $product['price'] >= $freeShippingMin)
			? 'Ücretsiz kargo'
			: 'Kargo ücreti ' . Tools::displayPrice(max(0.0, $shippingFee));

		return [
			'@context' => 'https://schema.org',
			'@type' => 'FAQPage',
			'mainEntity' => [
				self::faqItem($name . ' fiyatı ne kadar?', $priceLabel),
				self::faqItem($name . ' stokta var mı?', $inStock ? 'Evet, ürün stokta.' : 'Hayır, ürün şu anda stokta yok.'),
				self::faqItem($name . ' kargo ücreti ne kadar?', $shippingLabel),
				self::faqItem('İade süresi nedir?', 'Ürünlerde 14 gün içinde iade hakkınız bulunmaktadır.'),
			],
		];
	}

	/** @param array<int, array<string, mixed>> $questions */
	private static function buildFaqFromQuestions(array $questions): array
	{
		$entities = [];

		foreach ($questions as $row) {
			$question = trim((string) ($row['question'] ?? ''));
			$answer = trim((string) ($row['answer'] ?? ''));

			if ($question === '' || $answer === '') {
				continue;
			}

			$entities[] = self::faqItem($question, $answer);
		}

		if ($entities === []) {
			return [];
		}

		return [
			'@context' => 'https://schema.org',
			'@type' => 'FAQPage',
			'mainEntity' => $entities,
		];
	}

	private static function faqItem(string $question, string $answer): array
	{
		return [
			'@type' => 'Question',
			'name' => $question,
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text' => $answer,
			],
		];
	}

	/** @return array<int, array<string, mixed>> */
	private static function getPublishedQuestions(int $idProduct): array
	{
		if (!Module::isEnabled('question') || !class_exists('QuestionModule')) {
			return [];
		}

		return QuestionModule::getPublishedForProduct($idProduct, 8);
	}

	/** @return array<string, mixed>|null */
	private static function getProductRating(int $idProduct): ?array
	{
		if (!Module::isEnabled('reviews') || !class_exists('ReviewsModule')) {
			return null;
		}

		$count = ReviewsModule::countApproved($idProduct);

		if ($count <= 0) {
			return null;
		}

		return [
			'@type' => 'AggregateRating',
			'ratingValue' => number_format(ReviewsModule::getAverageRating($idProduct), 1, '.', ''),
			'reviewCount' => $count,
			'bestRating' => '5',
			'worstRating' => '1',
		];
	}

	/** @return string[] */
	private static function socialLinks(): array
	{
		$pairs = [
			['SCHEMA_FACEBOOK_URL', 'FACEBOOK_LINK'],
			['SCHEMA_INSTAGRAM_URL', 'INSTAGRAM_LINK'],
			['', 'X_LINK'],
			['SCHEMA_YOUTUBE_URL', 'YOUTUBE_LINK'],
			['', 'LINKEDIN_LINK'],
			['', 'PINTEREST_LINK'],
			['', 'TIKTOK_LINK'],
		];
		$links = [];
		$seen = [];

		foreach ($pairs as $pair) {
			$url = $pair[0] !== '' ? trim((string) Settings::get($pair[0])) : '';

			if ($url === '') {
				$url = trim((string) Settings::get($pair[1]));
			}

			if ($url === '' || isset($seen[$url])) {
				continue;
			}

			$seen[$url] = true;
			$links[] = $url;
		}

		return $links;
	}

	private static function firstSetting(string ...$keys): string
	{
		foreach ($keys as $key) {
			$value = trim((string) Settings::get($key));

			if ($value !== '') {
				return $value;
			}
		}

		return '';
	}

	private static function numericCoord(string $value): ?float
	{
		$value = trim(str_replace(',', '.', $value));

		if ($value === '' || !is_numeric($value)) {
			return null;
		}

		return (float) $value;
	}

	private static function hourSpec(string $value, string $default): string
	{
		$value = trim($value);

		if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $value)) {
			return $value;
		}

		return $default;
	}

	private static function formatPhone(string $phone): string
	{
		$digits = preg_replace('/\D+/', '', $phone);

		if (empty($digits)) {
			return $phone;
		}

		// 0544... -> 90544...
		if (strpos($digits, '0') === 0) {
			$digits = '9' . $digits;
		}

		// 544... -> 90544...
		if (strpos($digits, '90') !== 0) {
			$digits = '90' . ltrim($digits, '0');
		}

		return '+' . $digits;
	}

	private static function plainText(string $text, int $max = 500): string
	{
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = preg_replace('/\s+/u', ' ', trim($text)) ?: '';

		if (mb_strlen($text) > $max) {
			$text = mb_substr($text, 0, $max - 3, 'UTF-8') . '...';
		}

		return $text;
	}

	private static function encode(array $data): string
	{
		if ($data === []) {
			return '';
		}

		$json = Security::jsonForHtmlScript($data);

		return ($json === 'null' || $json === '') ? '' : $json;
	}
}
