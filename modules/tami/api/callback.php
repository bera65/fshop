<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

require_once dirname(__DIR__) . '/lib/TamiClient.php';
require_once dirname(__DIR__) . '/tami.php';

$post = $_POST;

if ($post === [] && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
	// Bazı bankalar GET ile dönebilir
	$post = $_GET;
}

$result = TamiModule::finalizeFromCallback(is_array($post) ? $post : []);

global $domain;
$base = rtrim((string) $domain, '/') . '/';

if ($result['ok']) {
	$idOrder = (int) ($result['id_order'] ?? 0);
	$ref = (string) ($result['reference'] ?? '');

	if ($idOrder > 0) {
		header('Location: ' . $base . 'checkout-success?id=' . $idOrder);
		exit;
	}

	if ($ref !== '') {
		header('Location: ' . $base . 'checkout-success?ref=' . rawurlencode($ref));
		exit;
	}

	header('Location: ' . $base . 'checkout-success');
	exit;
}

$msg = rawurlencode(mb_substr((string) ($result['message'] ?? 'Ödeme başarısız'), 0, 180));
header('Location: ' . $base . 'tami-payment?fail=' . $msg);
exit;
