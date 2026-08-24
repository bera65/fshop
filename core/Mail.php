<?php

class Mail
{
	private static string $lastError = '';

	public static function getLastError(): string
	{
		return self::$lastError;
	}

	public static function usesSmtp(): bool
	{
		return Settings::get('MAIL_DRIVER') === 'smtp';
	}

	public static function isConfigured(): bool
	{
		if (self::usesSmtp()) {
			return trim(Settings::get('SMTP_HOST')) !== ''
				&& trim(Settings::get('SMTP_USER')) !== ''
				&& Settings::get('SMTP_PASS') !== '';
		}

		return trim(self::getFromEmail()) !== '';
	}

	public static function send(string $to, string $subject, string $bodyHtml): bool
	{
		self::$lastError = '';
		$to = trim($to);

		if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
			self::$lastError = 'Geçersiz alıcı e-posta adresi';
			return false;
		}

		$body = self::wrapTemplate($bodyHtml);

		if (self::usesSmtp()) {
			if (!self::isConfigured()) {
				self::$lastError = 'SMTP ayarları eksik';
				return false;
			}

			$sent = SmtpMailer::send($to, $subject, $body);
			if (!$sent) {
				self::$lastError = SmtpMailer::getLastError() ?: 'SMTP gönderimi başarısız';
			}

			return $sent;
		}

		return self::sendViaPhpMail($to, $subject, $body);
	}

	/** @return array<int, string> */
	public static function getLayoutPlaceholders(): array
	{
		return [
			'{site_name}',
			'{site_url}',
			'{logo_url}',
			'{contact_email}',
			'{contact_phone}',
		];
	}

	public static function getDefaultEmailHeader(): string
	{
		return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#ffffff;">'
			. '<tr><td align="center" style="padding:28px 20px 20px;border-bottom:1px solid #eeeeee;">'
			. '<a href="{site_url}" style="text-decoration:none;display:inline-block;">'
			. '<img src="{logo_url}" alt="{site_name}" width="160" style="display:block;max-width:160px;height:auto;border:0;">'
			. '</a></td></tr></table>';
	}

	public static function getDefaultEmailFooter(): string
	{
		return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8f9fa;">'
			. '<tr><td align="center" style="padding:22px 20px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#888888;">'
			. '<p style="margin:0 0 8px;font-weight:bold;color:#555555;">{site_name}</p>'
			. '<p style="margin:0 0 8px;">{contact_phone} · <a href="mailto:{contact_email}" style="color:#888888;text-decoration:none;">{contact_email}</a></p>'
			. '<p style="margin:0;"><a href="{site_url}" style="color:#888888;text-decoration:underline;">{site_url}</a></p>'
			. '<p style="margin:16px 0 0;font-size:11px;color:#aaaaaa;">Bu e-posta {site_name} tarafından gönderilmiştir.</p>'
			. '</td></tr></table>';
	}

	public static function getEmailHeader(): string
	{
		$stored = trim((string) Settings::get('MAIL_HEADER'));

		return $stored !== '' ? $stored : self::getDefaultEmailHeader();
	}

	public static function getEmailFooter(): string
	{
		$stored = trim((string) Settings::get('MAIL_FOOTER'));

		return $stored !== '' ? $stored : self::getDefaultEmailFooter();
	}

	public static function previewTemplate(string $contentHtml): string
	{
		return self::wrapTemplate($contentHtml);
	}

	private static function sendViaPhpMail(string $to, string $subject, string $bodyHtml): bool
	{
		if (!function_exists('mail')) {
			self::$lastError = 'PHP mail() is disabled on this server. Configure SMTP in Admin → Settings.';
			return false;
		}

		$from = self::getFromEmail();

		if ($from === '') {
			self::$lastError = 'Gönderen e-posta tanımlı değil (İletişim e-postası alanını doldurun)';
			return false;
		}

		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		$headers = [
			'MIME-Version: 1.0',
			'Content-type: text/html; charset=utf-8',
			'From: ' . self::encodeHeader($siteName) . ' <' . $from . '>',
			'Reply-To: ' . $from,
		];

		$sent = @mail($to, $encodedSubject, $bodyHtml, implode("\r\n", $headers));

		if (!$sent) {
			self::$lastError = 'PHP mail() gönderimi başarısız. Sunucunuzda mail() etkin mi kontrol edin.';
		}

		return $sent;
	}

	private static function getFromEmail(): string
	{
		if (self::usesSmtp()) {
			return trim(Settings::get('SMTP_FROM_EMAIL'))
				?: trim(Settings::get('SMTP_USER'));
		}

		return trim(Settings::get('CONTACT_EMAIL'));
	}

	public static function sendWelcome(string $to, string $fullName): bool
	{
		global $domain;

		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$name = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
		$loginUrl = htmlspecialchars(rtrim($domain, '/') . '/login', ENT_QUOTES, 'UTF-8');

		$body = '<h2 style="margin:0 0 16px;">Hoş geldiniz!</h2>'
			. '<p>Merhaba <strong>' . $name . '</strong>,</p>'
			. '<p>' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . ' ailesine katıldığınız için teşekkür ederiz. Hesabınız başarıyla oluşturuldu.</p>'
			. '<p>Siparişlerinizi takip etmek ve alışverişe devam etmek için hesabınıza giriş yapabilirsiniz.</p>'
			. '<p style="margin:24px 0;"><a href="' . $loginUrl . '" style="display:inline-block;padding:12px 24px;background:#1a1a1a;color:#fff;text-decoration:none;border-radius:6px;">Giriş Yap</a></p>';

		return self::send($to, $siteName . ' - Hoş geldiniz', $body);
	}

	public static function sendPasswordReset(string $to, string $fullName, string $resetUrl): bool
	{
		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$name = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
		$url = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

		$body = '<h2 style="margin:0 0 16px;">Şifre Sıfırlama</h2>'
			. '<p>Merhaba <strong>' . $name . '</strong>,</p>'
			. '<p>Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın. Bu bağlantı 1 saat geçerlidir.</p>'
			. '<p style="margin:24px 0;"><a href="' . $url . '" style="display:inline-block;padding:12px 24px;background:#1a1a1a;color:#fff;text-decoration:none;border-radius:6px;">Şifremi Sıfırla</a></p>'
			. '<p style="font-size:13px;color:#666;">Bu isteği siz yapmadıysanız bu e-postayı yok sayabilirsiniz.</p>'
			. '<p style="font-size:12px;color:#999;word-break:break-all;">' . $url . '</p>';

		return self::send($to, $siteName . ' - Şifre Sıfırlama', $body);
	}

	public static function sendAdminPasswordReset(string $to, string $fullName, string $resetUrl): bool
	{
		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$name = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
		$url = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

		$body = '<h2 style="margin:0 0 16px;">Yönetim Paneli Şifre Sıfırlama</h2>'
			. '<p>Merhaba <strong>' . $name . '</strong>,</p>'
			. '<p>Yönetim paneli şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın. Bu bağlantı 1 saat geçerlidir.</p>'
			. '<p style="margin:24px 0;"><a href="' . $url . '" style="display:inline-block;padding:12px 24px;background:#1a1a1a;color:#fff;text-decoration:none;border-radius:6px;">Şifremi Sıfırla</a></p>'
			. '<p style="font-size:13px;color:#666;">Bu isteği siz yapmadıysanız bu e-postayı yok sayabilirsiniz.</p>'
			. '<p style="font-size:12px;color:#999;word-break:break-all;">' . $url . '</p>';

		return self::send($to, $siteName . ' - Yönetim Paneli Şifre Sıfırlama', $body);
	}

	private static function encodeHeader(string $text): string
	{
		return '=?UTF-8?B?' . base64_encode($text) . '?=';
	}

	private static function wrapTemplate(string $body): string
	{
		$header = self::replaceLayoutPlaceholders(self::getEmailHeader());
		$content = self::wrapContentSection($body);
		$footer = self::replaceLayoutPlaceholders(self::getEmailFooter());
		$siteName = htmlspecialchars(Settings::get('SITE_NAME') ?: 'FShop', ENT_QUOTES, 'UTF-8');

		return '<!DOCTYPE html>'
			. '<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
			. '<title>' . $siteName . '</title></head>'
			. '<body style="margin:0;padding:0;background:#f4f4f5;">'
			. '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f4f5;">'
			. '<tr><td align="center" style="padding:24px 12px;">'
			. '<table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e8e8e8;">'
			. '<tr><td>' . $header . '</td></tr>'
			. '<tr><td>' . $content . '</td></tr>'
			. '<tr><td>' . $footer . '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}

	private static function wrapContentSection(string $body): string
	{
		if (strpos($body, '<') === false) {
			$body = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'), false);
		}

		return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">'
			. '<tr><td style="padding:28px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.65;color:#333333;">'
			. $body
			. '</td></tr></table>';
	}

	private static function replaceLayoutPlaceholders(string $text): string
	{
		global $domain;

		if (!class_exists('SiteAssets', false)) {
			require_once dirname(__DIR__) . '/core/SiteAssets.php';
		}

		$siteUrl = rtrim((string) $domain, '/');
		$siteName = (string) (Settings::get('SITE_NAME') ?: 'FShop');
		$map = [
			'{site_name}' => htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'),
			'{site_url}' => htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'),
			'{logo_url}' => htmlspecialchars(SiteAssets::getLogoUrl('header'), ENT_QUOTES, 'UTF-8'),
			'{contact_email}' => htmlspecialchars((string) Settings::get('CONTACT_EMAIL'), ENT_QUOTES, 'UTF-8'),
			'{contact_phone}' => htmlspecialchars((string) Settings::get('CONTACT_PHONE'), ENT_QUOTES, 'UTF-8'),
		];

		return str_replace(array_keys($map), array_values($map), $text);
	}
}
