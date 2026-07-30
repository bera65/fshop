<?php

namespace Hepsiburada;

class QuestionService
{
	/**
	 * @return array{ok: bool, message: string, count?: int, questions?: array<int, array<string, mixed>>}
	 */
	public static function syncQuestions(int $page = 1, int $size = 50, ?string $status = null): array
	{
		ProductSyncService::ensureSchema();

		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'Hepsiburada API kimlik bilgileri tanımlı değil'];
		}

		$params = [
			'page' => max(1, $page),
			'size' => min(100, max(1, $size)),
			'desc' => 'true',
		];

		$statusesToFetch = [];
		if ($status !== null && trim($status) !== '') {
			$statusesToFetch[] = trim($status);
		} else {
			$statusesToFetch = ['WaitingForAnswer', 'Answered'];
		}

		$count = 0;
		$now = date('Y-m-d H:i:s');
		$lastError = '';

		foreach ($statusesToFetch as $s) {
			$params['status'] = $s;
			$result = ProductSyncService::api()->getQuestions($params);

			if (ProductSyncService::isApiError($result)) {
				$lastError = (string) ($result['message'] ?? 'Sorular alınamadı');
				continue;
			}

			$content = self::extractQuestionList($result);

			foreach ($content as $q) {
				if (!is_array($q)) {
					continue;
				}

				self::upsertQuestion($q, $now);
				$count++;
			}
		}

		if ($count === 0 && $lastError !== '') {
			return ['ok' => false, 'message' => $lastError];
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
	public static function answer(string $questionId, string $text): array
	{
		ProductSyncService::ensureSchema();

		$questionId = trim($questionId);
		$text = trim($text);

		if ($questionId === '') {
			return ['ok' => false, 'message' => 'Soru numarası gerekli'];
		}

		if ($text === '') {
			return ['ok' => false, 'message' => 'Cevap metni boş olamaz'];
		}

		if (mb_strlen($text) > 2000) {
			return ['ok' => false, 'message' => 'Cevap en fazla 2000 karakter olabilir'];
		}

		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'Hepsiburada API kimlik bilgileri tanımlı değil'];
		}

		$result = ProductSyncService::api()->answerIssue($questionId, $text);

		if (ProductSyncService::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Cevap gönderilemedi')];
		}

		$existing = \MarketplaceTables::findQuestion('hepsiburada', $questionId);
		$now = date('Y-m-d H:i:s');

		if ($existing) {
			\MarketplaceTables::updateQuestion('hepsiburada', $questionId, [
				'answer_text' => $text,
				'answered' => 1,
				'status' => 'ANSWERED',
				'last_sync_at' => $now,
			]);
		}

		return ['ok' => true, 'message' => 'Cevap gönderildi'];
	}

	/**
	 * @param mixed $result
	 * @return array<int, mixed>
	 */
	private static function extractQuestionList($result): array
	{
		if (!is_array($result)) {
			return [];
		}

		if (isset($result[0]) && is_array($result[0])) {
			return $result;
		}

		foreach (['items', 'data', 'issues', 'content'] as $key) {
			if (isset($result[$key]) && is_array($result[$key])) {
				return $result[$key];
			}
		}

		return [];
	}

	/**
	 * @param array<string, mixed> $q
	 */
	private static function upsertQuestion(array $q, string $now): void
	{
		$normalized = self::normalizeIssue($q);
		$questionId = (string) ($normalized['question_id'] ?? '');

		if ($questionId === '' || $questionId === '0') {
			return;
		}

		$row = [
			'question_id' => $questionId,
			'product_name' => mb_substr((string) ($normalized['product_name'] ?? ''), 0, 255),
			'barcode' => (string) ($normalized['barcode'] ?? ''),
			'id_product' => 0,
			'question_text' => (string) ($normalized['question_text'] ?? ''),
			'answer_text' => (string) ($normalized['answer_text'] ?? ''),
			'status' => (string) ($normalized['status'] ?? ''),
			'answered' => (int) ($normalized['answered'] ?? 0),
			'customer_id' => (string) ($normalized['customer_id'] ?? ''),
			'raw_json' => json_encode($q, JSON_UNESCAPED_UNICODE),
			'question_date' => $normalized['question_date'] ?? null,
			'last_sync_at' => $now,
		];

		$barcode = trim((string) $row['barcode']);

		if ($barcode !== '') {
			$map = \DB::getRowSafe('hepsiburada_products', 'merchant_sku = ? OR hepsiburada_sku = ? OR barcode = ?', [$barcode, $barcode, $barcode]);
			if ($map) {
				$row['id_product'] = (int) ($map['id_product'] ?? 0);
			}
			if ($row['id_product'] <= 0) {
				$row['id_product'] = (int) (\DB::getValue('SELECT id_product FROM products WHERE barcode = ? OR stock_code = ? LIMIT 1', [$barcode, $barcode]) ?: 0);
			}
		}

		\MarketplaceTables::upsertQuestion('hepsiburada', $row);
	}

	/**
	 * @param array<string, mixed> $issue
	 * @return array<string, mixed>
	 */
	private static function normalizeIssue(array $issue): array
	{
		$issueNumber = (string) (
			$issue['number']
			?? ($issue['issueNumber'] ?? ($issue['id'] ?? ''))
		);

		$question = trim((string) ($issue['text'] ?? ($issue['question'] ?? ($issue['body'] ?? ($issue['content'] ?? ($issue['questionText'] ?? ''))))));
		$answer = '';
		$answered = 0;

		if (isset($issue['answer'])) {
			if (is_array($issue['answer'])) {
				$answer = trim((string) ($issue['answer']['text'] ?? ($issue['answer']['answer'] ?? ($issue['answer']['body'] ?? ($issue['answer']['content'] ?? '')))));
			} else {
				$answer = trim((string) $issue['answer']);
			}
		}

		if ($answer === '' && !empty($issue['answerText'])) {
			$answer = trim((string) $issue['answerText']);
		}

		if ($answer !== '') {
			$answered = 1;
		}

		// Eski ask-to-seller conversations formatı (geriye uyum)
		if (!empty($issue['conversations']) && is_array($issue['conversations'])) {
			foreach ($issue['conversations'] as $conv) {
				if (!is_array($conv)) {
					continue;
				}

				$from = (string) ($conv['from'] ?? '');
				$content = trim((string) ($conv['content'] ?? ($conv['text'] ?? ($conv['body'] ?? ''))));

				if ($from === 'Customer' && $question === '') {
					$question = $content;
				}

				if ($from === 'Merchant' && $content !== '') {
					$answer = $content;
					$answered = 1;
				}
			}
		}

		$statusRaw = $issue['status'] ?? '';
		$statusRawStr = is_scalar($statusRaw) ? (string) $statusRaw : '';

		if ($question === '' && !empty($issue['lastContent'])) {
			$question = trim((string) $issue['lastContent']);
		}

		$sku = (string) ($issue['productSku'] ?? '');
		$productName = (string) ($issue['productName'] ?? 'Ürün');

		if ($sku === '' || $productName === 'Ürün') {
			$product = $issue['product'] ?? null;

			if (is_array($product)) {
				if ($sku === '') {
					$sku = (string) ($product['sku'] ?? ($product['merchantSku'] ?? ($product['productSku'] ?? '')));
				}

				if (!empty($product['name'])) {
					$productName = (string) $product['name'];
				}
			}
		}

		if ($sku !== '' && $productName === 'Ürün') {
			$productName = $sku;
		}

		$questionDate = null;
		$rawDate = $issue['createdDate'] ?? ($issue['createdAt'] ?? ($issue['creationDate'] ?? null));

		if (is_numeric($rawDate)) {
			$ts = strlen((string) $rawDate) > 10 ? (int) round(((int) $rawDate) / 1000) : (int) $rawDate;
			$questionDate = date('Y-m-d H:i:s', $ts);
		} elseif (is_string($rawDate) && $rawDate !== '') {
			$ts = strtotime($rawDate);
			$questionDate = $ts ? date('Y-m-d H:i:s', $ts) : null;
		}

		$status = 'WAITING_FOR_ANSWER';
		$statusLower = strtolower($statusRawStr);

		if (
			$answered
			|| in_array($statusRawStr, ['Answered', 'ANSWERED', '2', 2], true)
			|| strpos($statusLower, 'answer') !== false
		) {
			$status = 'ANSWERED';
			$answered = 1;
		} elseif (
			in_array($statusRawStr, ['Rejected', 'REJECTED', 'Closed', 'CLOSED'], true)
			|| strpos($statusLower, 'reject') !== false
		) {
			$status = 'REJECTED';
		} elseif (
			in_array($statusRawStr, ['Open', 'WaitingForAnswer', 'WAITING_FOR_ANSWER', '1', 1], true)
			|| strpos($statusLower, 'open') !== false
			|| strpos($statusLower, 'wait') !== false
		) {
			$status = 'WAITING_FOR_ANSWER';
		}

		return [
			'question_id' => $issueNumber,
			'product_name' => $productName,
			'barcode' => $sku,
			'question_text' => $question,
			'answer_text' => $answer,
			'status' => $status,
			'answered' => $answered,
			'customer_id' => (string) ($issue['customerId'] ?? ''),
			'question_date' => $questionDate,
		];
	}

	/** @return array<int, array<string, mixed>> */
	public static function getRecent(int $limit = 50, bool $unansweredOnly = false): array
	{
		return \MarketplaceTables::getRecentQuestions('hepsiburada', $limit, $unansweredOnly);
	}
}
