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
$text = (string) Tools::getValue('answer_text', Tools::getValue('text', ''));

if ($platform === 'hepsiburada') {
	$result = Hepsiburada\QuestionService::answer(
		(string) Tools::getValue('question_id', ''),
		$text
	);
} elseif ($platform === 'n11') {
	$result = N11\QuestionService::answer(
		(int) Tools::getValue('question_id', 0),
		$text
	);
} else {
	$result = Trendyol\QuestionService::answer(
		(int) Tools::getValue('question_id', 0),
		$text
	);
}

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'platform' => $platform,
], JSON_UNESCAPED_UNICODE);
exit;
