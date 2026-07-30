<?php

define('IN_SCRIPT', true);
require_once dirname(__DIR__) . '/config/settings.php';

$idOrder = (int) Tools::getValue('id_order');
$isAdmin = !empty($_SESSION['id_admin']);
$idUser = Customer::isLoggedIn() ? Customer::getId() : 0;

if ($idOrder <= 0 || !Order::canAccessInvoice($idOrder, $idUser, $isAdmin)) {
	http_response_code(403);
	exit;
}

Order::serveInvoiceFile($idOrder);
