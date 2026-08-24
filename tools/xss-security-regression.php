<?php

/**
 * XSS regression: JSON-LD / JS / JSON script-context encoding + HTML sanitizer.
 * CLI only: php tools/xss-security-regression.php
 */

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/core/Security.php';
require_once $root . '/core/SchemaOrg.php';

$failed = 0;
$passed = 0;

function check($cond, string $msg): void
{
	global $failed, $passed;
	if ($cond) {
		$passed++;
		echo "OK  {$msg}\n";
		return;
	}

	$failed++;
	echo "FAIL  {$msg}\n";
}

function hasEventAttr(string $html): bool
{
	return (bool) preg_match('/\son[a-z]+\s*=/i', $html);
}

function hasDangerousUrl(string $html): bool
{
	return (bool) preg_match('/\s(?:href|src|cite)\s*=\s*["\']?\s*(?:javascript|vbscript|livescript|mocha):/i', $html)
		|| (bool) preg_match('/\s(?:href|src|cite)\s*=\s*["\']?\s*data:(?!image\/(?:gif|jpe?g|png|webp))/i', $html);
}

$encode = new ReflectionMethod('SchemaOrg', 'encode');
$encode->setAccessible(true);

$jsonPayloads = [
	'</script><script>alert(1)</script>',
	'</SCRIPT><SCRIPT>alert(1)</SCRIPT>',
	'</script ',
	'<script>alert(1)</script>',
	'<!--</script><script>alert(1)//-->',
	'foo</script>bar',
];

foreach ($jsonPayloads as $payload) {
	$json = $encode->invoke(null, ['name' => $payload, 'q' => $payload]);
	check(is_string($json) && $json !== '', 'JSON-LD encode returns JSON for ' . substr($payload, 0, 24));
	check(stripos($json, '</script') === false, 'JSON-LD has no </script for ' . substr($payload, 0, 24));
	check(stripos($json, '<script') === false, 'JSON-LD has no <script for ' . substr($payload, 0, 24));
	check(strpos($json, '<') === false && strpos($json, '>') === false, 'JSON-LD has no raw < or > for payload');
	$decoded = json_decode($json, true);
	check(is_array($decoded) && ($decoded['name'] ?? null) === $payload, 'JSON-LD remains valid JSON for payload');
}

$out = Security::sanitizeHtml('<p>Güvenli <strong>açıklama</strong> ve <a href="/urun">link</a>.</p>');
check(strpos($out, '<strong>') !== false && strpos($out, 'href="/urun"') !== false, 'sanitizeHtml keeps safe product HTML');

$htmlCases = [
	'<img src=x onerror=alert(1)>',
	'<img src="x" onerror="alert(1)">',
	"<img src='x' onerror='alert(1)'>",
	'<img/src=x/onerror=alert(1)>',
	'<img src=x onerror=alert(1) />',
	'<IMG SRC=x ONERROR=alert(1)>',
	'<img src=x OnError=alert(1)>',
	'<img src=x  onerror=alert(1)>',
	'<img src=x onerror=alert(1) onload=alert(2)>',
	'<div onclick=alert(1)>x</div>',
	'<p ONCLICK=alert(1)>x</p>',
	'<a href="#" onclick="alert(1)">x</a>',
	'<body onload=alert(1)>x',
	'<img src=x onload=alert(1)>',
	'<a href="javascript:alert(1)">x</a>',
	'<a href="JAVASCRIPT:alert(1)">x</a>',
	'<a href=" javascript:alert(1)">x</a>',
	'<a href=javascript:alert(1)>x</a>',
	'<a href="java	script:alert(1)">x</a>',
	'<a href="java&#115;cript:alert(1)">x</a>',
	'<a href="vbscript:msgbox(1)">x</a>',
	'<a href="VBSCRIPT:msgbox(1)">x</a>',
	'<a href="data:text/html,<script>alert(1)</script>">x</a>',
	'<img src="javascript:alert(1)">',
	'<img src="vbscript:alert(1)">',
	'<img src="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">',
	'<img src="data:image/svg+xml;base64,PHN2Zy9vbmxvYWQ9YWxlcnQoMSk+">',
	'<script>alert(1)</script>',
	'<SCRIPT>alert(1)</SCRIPT>',
	'<p>ok</p><script src=//x></script>',
	'<img src=x style="expression(alert(1))">',
	'<div style="background:url(javascript:alert(1))">x</div>',
];

foreach ($htmlCases as $i => $input) {
	$san = Security::sanitizeHtml($input);
	check(stripos($san, '<script') === false, "sanitizeHtml strips script #$i");
	check(!hasEventAttr($san), "sanitizeHtml strips event attrs #$i");
	check(!hasDangerousUrl($san), "sanitizeHtml strips dangerous URLs #$i");
}

$safeImg = Security::sanitizeHtml('<img src="/img/products/a.jpg" alt="Ürün">');
check(strpos($safeImg, 'src="/img/products/a.jpg"') !== false && !hasEventAttr($safeImg), 'sanitizeHtml keeps relative product image');

$safeLink = Security::sanitizeHtml('<a href="https://example.com" target="_blank">x</a>');
check(strpos($safeLink, 'href="https://example.com"') !== false && strpos($safeLink, 'noopener') !== false, 'sanitizeHtml keeps https link and adds noopener');

$dataPng = Security::sanitizeHtml('<img src="data:image/png;base64,AAAA">');
check(strpos($dataPng, 'data:image/png;base64,AAAA') !== false, 'sanitizeHtml allows raster data:image/png');

$scriptPayloads = [
	'</script><script>alert(1)</script>',
	'</ScRiPt><script>alert(1)</script>',
	'"</script><script>alert(1)</script>',
	"'</script><script>alert(1)</script>",
	'\\',
	"\xE2\x80\xA8",
	"\xE2\x80\xA9",
	'javascript:alert(1)',
	'vbscript:msgbox(1)',
	'&#106;avascript:alert(1)',
	"java\x09script:alert(1)",
];

function scriptDoc(string $inner, string $type = ''): string
{
	$attr = $type !== '' ? ' type="' . $type . '"' : '';

	return '<script' . $attr . '>' . $inner . '</script>';
}

function assertSafeScriptInner(string $inner, string $label): void
{
	check(stripos($inner, '</script') === false, $label . ': no </script');
	check(stripos($inner, '<script') === false, $label . ': no <script');
	$html = scriptDoc($inner);
	check(preg_match_all('/<script\b/i', $html) === 1, $label . ': single <script');
	check(preg_match_all('/<\/script/i', $html) === 1, $label . ': single </script');
}

$unsafe = json_encode(['n' => '</script><script>alert(1)</script>'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
check(is_string($unsafe) && stripos($unsafe, '</script') !== false, 'control: JSON_UNESCAPED_SLASHES allows </script');

foreach ($scriptPayloads as $i => $payload) {
	$json = Security::jsonForHtmlScript(['name' => $payload, 'url' => $payload]);
	check($json !== 'null' && $json !== '', 'jsonForHtmlScript returns JSON #' . $i);
	check(strpos($json, '<') === false && strpos($json, '>') === false, 'jsonForHtmlScript has no raw <> #' . $i);
	check(strpos($json, "\xE2\x80\xA8") === false && strpos($json, "\xE2\x80\xA9") === false, 'jsonForHtmlScript has no raw U+2028/2029 #' . $i);
	assertSafeScriptInner($json, 'JSON-LD #' . $i);
	assertSafeScriptInner($json, 'application/json #' . $i);
	$decoded = json_decode($json, true);
	check(is_array($decoded) && ($decoded['name'] ?? null) === $payload, 'jsonForHtmlScript roundtrip #' . $i);

	$js = Security::jsString($payload);
	check(($js[0] ?? '') === '"' && substr($js, -1) === '"', 'jsString is quoted #' . $i);
	check(strpos($js, '&quot;') === false && strpos($js, '&#039;') === false, 'jsString is not HTML escape #' . $i);
	assertSafeScriptInner('var domain = ' . $js . ';', 'inline JS #' . $i);
	check(json_decode($js) === $payload, 'jsString roundtrip #' . $i);
}

$quoteJs = Security::jsString('";alert(1);//');
check(strpos($quoteJs, '";alert') === false, 'jsString blocks quote breakout');

$coord = new ReflectionMethod('SchemaOrg', 'numericCoord');
$coord->setAccessible(true);
check($coord->invoke(null, '41.0082') === 41.0082, 'numericCoord parses latitude');
check($coord->invoke(null, '</script><script>alert(1)</script>') === null, 'numericCoord rejects script breakout');
check($coord->invoke(null, '"1,2"') === null, 'numericCoord rejects quoted junk');
check($coord->invoke(null, '41,01') === 41.01, 'numericCoord accepts comma decimal');

$hour = new ReflectionMethod('SchemaOrg', 'hourSpec');
$hour->setAccessible(true);
check($hour->invoke(null, '09:30', '00:00') === '09:30', 'hourSpec keeps valid time');
check($hour->invoke(null, '</script><script>alert(1)</script>', '00:00') === '00:00', 'hourSpec rejects script breakout');

$ldJson = $encode->invoke(null, [
	'name' => '</script><script>alert(1)</script>',
	'latitude' => '</script>',
]);
check($ldJson !== '' && stripos($ldJson, '</script') === false, 'SchemaOrg::encode still script-safe');
check(strpos($ldJson, '<') === false && strpos($ldJson, '>') === false, 'SchemaOrg::encode has no raw <>');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
