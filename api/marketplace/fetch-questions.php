<?php

if (!defined('IN_SCRIPT')) {
	exit;
}


header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}


$status = trim((string) Tools::getValue('status', ''));
$result = Trendyol\QuestionService::syncQuestions(
	0,
	50,
	$status !== '' ? $status : null
);

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'count' => $result['count'] ?? 0,
	'questions' => $result['questions'] ?? [],
], JSON_UNESCAPED_UNICODE);
exit;
