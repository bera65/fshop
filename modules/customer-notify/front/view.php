<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

require_once dirname(__DIR__) . '/lib/CustomerNotifyService.php';

CustomerNotifyService::ensureSchema();

global $smarty, $domain, $pageTitle, $pageDesc, $skipPageRender;

if (!Customer::isLoggedIn()) {
	$_SESSION['auth_redirect'] = rtrim((string) $domain, '/') . '/customer-notification?id=' . (int) Tools::getValue('id');
	header('Location: ' . $domain . 'login');
	exit;
}

$idNotification = (int) Tools::getValue('id');
$idUser = Customer::getId();
$notification = Notification::getByIdForUser($idNotification, $idUser);

if (!$notification) {
	http_response_code(404);
	$skipPageRender = false;
	$container = '404';
	$pageTitle = translate('Notification not found');
	$pageDesc = '';

	return;
}

if ((int) ($notification['is_read'] ?? 0) === 0) {
	Notification::markRead($idNotification, $idUser);
}

$pageTitle = (string) ($notification['title'] ?? translate('Notifications'));
$pageDesc = mb_substr(trim(strip_tags((string) ($notification['message'] ?? ''))), 0, 160);
$skipPageRender = true;

$smarty->assign([
	'notification' => $notification,
	'pageName' => 'customer-notification',
	'pageTitle' => $pageTitle,
	'pageDesc' => $pageDesc,
	'breadcrumb' => [
		['name' => translate('Home Page'), 'url' => $domain],
		['name' => translate('My Account'), 'url' => $domain . 'my-account'],
		['name' => translate('Notifications'), 'url' => $domain . 'my-account#notifications'],
		['name' => (string) ($notification['title'] ?? ''), 'url' => ''],
	],
]);

$smarty->display(_THEME_BASE_DIR_ . 'header.tpl');
$smarty->display('file:' . dirname(__DIR__) . '/assets/templates/front/view.tpl');
$smarty->display(_THEME_BASE_DIR_ . 'footer.tpl');
