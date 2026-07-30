<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

global $domain;

header('Location: ' . rtrim((string) $domain, '/') . '/paypal-payment?fail=' . rawurlencode('PayPal ödemesi iptal edildi') . '&stay=1');
exit;
