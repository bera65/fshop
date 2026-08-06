<?php
define('IN_ADMIN', true);
require_once dirname(__DIR__) . '/lib/Theme4Assets.php';

$j = json_decode((string) file_get_contents(__DIR__ . '/premium.json'), true);
if (!is_array($j)) {
	fwrite(STDERR, "bad json\n");
	exit(1);
}

$r1 = Theme4Assets::writeColors($j['colors'], $j['structural']);
$r2 = Theme4Assets::writeCustomCss($j['custom_css']);
$r3 = Theme4Assets::writeCustomJs($j['custom_js'] ?? '');

$w = $j['settings']['site_width'] ?? '1420px';
$f = $j['settings']['font_family'] ?? "'Plus Jakarta Sans', system-ui, sans-serif";
$cssPath = dirname(__DIR__) . '/assets/css/theme-settings.css';
file_put_contents($cssPath, "/** Theme4 theme settings — auto-generated. */\n:root {\n\t--container: {$w};\n\t--font: {$f};\n\t--theme-container-max: {$w};\n\t--theme-font-family: {$f};\n}\n\nbody, body.sm-body, .prime-body {\n\tfont-family: var(--font);\n}\n\n.sm-container, .custom-container, .page > .container, .t4-builder-row.container {\n\tmax-width: var(--container);\n\twidth: 100%;\n\tmargin-left: auto;\n\tmargin-right: auto;\n}\n");

echo json_encode([
	'colors' => $r1,
	'css' => $r2,
	'js' => $r3,
	'theme_settings_css' => is_file($cssPath),
	'note' => 'Layouts/DB: use Admin → Theme4 → Import/Export → Apply Premium preset',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
