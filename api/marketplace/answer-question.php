<?php

if (!defined('IN_SCRIPT')) {
	exit;
}


header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}


$questionId = (int) Tools::getValue('question_id', 0);
$text = (string) Tools::getValue('answer_text', Tools::getValue('text', ''));

$result = Trendyol\QuestionService::answer($questionId, $text);

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
], JSON_UNESCAPED_UNICODE);
exit;
