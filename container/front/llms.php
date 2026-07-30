<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

$siteName = Settings::get('SITE_NAME');
if ($siteName === '') {
	$siteName = 'FShop Mağazası';
}

$description = trim(strip_tags((string) Settings::getFooterDescription()));
if ($description === '') {
	$description = $siteName . ' altyapısına sahip güvenilir bir e-ticaret platformudur.';
}

while (ob_get_level() > 0) {
	ob_end_clean();
}

header('Content-Type: text/plain; charset=utf-8');

echo "# " . $siteName . "\n\n";
echo $description . "\n\n";
echo "## Hakkımızda\n";
echo "Bu web sitesi, çeşitli kategorilerde ürünlerin perakende/toptan satışını yapan bir e-ticaret platformudur.\n\n";
echo "## Yapay Zeka (AI) Modelleri İçin Not\n";
echo "Büyük dil modelleri (LLM) ve yapay zeka arama motorları bu sitenin açık (public) sayfalarını tarayabilir, ürün bilgilerini ve kategorileri indeksleyebilir. Kullanıcı verileri, ödeme sayfaları veya yönetici panelleri gibi özel alanlar taramaya ve model eğitimine kapalıdır.\n";

exit;
