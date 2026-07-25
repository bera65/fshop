<?php

/**
 * DOMAIN ayarındaki host/scheme ile uyuşmayan istekleri (www / www'siz, http/https) 301 yönlendirir.
 */
class CanonicalHost
{
	public static function redirectIfNeeded(): void
	{
		if (PHP_SAPI === 'cli' || headers_sent()) {
			return;
		}

		$domain = trim((string) Settings::get('DOMAIN'));

		if ($domain === '') {
			return;
		}

		$parsed = parse_url($domain);

		if (empty($parsed['host'])) {
			return;
		}

		$wantScheme = strtolower((string) ($parsed['scheme'] ?? 'https'));
		$wantHost = strtolower((string) $parsed['host']);

		if (isset($parsed['port']) && !in_array((int) $parsed['port'], [80, 443], true)) {
			$wantHost .= ':' . (int) $parsed['port'];
		}

		$currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

		if ($currentHost === '') {
			return;
		}

		$currentScheme = self::requestScheme();

		if ($wantScheme === $currentScheme && self::normalizeHost($wantHost) === self::normalizeHost($currentHost)) {
			return;
		}

		if (!self::isRelatedHost($wantHost, $currentHost)) {
			return;
		}

		$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

		header('Location: ' . $wantScheme . '://' . $wantHost . $uri, true, 301);
		exit;
	}

	private static function requestScheme(): string
	{
		$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
			|| (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

		return $https ? 'https' : 'http';
	}

	private static function normalizeHost(string $host): string
	{
		return preg_replace('/:\d+$/', '', strtolower($host)) ?: strtolower($host);
	}

	private static function isRelatedHost(string $wantHost, string $currentHost): bool
	{
		$want = self::normalizeHost($wantHost);
		$current = self::normalizeHost($currentHost);

		if ($want === $current) {
			return true;
		}

		return $want === 'www.' . $current || $current === 'www.' . $want;
	}
}
