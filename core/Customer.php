<?php

class Customer
{
	/**
	 * Digits-only phone for storage / lookup.
	 * Keeps Turkish mobile convenience (5xx… / +90 5xx… → 05xxxxxxxxx)
	 * while accepting international numbers (E.164 digit length).
	 */
	public static function normalizePhone(string $phone): string
	{
		$digits = preg_replace('/\D+/', '', $phone);

		if (!is_string($digits) || $digits === '') {
			return '';
		}

		// International dial prefix 00…
		if (strpos($digits, '00') === 0 && strlen($digits) > 4) {
			$digits = substr($digits, 2);
		}

		// Turkey with country code: 905xxxxxxxxx → 05xxxxxxxxx (legacy local storage)
		if (preg_match('/^90(5[0-9]{9})$/', $digits, $m)) {
			return '0' . $m[1];
		}

		// Turkey national mobile without leading 0: 5xxxxxxxxx → 05xxxxxxxxx
		if (preg_match('/^5[0-9]{9}$/', $digits)) {
			return '0' . $digits;
		}

		return $digits;
	}

	/**
	 * Accept international numbers (7–15 digits per E.164) and Turkish 05xxxxxxxxx.
	 */
	public static function isValidPhone(string $phone): bool
	{
		$digits = self::normalizePhone($phone);
		$len = strlen($digits);

		if ($len < 7 || $len > 15 || !ctype_digit($digits)) {
			return false;
		}

		if (preg_match('/^0+$/', $digits)) {
			return false;
		}

		return true;
	}

	public static function hashPassword(string $password): string
	{
		return password_hash($password, PASSWORD_DEFAULT);
	}

	public static function verifyPassword(string $password, string $hash): bool
	{
		$info = password_get_info($hash);

		if ($info['algo'] !== null && $info['algo'] !== 0) {
			return password_verify($password, $hash);
		}

		if (strlen($hash) === 32 && ctype_xdigit($hash)) {
			return hash_equals(Tools::hash($password), $hash);
		}

		return false;
	}

	public static function register(string $fullName, string $phone, string $password, string $email = ''): array
	{
		$phone = self::normalizePhone($phone);
		$fullName = trim($fullName);
		$email = trim(strtolower($email));

		if (!Validate::isName($fullName)) {
			return self::fail('Geçerli bir ad soyad girin');
		}

		if (!self::isValidPhone($phone)) {
			return self::fail(translate('Please enter a valid phone number'));
		}

		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return self::fail('Geçerli bir e-posta adresi girin');
		}

		$passwordError = Security::validatePassword($password);

		if ($passwordError !== null) {
			return self::fail($passwordError);
		}

		$exists = DB::getValue('SELECT id_user FROM users WHERE phone = ? LIMIT 1', [$phone]);
		if ($exists) {
			return self::fail('Bu telefon numarası zaten kayıtlı');
		}

		$emailExists = DB::getValue('SELECT id_user FROM users WHERE email = ? LIMIT 1', [$email]);
		if ($emailExists) {
			return self::fail('Bu e-posta adresi zaten kayıtlı');
		}

		$isActive = self::shouldActivateNewMember() ? 1 : 0;
		self::ensureSchema();
		$idGroup = class_exists('CustomerGroup', false) ? CustomerGroup::getDefaultId() : 0;

		$id = DB::insert('users', [
			'user_full_name' => $fullName,
			'phone' => $phone,
			'email' => $email,
			'password' => self::hashPassword($password),
			'active' => $isActive,
			'id_group' => $idGroup,
		]);

		if (!$id) {
			return self::fail('Kayıt oluşturulamadı');
		}

		if ($isActive) {
			self::loginSession((int) $id, true);
			Notification::welcome((int) $id, $fullName);
			Mail::sendWelcome($email, $fullName);

			return self::ok(translate('Registration successful'));
		}

		return [
			'success' => true,
			'message' => translate('Your registration was received. You can sign in after admin approval.'),
			'user' => null,
		];
	}

	public static function login(string $login, string $password, bool $remember = true): array
	{
		$login = trim($login);
		$rateKey = RateLimit::loginIdentifier($login !== '' ? $login : 'empty');

		if (RateLimit::isLimited(RateLimit::SCOPE_CUSTOMER_LOGIN, $rateKey, 8, 900)) {
			return self::fail('Çok fazla başarısız giriş denemesi. Lütfen 15 dakika sonra tekrar deneyin.');
		}

		$user = null;

		$userAll = null;

		if ($login !== '' && filter_var($login, FILTER_VALIDATE_EMAIL)) {
			$email = strtolower($login);
			$userAll = DB::getRowSafe('users', 'email = ?', [$email]);
			$user = DB::getRowSafe('users', 'email = ? AND active = 1', [$email]);
		} else {
			$phone = self::normalizePhone($login);

			if (!self::isValidPhone($phone)) {
				RateLimit::record(RateLimit::SCOPE_CUSTOMER_LOGIN, $rateKey);

				return self::fail('E-posta / telefon veya şifre hatalı');
			}

			$userAll = DB::getRowSafe('users', 'phone = ?', [$phone]);
			$user = DB::getRowSafe('users', 'phone = ? AND active = 1', [$phone]);
		}

		if ($userAll && !$user && self::verifyPassword($password, $userAll['password'])) {
			RateLimit::clear(RateLimit::SCOPE_CUSTOMER_LOGIN, $rateKey);
			return self::fail(translate('Your account is pending admin approval or is inactive.'));
		}

		if (!$user || !self::verifyPassword($password, $user['password'])) {
			RateLimit::record(RateLimit::SCOPE_CUSTOMER_LOGIN, $rateKey);

			return self::fail('E-posta / telefon veya şifre hatalı');
		}

		RateLimit::clear(RateLimit::SCOPE_CUSTOMER_LOGIN, $rateKey);

		self::upgradePasswordIfNeeded((int) $user['id_user'], $password, $user['password']);
		self::loginSession((int) $user['id_user'], $remember);

		return self::ok('Giriş başarılı');
	}

	public static function loginSession(int $idUser, bool $remember = true): void
	{
		session_regenerate_id(true);
		$_SESSION['id_user'] = $idUser;

		if (class_exists('GroupPricing', false)) {
			GroupPricing::resetCache();
		}

		if ($remember) {
			Cookie::issueRememberToken($idUser);
		}
	}

	public static function logout(): void
	{
		if (!empty($_SESSION['id_user'])) {
			DB::execute(
				'UPDATE users SET login_code = ? WHERE id_user = ?',
				['', (int) $_SESSION['id_user']]
			);
		}

		Cookie::clearRememberCookie();
		unset($_SESSION['id_user']);

		if (class_exists('GroupPricing', false)) {
			GroupPricing::resetCache();
		}

		session_regenerate_id(true);
	}

	public static function isLoggedIn(): bool
	{
		return !empty($_SESSION['id_user']);
	}

	public static function getId(): int
	{
		return (int) ($_SESSION['id_user'] ?? 0);
	}

	public static function getCurrent(): ?array
	{
		if (!self::isLoggedIn()) {
			return null;
		}

		$user = DB::getRowSafe('users', 'id_user = ? AND active = 1', [self::getId()]);

		if (!$user) {
			self::logout();

			return null;
		}

		unset($user['password'], $user['login_code']);

		return $user;
	}

	public static function publicUser(?array $user): ?array
	{
		if (!$user) {
			return null;
		}

		return [
			'id_user' => (int) $user['id_user'],
			'user_full_name' => $user['user_full_name'],
			'phone' => $user['phone'],
			'email' => $user['email'] ?? '',
			'image' => $user['image'],
		];
	}

	public static function updateProfile(string $fullName, string $phone, string $email = ''): array
	{
		if (!self::isLoggedIn()) {
			return self::fail('Giriş yapmalısınız');
		}

		$fullName = trim($fullName);
		$phone = self::normalizePhone($phone);
		$email = trim(strtolower($email));
		$idUser = self::getId();
		$current = self::getCurrent();

		if (!$current) {
			return self::fail('Oturum bulunamadı');
		}

		if (!Validate::isName($fullName)) {
			return self::fail('Geçerli bir ad soyad girin');
		}

		if (!self::isValidPhone($phone)) {
			return self::fail('Geçerli bir telefon numarası girin');
		}

		if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return self::fail('Geçerli bir e-posta adresi girin');
		}

		if ($phone !== $current['phone']) {
			$exists = DB::getValue(
				'SELECT id_user FROM users WHERE phone = ? AND id_user != ? LIMIT 1',
				[$phone, $idUser]
			);

			if ($exists) {
				return self::fail('Bu telefon numarası başka bir hesapta kayıtlı');
			}
		}

		if ($email !== '' && $email !== ($current['email'] ?? '')) {
			$emailExists = DB::getValue(
				'SELECT id_user FROM users WHERE email = ? AND id_user != ? LIMIT 1',
				[$email, $idUser]
			);

			if ($emailExists) {
				return self::fail('Bu e-posta adresi başka bir hesapta kayıtlı');
			}
		}

		$updated = DB::update(
			'users',
			[
				'user_full_name' => $fullName,
				'phone' => $phone,
				'email' => $email,
			],
			'id_user = :id_user',
			['id_user' => $idUser]
		);

		if ($updated === false) {
			return self::fail('Profil güncellenemedi');
		}

		return self::ok('Profil bilgileriniz güncellendi');
	}

	public static function updatePassword(string $currentPassword, string $newPassword): array
	{
		if (!self::isLoggedIn()) {
			return self::fail('Giriş yapmalısınız');
		}

		$user = DB::getRowSafe('users', 'id_user = ? AND active = 1', [self::getId()]);

		if (!$user) {
			return self::fail('Oturum bulunamadı');
		}

		if (!self::verifyPassword($currentPassword, $user['password'])) {
			return self::fail('Mevcut şifre hatalı');
		}

		$passwordError = Security::validatePassword($newPassword);

		if ($passwordError !== null) {
			return self::fail($passwordError);
		}

		$updated = DB::update(
			'users',
			['password' => self::hashPassword($newPassword)],
			'id_user = :id_user',
			['id_user' => (int) $user['id_user']]
		);

		if ($updated === false) {
			return self::fail('Şifre güncellenemedi');
		}

		return self::ok('Şifreniz güncellendi');
	}

	private static function upgradePasswordIfNeeded(int $idUser, string $password, string $storedHash): void
	{
		$info = password_get_info($storedHash);

		if ($info['algo'] !== null && $info['algo'] !== 0) {
			if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
				DB::update(
					'users',
					['password' => self::hashPassword($password)],
					'id_user = :id_user',
					['id_user' => $idUser]
				);
			}

			return;
		}

		if (strlen($storedHash) === 32 && ctype_xdigit($storedHash)) {
			DB::update(
				'users',
				['password' => self::hashPassword($password)],
				'id_user = :id_user',
				['id_user' => $idUser]
			);
		}
	}

	public static function countAdmin(string $query = ''): int
	{
		$params = [];
		$where = '1=1';

		if ($query !== '') {
			$where .= ' AND (user_full_name LIKE ? OR phone LIKE ? OR email LIKE ?)';
			$like = '%' . $query . '%';
			$params = [$like, $like, $like];
		}

		return (int) DB::getValue('SELECT COUNT(*) FROM users WHERE ' . $where, $params);
	}

	public static function getAdminList(string $query = '', int $limit = 30, int $offset = 0): array
	{
		$params = [];
		$where = '1=1';

		if ($query !== '') {
			$where .= ' AND (u.user_full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)';
			$like = '%' . $query . '%';
			$params = [$like, $like, $like];
		}

		$sql = 'SELECT u.*, COUNT(o.id_order) AS order_count,
				COALESCE(SUM(o.total), 0) AS order_total
				FROM users u
				LEFT JOIN orders o ON o.id_user = u.id_user
				WHERE ' . $where . '
				GROUP BY u.id_user
				ORDER BY u.date_add DESC
				LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		$rows = DB::execute($sql, $params) ?: [];

		foreach ($rows as &$row) {
			$row['order_count'] = (int) $row['order_count'];
			$row['order_total_formatted'] = Tools::displayPrice($row['order_total']);
			$row['date_formatted'] = Tools::formatDate3($row['date_add']);
			$row['active'] = (int) $row['active'];
		}
		unset($row);

		return $rows;
	}

	public static function getByIdAdmin(int $idUser): ?array
	{
		self::ensureSchema();
		$user = DB::getRowSafe('users', 'id_user = ?', [$idUser]);

		if (!$user) {
			return null;
		}

		unset($user['password'], $user['login_code']);
		$user['date_formatted'] = Tools::formatDate3($user['date_add']);
		$user['id_group'] = (int) ($user['id_group'] ?? 0);
		$user['orders'] = Order::getUserOrders($idUser);

		return $user;
	}

	public static function updateByAdmin(int $idUser, string $fullName, string $phone, string $email = '', int $idGroup = 0): array
	{
		if ($idUser <= 0) {
			return self::fail('Geçersiz müşteri');
		}

		self::ensureSchema();
		$user = DB::getRowSafe('users', 'id_user = ?', [$idUser]);

		if (!$user) {
			return self::fail('Müşteri bulunamadı');
		}

		$fullName = trim($fullName);
		$phone = self::normalizePhone($phone);
		$email = trim(strtolower($email));

		if (!Validate::isName($fullName)) {
			return self::fail('Geçerli bir ad soyad girin');
		}

		if (!self::isValidPhone($phone)) {
			return self::fail(translate('Please enter a valid phone number'));
		}

		if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return self::fail('Geçerli bir e-posta adresi girin');
		}

		if ($phone !== (string) ($user['phone'] ?? '')) {
			$exists = DB::getValue(
				'SELECT id_user FROM users WHERE phone = ? AND id_user != ? LIMIT 1',
				[$phone, $idUser]
			);

			if ($exists) {
				return self::fail('Bu telefon numarası başka bir hesapta kayıtlı');
			}
		}

		if ($email !== '' && $email !== (string) ($user['email'] ?? '')) {
			$emailExists = DB::getValue(
				'SELECT id_user FROM users WHERE email = ? AND id_user != ? LIMIT 1',
				[$email, $idUser]
			);

			if ($emailExists) {
				return self::fail('Bu e-posta adresi başka bir hesapta kayıtlı');
			}
		}

		if ($idGroup <= 0 && class_exists('CustomerGroup', false)) {
			$idGroup = CustomerGroup::getDefaultId();
		}

		if ($idGroup > 0 && class_exists('CustomerGroup', false)) {
			$group = CustomerGroup::getById($idGroup);

			if (!$group || empty($group['active'])) {
				return self::fail('Geçersiz müşteri grubu');
			}
		}

		$data = [
			'user_full_name' => $fullName,
			'phone' => $phone,
			'email' => $email,
		];

		if ($idGroup > 0) {
			$data['id_group'] = $idGroup;
		}

		$updated = DB::update(
			'users',
			$data,
			'id_user = :id_user',
			['id_user' => $idUser]
		);

		if ($updated === false) {
			return self::fail('Müşteri güncellenemedi');
		}

		return [
			'success' => true,
			'message' => 'Müşteri bilgileri güncellendi',
		];
	}

	public static function requestPasswordReset(string $email): array
	{
		$email = trim(strtolower($email));

		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return self::fail('Geçerli bir e-posta adresi girin');
		}

		$user = DB::getRowSafe('users', 'email = ? AND active = 1', [$email]);

		if ($user) {
			global $domain;

			$rawToken = bin2hex(random_bytes(32));
			$tokenHash = hash('sha256', $rawToken);
			$expires = date('Y-m-d H:i:s', time() + 3600);

			DB::update(
				'users',
				[
					'reset_token' => $tokenHash,
					'reset_expires' => $expires,
				],
				'id_user = :id_user',
				['id_user' => (int) $user['id_user']]
			);

			$resetUrl = rtrim($domain, '/') . '/reset-password?token=' . $rawToken;
			Mail::sendPasswordReset($email, (string) $user['user_full_name'], $resetUrl);
		}

		return self::ok('Şifre sıfırlama bağlantısı e-posta adresinize gönderildi');
	}

	public static function resetPassword(string $token, string $password, string $password2): array
	{
		$token = trim($token);

		$passwordError = Security::validatePassword($password);

		if ($passwordError !== null) {
			return self::fail($passwordError);
		}

		if ($password !== $password2) {
			return self::fail('Şifreler eşleşmiyor');
		}

		if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
			return self::fail('Geçersiz veya süresi dolmuş bağlantı');
		}

		$tokenHash = hash('sha256', $token);
		$user = DB::getRowSafe(
			'users',
			'reset_token = ? AND active = 1 AND reset_expires IS NOT NULL AND reset_expires > NOW()',
			[$tokenHash]
		);

		if (!$user) {
			return self::fail('Geçersiz veya süresi dolmuş bağlantı');
		}

		$updated = DB::update(
			'users',
			[
				'password' => self::hashPassword($password),
				'reset_token' => '',
				'reset_expires' => null,
				'login_code' => '',
			],
			'id_user = :id_user',
			['id_user' => (int) $user['id_user']]
		);

		if ($updated === false) {
			return self::fail('Şifre güncellenemedi');
		}

		Cookie::clearRememberCookie();

		return self::ok('Şifreniz güncellendi, giriş yapabilirsiniz');
	}

	public static function setActive(int $idUser, bool $active): array
	{
		if ($idUser <= 0) {
			return self::fail('Geçersiz müşteri');
		}

		$updated = DB::update(
			'users',
			['active' => $active ? 1 : 0],
			'id_user = :id_user',
			['id_user' => $idUser]
		);

		if ($updated === false) {
			return self::fail('Müşteri güncellenemedi');
		}

		return [
			'success' => true,
			'message' => $active ? 'Müşteri aktif edildi' : 'Müşteri pasif edildi',
		];
	}

	public static function createByAdmin(string $fullName, string $phone, string $email = ''): array
	{
		$fullName = trim($fullName);
		$phone = self::normalizePhone($phone);
		$email = trim(strtolower($email));

		if (!Validate::isName($fullName)) {
			return self::fail('Geçerli bir ad soyad girin');
		}

		if (!self::isValidPhone($phone)) {
			return self::fail(translate('Please enter a valid phone number'));
		}

		if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return self::fail('Geçerli bir e-posta adresi girin');
		}

		$exists = DB::getValue('SELECT id_user FROM users WHERE phone = ? LIMIT 1', [$phone]);

		if ($exists) {
			return self::fail('Bu telefon numarası zaten kayıtlı');
		}

		if ($email !== '') {
			$emailExists = DB::getValue('SELECT id_user FROM users WHERE email = ? LIMIT 1', [$email]);

			if ($emailExists) {
				return self::fail('Bu e-posta adresi zaten kayıtlı');
			}
		}

		self::ensureSchema();
		$idGroup = class_exists('CustomerGroup', false) ? CustomerGroup::getDefaultId() : 0;

		$id = DB::insert('users', [
			'user_full_name' => mb_substr($fullName, 0, 128),
			'phone' => $phone,
			'email' => $email,
			'password' => self::hashPassword(bin2hex(random_bytes(12))),
			'active' => 1,
			'id_group' => $idGroup,
		]);

		if (!$id) {
			return self::fail('Müşteri oluşturulamadı');
		}

		return [
			'success' => true,
			'message' => 'Müşteri oluşturuldu',
			'id_user' => (int) $id,
		];
	}

	public static function setPasswordByAdmin(int $idUser, string $password, string $password2): array
	{
		if ($idUser <= 0) {
			return self::fail('Geçersiz müşteri');
		}

		$user = DB::getRowSafe('users', 'id_user = ?', [$idUser]);

		if (!$user) {
			return self::fail('Müşteri bulunamadı');
		}

		$passwordError = Security::validatePassword($password, false);

		if ($passwordError !== null) {
			return self::fail($passwordError);
		}

		if ($password !== $password2) {
			return self::fail('Şifreler eşleşmiyor');
		}

		$hash = self::hashPassword($password);

		$updated = DB::update(
			'users',
			[
				'password' => $hash,
				'reset_token' => '',
				'reset_expires' => null,
				'login_code' => '',
			],
			'id_user = :id_user',
			['id_user' => $idUser]
		);

		if ($updated === false) {
			return self::fail('Şifre güncellenemedi');
		}

		$stored = (string) DB::getValue(
			'SELECT password FROM users WHERE id_user = ? LIMIT 1',
			[$idUser]
		);

		if ($stored === '' || !self::verifyPassword($password, $stored)) {
			return self::fail('Şifre kaydedildi ama doğrulanamadı. Lütfen tekrar deneyin.');
		}

		return [
			'success' => true,
			'message' => 'Müşteri şifresi güncellendi. Giriş için telefon + yeni şifreyi kullanın.',
		];
	}

	private static function ok(string $message): array
	{
		return [
			'success' => true,
			'message' => $message,
			'user' => self::publicUser(self::getCurrent()),
		];
	}

	private static function fail(string $message): array
	{
		return [
			'success' => false,
			'message' => $message,
			'user' => null,
		];
	}

	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		$googleId = DB::execute("SHOW COLUMNS FROM `users` LIKE 'google_id'");

		if (empty($googleId)) {
			DB::execute(
				"ALTER TABLE `users`
				 ADD COLUMN `google_id` varchar(64) DEFAULT NULL AFTER `email`,
				 ADD UNIQUE KEY `google_id` (`google_id`)"
			);
		}

		if (class_exists('CustomerGroup', false)) {
			CustomerGroup::ensureSchema();
		} elseif (is_file(dirname(__FILE__) . '/CustomerGroup.php')) {
			require_once dirname(__FILE__) . '/CustomerGroup.php';
			CustomerGroup::ensureSchema();
		}
	}

	public static function authWithGoogle(string $googleId, string $email, string $fullName, bool $emailVerified = false): array
	{
		self::ensureSchema();

		$googleId = trim($googleId);
		$email = strtolower(trim($email));
		$fullName = trim($fullName);

		if ($googleId === '') {
			return self::fail(translate('Invalid request, please refresh and try again'));
		}

		if (!$emailVerified) {
			return self::fail(translate('Google login failed'));
		}

		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return self::fail(translate('Please enter a valid email'));
		}

		if ($fullName === '') {
			$fullName = strstr($email, '@', true) ?: 'Google User';
		}

		$user = DB::getRowSafe('users', 'google_id = ?', [$googleId]);

		if ($user) {
			if ((int) $user['active'] !== 1) {
				return self::fail(translate('Your account is pending admin approval or is inactive.'));
			}

			self::loginSession((int) $user['id_user'], true);

			return self::ok(translate('Login successful'));
		}

		$user = DB::getRowSafe('users', 'email = ?', [$email]);

		if ($user) {
			DB::execute(
				'UPDATE users SET google_id = ?, user_full_name = IF(user_full_name = "", ?, user_full_name) WHERE id_user = ?',
				[$googleId, $fullName, (int) $user['id_user']]
			);

			if ((int) $user['active'] !== 1) {
				return self::fail(translate('Your account is pending admin approval or is inactive.'));
			}

			self::loginSession((int) $user['id_user'], true);

			return self::ok(translate('Login successful'));
		}

		$isActive = self::shouldActivateNewMember() ? 1 : 0;
		$idGroup = class_exists('CustomerGroup', false) ? CustomerGroup::getDefaultId() : 0;

		$phone = self::generateGooglePlaceholderPhone($googleId);
		$id = DB::insert('users', [
			'user_full_name' => mb_substr($fullName, 0, 128),
			'phone' => $phone,
			'email' => $email,
			'google_id' => $googleId,
			'password' => self::hashPassword(bin2hex(random_bytes(16))),
			'active' => $isActive,
			'id_group' => $idGroup,
		]);

		if (!$id) {
			return self::fail(translate('Register failed'));
		}

		if ($isActive) {
			self::loginSession((int) $id, true);
			Notification::welcome((int) $id, $fullName);
			Mail::sendWelcome($email, $fullName);

			return self::ok(translate('Register successful'));
		}

		return [
			'success' => true,
			'pending' => true,
			'message' => translate('Your registration was received. You can sign in after admin approval.'),
			'user' => null,
		];
	}

	/**
	 * Admin > Üye onayı ayarı:
	 * - auto → yeni üye hemen aktif
	 * - manual → yönetici onayı gerekir (active=0)
	 */
	public static function shouldActivateNewMember(): bool
	{
		return strtolower(trim((string) Settings::get('MEMBER_APPROVAL'))) !== 'manual';
	}

	private static function generateGooglePlaceholderPhone(string $googleId): string
	{
		$base = '05' . str_pad((string) (abs(crc32($googleId)) % 1000000000), 9, '0', STR_PAD_LEFT);
		$phone = $base;
		$attempt = 0;

		while (DB::getValue('SELECT id_user FROM users WHERE phone = ? LIMIT 1', [$phone]) && $attempt < 20) {
			$phone = '05' . str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);
			$attempt++;
		}

		return $phone;
	}
}
