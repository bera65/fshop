<?php

/**
 * Product sale units: piece (adet) or m2 (metrekare).
 */
class SaleUnit
{
	public const PIECE = 'piece';
	public const M2 = 'm2';

	/** @return list<string> */
	public static function allowed(): array
	{
		return [self::PIECE, self::M2];
	}

	public static function normalize(string $unit): string
	{
		$unit = strtolower(trim($unit));

		return in_array($unit, self::allowed(), true) ? $unit : self::PIECE;
	}

	public static function isM2($productOrUnit): bool
	{
		if (is_array($productOrUnit)) {
			return self::normalize((string) ($productOrUnit['sale_unit'] ?? self::PIECE)) === self::M2;
		}

		return self::normalize((string) $productOrUnit) === self::M2;
	}

	public static function getMin(array $product): float
	{
		$min = (float) ($product['sale_qty_min'] ?? 0);

		if ($min <= 0) {
			$min = self::isM2($product) ? 0.01 : 1.0;
		}

		return $min;
	}

	public static function getStep(array $product): float
	{
		$step = (float) ($product['sale_qty_step'] ?? 0);

		if ($step <= 0) {
			$step = self::isM2($product) ? 0.01 : 1.0;
		}

		return $step;
	}

	public static function unitLabel(string $unit): string
	{
		$unit = self::normalize($unit);

		if ($unit === self::M2) {
			return translate('m²');
		}

		return translate('pcs');
	}

	public static function priceSuffix(string $unit): string
	{
		$unit = self::normalize($unit);

		if ($unit === self::M2) {
			return ' / ' . translate('m²');
		}

		return '';
	}

	/**
	 * Round qty to step and enforce minimum. Returns 0 if invalid.
	 */
	public static function normalizeQty(float $qty, array $product): float
	{
		$min = self::getMin($product);
		$step = self::getStep($product);

		if ($qty <= 0) {
			return 0.0;
		}

		if ($step > 0) {
			$steps = (int) round($qty / $step);
			if ($steps < 1) {
				$steps = 1;
			}
			$qty = round($steps * $step, 3);
		}

		$qty = round(max($min, $qty), 3);

		return $qty;
	}

	/**
	 * @return array{sale_unit: string, width_m: float, length_m: float}|null
	 */
	public static function buildMeasure(array $product, float $widthM, float $lengthM): ?array
	{
		if (!self::isM2($product)) {
			return null;
		}

		$widthM = round(max(0, $widthM), 3);
		$lengthM = round(max(0, $lengthM), 3);

		if ($widthM <= 0 || $lengthM <= 0) {
			return null;
		}

		return [
			'sale_unit' => self::M2,
			'width_m' => $widthM,
			'length_m' => $lengthM,
		];
	}

	public static function areaFromMeasure(?array $measure): float
	{
		if (!is_array($measure)) {
			return 0.0;
		}

		$w = (float) ($measure['width_m'] ?? 0);
		$l = (float) ($measure['length_m'] ?? 0);

		if ($w <= 0 || $l <= 0) {
			return 0.0;
		}

		return round($w * $l, 3);
	}

	/**
	 * Normalize measure payload from cart meta / request.
	 *
	 * @return array{sale_unit: string, width_m?: float, length_m?: float}
	 */
	public static function normalizeMeasure(array $measure, array $product): array
	{
		$unit = self::normalize((string) ($measure['sale_unit'] ?? ($product['sale_unit'] ?? self::PIECE)));
		$out = ['sale_unit' => $unit];

		if ($unit === self::M2) {
			$w = round((float) ($measure['width_m'] ?? 0), 3);
			$l = round((float) ($measure['length_m'] ?? 0), 3);
			if ($w > 0 && $l > 0) {
				$out['width_m'] = $w;
				$out['length_m'] = $l;
			}
		}

		return $out;
	}

	public static function formatMeasureLabel(?array $measure, float $qty = 0): string
	{
		if (!is_array($measure) || self::normalize((string) ($measure['sale_unit'] ?? '')) !== self::M2) {
			return '';
		}

		$w = (float) ($measure['width_m'] ?? 0);
		$l = (float) ($measure['length_m'] ?? 0);
		$area = $qty > 0 ? $qty : self::areaFromMeasure($measure);

		if ($w > 0 && $l > 0) {
			return sprintf(
				'%s × %s m = %s %s',
				self::formatNumber($w),
				self::formatNumber($l),
				self::formatNumber($area),
				translate('m²')
			);
		}

		if ($area > 0) {
			return self::formatNumber($area) . ' ' . translate('m²');
		}

		return '';
	}

	public static function formatQty(float $qty, string $unit = self::PIECE): string
	{
		$unit = self::normalize($unit);

		if ($unit === self::M2 || abs($qty - round($qty)) > 0.0001) {
			return self::formatNumber($qty) . ' ' . self::unitLabel($unit);
		}

		return (string) (int) round($qty) . ' ' . self::unitLabel($unit);
	}

	public static function formatNumber(float $n): string
	{
		$formatted = number_format($n, 3, ',', '');
		$formatted = rtrim(rtrim($formatted, '0'), ',');

		return $formatted === '' ? '0' : $formatted;
	}

	/**
	 * Encode measure for cart key hashing (stable).
	 */
	public static function measureKeyPart(?array $measure): string
	{
		if (!is_array($measure) || ($measure['sale_unit'] ?? '') !== self::M2) {
			return '';
		}

		$payload = [
			'sale_unit' => self::M2,
			'width_m' => round((float) ($measure['width_m'] ?? 0), 3),
			'length_m' => round((float) ($measure['length_m'] ?? 0), 3),
		];

		if ($payload['width_m'] <= 0 || $payload['length_m'] <= 0) {
			return '';
		}

		return substr(md5(json_encode($payload)), 0, 10);
	}

	/** @return array<string, mixed> */
	public static function lineMetaForOrder(?array $measure, float $qty): array
	{
		$meta = [
			'sale_unit' => self::normalize((string) ($measure['sale_unit'] ?? self::PIECE)),
			'area_m2' => null,
			'width_m' => null,
			'length_m' => null,
		];

		if ($meta['sale_unit'] === self::M2) {
			$meta['area_m2'] = round($qty, 3);
			if (!empty($measure['width_m']) && !empty($measure['length_m'])) {
				$meta['width_m'] = round((float) $measure['width_m'], 3);
				$meta['length_m'] = round((float) $measure['length_m'], 3);
			}
		}

		return $meta;
	}

	/** Enrich order_detail row with measure display fields. */
	public static function enrichOrderItem(array &$item): void
	{
		$meta = [];
		$raw = $item['line_meta'] ?? '';

		if (is_string($raw) && $raw !== '') {
			$decoded = json_decode($raw, true);
			if (is_array($decoded)) {
				$meta = $decoded;
			}
		} elseif (is_array($raw)) {
			$meta = $raw;
		}

		$unit = self::normalize((string) ($meta['sale_unit'] ?? self::PIECE));
		$qty = (float) ($item['qty'] ?? 0);
		$item['sale_unit'] = $unit;
		$item['measure_label'] = self::formatMeasureLabel($meta, $qty);
		$item['qty_label'] = self::formatQty($qty, $unit);
		$item['qty_display'] = $unit === self::M2
			? self::formatNumber($qty)
			: (string) (int) round($qty);
	}
}
