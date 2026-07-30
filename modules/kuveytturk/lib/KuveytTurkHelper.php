<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

class KuveytTurkHelper
{
	public const PAYGATE_URL_PROD = 'https://sanalpos.kuveytturk.com.tr/ServiceGateWay/Home/ThreeDModelPayGate';
	public const PROVISION_URL_PROD = 'https://sanalpos.kuveytturk.com.tr/ServiceGateWay/Home/ThreeDModelProvisionGate';

	public const PAYGATE_URL_TEST = 'https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home/ThreeDModelPayGate';
	public const PROVISION_URL_TEST = 'https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home/ThreeDModelProvisionGate';

	private function toISO8859_9(string $str): string
	{
		if (!function_exists('iconv')) {
			return $str;
		}

		$out = @iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $str);

		return ($out === false) ? $str : $out;
	}

	private function sha1Base64Iso(string $str): string
	{
		$str = $this->toISO8859_9($str);

		return base64_encode(sha1($str, true));
	}

	public function buildHashedPassword(string $password): string
	{
		return $this->sha1Base64Iso($password);
	}

	public function buildHashDataPayGate(
		string $merchantId,
		string $orderId,
		string $amount,
		string $okUrl,
		string $failUrl,
		string $userName,
		string $hashedPassword
	): string {
		$raw = $merchantId . $orderId . $amount . $okUrl . $failUrl . $userName . $hashedPassword;

		return $this->sha1Base64Iso($raw);
	}

	public function buildHashDataProvision(
		string $merchantId,
		string $orderId,
		string $amount,
		string $userName,
		string $hashedPassword
	): string {
		$raw = $merchantId . $orderId . $amount . $userName . $hashedPassword;

		return $this->sha1Base64Iso($raw);
	}

	private function curlPostXml(string $url, string $xml): array
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/xml; charset=utf-8',
			'Content-Length: ' . strlen($xml),
			'Expect:',
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		$response = curl_exec($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		$errno = curl_errno($ch);
		curl_close($ch);

		return [
			'success' => ($response !== false && $httpCode >= 200 && $httpCode < 400),
			'http_code' => $httpCode,
			'errno' => $errno,
			'error' => $error,
			'response' => ($response === false ? '' : $response),
		];
	}

	public function sendPayGateRequest(array $data): array
	{
		$hashedPassword = $this->buildHashedPassword((string) ($data['Password'] ?? ''));
		$hashData = $this->buildHashDataPayGate(
			(string) ($data['MerchantId'] ?? ''),
			(string) ($data['MerchantOrderId'] ?? ''),
			(string) ($data['Amount'] ?? ''),
			(string) ($data['OkUrl'] ?? ''),
			(string) ($data['FailUrl'] ?? ''),
			(string) ($data['UserName'] ?? ''),
			$hashedPassword
		);

		$xml = '<KuveytTurkVPosMessage xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">' .
			'<APIVersion>TDV2.0.0</APIVersion>' .
			'<OkUrl>' . htmlspecialchars((string) ($data['OkUrl'] ?? ''), ENT_XML1, 'UTF-8') . '</OkUrl>' .
			'<FailUrl>' . htmlspecialchars((string) ($data['FailUrl'] ?? ''), ENT_XML1, 'UTF-8') . '</FailUrl>' .
			'<HashData>' . $hashData . '</HashData>' .
			'<MerchantId>' . htmlspecialchars((string) ($data['MerchantId'] ?? ''), ENT_XML1, 'UTF-8') . '</MerchantId>' .
			'<CustomerId>' . htmlspecialchars((string) ($data['CustomerId'] ?? ''), ENT_XML1, 'UTF-8') . '</CustomerId>' .
			'<UserName>' . htmlspecialchars((string) ($data['UserName'] ?? ''), ENT_XML1, 'UTF-8') . '</UserName>' .
			'<IdentityTaxNumber>' . htmlspecialchars((string) ($data['IdentityTaxNumber'] ?? '11111111111'), ENT_XML1, 'UTF-8') . '</IdentityTaxNumber>' .
			'<Description>' . htmlspecialchars((string) ($data['Description'] ?? 'FShop Order'), ENT_XML1, 'UTF-8') . '</Description>' .
			'<CardNumber>' . htmlspecialchars((string) ($data['CardNumber'] ?? ''), ENT_XML1, 'UTF-8') . '</CardNumber>' .
			'<CardExpireDateYear>' . htmlspecialchars((string) ($data['CardExpireDateYear'] ?? ''), ENT_XML1, 'UTF-8') . '</CardExpireDateYear>' .
			'<CardExpireDateMonth>' . htmlspecialchars((string) ($data['CardExpireDateMonth'] ?? ''), ENT_XML1, 'UTF-8') . '</CardExpireDateMonth>' .
			'<CardCVV2>' . htmlspecialchars((string) ($data['CardCVV2'] ?? ''), ENT_XML1, 'UTF-8') . '</CardCVV2>' .
			'<CardHolderName>' . htmlspecialchars((string) ($data['CardHolderName'] ?? ''), ENT_XML1, 'UTF-8') . '</CardHolderName>' .
			'<CardType>MasterCard</CardType>' .
			'<BatchID>0</BatchID>' .
			'<TransactionType>Sale</TransactionType>' .
			'<InstallmentCount>0</InstallmentCount>' .
			'<Amount>' . htmlspecialchars((string) ($data['Amount'] ?? ''), ENT_XML1, 'UTF-8') . '</Amount>' .
			'<DisplayAmount>' . htmlspecialchars((string) ($data['Amount'] ?? ''), ENT_XML1, 'UTF-8') . '</DisplayAmount>' .
			'<CurrencyCode>0949</CurrencyCode>' .
			'<MerchantOrderId>' . htmlspecialchars((string) ($data['MerchantOrderId'] ?? ''), ENT_XML1, 'UTF-8') . '</MerchantOrderId>' .
			'<TransactionSecurity>3</TransactionSecurity>' .
			'</KuveytTurkVPosMessage>';

		$isTest = Settings::get('KUVEYTTURK_TEST_MODE') !== '0';
		$url = $isTest ? self::PAYGATE_URL_TEST : self::PAYGATE_URL_PROD;

		return $this->curlPostXml($url, $xml);
	}

	public function sendProvisionRequest(array $data): array
	{
		$hashedPassword = $this->buildHashedPassword((string) ($data['Password'] ?? ''));
		$hashData = $this->buildHashDataProvision(
			(string) ($data['MerchantId'] ?? ''),
			(string) ($data['MerchantOrderId'] ?? ''),
			(string) ($data['Amount'] ?? ''),
			(string) ($data['UserName'] ?? ''),
			$hashedPassword
		);

		$mdData = (string) ($data['MD'] ?? '');

		$xml = '<KuveytTurkVPosMessage xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">' .
			'<APIVersion>TDV2.0.0</APIVersion>' .
			'<HashData>' . $hashData . '</HashData>' .
			'<MerchantId>' . htmlspecialchars((string) ($data['MerchantId'] ?? ''), ENT_XML1, 'UTF-8') . '</MerchantId>' .
			'<CustomerId>' . htmlspecialchars((string) ($data['CustomerId'] ?? ''), ENT_XML1, 'UTF-8') . '</CustomerId>' .
			'<UserName>' . htmlspecialchars((string) ($data['UserName'] ?? ''), ENT_XML1, 'UTF-8') . '</UserName>' .
			'<IdentityTaxNumber>' . htmlspecialchars((string) ($data['IdentityTaxNumber'] ?? '11111111111'), ENT_XML1, 'UTF-8') . '</IdentityTaxNumber>' .
			'<Description>' . htmlspecialchars((string) ($data['Description'] ?? 'FShop Order'), ENT_XML1, 'UTF-8') . '</Description>' .
			'<TransactionType>Sale</TransactionType>' .
			'<InstallmentCount>0</InstallmentCount>' .
			'<CurrencyCode>0949</CurrencyCode>' .
			'<Amount>' . htmlspecialchars((string) ($data['Amount'] ?? ''), ENT_XML1, 'UTF-8') . '</Amount>' .
			'<MerchantOrderId>' . htmlspecialchars((string) ($data['MerchantOrderId'] ?? ''), ENT_XML1, 'UTF-8') . '</MerchantOrderId>' .
			'<TransactionSecurity>3</TransactionSecurity>' .
			'<KuveytTurkVPosAdditionalData>' .
			'<AdditionalData><Key>MD</Key><Data>' . htmlspecialchars($mdData, ENT_XML1, 'UTF-8') . '</Data></AdditionalData>' .
			'</KuveytTurkVPosAdditionalData>' .
			'</KuveytTurkVPosMessage>';

		$isTest = Settings::get('KUVEYTTURK_TEST_MODE') !== '0';
		$url = $isTest ? self::PROVISION_URL_TEST : self::PROVISION_URL_PROD;

		$result = $this->curlPostXml($url, $xml);

		$responseCode = '';
		$responseMessage = '';

		if (!empty($result['response'])) {
			$xmlResponse = @simplexml_load_string($result['response']);
			if ($xmlResponse) {
				$responseCode = $this->getXmlValue($xmlResponse, 'ResponseCode');
				$responseMessage = $this->getXmlValue($xmlResponse, 'ResponseMessage');
			}
		}

		if ($responseMessage === '' && !empty($result['error'])) {
			$responseMessage = $result['error'];
		}

		$result['response_code'] = $responseCode;
		$result['response_message'] = $responseMessage;

		return $result;
	}

	public function getXmlValue($xml, string $nodeName): string
	{
		if (!is_object($xml)) {
			return '';
		}

		$value = '';

		if (isset($xml->$nodeName)) {
			$value = trim((string) $xml->$nodeName);
		}

		if (empty($value) && isset($xml->VPosMessage) && isset($xml->VPosMessage->$nodeName)) {
			$value = trim((string) $xml->VPosMessage->$nodeName);
		}

		if (empty($value)) {
			$xmlStr = $xml->asXML();
			$xmlStr2 = preg_replace('/\sxmlns(:\w+)?="[^"]+"/i', '', $xmlStr);
			$xml2 = @simplexml_load_string($xmlStr2);

			if ($xml2) {
				if (isset($xml2->$nodeName)) {
					$value = trim((string) $xml2->$nodeName);
				} elseif (isset($xml2->VPosMessage) && isset($xml2->VPosMessage->$nodeName)) {
					$value = trim((string) $xml2->VPosMessage->$nodeName);
				}
			}
		}

		return $value;
	}

	public function logTransaction(
		?int $idCart,
		?int $idOrder,
		string $merchantOrderId,
		string $transactionType,
		float $amount,
		?string $requestData,
		?string $responseData,
		?string $responseCode,
		?string $responseMessage,
		?string $md,
		string $status
	): void {
		DB::execute(
			'INSERT INTO `kuveytturk_log` 
			(`id_cart`, `id_order`, `merchant_order_id`, `transaction_type`, `amount`, `request_data`, `response_data`, `response_code`, `response_message`, `md`, `status`, `date_add`, `date_upd`) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
			[
				$idCart,
				$idOrder,
				$merchantOrderId,
				$transactionType,
				$amount,
				$requestData,
				$responseData,
				$responseCode,
				$responseMessage,
				$md,
				$status,
			]
		);
	}

	public function updateTransactionLog(string $merchantOrderId, array $data): void
	{
		$existing = DB::execute('SELECT id_log FROM kuveytturk_log WHERE merchant_order_id = ? ORDER BY id_log DESC LIMIT 1', [$merchantOrderId]);

		if (empty($existing)) {
			return;
		}

		$fields = [];
		$params = [];

		if (isset($data['id_order'])) {
			$fields[] = '`id_order` = ?';
			$params[] = (int) $data['id_order'];
		}
		if (isset($data['response_data'])) {
			$fields[] = '`response_data` = ?';
			$params[] = (string) $data['response_data'];
		}
		if (isset($data['response_code'])) {
			$fields[] = '`response_code` = ?';
			$params[] = (string) $data['response_code'];
		}
		if (isset($data['response_message'])) {
			$fields[] = '`response_message` = ?';
			$params[] = (string) $data['response_message'];
		}
		if (isset($data['md'])) {
			$fields[] = '`md` = ?';
			$params[] = (string) $data['md'];
		}
		if (isset($data['status'])) {
			$fields[] = '`status` = ?';
			$params[] = (string) $data['status'];
		}

		if (empty($fields)) {
			return;
		}

		$fields[] = '`date_upd` = NOW()';
		$params[] = $merchantOrderId;

		$sql = 'UPDATE `kuveytturk_log` SET ' . implode(', ', $fields) . ' WHERE `merchant_order_id` = ?';
		DB::execute($sql, $params);
	}

	public function getRecentLogs(int $limit = 50): array
	{
		return DB::execute('SELECT * FROM kuveytturk_log ORDER BY id_log DESC LIMIT ' . (int) $limit) ?: [];
	}
}
