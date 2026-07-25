<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

require_once dirname(__DIR__) . '/lib/MobilAppService.php';

$scope = MobilAppService::getScopePath();

header('Content-Type: application/javascript; charset=utf-8');
header('Service-Worker-Allowed: ' . $scope);
header('Cache-Control: public, max-age=120');

echo MobilAppService::renderServiceWorker();
