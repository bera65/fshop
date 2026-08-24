<?php
	class Tools
	{
		public static function strlen($str, $encoding = 'UTF-8')
		{
			if (is_array($str))
				return false;
			$str = html_entity_decode($str, ENT_COMPAT, 'UTF-8');
			if (function_exists('mb_strlen'))
				return mb_strlen($str, $encoding);
			return strlen($str);
		}
		public static function isSubmit($submit)
		{
			return (
				isset($_POST[$submit]) || isset($_POST[$submit.'_x']) || isset($_POST[$submit.'_y'])
				|| isset($_GET[$submit]) || isset($_GET[$submit.'_x']) || isset($_GET[$submit.'_y'])
			);
		}
		public static function getValue($key, $default_value = false)
		{
			if (!isset($key) || empty($key) || !is_string($key))
				return false;
			$ret = (isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $default_value));

			if (is_string($ret) === true)
				$ret = urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($ret)));
			return !is_string($ret)? $ret : stripslashes($ret);
		}
		public static function getIsset($key)
		{
			if (!is_string($key)) {
				return false;
			}

			return isset($_POST[$key]) || isset($_GET[$key]);
		}
		public static function htmlentitiesUTF8($string, $type = ENT_QUOTES)
		{
			if (is_array($string)) {
				return array_map(['Funcitons', 'htmlentitiesUTF8'], $string);
			}

			return htmlentities((string) $string, $type, 'utf-8');
		}
		public static function htmlentitiesDecodeUTF8($string)
		{
			if (is_array($string)) {
				$string = array_map(['Funcitons', 'htmlentitiesDecodeUTF8'], $string);

				return (string) array_shift($string);
			}

			return html_entity_decode((string) $string, ENT_QUOTES, 'utf-8');
		}
		public static function encrypt($passwd)
		{
			return self::hash($passwd);
		}
		public static function secureKey($length = 48)
		{
			$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789?=)(&%+#@.:';
			$code = '';
			for ($i = 0; $i < $length; $i++) {
				$randomIndex = rand(0, strlen($characters) - 1);
				$code .= $characters[$randomIndex];
			}
			return $code;
		}
		public static function hash($passwd)
		{
			return md5(Settings::get('SHOP_TOKEN') . $passwd);
		}
		public static function displayPrice($price)
		{
			if (!class_exists('Currency', false)) {
				require_once dirname(__DIR__) . '/core/Currency.php';
			}

			return Currency::formatFromShop((float) $price);
		}
		public static function formatNumber($price)
		{
			if (Validate::isFloat($price))
			{
				$price = number_format($price, 2);
				return $price;
			}
			else
				return 0;
		}

		/** Stock / qty display: always 2 decimal places (avoids decimal(12,3) “2.000” looking like 2000). */
		public static function displayStock($qty): string
		{
			$n = round((float) $qty, 2);
			$useComma = true;

			if (defined('IN_ADMIN') && class_exists('AdminLang', false)) {
				$useComma = strtolower((string) AdminLang::current()) !== 'en';
			} elseif (class_exists('Lang', false)) {
				$useComma = strtolower((string) Lang::current()) !== 'en'
					&& strtolower((string) Lang::getDefault()) !== 'en';
			}

			if ($useComma) {
				return number_format($n, 2, ',', '.');
			}

			return number_format($n, 2, '.', ',');
		}
		public static function formatDate($timestamp)
		{
			$utcTimestamp = $timestamp / 1000;
			$localTimestamp = $utcTimestamp - (3 * 3600);

			return date('d.m.Y H:i', $localTimestamp);
		}
		public static function formatDate2($timestamp)
		{
			$adjustedTimestamp = $timestamp / 1000;
			return date('Y.m.d H:i:s', $adjustedTimestamp);
		}
		public static function formatDate3($date)
		{
			return date('d.m.Y H:i', strtotime($date));
		}
		public static function maskName($name) {
			$names = explode(' ', $name);
			$maskname = [];

			foreach ($names as $nm) {
				$firstChar = mb_strtoupper(mb_substr($nm, 0, 1, 'UTF-8'), 'UTF-8');
				//$hideChar = str_repeat('*', mb_strlen($nm, 'UTF-8') - 1);
				$maskname[] = $firstChar . '**';
			}
			return implode(' ', $maskname);
		}
		
		public static function timeAgo($orderDate) 
		{
			$currentDate = new DateTime();
			$orderDateObj = new DateTime($orderDate);
			$interval = $currentDate->diff($orderDateObj);
			if ($interval->y > 0) {
				return $interval->y . ' yıl önce';
			} elseif ($interval->m > 0) {
				return $interval->m . ' ay önce';
			} elseif ($interval->d > 0) {
				return $interval->d . ' gün önce';
			} elseif ($interval->h > 0) {
				return $interval->h . ' saat önce';
			} elseif ($interval->i > 0) {
				return $interval->i . ' dk önce';
			} else {
				return 'şimdi';
			}
		}
		
		public static function getFirstImage($images)
		{
			$image = explode(";", $images);
			return $image[0];
		}
		public static function deleteTags($name)
		{
			return preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $name);
		}
		public static function createSlug($text)
		{
			$turkish = ['ç','Ç','ğ','Ğ','ı','İ','ö','Ö','ş','Ş','ü','Ü'];
			$english = ['c','c','g','g','i','i','o','o','s','s','u','u'];
			$text = str_replace($turkish, $english, $text);
			$text = strtolower($text);
			$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
			$text = preg_replace('/[^a-z0-9]+/u', '-', $text);
			$text = trim($text, '-');
			$text = preg_replace('/-+/', '-', $text);
			return $text;
		}
		public static function appLog($msg): void {
			$file = __DIR__ . '/debug.log'; // bu dosya yolunu projene göre ayarla
			$line = '['.date('Y-m-d H:i:s').'] '.$msg."\n";
			@file_put_contents($file, $line, FILE_APPEND);
		}
		public static function getDiscount($oldPrice, $price) {
			$oldPrice = (float) $oldPrice;
			$price = (float) $price;

			if ($oldPrice <= 0 || $oldPrice <= $price) {
				return 0;
			}

			$fark = $oldPrice - $price;
			$oran = ($fark * 100) / $oldPrice;

			return number_format($oran, 0);
		}
	}
	
	class Validate
	{
		public static function isFloat($float)
		{
			return strval((float)$float) == strval($float);
		}
		public static function isInt($value)
		{
			return (string) (int) $value === (string) $value || $value === false;
		}
		public static function isUrl($url)
		{
			if (trim($url) === '') {
				return true;
			}

			if (!filter_var($url, FILTER_VALIDATE_URL)) {
				return false;
			}

			$scheme = parse_url($url, PHP_URL_SCHEME);
			return in_array($scheme, ['http', 'https'], true);
		}

		public static function isCleanHtml($html, $allow_iframe = false)
		{
			$events = 'onmousedown|onmousemove|onmmouseup|onmouseover|onmouseout|onload|onunload|onfocus|onblur|onchange';
			$events .= '|onsubmit|ondblclick|onclick|onkeydown|onkeyup|onkeypress|onmouseenter|onmouseleave|onerror|onselect|onreset|onabort|ondragdrop|onresize|onactivate|onafterprint|onmoveend';
			$events .= '|onafterupdate|onbeforeactivate|onbeforecopy|onbeforecut|onbeforedeactivate|onbeforeeditfocus|onbeforepaste|onbeforeprint|onbeforeunload|onbeforeupdate|onmove';
			$events .= '|onbounce|oncellchange|oncontextmenu|oncontrolselect|oncopy|oncut|ondataavailable|ondatasetchanged|ondatasetcomplete|ondeactivate|ondrag|ondragend|ondragenter|onmousewheel';
			$events .= '|ondragleave|ondragover|ondragstart|ondrop|onerrorupdate|onfilterchange|onfinish|onfocusin|onfocusout|onhashchange|onhelp|oninput|onlosecapture|onmessage|onmouseup|onmovestart';
			$events .= '|onoffline|ononline|onpaste|onpropertychange|onreadystatechange|onresizeend|onresizestart|onrowenter|onrowexit|onrowsdelete|onrowsinserted|onscroll|onsearch|onselectionchange';
			$events .= '|onselectstart|onstart|onstop';

			if (preg_match('/<[\s]*script/ims', $html) || preg_match('/(' . $events . ')[\s]*=/ims', $html) || preg_match('/.*script\:/ims', $html)) {
				return false;
			}

			if (!$allow_iframe && preg_match('/<[\s]*(i?frame|form|input|embed|object)/ims', $html)) {
				return false;
			}

			return true;
		}
		public static function isName($name)
		{
			return preg_match('/^[a-zA-ZÇĞİÖŞÜçğıöşü\s\-\.]+$/u', trim($name));
		}
		public static function isGeneric($name) 
		{
			return empty($name) || preg_match('/^[a-zA-Z0-9\s->]*$/u', $name);
		}
		public static function isMd5($md5)
		{
			return preg_match('/^[a-f0-9A-F]{32}$/', $md5);
		}
		public static function isGenericName($name)
		{
			return empty($name) || preg_match('/^[^<={}]*$/u', $name);
		}
		public static function isDate($date)
		{
			if (!preg_match('/^([0-9]{4})-((?:0?[0-9])|(?:1[0-2]))-((?:0?[0-9])|(?:[1-2][0-9])|(?:3[01]))( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date, $matches)) {
				return false;
			}

			return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
		}
		public static function isEmail($email) {
			$email = trim($email);
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
				return true;
			}
			return false;
		}
		public static function isPhoneNumber($number)
		{
			return preg_match('/^[+0-9. ()\/-]*$/', $number);
		}
		public static function isUserName($username) 
		{
			if (empty($username)) {
				return false;
			}
			return preg_match('/^[a-zA-Z0-9.\-_]+$/', $username);
		}
	}
	class Cookie
	{
		public static function setRememberCookie(string $token, int $days = 30): void {
			$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
			$params = [
				'expires'  => time() + ($days * 86400),
				'path'     => '/',
				'domain'   => '',
				'secure'   => $secure,
				'httponly' => true,
				'samesite' => 'Lax',
			];
			setcookie('remember', $token, $params);
		}

		public static function clearRememberCookie(): void 
		{
			$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

			setcookie('remember', '', [
				'expires'  => time() - 3600,
				'path'     => '/',
				'domain'   => '',
				'secure'   => $secure,
				'httponly' => true,
				'samesite' => 'Lax',
			]);

			unset($_COOKIE['remember']);
		}

		public static function issueRememberToken(int $idUser): void
		{
			$raw  = bin2hex(random_bytes(32));
			$hash = hash('sha256', $raw);

			$ok = DB::execute(
				'UPDATE users SET login_code = ? WHERE id_user = ?',
				[$hash, $idUser]
			);

			$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
			$hs = headers_sent($file, $line) ? "YES {$file}:{$line}" : "NO";

			//error_log("ISSUE_REMEMBER id={$idUser} ok=".print_r($ok,true)." https=".($secure?'1':'0')." headers_sent={$hs} raw={$raw} hash={$hash}");

			self::setRememberCookie($raw, 30);
		}

		public static function autoLoginFromRememberCookie(): void
		{
			if (session_status() !== PHP_SESSION_ACTIVE) session_start();

			if (!empty($_SESSION['id_user'])) return;
			if (empty($_COOKIE['remember'])) return;

			$raw = $_COOKIE['remember'];

			// bin2hex(random_bytes(32)) => 64 hex karakter
			if (!is_string($raw) || !preg_match('/^[a-f0-9]{64}$/i', $raw)) {
				self::clearRememberCookie();
				return;
			}

			$hash = hash('sha256', $raw);

			$idUser = (int) DB::getValue(
				'SELECT id_user FROM users WHERE login_code = ? LIMIT 1',
				[$hash]
			);

			if ($idUser > 0) {
				session_regenerate_id(true); // ✅ sadece burada
				$_SESSION['id_user'] = $idUser;

				// token rotate (güzel pratik)
				self::issueRememberToken($idUser);
		} else {
			self::clearRememberCookie();
		}
	}
}

if (!function_exists('clearSQL')) {
	function clearSQL($data)
	{
		$data = trim($data);
		$data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

		return $data;
	}
}

if (!function_exists('fshop_validate_captcha')) {
	function fshop_validate_captcha(string $form): string
	{
		$captchaFile = dirname(__FILE__) . '/../core/Captcha.php';

		if (!class_exists('Captcha', false) && is_file($captchaFile)) {
			require_once $captchaFile;
		}

		if (class_exists('Captcha', false)) {
			return Captcha::validate($form);
		}

		if (!class_exists('Module', false)) {
			return '';
		}

		$error = '';
		Module::runHook('form.captcha.validate', [$form, &$error]);

		return trim((string) $error);
	}
}