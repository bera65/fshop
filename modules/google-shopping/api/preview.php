<?php

/**
 * Admin önizleme endpoint'i — ilk 5 ürünü JSON olarak döner
 * URL: /api/module.php?m=google-shopping&action=preview
 * Yalnızca admin oturumunda çağrılmalıdır.
 */

if (!defined('IN_SCRIPT')) {
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!class_exists('Admin')) { require_once dirname(__DIR__, 3) . '/core/Admin.php'; }
Admin::requireModuleApiAuth();

try {
    // Geçici olarak feed'i üret, parse et, ilk 5 item'ı döndür
    $xml = GoogleShoppingModule::generateXml();

    $dom = new DOMDocument();
    $dom->loadXML($xml);

    $items   = $dom->getElementsByTagName('item');
    $preview = [];

    $limit = min(5, $items->length);
    for ($i = 0; $i < $limit; $i++) {
        $item    = $items->item($i);
        $row     = [];
        foreach ($item->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $localName = $child->localName;
            $row[$localName] = $child->nodeValue;
        }
        $preview[] = $row;
    }

    echo json_encode([
        'success'      => true,
        'total'        => $items->length,
        'preview'      => $preview,
        'generated_at' => date('d.m.Y H:i:s'),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
