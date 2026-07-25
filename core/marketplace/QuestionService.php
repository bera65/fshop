<?php

namespace Trendyol;

class QuestionService
{
	/**
	 * @return array{ok: bool, message: string, count?: int, questions?: array<int, array<string, mixed>>}
	 */
	public static function syncQuestions(int $page = 0, int $size = 25, ?string $status = null): array
	{
		ProductSyncService::ensureSchema();

		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'Trendyol API kimlik bilgileri tanımlı değil'];
		}

		$result = ProductSyncService::api()->getQuestion($page, $size, $status);

		if (ProductSyncService::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Sorular alınamadı')];
		}

		$content = [];

		if (isset($result['content']) && is_array($result['content'])) {
			$content = $result['content'];
		} elseif (is_array($result) && isset($result[0])) {
			$content = $result;
		}

		$count = 0;
		$now = date('Y-m-d H:i:s');

		foreach ($content as $q) {
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
			return ['ok' => false, 'message' => 'Trendyol API kimlik bilgileri tanımlı değil'];
		}

		$result = ProductSyncService::api()->postQuestion($questionId, $text);

		if (ProductSyncService::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Cevap gönderilemedi')];
		}

		$existing = \DB::getRowSafe('trendyol_questions', 'question_id = ?', [$questionId]);
		$now = date('Y-m-d H:i:s');

		if ($existing) {
			\DB::update('trendyol_questions', [
				'answer_text' => $text,
				'answered' => 1,
				'status' => 'ANSWERED',
				'last_sync_at' => $now,
			], 'question_id = :where_id', ['where_id' => $questionId]);
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

		$answerText = '';
		$answered = 0;

		if (!empty($q['answer']['text'])) {
			$answerText = (string) $q['answer']['text'];
			$answered = 1;
		} elseif (!empty($q['answered'])) {
			$answered = 1;
			$answerText = (string) ($q['answerText'] ?? '');
		}

		$status = (string) ($q['status'] ?? ($answered ? 'ANSWERED' : 'WAITING_FOR_ANSWER'));
		$questionDate = null;
		$ts = $q['creationDate'] ?? ($q['createdDate'] ?? null);

		if (is_numeric($ts)) {
			$questionDate = date('Y-m-d H:i:s', (int) round(((int) $ts) / 1000));
		}

		$row = [
			'question_id' => $questionId,
			'product_name' => mb_substr((string) ($q['productName'] ?? ''), 0, 255),
			'barcode' => (string) ($q['barcode'] ?? ''),
			'question_text' => (string) ($q['text'] ?? ($q['question'] ?? '')),
			'answer_text' => $answerText,
			'status' => $status,
			'answered' => $answered,
			'customer_id' => (string) ($q['customerId'] ?? ''),
			'raw_json' => json_encode($q, JSON_UNESCAPED_UNICODE),
			'question_date' => $questionDate,
			'last_sync_at' => $now,
		];

		$existing = \DB::getRowSafe('trendyol_questions', 'question_id = ?', [$questionId]);

		if ($existing) {
			\DB::update(
				'trendyol_questions',
				$row,
				'id = :where_id',
				['where_id' => (int) $existing['id']]
			);
		} else {
			\DB::insert('trendyol_questions', $row);
		}
	}

	/** @return array<int, array<string, mixed>> */
	public static function getRecent(int $limit = 50, bool $unansweredOnly = false): array
	{
		ProductSyncService::ensureSchema();
		$limit = max(1, min(200, $limit));

		$sql = 'SELECT * FROM trendyol_questions';

		if ($unansweredOnly) {
			$sql .= ' WHERE answered = 0';
		}

		$sql .= ' ORDER BY COALESCE(question_date, last_sync_at) DESC, id DESC LIMIT ' . (int) $limit;

		return \DB::execute($sql) ?: [];
	}
}
