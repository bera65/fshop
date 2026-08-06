<?php

/**
 * Core class autoloader.
 *
 * Rule: class `SaleUnit` → file `core/SaleUnit.php`
 * Only simple class names (letters, numbers, underscore). No namespaces.
 *
 * - Invalid name → return (not our job)
 * - File missing → return (no include warning; other autoloaders may handle it)
 * - Path escapes core/ → ClassNotFoundException
 * - File loaded but class missing → ClassNotFoundException
 */
require_once __DIR__ . '/ClassNotFoundException.php';

(static function (): void {
	static $registered = false;

	if ($registered) {
		return;
	}

	$registered = true;
	$coreDir = __DIR__;
	$coreReal = realpath($coreDir);

	spl_autoload_register(static function (string $class) use ($coreDir, $coreReal): void {
		if ($class === '' || strpos($class, '\\') !== false) {
			return;
		}

		if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $class)) {
			return;
		}

		$file = $coreDir . DIRECTORY_SEPARATOR . $class . '.php';

		if (!is_file($file)) {
			return;
		}

		$realFile = realpath($file);

		if ($coreReal === false || $realFile === false) {
			return;
		}

		$corePrefix = $coreReal . DIRECTORY_SEPARATOR;

		if (strpos($realFile, $corePrefix) !== 0 && $realFile !== $coreReal) {
			throw new ClassNotFoundException(
				$class,
				'Refusing to load "' . $class . '": resolved path is outside core/'
			);
		}

		require_once $realFile;

		if (
			!class_exists($class, false)
			&& !interface_exists($class, false)
			&& !trait_exists($class, false)
		) {
			throw new ClassNotFoundException(
				$class,
				'File loaded for "' . $class . '" but class/interface/trait was not defined'
			);
		}
	});
})();
