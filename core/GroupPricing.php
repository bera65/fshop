<?php

class GroupPricing
{
	private static ?float $cachedPercent = null;
	private static bool $cacheReady = false;

	public static function resetCache(): void
	{
		self::$cachedPercent = null;
		self::$cacheReady = false;
	}

	public static function currentDiscountPercent(): float
	{
		if (self::$cacheReady) {
			return (float) self::$cachedPercent;
		}

		self::$cacheReady = true;
		self::$cachedPercent = 0.0;

		if (!class_exists('Customer', false) || !Customer::isLoggedIn()) {
			return 0.0;
		}

		$idUser = Customer::getId();

		if ($idUser <= 0) {
			return 0.0;
		}

		if (!class_exists('CustomerGroup', false)) {
			return 0.0;
		}

		self::$cachedPercent = CustomerGroup::getDiscountPercentForUser($idUser);

		return (float) self::$cachedPercent;
	}

	public static function apply(float $price): float
	{
		$percent = self::currentDiscountPercent();

		if ($percent <= 0) {
			return round(max(0, $price), 2);
		}

		return round(max(0, $price * (1 - ($percent / 100))), 2);
	}
}
