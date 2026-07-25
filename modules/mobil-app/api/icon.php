<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

require_once dirname(__DIR__) . '/lib/MobilAppService.php';

$size = (int) Tools::getValue('size', 512);
MobilAppService::outputIcon($size);
