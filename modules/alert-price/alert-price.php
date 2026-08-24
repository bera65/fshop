<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
    exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class AlertPriceModule extends ModuleBase
{
    public string $name = 'alert-price';
    public string $title = 'Fiyatı Düşünce Haber Ver';
    public string $version = '1.0.0';
    public string $description = 'Müşteriler ürün fiyatı belirli bir seviyeye düşünce e-posta ile bilgilendirilir';
    public string $author = 'FShop';

    public array $displayHooks = [
        'product_inf' => 'Ürün sayfasında fiyat alarm butonu',
    ];
    public array $defaultDisplayHooks = ['product_inf'];

    public array $frontStylesheets = ['alert-price.css'];
    public array $frontScripts = ['alert-price.js'];

    public array $apiActions = [
        'subscribe' => 'api/subscribe.php',
        'cron' => 'api/cron.php',
    ];

    public function install(): bool
    {
        return $this->runSqlFile('install.sql');
    }

    public function uninstall(): bool
    {
        return $this->runSqlFile('uninstall.sql');
    }

    public function adminPage(): void
    {
        global $smarty, $adminToken, $domain;

        $flash = '';

        if (Tools::isSubmit('saveSettings')) {
            $postToken = (string) Tools::getValue('token');

            if (hash_equals($adminToken, $postToken)) {
                Settings::set('ALERT_PRICE_CRON_TOKEN', trim((string) Tools::getValue('cron_token')));
                Settings::set('ALERT_PRICE_CRON_ENABLED', (int) Tools::isSubmit('cron_enabled'));
                $flash = 'Ayarlar kaydedildi';
                $flashType = 'success';
            } else {
                $flash = 'Geçersiz istek';
                $flashType = 'danger';
            }
        }

        $cronToken = Settings::get('ALERT_PRICE_CRON_TOKEN');
        if ($cronToken === '') {
            $cronToken = bin2hex(random_bytes(16));
            Settings::set('ALERT_PRICE_CRON_TOKEN', $cronToken);
        }

        $smarty->assign([
            'cronToken' => $cronToken,
            'cronEnabled' => (int) Settings::get('ALERT_PRICE_CRON_ENABLED'),
            'flash' => $flash,
            'flashType' => $flashType ?? 'success',
            'cronUrl' => rtrim($domain, '/') . '/api/module.php?m=alert-price&action=cron&token=' . rawurlencode($cronToken),
        ]);
    }
/*
    public function renderDisplayHook(string $hook, array $context = []): ?string
    {
        if ($hook !== 'product_inf') {
            return null;
        }

        $idProduct = (int) ($context['id_product'] ?? 0);

        if ($idProduct <= 0) {
            return null;
        }

        $product = Product::getById($idProduct);

        if (!$product) {
            return null;
        }

        $currentPrice = (float) ($product['price'] ?? 0);
        $productName = $product['product_name'] ?? 'Ürün';
        $productUrl = $product['url'] ?? Product::getLink($product);

        global $domain;

        $html = $this->renderFrontTemplate('product_inf', [
            'id_product' => $idProduct,
            'product_name' => $productName,
            'product_url' => $productUrl,
            'current_price' => $currentPrice,
            'current_price_formatted' => Tools::displayPrice($currentPrice),
            'api_url' => rtrim($domain, '/') . '/api/module.php?m=alert-price&action=subscribe',
            'isLoggedIn' => Customer::isLoggedIn(),
            'user_email' => Customer::isLoggedIn() ? (Customer::getCurrent()['email'] ?? '') : '',
        ]);

        return $html !== '' ? $html : null;
    }
*/
    // ─────────────────────────────────────────────────────────────
    // STATIC METHODS
    // ─────────────────────────────────────────────────────────────

    public static function subscribe(int $idProduct, string $email, float $targetPrice, ?int $idUser = null): array
    {
        $idProduct = (int) $idProduct;
        $email = trim(strtolower($email));
        $targetPrice = (float) $targetPrice;

        if ($idProduct <= 0) {
            return ['success' => false, 'message' => translate('Invalid Product')];
        }

        $product = Product::getById($idProduct);

        if (!$product) {
            return ['success' => false, 'message' => translate('Product Not Found')];
        }

        if (!Validate::isEmail($email)) {
            return ['success' => false, 'message' => translate('Invalid E-Mail')];
        }

        if ($targetPrice <= 0) {
            return ['success' => false, 'message' => translate('The price must be greater than 0')];
        }

        $currentPrice = (float) ($product['price'] ?? 0);

        if ($targetPrice >= $currentPrice) {
            return ['success' => false, 'message' => translate('The price must be lower than the current price.')];
        }

        $existing = DB::getRowSafe('alert_price_subscriptions', 'id_product = ? AND email = ? AND is_sent = 0', [
            $idProduct,
            $email,
        ]);

        if ($existing) {
            return ['success' => false, 'message' => translate('You have a pending request.')];
        }

        $productUrl = $product['url'] ?? Product::getLink($product);

        $id = DB::insert('alert_price_subscriptions', [
            'id_product' => $idProduct,
            'id_user' => $idUser ?? 0,
            'email' => $email,
            'product_name' => mb_substr($product['product_name'] ?? '', 0, 255),
            'product_url' => $productUrl,
            'target_price' => $targetPrice,
            'current_price_at_subscribe' => $currentPrice,
            'is_sent' => 0,
            'date_add' => date('Y-m-d H:i:s'),
        ]);

        if (!$id) {
            return ['success' => false, 'message' => translate('Data could not be added.')];
        }

        return [
            'success' => true,
            'message' => translate('Your request has been received.'),
        ];
    }

    public static function processCron(string $token): array
    {
        $storedToken = Settings::get('ALERT_PRICE_CRON_TOKEN');

        if ($storedToken === '' || $token !== $storedToken) {
            return ['success' => false, 'message' => 'Geçersiz token'];
        }

        if (!(int) Settings::get('ALERT_PRICE_CRON_ENABLED')) {
            return ['success' => false, 'message' => 'Cron işlemi devre dışı'];
        }

        $subscriptions = DB::execute(
            "SELECT * FROM alert_price_subscriptions 
             WHERE is_sent = 0 
             ORDER BY id_subscription ASC"
        ) ?: [];

        $sentCount = 0;
        $errors = [];

        foreach ($subscriptions as $sub) {
            $product = Product::getById((int) $sub['id_product']);

            if (!$product) {
                continue;
            }

            $currentPrice = (float) ($product['price'] ?? 0);
            $targetPrice = (float) $sub['target_price'];

            if ($currentPrice <= $targetPrice) {
                $sent = self::sendNotificationEmail($sub, $product, $currentPrice);

                if ($sent) {
                    DB::update(
                        'alert_price_subscriptions',
                        [
                            'is_sent' => 1,
                            'sent_at' => date('Y-m-d H:i:s'),
                            'price_when_sent' => $currentPrice,
                        ],
                        'id_subscription = :id',
                        ['id' => (int) $sub['id_subscription']]
                    );
                    $sentCount++;
                } else {
                    $errors[] = 'E-posta gönderilemedi: ' . $sub['email'];
                }
            }
        }

        return [
            'success' => true,
            'processed' => count($subscriptions),
            'sent' => $sentCount,
            'errors' => $errors,
        ];
    }

    private static function sendNotificationEmail(array $subscription, array $product, float $currentPrice): bool
    {
        $productName = $subscription['product_name'] ?: ($product['product_name'] ?? 'Ürün');
        $productUrl = $subscription['product_url'];

        $targetPriceFormatted = Tools::displayPrice((float) $subscription['target_price']);
        $currentPriceFormatted = Tools::displayPrice($currentPrice);

        $bodyHtml = "
            <h2>Fiyat Düştü!</h2>
            <p>Takip ettiğiniz <strong>" . htmlspecialchars($productName) . "</strong> ürününün fiyatı istediğiniz seviyeye düştü.</p>
            <table style='border-collapse: collapse; width: 100%; max-width: 500px;'>
                <tr><td style='padding: 8px; background: #f5f5f5;'><strong>Hedef fiyatınız:</strong></td><td style='padding: 8px;'>{$targetPriceFormatted}</td></tr>
                <tr><td style='padding: 8px; background: #f5f5f5;'><strong>Güncel fiyat:</strong></td><td style='padding: 8px;'>{$currentPriceFormatted}</td></tr>
            </table>
            <p style='margin-top: 20px;'>
                <a href='{$productUrl}' style='background: #28a745; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                    Ürünü İncele
                </a>
            </p>
            <hr>
            <small>Bu e-posta size " . htmlspecialchars(rtrim(Settings::get('DOMAIN'), '/'), ENT_QUOTES, 'UTF-8') . " üzerindeki fiyat alarm talebiniz nedeniyle gönderilmiştir.</small>
        ";

        return Mail::send(
            $subscription['email'],
            '💰 ' . $productName . ' fiyatı düştü!',
            $bodyHtml
        );
    }

    public static function getSubscriptionsForAdmin(int $limit, int $offset, string $filter = 'all'): array
    {
        $sql = "SELECT s.*, p.product_name as current_product_name
                FROM alert_price_subscriptions s
                LEFT JOIN products p ON p.id_product = s.id_product
                WHERE 1=1";
        $params = [];

        if ($filter === 'pending') {
            $sql .= " AND s.is_sent = 0";
        } elseif ($filter === 'sent') {
            $sql .= " AND s.is_sent = 1";
        }

        $sql .= " ORDER BY s.id_subscription DESC LIMIT " . (int) $limit . " OFFSET " . (int) $offset;

        $rows = DB::execute($sql, $params) ?: [];

        return array_map(static function ($row) {
            $row['date_formatted'] = Tools::formatDate3($row['date_add']);
            $row['target_price_formatted'] = Tools::displayPrice((float) $row['target_price']);
            $row['sent_at_formatted'] = $row['sent_at'] ? Tools::formatDate3($row['sent_at']) : '-';
            return $row;
        }, $rows);
    }

    public static function countSubscriptions(string $filter = 'all'): int
    {
        $sql = "SELECT COUNT(*) FROM alert_price_subscriptions WHERE 1=1";

        if ($filter === 'pending') {
            $sql .= " AND is_sent = 0";
        } elseif ($filter === 'sent') {
            $sql .= " AND is_sent = 1";
        }

        return (int) DB::getValue($sql);
    }

    public static function deleteOldSubscriptions(int $daysOld = 90): int
    {
        $deleted = DB::execute(
            "DELETE FROM alert_price_subscriptions 
             WHERE is_sent = 1 AND sent_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$daysOld]
        );

        return $deleted !== false ? $deleted : 0;
    }
}