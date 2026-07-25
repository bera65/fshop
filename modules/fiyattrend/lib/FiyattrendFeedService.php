<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

class FiyattrendFeedService
{
	public const PANEL_URL = 'https://fiyattrend.com/panel';

	private const CACHE_FILE = 'fiyattrend_feed.xml';

	public static function cachePath(): string
	{
		return dirname(__DIR__, 3) . '/cache/' . self::CACHE_FILE;
	}

	public static function isEnabled(): bool
	{
		return Settings::get('FT_ENABLED') === '1';
	}

	public static function getFeedToken(): string
	{
		return trim((string) Settings::get('FT_FEED_TOKEN'));
	}

	public static function buildFeedUrl(string $domain): string
	{
		$token = self::getFeedToken();

		return rtrim($domain, '/') . '/api/module.php?m=fiyattrend&action=feed&token=' . rawurlencode($token);
	}

	public static function buildFeed(): string
	{
		$cachePath = self::cachePath();
		$ttl = (int) (Settings::get('FT_CACHE_TTL') ?: 360) * 60;

		if (file_exists($cachePath) && (time() - filemtime($cachePath)) < $ttl) {
			return (string) file_get_contents($cachePath);
		}

		$xml = self::generateXml();
		$dir = dirname($cachePath);

		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		file_put_contents($cachePath, $xml);
		Settings::set('FT_LAST_REGEN', date('d.m.Y H:i'));

		return $xml;
	}

	public static function regenerate(): array
	{
		@unlink(self::cachePath());
		$xml = self::buildFeed();
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$count = $dom->getElementsByTagName('item')->length;

		return [
			'success' => true,
			'message' => 'Feed yenilendi. ' . $count . ' ürün işlendi.',
			'product_count' => $count,
			'generated_at' => Settings::get('FT_LAST_REGEN') ?: date('d.m.Y H:i'),
		];
	}

	/** @return array{success: bool, total: int, preview: array<int, array<string, string>>, generated_at: string} */
	public static function preview(int $limit = 5): array
	{
		$xml = self::generateXml();
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$items = $dom->getElementsByTagName('item');
		$preview = [];
		$max = min($limit, $items->length);

		for ($i = 0; $i < $max; $i++) {
			$item = $items->item($i);
			$row = [];

			if (!$item) {
				continue;
			}

			foreach ($item->childNodes as $child) {
				if ($child->nodeType !== XML_ELEMENT_NODE) {
					continue;
				}

				$row[$child->localName] = $child->nodeValue;
			}

			$preview[] = $row;
		}

		return [
			'success' => true,
			'total' => $items->length,
			'preview' => $preview,
			'generated_at' => date('d.m.Y H:i:s'),
		];
	}

	public static function generateXml(): string
	{
		$domain = rtrim((string) Settings::get('DOMAIN'), '/');
		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$currency = Settings::get('FT_CURRENCY') ?: 'TRY';
		$condition = Settings::get('FT_CONDITION') ?: 'new';
		$brandDefault = Settings::get('FT_BRAND_FALLBACK') ?: $siteName;
		$inclOutstock = Settings::get('FT_INCLUDE_OUTSTOCK') === '1';

		$excludeCatIds = array_filter(
			array_map('intval', explode(',', (string) Settings::get('FT_EXCLUDE_CATS')))
		);

		$products = self::fetchProducts($inclOutstock, $excludeCatIds);

		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;

		$rss = $dom->createElement('rss');
		$rss->setAttribute('version', '2.0');
		$rss->setAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
		$dom->appendChild($rss);

		$channel = $dom->createElement('channel');
		$rss->appendChild($channel);

		self::addTextNode($dom, $channel, 'title', $siteName . ' — FiyatTrend Feed');
		self::addTextNode($dom, $channel, 'link', $domain . '/');
		self::addTextNode($dom, $channel, 'description', $siteName . ' ürün kataloğu (Google Merchant / FiyatTrend)');

		foreach ($products as $p) {
			$item = $dom->createElement('item');

			self::addGNode($dom, $item, 'id', (string) $p['id_product']);
			self::addTextNode($dom, $item, 'title', self::cleanTitle($p['product_name']));
			self::addTextNode($dom, $item, 'link', self::toAbsoluteUrl($domain, (string) ($p['url'] ?? '')));

			$desc = !empty($p['description_short']) ? $p['description_short'] : ($p['description'] ?? $p['product_name']);
			self::addTextNode($dom, $item, 'description', self::stripHtml((string) $desc));

			self::addGNode($dom, $item, 'image_link', self::toAbsoluteUrl($domain, (string) ($p['image_url'] ?? '')));
			self::addGNode($dom, $item, 'availability', $p['in_stock'] ? 'in_stock' : 'out_of_stock');

			$priceStr = number_format((float) $p['price'], 2, '.', '') . ' ' . $currency;
			self::addGNode($dom, $item, 'price', $priceStr);

			if (!empty($p['old_price']) && (float) $p['old_price'] > (float) $p['price']) {
				self::addGNode($dom, $item, 'sale_price', $priceStr);
				$oldStr = number_format((float) $p['old_price'], 2, '.', '') . ' ' . $currency;
				$nodes = $item->getElementsByTagNameNS('http://base.google.com/ns/1.0', 'price');

				if ($nodes->length >= 1) {
					$nodes->item($nodes->length - 1)->nodeValue = $oldStr;
				}
			}

			self::addGNode($dom, $item, 'condition', $condition);

			$brand = !empty($p['brand_name']) ? $p['brand_name'] : $brandDefault;
			self::addGNode($dom, $item, 'brand', $brand);

			if (!empty($p['barcode'])) {
				self::addGNode($dom, $item, 'gtin', preg_replace('/\D/', '', (string) $p['barcode']));
			}

			if (!empty($p['product_code'])) {
				self::addGNode($dom, $item, 'mpn', (string) $p['product_code']);
			}

			if (!empty($p['category_name'])) {
				self::addGNode($dom, $item, 'product_type', htmlspecialchars((string) $p['category_name'], ENT_XML1));
			}

			if (isset($p['quantity'])) {
				self::addGNode($dom, $item, 'quantity_to_sell_on_google', (string) (int) $p['quantity']);
			}

			if (!empty($p['extra_images'])) {
				$extras = is_array($p['extra_images']) ? $p['extra_images'] : json_decode((string) $p['extra_images'], true);

				if (is_array($extras)) {
					foreach (array_slice($extras, 0, 9) as $img) {
						self::addGNode($dom, $item, 'additional_image_link', self::toAbsoluteUrl($domain, (string) $img));
					}
				}
			}

			$shipping = $dom->createElementNS('http://base.google.com/ns/1.0', 'g:shipping');
			self::addGNode($dom, $shipping, 'country', 'TR');
			self::addGNode($dom, $shipping, 'service', 'Standart Kargo');
			$cargoHints = class_exists('Cargo') ? Cargo::getDisplayHints() : ['free_shipping_min' => 0.0, 'shipping_fee' => 0.0];
			$freeMin = (float) ($cargoHints['free_shipping_min'] ?? 0);
			$shippingFee = (float) ($cargoHints['shipping_fee'] ?? 0);
			$shippingCost = ($freeMin > 0 && (float) $p['price'] >= $freeMin) ? 0.0 : $shippingFee;
			self::addGNode($dom, $shipping, 'price', number_format($shippingCost, 2, '.', '') . ' ' . $currency);
			$item->appendChild($shipping);

			$channel->appendChild($item);
		}

		return $dom->saveXML();
	}

	/** @return array<int, array<string, mixed>> */
	private static function fetchProducts(bool $inclOutstock, array $excludeCatIds): array
	{
		$sql = '
			SELECT
				p.id_product,
				p.product_name,
				p.product_link,
				p.description,
				p.short_description AS description_short,
				p.price,
				p.old_price,
				p.stock,
				p.stock AS quantity,
				p.barcode,
				p.stock_code AS product_code,
				p.id_category,
				c.category_link,
				c.category_name,
				b.brand_name,
				i.id_image
			FROM products p
			LEFT JOIN categories c ON c.id_category = p.id_category
			LEFT JOIN brands b ON b.id_brand = p.id_brand
			LEFT JOIN images i ON i.id_product = p.id_product AND i.cover = 1
			WHERE p.active = 1
		';

		if (!$inclOutstock) {
			$sql .= ' AND p.stock > 0';
		}

		if ($excludeCatIds !== []) {
			$placeholders = implode(',', array_fill(0, count($excludeCatIds), '?'));
			$sql .= ' AND p.id_category NOT IN (' . $placeholders . ')';
		}

		$sql .= ' ORDER BY p.id_product ASC';

		$rows = DB::execute($sql, $excludeCatIds !== [] ? array_values($excludeCatIds) : []);

		if (!is_array($rows)) {
			return [];
		}

		return array_map(static function (array $row): array {
			$row = Product::enrich($row);
			$row['quantity'] = (int) ($row['stock'] ?? 0);

			return $row;
		}, $rows);
	}

	private static function addTextNode(DOMDocument $dom, DOMNode $parent, string $tag, string $value): void
	{
		$node = $dom->createElement($tag);
		$node->appendChild($dom->createCDATASection($value));
		$parent->appendChild($node);
	}

	private static function addGNode(DOMDocument $dom, DOMNode $parent, string $tag, string $value): void
	{
		$node = $dom->createElementNS('http://base.google.com/ns/1.0', 'g:' . $tag);
		$node->appendChild($dom->createTextNode($value));
		$parent->appendChild($node);
	}

	private static function stripHtml(string $html): string
	{
		$text = strip_tags($html);
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = preg_replace('/\s+/', ' ', $text);

		return trim((string) $text);
	}

	private static function cleanTitle(string $title): string
	{
		$title = strip_tags($title);
		$title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		return mb_substr(trim($title), 0, 150);
	}

	private static function toAbsoluteUrl(string $domain, string $value): string
	{
		$value = trim($value);

		if ($value === '') {
			return rtrim($domain, '/') . '/';
		}

		if (preg_match('~^https?://~i', $value)) {
			return $value;
		}

		return rtrim($domain, '/') . '/' . ltrim($value, '/');
	}
}
