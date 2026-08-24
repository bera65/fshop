<?php

class Security
{
	public static function sanitizeContainerSlug(string $slug): string
	{
		$slug = trim($slug);

		if ($slug === '' || !preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
			return '';
		}

		return $slug;
	}

	public static function validatePassword(string $password, bool $requireComplexity = true): ?string
	{
		$password = (string) $password;

		if (strlen($password) < 8) {
			return 'Şifre en az 8 karakter olmalı';
		}

		if ($requireComplexity && (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password))) {
			return 'Şifre en az bir harf ve bir rakam içermeli';
		}

		return null;
	}

	/**
	 * Encode a value as JSON safe to embed inside HTML <script>
	 * (application/ld+json, application/json, or inline JS).
	 *
	 * Context rules:
	 * - HTML text / attributes: htmlspecialchars / Smarty |escape
	 * - Inline JavaScript strings: jsString() / Smarty |js  (not HTML escape)
	 * - JSON in <script>: this method (never JSON_UNESCAPED_SLASHES alone)
	 *
	 * Fail-closed: returns JSON null if a script breakout marker remains.
	 *
	 * @param mixed $data
	 */
	public static function jsonForHtmlScript($data): string
	{
		$flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

		if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
			$flags |= JSON_INVALID_UTF8_SUBSTITUTE;
		}

		$json = json_encode($data, $flags);

		if (!is_string($json) || $json === '') {
			return 'null';
		}

		$json = str_replace(['<', '>'], ['\u003C', '\u003E'], $json);
		$json = str_replace(["\xE2\x80\xA8", "\xE2\x80\xA9"], ['\u2028', '\u2029'], $json);

		if (stripos($json, '</script') !== false || stripos($json, '<script') !== false) {
			return 'null';
		}

		return $json;
	}

	/**
	 * Encode a scalar as a JavaScript string literal, including quotes.
	 * Use inside <script>: var domain = {$domain|js nofilter};
	 *
	 * @param mixed $value
	 */
	public static function jsString($value): string
	{
		$json = self::jsonForHtmlScript((string) $value);

		if ($json === '' || ($json[0] ?? '') !== '"') {
			return '""';
		}

		return $json;
	}

	public static function isSafeOutboundUrl(string $url): bool
	{
		$url = trim($url);

		if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
			return false;
		}

		$scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?? ''));

		if (!in_array($scheme, ['http', 'https'], true)) {
			return false;
		}

		$host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));

		if ($host === '') {
			return false;
		}

		if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
			return false;
		}

		$ips = [];

		if (filter_var($host, FILTER_VALIDATE_IP)) {
			$ips[] = $host;
		} else {
			$records = @dns_get_record($host, DNS_A + DNS_AAAA);

			if (empty($records)) {
				return false;
			}

			foreach ($records as $record) {
				if (!empty($record['ip'])) {
					$ips[] = $record['ip'];
				}

				if (!empty($record['ipv6'])) {
					$ips[] = $record['ipv6'];
				}
			}
		}

		foreach ($ips as $ip) {
			if (self::isBlockedOutboundIp($ip)) {
				return false;
			}
		}

		return true;
	}

	private const HTML_ALLOW_TAGS = [
		'p' => true, 'br' => true, 'strong' => true, 'b' => true, 'em' => true, 'i' => true,
		'u' => true, 's' => true, 'ul' => true, 'ol' => true, 'li' => true, 'a' => true, 'img' => true,
		'h1' => true, 'h2' => true, 'h3' => true, 'h4' => true, 'h5' => true, 'h6' => true,
		'blockquote' => true, 'table' => true, 'thead' => true, 'tbody' => true, 'tr' => true,
		'th' => true, 'td' => true, 'span' => true, 'div' => true, 'hr' => true, 'pre' => true,
		'code' => true, 'figure' => true, 'figcaption' => true, 'sub' => true, 'sup' => true,
	];

	private const HTML_DROP_TAGS = [
		'script' => true, 'style' => true, 'iframe' => true, 'object' => true, 'embed' => true,
		'applet' => true, 'form' => true, 'input' => true, 'button' => true, 'textarea' => true,
		'select' => true, 'option' => true, 'link' => true, 'meta' => true, 'base' => true,
		'svg' => true, 'math' => true, 'noscript' => true, 'template' => true, 'video' => true,
		'audio' => true, 'source' => true, 'track' => true, 'frame' => true, 'frameset' => true,
		'noframes' => true, 'canvas' => true, 'xmp' => true, 'listing' => true, 'plaintext' => true,
		'noembed' => true, 'title' => true, 'head' => true, 'html' => true, 'body' => true,
	];

	private const HTML_ALLOW_ATTRS = [
		'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true, 'class' => true],
		'img' => ['src' => true, 'alt' => true, 'width' => true, 'height' => true, 'title' => true, 'class' => true],
		'td' => ['colspan' => true, 'rowspan' => true, 'class' => true],
		'th' => ['colspan' => true, 'rowspan' => true, 'class' => true],
		'blockquote' => ['cite' => true, 'class' => true],
	];

	/**
	 * Allowlist HTML for product/CMS/blog editors (XSS hardening).
	 * Parses as HTML so slash-separated attributes (<img/src=x/onerror=...>) cannot skip filters.
	 */
	public static function sanitizeHtml(string $html): string
	{
		$html = trim($html);

		if ($html === '') {
			return '';
		}

		$html = str_replace("\0", '', $html);

		if (!class_exists('DOMDocument')) {
			return strip_tags($html, '<' . implode('><', array_keys(self::HTML_ALLOW_TAGS)) . '>');
		}

		$previous = libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'
			. '<div id="fshop-html-root">' . $html . '</div></body></html>';
		$flags = 0;

		if (defined('LIBXML_NONET')) {
			$flags |= LIBXML_NONET;
		}

		$loaded = $dom->loadHTML($wrapped, $flags);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if ($loaded !== true) {
			return '';
		}

		$root = $dom->getElementById('fshop-html-root');

		if (!$root instanceof DOMElement) {
			$xpath = new DOMXPath($dom);
			$nodes = $xpath->query('//*[@id="fshop-html-root"]');
			$root = ($nodes instanceof DOMNodeList && $nodes->length > 0) ? $nodes->item(0) : null;
		}

		if (!$root instanceof DOMElement) {
			return '';
		}

		self::sanitizeDomChildren($root);

		$out = '';

		foreach ($root->childNodes as $child) {
			$out .= $dom->saveHTML($child);
		}

		return $out;
	}

	private static function sanitizeDomChildren(DOMNode $parent): void
	{
		$snapshot = [];

		foreach ($parent->childNodes as $child) {
			$snapshot[] = $child;
		}

		foreach ($snapshot as $child) {
			if ($child instanceof DOMElement) {
				self::sanitizeDomElement($child);
			} elseif ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
				if ($child->parentNode) {
					$child->parentNode->removeChild($child);
				}
			}
		}
	}

	private static function sanitizeDomElement(DOMElement $el): void
	{
		if ($el->parentNode === null) {
			return;
		}

		$tag = strtolower($el->tagName);

		if (isset(self::HTML_DROP_TAGS[$tag])) {
			$el->parentNode->removeChild($el);

			return;
		}

		self::sanitizeDomChildren($el);

		if ($el->parentNode === null) {
			return;
		}

		if (!isset(self::HTML_ALLOW_TAGS[$tag])) {
			$parent = $el->parentNode;

			while ($el->firstChild) {
				$parent->insertBefore($el->firstChild, $el);
			}

			$parent->removeChild($el);

			return;
		}

		self::sanitizeAllowedAttributes($el, $tag);
	}

	private static function sanitizeAllowedAttributes(DOMElement $el, string $tag): void
	{
		$allowed = self::HTML_ALLOW_ATTRS[$tag] ?? ['class' => true];
		$remove = [];

		if ($el->hasAttributes()) {
			foreach ($el->attributes as $attr) {
				$name = strtolower($attr->name);

				if (strpos($name, 'on') === 0 || strpos($name, 'xmlns') === 0 || !isset($allowed[$name])) {
					$remove[] = $attr->name;
					continue;
				}

				if (($name === 'href' || $name === 'src' || $name === 'cite') && !self::isSafeHtmlAttrUrl($name, $attr->value)) {
					$remove[] = $attr->name;
					continue;
				}

				if ($name === 'target' && $attr->value !== '_blank' && $attr->value !== '_self') {
					$remove[] = $attr->name;
					continue;
				}

				if ($name === 'style') {
					$remove[] = $attr->name;
				}
			}
		}

		foreach ($remove as $name) {
			$el->removeAttribute($name);
		}

		if ($tag === 'a' && strtolower($el->getAttribute('target')) === '_blank') {
			$rel = strtolower($el->getAttribute('rel'));
			$tokens = preg_split('/\s+/', $rel, -1, PREG_SPLIT_NO_EMPTY) ?: [];

			foreach (['noopener', 'noreferrer'] as $need) {
				if (!in_array($need, $tokens, true)) {
					$tokens[] = $need;
				}
			}

			$el->setAttribute('rel', implode(' ', $tokens));
		}
	}

	private static function isSafeHtmlAttrUrl(string $attr, string $value): bool
	{
		$value = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$value = str_replace("\0", '', $value);

		if ($value === '') {
			return false;
		}

		$compact = strtolower((string) preg_replace('/[\s\x00-\x1f\x7f]+/', '', $value));

		if (strpos($compact, 'javascript:') === 0 || strpos($compact, 'vbscript:') === 0
			|| strpos($compact, 'livescript:') === 0 || strpos($compact, 'mocha:') === 0) {
			return false;
		}

		if (strpos($compact, 'data:') === 0) {
			if ($attr !== 'src') {
				return false;
			}

			return (bool) preg_match('#^data:image/(gif|jpe?g|png|webp)(;|,)#i', $compact);
		}

		$scheme = strtolower((string) (parse_url($value, PHP_URL_SCHEME) ?? ''));

		if ($scheme === '') {
			return !preg_match('/^[a-z][a-z0-9+.-]*:/i', $compact);
		}

		if ($attr === 'href') {
			return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
		}

		return in_array($scheme, ['http', 'https'], true);
	}

	public static function isBlockedOutboundIp(string $ip): bool
	{
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return filter_var(
				$ip,
				FILTER_VALIDATE_IP,
				FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
			) === false;
		}

		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			return true;
		}

		$packed = inet_pton($ip);

		if ($packed === false) {
			return true;
		}

		if ($packed === inet_pton('::1')) {
			return true;
		}

		// IPv4-mapped IPv6 (::ffff:127.0.0.1) and IPv4-compatible (::127.0.0.1)
		if (strlen($packed) === 16) {
			$prefixMapped = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff";
			$prefixCompat = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

			if (substr($packed, 0, 12) === $prefixMapped || substr($packed, 0, 12) === $prefixCompat) {
				$v4 = inet_ntop(substr($packed, 12));

				if (is_string($v4) && $v4 !== '::' && self::isBlockedOutboundIp($v4)) {
					return true;
				}
			}
		}

		$first = ord($packed[0]);

		if (($first & 0xfe) === 0xfc) {
			return true;
		}

		if ($first === 0xfe && (ord($packed[1]) & 0xc0) === 0x80) {
			return true;
		}

		return false;
	}

	public static function sanitizeSvg(string $content): ?string
	{
		if (stripos($content, '<svg') === false) {
			return null;
		}

		$content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content) ?? '';
		$content = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $content) ?? '';
		$content = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $content) ?? '';
		$content = preg_replace('/\b(href|xlink:href)\s*=\s*["\']?\s*javascript:/i', '', $content) ?? '';

		if (preg_match('/<script|javascript:|data:text\/html/i', $content)) {
			return null;
		}

		return $content;
	}

	/**
	 * Session CSRF token (front or admin).
	 */
	public static function getCsrfToken(string $scope = 'front'): string
	{
		$key = $scope === 'admin' ? 'admin_csrf_token' : 'csrf_token';
		$token = (string) ($_SESSION[$key] ?? '');

		if ($token === '') {
			$token = bin2hex(random_bytes(32));
			$_SESSION[$key] = $token;
		}

		return $token;
	}

	/**
	 * Token from POST/GET `token` / `_csrf` / legacy aliases or X-CSRF-TOKEN header.
	 */
	public static function getRequestCsrfToken(): string
	{
		$tokens = self::getRequestCsrfTokens();

		return $tokens[0] ?? '';
	}

	/**
	 * All CSRF candidates on the request (first match wins in validateCsrf).
	 *
	 * Legacy admin/front forms sometimes put the session token in alternate
	 * field names or submit-button values (deleteProduct, csf, …).
	 *
	 * @return list<string>
	 */
	public static function getRequestCsrfTokens(): array
	{
		$candidates = [];

		foreach (['token', '_csrf', 'csf', 'bulkProductToken'] as $key) {
			if (!isset($_POST[$key])) {
				continue;
			}

			$value = trim((string) $_POST[$key]);

			if ($value !== '') {
				$candidates[] = $value;
			}
		}

		// Submit buttons whose value is the session CSRF token.
		foreach ([
			'deleteProduct',
			'deleteCategory',
			'deleteBrand',
			'saveKargo',
			'testOrder',
			'addDiscount',
		] as $key) {
			if (!isset($_POST[$key])) {
				continue;
			}

			$value = trim((string) $_POST[$key]);

			if ($value !== '') {
				$candidates[] = $value;
			}
		}

		if (isset($_GET['token'])) {
			$value = trim((string) $_GET['token']);

			if ($value !== '') {
				$candidates[] = $value;
			}
		}

		if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
			$value = trim((string) $_SERVER['HTTP_X_CSRF_TOKEN']);

			if ($value !== '') {
				$candidates[] = $value;
			}
		}

		return array_values(array_unique($candidates));
	}

	/**
	 * External POSTs that cannot send our session CSRF (PSP callback, cron, API key).
	 */
	public static function isCsrfExemptRequest(): bool
	{
		$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
		$action = strtolower(trim((string) (
			$_POST['action'] ?? $_GET['action'] ?? ''
		)));

		if (strpos($script, '/install/') !== false) {
			return true;
		}

		if (preg_match('#/api/cron\.php$#', $script)) {
			return true;
		}

		if (preg_match('#/api/webapi\.php$#', $script)) {
			return true;
		}

		// Marketplace cron uses SHOP_TOKEN, not session CSRF.
		if (preg_match('#/api/marketplace\.php$#', $script) && $action === 'cron') {
			return true;
		}

		// Payment / module webhooks (bank POST back).
		$uri = str_replace('\\', '/', (string) ($_SERVER['REQUEST_URI'] ?? ''));

		if (preg_match('#/api/module\.php(\?|$)#', $script) || preg_match('#/api/module\.php(\?|$)#', $uri)) {
			$webhookActions = ['callback', 'notify', 'webhook', 'ipn', 'return', '3d-return', '3dreturn'];
			$queryAction = strtolower(trim((string) ($_GET['action'] ?? '')));

			if (in_array($action, $webhookActions, true) || in_array($queryAction, $webhookActions, true)) {
				return true;
			}
		}

		if (preg_match('#/(iyzico-callback|paytr-callback|parampos-callback|esnekpos-callback|kuveytturk-callback|tami-callback)(/|\?|$)#i', $uri)) {
			return true;
		}

		return false;
	}

	public static function validateCsrf(string $scope = 'front'): bool
	{
		$sessionToken = self::getCsrfToken($scope);
		$providedList = self::getRequestCsrfTokens();

		// Never treat empty === empty as valid.
		if ($providedList === []) {
			return false;
		}

		$candidates = [];

		if ($sessionToken !== '') {
			$candidates[] = $sessionToken;
		}

		// Admin UI forms posting to /api/module.php use front bootstrap but send admin_csrf_token.
		if ($scope === 'front') {
			$adminToken = (string) ($_SESSION['admin_csrf_token'] ?? '');

			if ($adminToken !== '') {
				$candidates[] = $adminToken;
			}
		}

		if ($candidates === []) {
			return false;
		}

		foreach ($providedList as $provided) {
			foreach ($candidates as $candidate) {
				if (hash_equals($candidate, $provided)) {
					return true;
				}

				// Legacy admin forms sometimes submit md5(token).
				if ($scope === 'admin' && hash_equals(md5($candidate), $provided)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Enforce CSRF on the current request. Call only for POST (or state-changing) requests.
	 */
	public static function requireCsrf(string $scope = 'front'): void
	{
		if (self::validateCsrf($scope)) {
			return;
		}

		http_response_code(403);

		$wantsJson = (
			strpos(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')), '/api/') !== false
			|| strpos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false
			|| strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
		);

		if ($wantsJson) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => false,
				'message' => 'Geçersiz istek (CSRF)',
				'error' => 'csrf_invalid',
			], JSON_UNESCAPED_UNICODE);
			exit;
		}

		header('Content-Type: text/plain; charset=utf-8');
		echo 'Geçersiz istek (CSRF)';
		exit;
	}

	/**
	 * If this is POST and not exempt, require a valid CSRF token.
	 */
	public static function enforcePostCsrf(string $scope = 'front'): void
	{
		$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

		if ($method !== 'POST' && $method !== 'PUT' && $method !== 'PATCH' && $method !== 'DELETE') {
			return;
		}

		if (self::isCsrfExemptRequest()) {
			return;
		}

		self::requireCsrf($scope);
	}
}
