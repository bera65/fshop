<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

require_once dirname(__DIR__) . '/lib/MobilAppService.php';

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=120');

echo MobilAppService::renderManifestJson();
