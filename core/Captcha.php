<?php

class Captcha
{
	public static function validate(string $form): string
	{
		if (!class_exists('Module', false)) {
			return '';
		}

		$error = '';
		Module::runHook('form.captcha.validate', [$form, &$error]);

		return trim((string) $error);
	}
}
