<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

/**
 * Örnek Sanal POS modülü.
 *
 * Akış:
 * 1. Checkout'ta "Kredi Kartı" seçilir, "Siparişi Onayla" denir.
 * 2. paysBeforeOrder = true olduğu için sipariş OLUŞTURULMAZ;
 *    checkout verisi session'da bekletilir, müşteri /odeme-karti sayfasına gider.
 * 3. Kart bilgileri girilir, "Ödemeyi Onayla" denir → chargeCard() bankaya gider.
 * 4. Banka ONAY verirse Order::placePending() siparişi oluşturur,
 *    durum "Hazırlanıyor" yapılır ve müşteri checkout-success'e gider.
 *    Banka RED verirse hata gösterilir, müşteri tekrar deneyebilir.
 *
 * Gerçek entegrasyonda (PayTR, iyzico, banka sanal POS'u) sadece
 * chargeCard() içeriği değişir; akışın geri kalanı aynı kalır.
 */
class SanalposModule extends ModuleBase
{
	public string $name = 'sanalpos';
	public string $title = 'Sanal POS';
	public string $version = '1.0.0';
	public string $description = 'Kredi kartı ile ödeme (örnek sanal POS)';
	public string $author = 'FShop';

	public bool $isPayment = true;
	public string $paymentMethodId = 'credit_card';
	public string $paymentMethodLabel = 'Kredi Kartı';
	public bool $paysBeforeOrder = true;

	/** Kart sayfası: site.com/odeme-karti */
	public array $routes = [
		'odeme-karti' => 'front/payment.php',
	];

	public array $displayHooks = [
		'order_payment' => 'Checkout ödeme seçeneği',
	];

	public array $defaultDisplayHooks = ['order_payment'];

	public function install(): bool
	{
		return true;
	}

	public function uninstall(): bool
	{
		return true;
	}

	public function getPaymentPageUrl(): string
	{
		global $domain;

		return $domain . 'odeme-karti';
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'order_payment') {
			return null;
		}

		$html = $this->renderFrontTemplate('order_payment', []);

		return $html !== '' ? $html : null;
	}

	/**
	 * Kartı bankadan tahsil eder.
	 *
	 * !!! BURASI SİMÜLASYONDUR !!!
	 * Gerçek entegrasyonda bu metod banka/PSP API'sine istek atar:
	 * - PayTR: token al, iframe/direkt API'ye POST, callback bekle
	 * - iyzico: CreatePaymentRequest gönder
	 * - Banka sanal POS: 3D Secure yönlendirmesi + callback doğrulama
	 *
	 * Test kuralları:
	 * - 4000 0000 0000 0002 → banka reddi (limit yetersiz)
	 * - Luhn doğrulamasından geçen diğer tüm kartlar → onay
	 */
	public function chargeCard(array $card, float $amount): array
	{
		// --- GERÇEK BANKA İSTEĞİ BURAYA GELECEK (örnek alanlar) ---
		// $response = $this->httpPost('https://banka-api/odeme', [
		// 	'merchant_id' => Settings::get('SANALPOS_MERCHANT_ID'),
		// 	'amount' => number_format($amount, 2, '.', ''),
		// 	'card_number' => $card['number'],
		// 	...
		// ]);
		// ----------------------------------------------------------

		if ($card['number'] === '4000000000000002') {
			return [
				'success' => false,
				'transaction_id' => '',
				'message' => 'Banka ödemeyi reddetti: kart limiti yetersiz',
			];
		}

		return [
			'success' => true,
			'transaction_id' => 'SIM-' . strtoupper(substr(md5(uniqid('', true)), 0, 12)),
			'message' => 'Ödeme onaylandı',
		];
	}

	/** Kart numarası Luhn algoritması doğrulaması */
	public static function isValidCardNumber(string $number): bool
	{
		if (!preg_match('/^[0-9]{13,19}$/', $number)) {
			return false;
		}

		$sum = 0;
		$alt = false;

		for ($i = strlen($number) - 1; $i >= 0; $i--) {
			$digit = (int) $number[$i];

			if ($alt) {
				$digit *= 2;

				if ($digit > 9) {
					$digit -= 9;
				}
			}

			$sum += $digit;
			$alt = !$alt;
		}

		return $sum % 10 === 0;
	}

	/** Son kullanma tarihi geçerli ve gelecekte mi? */
	public static function isValidExpiry(int $month, int $year): bool
	{
		if ($month < 1 || $month > 12) {
			return false;
		}

		if ($year < 100) {
			$year += 2000;
		}

		$now = (int) date('Y') * 100 + (int) date('n');

		return ($year * 100 + $month) >= $now;
	}
}
