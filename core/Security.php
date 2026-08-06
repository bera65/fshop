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

	/**
	 * Allowlist HTML for product/CMS/blog editors (XSS hardening).
	 * Keeps common editor tags; strips scripts, event handlers, javascript: URLs.
	 */
	public static function sanitizeHtml(string $html): string
	{
		$html = trim($html);

		if ($html === '') {
			return '';
		}

		$allowed = '<p><br><br/><strong><b><em><i><u><s><ul><ol><li><a><img>'
			. '<h1><h2><h3><h4><h5><h6><blockquote><table><thead><tbody><tr><th><td>'
			. '<span><div><hr><pre><code><figure><figcaption><sub><sup>';

		$html = strip_tags($html, $allowed);
		$html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
		$html = preg_replace_callback(
			'/\s(href|src|xlink:href)\s*=\s*("|\')\s*(.*?)\s*\2/is',
			static function (array $m): string {
				$attr = strtolower($m[1]);
				$quote = $m[2];
				$url = trim(html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
				$lower = strtolower($url);

				if (strpos($lower, 'javascript:') === 0 || strpos($lower, 'vbscript:') === 0) {
					return '';
				}

				if ($attr === 'href' && strpos($lower, 'data:') === 0) {
					return '';
				}

				if ($attr === 'src' && strpos($lower, 'data:') === 0 && strpos($lower, 'data:image/') !== 0) {
					return '';
				}

				return ' ' . $attr . '=' . $quote . $m[3] . $quote;
			},
			$html
		) ?? '';

		return $html;
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
		if (preg_match('#/api/module\.php$#', $script)) {
			$webhookActions = ['callback', 'notify', 'webhook', 'ipn', 'return', '3d-return', '3dreturn'];

			if (in_array($action, $webhookActions, true)) {
				return true;
			}
		}

		return false;
	}

	public static function validateCsrf(string $scope = 'front'): bool
	{
		$sessionToken = self::getCsrfToken($scope);
		$providedList = self::getRequestCsrfTokens();

		// Never treat empty === empty as valid.
		if ($sessionToken === '' || $providedList === []) {
			return false;
		}

		foreach ($providedList as $provided) {
			if (hash_equals($sessionToken, $provided)) {
				return true;
			}

			// Legacy admin forms sometimes submit md5(token).
			if ($scope === 'admin' && hash_equals(md5($sessionToken), $provided)) {
				return true;
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
