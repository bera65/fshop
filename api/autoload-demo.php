<?php
/**
 * Debug-only: autoload / ClassNotFoundException demo.
 *
 * URL (APP_DEBUG veya PERF_DEBUG açıkken):
 *   /api/autoload-demo.php
 *   /api/autoload-demo.php?case=exception
 *   /api/autoload-demo.php?case=missing
 *
 * Production / debug kapalıysa 404.
 */
define('IN_SCRIPT', true);

require_once dirname(__DIR__) . '/config/install_gate.php';

if (!fshop_is_installed()) {
	fshop_redirect_to_installer();
}

require_once dirname(__DIR__) . '/config/settings.php';

if (!App::isDebug()) {
	http_response_code(404);
	header('Content-Type: text/plain; charset=utf-8');
	echo 'Not found';
	exit;
}

$case = strtolower(trim((string) Tools::getValue('case', 'exception')));

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>Autoload demo</title>';
echo '<style>body{font-family:system-ui,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;line-height:1.5}';
echo 'code,pre{background:#f4f4f5;padding:2px 6px;border-radius:4px}pre{padding:12px;overflow:auto}';
echo 'a{color:#5C4033}.box{border:1px solid #e5e5e5;border-radius:8px;padding:16px;margin:16px 0}</style></head><body>';
echo '<h1>Core autoload hata demosu</h1>';
echo '<p>Sadece <code>APP_DEBUG</code> / debug açıkken çalışır. Canlıda 404 döner.</p>';
echo '<div class="box"><strong>Senaryolar</strong><ul>';
echo '<li><a href="?case=exception"><code>?case=exception</code></a> — doğrudan <code>ClassNotFoundException</code> fırlat (aşağıda yakalanıp gösterilir)</li>';
echo '<li><a href="?case=exception_raw"><code>?case=exception_raw</code></a> — aynı exception, yakalanmadan (PHP ekran çıktısı)</li>';
echo '<li><a href="?case=missing"><code>?case=missing</code></a> — olmayan class → autoload <code>return</code> → PHP fatal/Error</li>';
echo '</ul></div>';

if ($case === 'exception' || $case === 'exception_raw') {
	$ex = new ClassNotFoundException(
		'OrnekOlmayanSinif',
		'Demo: core dosyası beklenen sınıfı tanımlamadı veya yol core/ dışına çıktı. (Bilinçli test hatası)'
	);

	if ($case === 'exception_raw') {
		echo '<p>Şimdi exception fırlatılıyor (raw)…</p></body></html>';
		throw $ex;
	}

	echo '<div class="box" style="border-color:#c0392b;background:#fdf2f0">';
	echo '<h2 style="margin-top:0;color:#c0392b">ClassNotFoundException</h2>';
	echo '<p><strong>getClassName():</strong> <code>' . htmlspecialchars($ex->getClassName(), ENT_QUOTES, 'UTF-8') . '</code></p>';
	echo '<p><strong>getMessage():</strong> ' . htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
	echo '<pre>' . htmlspecialchars($ex->getFile() . ':' . $ex->getLine() . "\n\n" . $ex->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
	echo '</div>';
	echo '<p>Ham PHP çıktısı için <a href="?case=exception_raw">exception_raw</a> kullan.</p>';
	echo '</body></html>';
	exit;
}

if ($case === 'missing') {
	echo '<p>Olmayan sınıf örneği: <code>new CompletelyFakeCoreClass999()</code></p>';
	echo '<p>Autoload dosyayı bulamaz → <code>return</code> → PHP kendi hatasını basar:</p></body></html>';
	flush();
	// Triggers autoload; file does not exist → return → PHP Error.
	new CompletelyFakeCoreClass999();
	exit;
}

echo '<p>Bilinmeyen case. Yukarıdaki linklerden birini seç.</p></body></html>';
