<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}

$platform = strtolower(trim((string) Tools::getValue('platform', 'trendyol')));
$status = trim((string) Tools::getValue('status', ''));

if ($platform === 'hepsiburada') {
	$result = Hepsiburada\QuestionService::syncQuestions(1, 50, $status !== '' ? $status : null);
} elseif ($platform === 'n11') {
	$result = N11\QuestionService::syncQuestions(0, 50, $status !== '' ? $status : 'OPEN');
} else {
	$result = Trendyol\QuestionService::syncQuestions(
		0,
		50,
		$status !== '' ? $status : null
	);
}

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'count' => $result['count'] ?? 0,
	'questions' => $result['questions'] ?? [],
	'platform' => $platform,
], JSON_UNESCAPED_UNICODE);
exit;
