<?php

class FShop
{
	public const VERSION = '2.5.4';
	public const NAME = 'FriSay';

	public static function version(): string
	{
		return self::VERSION;
	}

	public static function fullName(): string
	{
		return self::NAME . ' ' . self::VERSION;
	}
}
