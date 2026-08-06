<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

/**
 * Theme4 color / custom asset helpers (writes theme4 css/js only).
 */
class Theme4Assets
{
	public const THEME = 'theme4';

	/** @var array<string, string> */
	public const DEFAULT_COLORS = [
		'primary' => '#3D47D9',
		'primary-hover' => '#3239C4',
		'primary-light' => '#EBEBFF',
		'secondary' => '#1A1A1A',
		'text' => '#1A1A1A',
		'text-muted' => '#7E7E7E',
		'border' => '#EDEDED',
		'background' => '#F8F9FA',
		'surface' => '#FFFFFF',
		'white' => '#FFFFFF',
		'danger' => '#EF4444',
	];

	/** @var array<string, string> kept when rewriting colors.css */
	public const STRUCTURAL = [
		'radius' => '12px',
		'radius-lg' => '16px',
		'radius-pill' => '999px',
		'shadow' => '0 4px 20px rgba(26, 26, 26, 0.06)',
		'shadow-nav' => '0 -4px 24px rgba(26, 26, 26, 0.08)',
		'container' => '1320px',
		'transition' => '250ms ease',
		'font' => "'Inter', system-ui, -apple-system, sans-serif",
	];

	/** @var array<string, string> */
	public const COLOR_ALIASES = [
		'sm-primary' => 'var(--primary)',
		'sm-primary-dark' => 'var(--primary-hover)',
		'sm-primary-soft' => 'var(--primary-light)',
		'sm-accent' => 'var(--primary)',
		'sm-accent-dark' => 'var(--primary-hover)',
		'sm-bg' => 'var(--background)',
		'sm-surface' => 'var(--surface)',
		'sm-text' => 'var(--text)',
		'sm-muted' => 'var(--text-muted)',
		'sm-border' => 'var(--border)',
		'sm-radius' => 'var(--radius)',
		'sm-radius-lg' => 'var(--radius-lg)',
		'sm-shadow' => 'var(--shadow)',
		'sm-shadow-nav' => 'var(--shadow-nav)',
	];

	/**
	 * Group key => [cssVar => labelKey]
	 * Labels are English source strings for module i18n.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function colorGroups(): array
	{
		return [
			'Background colors' => [
				'background' => 'Page background',
				'surface' => 'Surface / cards',
				'primary-light' => 'Primary soft background',
				'white' => 'White',
			],
			'Text colors' => [
				'text' => 'Body text',
				'text-muted' => 'Muted text',
			],
			'Button colors' => [
				'primary' => 'Primary button',
				'primary-hover' => 'Primary button hover',
				'secondary' => 'Secondary button',
				'danger' => 'Danger / error',
			],
			'Border & UI' => [
				'border' => 'Border',
			],
		];
	}

	public static function colorsPath(): string
	{
		if (class_exists('Theme', false)) {
			return Theme::colorsPath(self::THEME);
		}

		return dirname(__DIR__, 3) . '/templates/' . self::THEME . '/css/colors.css';
	}

	public static function customCssPath(): string
	{
		if (class_exists('Theme', false)) {
			return Theme::customCssPath(self::THEME);
		}

		return dirname(__DIR__, 3) . '/templates/' . self::THEME . '/css/custom.css';
	}

	public static function customJsPath(): string
	{
		return dirname(__DIR__, 3) . '/templates/' . self::THEME . '/js/custom.js';
	}

	/** @return array<string, string> */
	public static function readColors(): array
	{
		$colors = self::DEFAULT_COLORS;
		$path = self::colorsPath();

		if (!is_file($path)) {
			return $colors;
		}

		$content = file_get_contents($path);

		if ($content === false) {
			return $colors;
		}

		if (preg_match_all('/--([a-z0-9-]+)\s*:\s*([^;]+);/i', $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$key = $match[1];

				if (array_key_exists($key, $colors)) {
					$colors[$key] = trim($match[2]);
				}
			}
		}

		return $colors;
	}

	/** @return array<string, string> */
	public static function readStructural(): array
	{
		$out = self::STRUCTURAL;
		$path = self::colorsPath();

		if (!is_file($path)) {
			return $out;
		}

		$content = file_get_contents($path);

		if ($content === false) {
			return $out;
		}

		if (preg_match_all('/--([a-z0-9-]+)\s*:\s*([^;]+);/i', $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$key = $match[1];

				if (array_key_exists($key, $out)) {
					$out[$key] = trim($match[2]);
				}
			}
		}

		return $out;
	}

	public static function readCustomCss(): string
	{
		$path = self::customCssPath();

		if (!is_file($path)) {
			return '';
		}

		$content = file_get_contents($path);

		return $content === false ? '' : $content;
	}

	public static function readCustomJs(): string
	{
		$path = self::customJsPath();

		if (!is_file($path)) {
			return '';
		}

		$content = file_get_contents($path);

		return $content === false ? '' : $content;
	}

	/**
	 * @param array<string, string> $colors
	 * @param array<string, string> $structural
	 * @return array{success:bool,message:string}
	 */
	public static function writeColors(array $colors, array $structural = []): array
	{
		$normalized = self::DEFAULT_COLORS;

		foreach (array_keys(self::DEFAULT_COLORS) as $key) {
			$value = trim((string) ($colors[$key] ?? ''));

			if ($value === '') {
				$value = $normalized[$key];
			}

			if (!self::isValidColorValue($value)) {
				return ['success' => false, 'message' => 'Invalid color: --' . $key];
			}

			$normalized[$key] = $value;
		}

		$struct = self::STRUCTURAL;

		foreach (array_keys(self::STRUCTURAL) as $key) {
			$value = trim((string) ($structural[$key] ?? $struct[$key]));

			if ($value !== '') {
				$struct[$key] = $value;
			}
		}

		$css = self::buildColorsCss($normalized, $struct);
		$path = self::colorsPath();
		$dir = dirname($path);

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			return ['success' => false, 'message' => 'css_dir'];
		}

		if (file_put_contents($path, $css) === false) {
			return ['success' => false, 'message' => 'colors_write'];
		}

		return ['success' => true, 'message' => 'colors_ok'];
	}

	/** @return array{success:bool,message:string} */
	public static function writeCustomCss(string $css): array
	{
		$path = self::customCssPath();
		$dir = dirname($path);

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			return ['success' => false, 'message' => 'css_dir'];
		}

		if (file_put_contents($path, $css) === false) {
			return ['success' => false, 'message' => 'custom_css_write'];
		}

		return ['success' => true, 'message' => 'custom_css_ok'];
	}

	/** @return array{success:bool,message:string} */
	public static function writeCustomJs(string $js): array
	{
		$path = self::customJsPath();
		$dir = dirname($path);

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			return ['success' => false, 'message' => 'js_dir'];
		}

		if (file_put_contents($path, $js) === false) {
			return ['success' => false, 'message' => 'custom_js_write'];
		}

		return ['success' => true, 'message' => 'custom_js_ok'];
	}

	/**
	 * @param array<string, string> $colors
	 * @param array<string, string> $structural
	 */
	private static function buildColorsCss(array $colors, array $structural): string
	{
		$lines = [
			'/**',
			' * Theme4 colors — managed by theme4 module. Do not edit by hand if using the admin UI.',
			' */',
			':root {',
		];

		foreach ($colors as $key => $value) {
			$lines[] = "\t--{$key}: {$value};";
		}

		$lines[] = '';

		foreach ($structural as $key => $value) {
			$lines[] = "\t--{$key}: {$value};";
		}

		$lines[] = '';
		$lines[] = "\t/* style.css aliases */";

		foreach (self::COLOR_ALIASES as $key => $value) {
			$lines[] = "\t--{$key}: {$value};";
		}

		$lines[] = '}';
		$lines[] = '';
		$lines[] = 'body.sm-body {';
		$lines[] = "\tbackground-color: var(--background);";
		$lines[] = "\tcolor: var(--text);";
		$lines[] = "\tfont-family: var(--font);";
		$lines[] = '}';
		$lines[] = '';

		return implode("\n", $lines);
	}

	public static function isValidColorValue(string $value): bool
	{
		if ($value === '') {
			return false;
		}

		if (preg_match('/^var\(--[a-z0-9-]+(?:,\s*[^)]+)?\)$/i', $value)) {
			return true;
		}

		if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value)) {
			return true;
		}

		if (preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+(?:\s*,\s*(?:0?\.\d+|1))?\s*\)$/i', $value)) {
			return true;
		}

		return false;
	}

	public static function hexForPicker(string $value): string
	{
		if (preg_match('/^#([0-9a-f]{6})$/i', $value, $match)) {
			return '#' . strtolower($match[1]);
		}

		if (preg_match('/^#([0-9a-f]{3})$/i', $value, $match)) {
			$h = $match[1];

			return '#' . strtolower($h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2]);
		}

		return '#3d47d9';
	}
}
