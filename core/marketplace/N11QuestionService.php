<?php

namespace N11;

class QuestionService
{
	/**
	 * @return array{ok: bool, message: string, count?: int, questions?: array<int, array<string, mixed>>}
	 */
	public static function syncQuestions(int $page = 0, int $size = 50, string $status = 'OPEN'): array
	{
		ProductSyncService::ensureSchema();

		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'N11 API kimlik bilgileri tanımlı değil'];
		}

		$response = ProductSyncService::api()->getProductQuestionList($status, $page, $size);
		$extracted = N11Api::extractQuestionsFromListResponse($response);

		if ($extracted['error'] !== '') {
			return ['ok' => false, 'message' => $extracted['error']];
		}

		$count = 0;
		$now = date('Y-m-d H:i:s');

		foreach ($extracted['items'] as $q) {
			if (!is_array($q)) {
				continue;
			}

			self::upsertQuestion($q, $now);
			$count++;
		}

		return [
			'ok' => true,
			'message' => $count . ' soru senkronize edildi',
			'count' => $count,
			'questions' => self::getRecent(50),
		];
	}

	/**
	 * @return array{ok: bool, message: string}
	 */
	public static function answer(int $questionId, string $text): array
	{
		ProductSyncService::ensureSchema();

		$text = trim($text);

		if ($questionId <= 0) {
			return ['ok' => false, 'message' => 'Soru ID gerekli'];
		}

		if ($text === '') {
			return ['ok' => false, 'message' => 'Cevap metni boş olamaz'];
		}

		if (mb_strlen($text) > 2000) {
			return ['ok' => false, 'message' => 'Cevap en fazla 2000 karakter olabilir'];
		}

		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'N11 API kimlik bilgileri tanımlı değil'];
		}

		$result = ProductSyncService::api()->saveProductAnswer($questionId, $text);

		if (!N11Api::isSoapSuccess($result)) {
			return [
				'ok' => false,
				'message' => N11Api::extractSoapError($result) ?: 'Cevap gönderilemedi',
			];
		}

		$existing = \MarketplaceTables::findQuestion('n11', (string) $questionId);
		$now = date('Y-m-d H:i:s');

		if ($existing) {
			\MarketplaceTables::updateQuestion('n11', (string) $questionId, [
				'answer_text' => $text,
				'answered' => 1,
				'status' => 'ANSWERED',
				'last_sync_at' => $now,
			]);
		}

		return ['ok' => true, 'message' => 'Cevap gönderildi'];
	}

	/**
	 * @param array<string, mixed> $q
	 */
	private static function upsertQuestion(array $q, string $now): void
	{
		$questionId = (int) ($q['id'] ?? 0);

		if ($questionId <= 0) {
			return;
		}

		$answerText = trim((string) ($q['answer'] ?? ''));
		$answered = $answerText !== '' ? 1 : 0;
		$status = $answered ? 'ANSWERED' : 'WAITING_FOR_ANSWER';

		$questionDate = null;
		$rawDate = $q['creationDate'] ?? null;

		if (is_string($rawDate) && $rawDate !== '') {
			$ts = strtotime($rawDate);
			$questionDate = $ts ? date('Y-m-d H:i:s', $ts) : null;
		}

		$row = [
			'question_id' => (string) $questionId,
			'product_name' => mb_substr((string) ($q['productName'] ?? ''), 0, 255),
			'barcode' => (string) ($q['productId'] ?? ''),
			'id_product' => 0,
			'question_text' => (string) ($q['text'] ?? ''),
			'answer_text' => $answerText,
			'status' => $status,
			'answered' => $answered,
			'customer_id' => (string) ($q['userName'] ?? ''),
			'raw_json' => json_encode($q, JSON_UNESCAPED_UNICODE),
			'question_date' => $questionDate,
			'last_sync_at' => $now,
		];

		$barcode = trim((string) $row['barcode']);

		if ($barcode !== '') {
			$map = \DB::getRowSafe('n11_products', 'stock_code = ? OR barcode = ?', [$barcode, $barcode]);
			if ($map) {
				$row['id_product'] = (int) ($map['id_product'] ?? 0);
			}
			if ($row['id_product'] <= 0) {
				$row['id_product'] = (int) (\DB::getValue('SELECT id_product FROM products WHERE stock_code = ? OR barcode = ? LIMIT 1', [$barcode, $barcode]) ?: 0);
			}
		}

		\MarketplaceTables::upsertQuestion('n11', $row);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getRecent(int $limit = 50, bool $unansweredOnly = false): array
	{
		return \MarketplaceTables::getRecentQuestions('n11', $limit, $unansweredOnly);
	}
}
