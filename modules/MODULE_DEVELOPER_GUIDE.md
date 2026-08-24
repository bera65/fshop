# FShop Modül Geliştirici Rehberi

Bu belge, FShop e-ticaret projesinde **sıfırdan modül yazmak** için gereken tüm kuralları, sabitleri, dosya yapısını ve kod kalıplarını içerir. Bir yapay zekâya veya geliştiriciye bu dosyayı vererek doğrudan çalışan modül üretmesini sağlayabilirsiniz.

**Hedef ortam:** PHP 7.4+ · MySQL · Smarty 5 · Vanilla JS (jQuery mevcut)

---

## 1. Modül nedir?

Modül, `modules/{ad}/` altında yaşayan bağımsız bir PHP sınıfıdır. Şunları yapabilir:

- Admin panelinde yapılandırma ekranı
- Mağaza sayfalarına HTML parçası (display hook)
- REST benzeri JSON API endpoint
- Özel mağaza sayfası (front route)
- Ödeme yöntemi (checkout entegrasyonu)
- Veritabanı tablosu (kurulum/kaldırma SQL)

Modüller **tema dosyalarına dokunmadan** çalışır. Tüm şablonlar modül klasöründe kalır.

---

## 2. Zorunlu dosya yapısı

```
modules/{ad}/
  {ad}.php                          ← Ana sınıf (ZORUNLU)
  logo.png                          ← Admin modül listesinde ikon (önerilir)
  install.sql                       ← Kurulum SQL (varsa)
  uninstall.sql                     ← Kaldırma SQL (varsa)
  assets/
    templates/
      admin/
        admin.tpl                   ← Yapılandırma arayüzü
      front/
        {hook_adı}.tpl              ← Mağaza hook çıktıları
    css/
      front.css                     ← Mağaza stilleri
      admin.css                     ← Admin stilleri
    js/
      front.js                      ← Mağaza scriptleri
      admin.js                      ← Admin scriptleri
    img/                            ← Görseller (isteğe bağlı)
  api/
    {action}.php                    ← API endpoint dosyaları
  front/
    {sayfa}.php                     ← Özel mağaza route dosyası
```

### İsimlendirme kuralları (KRİTİK)

| Öğe | Kural | Örnek |
|-----|-------|-------|
| Klasör adı | Küçük harf, tire ile | `product-badge`, `my-module` |
| Ana dosya | `{ad}/{ad}.php` | `modules/my-module/my-module.php` |
| PHP sınıfı | Tireler kaldırılır, PascalCase + `Module` | `my-module` → `MyModuleModule` |
| Admin URL | Otomatik: `/admin/module-{ad}` | `/admin/module-my-module` |
| API URL | `/api/module.php?m={ad}&action={action}` | `?m=reviews&action=submit` |
| Asset URL | `/modules/{ad}/assets/css/...` | Otomatik |

**Sınıf adı üretim algoritması** (`core/Module.php`):

```php
// "product-badge" → explode('-') → ['product','badge'] → ucfirst → ProductBadge → ProductBadgeModule
```

---

## 3. Ana sınıf iskeleti (minimum çalışan modül)

```php
<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class OrnekModule extends ModuleBase
{
	// ── Kimlik bilgileri (ZORUNLU) ──────────────────────────────
	public string $name = 'ornek';           // klasör adı ile AYNI olmalı
	public string $title = 'Örnek Modül';
	public string $version = '1.0.0';
	public string $description = 'Kısa açıklama';
	public string $author = 'FShop';

	// ── Display hook tanımları ──────────────────────────────────
	public array $displayHooks = [
		'footer' => 'Footer alanına HTML ekler',
	];
	public array $defaultDisplayHooks = ['footer'];

	// ── CSS/JS (boş bırakılırsa klasördeki tüm dosyalar yüklenir) ─
	public array $frontStylesheets = ['front.css'];
	public array $frontScripts = [];
	public array $adminStylesheets = ['admin.css'];
	public array $adminScripts = [];

	// ── API endpoint haritası ────────────────────────────────────
	public array $apiActions = [
		'save' => 'api/save.php',
	];

	// ── Özel mağaza sayfası route'ları ─────────────────────────
	// slug => modül içi dosya yolu
	public array $routes = [
		// 'ornek-sayfa' => 'front/page.php',
	];

	// ── ZORUNLU: kurulum / kaldırma ─────────────────────────────
	public function install(): bool
	{
		return $this->runSqlFile('install.sql');
		// Tablo yoksa: return true;
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	// ── İsteğe bağlı: modül yüklendiğinde ───────────────────────
	public function boot(): void
	{
		// Module::registerHook('order.placed', function ($order) { ... });
	}

	// ── Admin yapılandırma ekranı ───────────────────────────────
	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';

		if (Tools::isSubmit('saveOrnek')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				Settings::set('ORNEK_AYAR', trim((string) Tools::getValue('ayar')));
				$flash = 'Kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		$smarty->assign([
			'ornekAyar' => Settings::get('ORNEK_AYAR'),
			'flash' => $flash,
		]);
	}

	// ── Mağaza hook çıktısı ─────────────────────────────────────
	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'footer') {
			return null;
		}

		$html = $this->renderFrontTemplate('footer', [
			'ornekMesaj' => Settings::get('ORNEK_AYAR'),
		]);

		return $html !== '' ? $html : null;
	}
}
```

---

## 4. ModuleBase — tüm özellikler ve sabitler

`ModuleBase` soyut sınıfı (`core/ModuleBase.php`) tüm modüllerin atasıdır.

### 4.1 Kimlik özellikleri

```php
public string $name = '';          // Benzersiz slug (klasör adı)
public string $title = '';         // Admin'de görünen başlık
public string $version = '1.0.0';
public string $description = '';
public string $author = 'FShop';
```

### 4.2 Route ve API

```php
/** Mağaza özel sayfa: site.com/{slug} → modules/{ad}/{dosya} */
public array $routes = [
	'odeme-karti' => 'front/payment.php',
];

/** API: /api/module.php?m={name}&action={key} */
public array $apiActions = [
	'submit' => 'api/submit.php',
];
```

### 4.3 Display hook

```php
/** Modülün desteklediği hook'lar: anahtar => açıklama */
public array $displayHooks = [
	'footer' => 'Footer alanı',
];

/** Kurulumda otomatik atanacak hook'lar */
public array $defaultDisplayHooks = ['footer'];
```

### 4.4 CSS / JS

```php
public array $frontStylesheets = ['front.css'];  // Mağaza header'da
public array $frontScripts = ['front.js'];       // Mağaza footer'da
public array $adminStylesheets = ['admin.css'];  // Sadece /admin/module-{ad}
public array $adminScripts = ['admin.js'];
```

Diziler **boş** bırakılırsa `assets/css/` veya `assets/js/` altındaki **tüm** `.css` / `.js` dosyaları otomatik yüklenir.

### 4.5 Ödeme modülü özellikleri

```php
public bool $isPayment = false;              // true → checkout'ta ödeme yöntemi
public string $paymentMethodId = '';         // orders.payment_method değeri (ör. bank_transfer)
public string $paymentMethodLabel = '';      // Müşteriye görünen etiket
public bool $paysBeforeOrder = false;        // true → sipariş öncesi ödeme (sanal POS)
```

### 4.6 Geriye dönük uyumluluk (kullanmayın, yeni modüllerde gerek yok)

```php
public array $adminPages = [];
public array $adminRoutes = [];
public array $positions = [];      // displayHooks yerine eski ad
public array $hooksMeta = [];      // Dokümantasyon
```

### 4.7 Soyut metodlar (ZORUNLU implement)

```php
abstract public function install(): bool;
abstract public function uninstall(): bool;
```

### 4.8 Override edilebilir metodlar

| Metod | Ne zaman çağrılır |
|-------|-------------------|
| `boot()` | Aktif modül yüklenirken (her istek) |
| `adminPage()` | `/admin/module-{ad}` açılınca |
| `renderDisplayHook($hook, $context)` | Hook render edilirken |
| `getPaymentPageUrl()` | `paysBeforeOrder = true` ise ödeme sayfası URL |
| `processPayment($order)` | Sipariş kaydedildikten sonra ödeme işlemi |

### 4.9 Yardımcı metodlar

```php
$this->getPath()                          // modules/{ad}/ mutlak yol
$this->getUrl('logo.png')                 // https://site.com/modules/{ad}/logo.png
$this->getAssetUrl('css/front.css')      // Asset tam URL
$this->getAdminSlug()                     // module-{ad}
$this->getAdminPageTitle()                // {title} — Yapılandır
$this->runSqlFile('install.sql')          // SQL dosyasını çalıştır
$this->renderFrontTemplate('footer', []) // Modül front şablonu render
$this->hasAdminTemplate()                 // admin.tpl var mı?
$this->hasFrontTemplate('footer')        // front şablonu var mı?
```

---

## 5. Display hook kataloğu

Şablonda `{$hooks.{ad}}` ile kullanılır. Modül admin detay sayfasından hangi hook'lara bağlanacağı seçilir.

| Hook anahtarı | Şablonda | Ne zaman render | Bağlam (`$context`) |
|---------------|----------|-----------------|---------------------|
| `footer` | `{$hooks.footer}` | Sayfa yüklenince | — |
| `header` | `{$hooks.header}` | Sayfa yüklenince | — |
| `home` | `{$hooks.home}` | Ana sayfa | — |
| `home_slider` | `{$hooks.home_slider}` | Ana sayfa header | — |
| `home_promo_slider` | `{$hooks.home_promo_slider}` | Ana sayfa | — |
| `product` | `{$hooks.product}` | Ürün sayfası | `id_product` |
| `product_detail` | `{$hooks.product_detail}` | Ürün sayfası | `id_product` |
| `product_tab` | `{$hooks.product_tab}` | Ürün sekmesi butonu | `id_product` |
| `product_tab_content` | `{$hooks.product_tab_content}` | Ürün sekme içeriği | `id_product` |
| `product_inf` | `{$hooks.product_inf}` | Ürün başlık altı | `id_product` |
| `order_payment` | `{$hooks.order_payment}` | Checkout | `cart` |
| `order_confirmation` | `{$hooks.order_confirmation}` | Sipariş onay | `order` |

### Admin display hook'lar (`{$adminHooks.*}`)

Şablon: `assets/templates/admin/{hook}.tpl` — `renderAdminDisplayHook()` / varsayılan `ModuleBase` davranışı.

| Hook | Şablonda | Konum |
|------|----------|--------|
| `admin_header` | `{$adminHooks.admin_header}` | Tüm admin sayfaları — orta alan üstü |
| `admin_footer` | `{$adminHooks.admin_footer}` | Tüm admin sayfaları — footer |
| `admin_product_button` | `{$adminHooks.admin_product_button}` | Ürün düzenleme |
| `admin_order_detail` | `{$adminHooks.admin_order_detail}` | Sipariş detay |
| `admin_dashboard_*` | `{$adminHooks.admin_dashboard_*}` | Gösterge paneli |

```php
public array $displayHooks = [
	'admin_header' => 'Admin orta alan üstü',
	'admin_footer' => 'Admin footer',
];
public array $defaultDisplayHooks = ['admin_header', 'admin_footer'];

public function renderAdminDisplayHook(string $hook, array $context = []): ?string
{
	if ($hook === 'admin_header') {
		return $this->renderAdminTemplate('admin_header', $context) ?: null;
	}
	if ($hook === 'admin_footer') {
		return $this->renderAdminTemplate('admin_footer', $context) ?: null;
	}
	return null;
}
```

### Erteleme (deferred) hook'lar

`product`, `product_tab`, `product_tab_content`, `product_inf`, `order_payment`, `order_confirmation` hook'ları sayfa yüklenirken **boş** gelir. İlgili controller `Module::refreshHook()` çağırır:

```php
// container/front/product.php
Module::refreshHook($smarty, 'product_tab', ['id_product' => $idProduct]);
Module::refreshHook($smarty, 'product_tab_content', ['id_product' => $idProduct]);

// container/front/checkout.php
Module::refreshHook($smarty, 'order_payment', ['cart' => $cart]);

// container/front/checkout-success.php
Module::refreshHook($smarty, 'order_confirmation', ['order' => $order]);
```

Yeni bir ürün hook'u ekliyorsanız hem `Module::getDisplayHookCatalog()` hem ilgili controller'da `refreshHook` gerekir.

### Ürün sekmesi kalıbı

**product_tab.tpl** — sekme butonu:

```smarty
<li class="nav-item" role="presentation">
	<button class="nav-link" data-bs-toggle="tab" data-bs-target="#my-tab" type="button">
		Sekme Başlığı
	</button>
</li>
```

**product_tab_content.tpl** — sekme içeriği:

```smarty
<div class="tab-pane fade" id="my-tab">
	<!-- içerik -->
</div>
```

`data-bs-target` ile `id` eşleşmeli.

---

## 6. renderFrontTemplate — ÖNEMLİ KURAL

```php
$html = $this->renderFrontTemplate('footer', [
	'degisken' => 'değer',
]);
```

- Şablon yolu: `assets/templates/front/{template}.tpl`
- Değişkenler **izole alt şablonda** render edilir; global Smarty değişkenlerini (`isLoggedIn`, `customer`, `cart` vb.) **bozmaz**
- `isLoggedIn` gibi isimleri modül değişkeni olarak kullanabilirsiniz; header etkilenmez
- Çıktı HTML string döner; şablonda `{$hooks.footer nofilter}` ile basılır

---

## 7. Admin yapılandırma ekranı

### Akış

1. Admin → Modüller → **Kur**
2. **Yapılandır** → `/admin/module-{ad}`
3. `adminPage()` çalışır → Smarty değişkenleri atanır
4. `assets/templates/admin/admin.tpl` render edilir

### Admin şablonunda kullanılabilir global değişkenler

| Değişken | Açıklama |
|----------|----------|
| `$adminToken` | Form CSRF token (POST formlarında zorunlu) |
| `$domain` | Site kök URL |
| `$moduleName` | Modül slug |
| `$moduleConfigUrl` | Yapılandırma URL |
| `$moduleDetailUrl` | Modül detay URL |
| `$moduleAdminAssets` | `['css'=>[], 'js'=>[]]` |

### Admin form güvenliği (ZORUNLU)

```php
if (Tools::isSubmit('saveSomething')) {
	$postToken = (string) Tools::getValue('token');

	if (hash_equals($adminToken, $postToken)) {
		// işlem
	} else {
		$flash = 'Geçersiz istek';
	}
}
```

```smarty
<form method="post">
	<input type="hidden" name="saveSomething" value="1">
	<input type="hidden" name="token" value="{$adminToken}">
	<!-- alanlar -->
</form>
```

### Admin URL notu

- Doğru: `/admin/module-slider?group=promo`
- Yanlış: `/admin/module?name=slider&group=promo` (eski link; otomatik yönlendirilir)

---

## 8. API endpoint yazımı

### Kayıt

```php
public array $apiActions = [
	'submit' => 'api/submit.php',
];
```

### URL

```
POST /api/module.php?m={modul_adı}&action={action}
```

### api/submit.php kalıbı

```php
<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Method not allowed']);
	exit;
}

// Mağaza CSRF token
$token = Tools::getValue('token') ?: ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) $token)) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
	exit;
}

// İş mantığı — static metod veya doğrudan DB
$result = OrnekModule::doSomething(
	(string) Tools::getValue('field')
);

echo json_encode($result);
```

### JSON yanıt formatı

```php
// Başarı
['success' => true, 'message' => 'İşlem tamam']

// Hata
['success' => false, 'message' => 'Hata açıklaması']
```

### Frontend'den çağrı

```javascript
// header.tpl'de tanımlı: csrfToken, domain
fetch(domain + 'api/module.php?m=ornek&action=save', {
	method: 'POST',
	headers: {
		'Content-Type': 'application/x-www-form-urlencoded',
		'X-CSRF-Token': csrfToken
	},
	body: new URLSearchParams({ token: csrfToken, field: 'değer' })
})
.then(r => r.json())
.then(data => { /* ... */ });
```

Modül aktif değilse API **404** döner.

### CSRF token — sık yapılan hata (KRİTİK)

Mağaza API'leri `$_SESSION['csrf_token']` ile doğrular. Bu token **header.tpl** içinde JS değişkeni olarak tanımlıdır:

```javascript
// templates/{tema}/header.tpl — her mağaza sayfasında mevcut
var csrfToken = "{$token}";
```

| Doğru | Yanlış |
|-------|--------|
| `token: csrfToken` (global JS değişkeni) | `Settings::get('ALERT_PRICE_CRON_TOKEN')` veya başka modül token'ı |
| PHP'de `global $token` → Smarty'ye atayıp `{$token}` | Smarty şablonunda `{Settings::get(...)}` (PHP çağrısı çalışmaz) |
| Admin formlarında `{$adminToken}` | Mağaza API'sinde `$adminToken` kullanmak |

**Cron token ≠ CSRF token.** Cron URL'sindeki `?token=` sadece modülün `api/cron.php` endpoint'i içindir; müşteri API isteklerinde **asla** kullanılmaz.

### Cron / zamanlanmış görev endpoint'i

`modules/.htaccess` **tüm `.php` dosyalarına doğrudan HTTP erişimini engeller** (`Require all denied`). Bu yüzden şu URL **her zaman Forbidden (403)** verir:

```
/modules/alert-price/cron.php?token=...   ← YANLIŞ, çalışmaz
```

Cron işlemleri `apiActions` ile tanımlanmalıdır:

```php
public array $apiActions = [
	'subscribe' => 'api/subscribe.php',
	'cron' => 'api/cron.php',
];
```

**api/cron.php** — CSRF kontrolü **yok**; kendi gizli token'ınızı doğrulayın:

```php
<?php
if (!defined('IN_SCRIPT')) { exit; }
header('Content-Type: application/json; charset=utf-8');

$token = trim((string) Tools::getValue('token', ''));
if ($token === '') {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => 'Token gerekli']);
	exit;
}

$result = OrnekModule::processCron($token);
echo json_encode($result);
```

Doğru cron URL:

```
GET /api/module.php?m=alert-price&action=cron&token={CRON_TOKEN}
```

Örnek curl:

```bash
curl -s "http://localhost/fshop/api/module.php?m=alert-price&action=cron&token=TOKEN"
```

Admin panelinde gösterilen cron URL'si de bu formatta olmalıdır (`Settings` içindeki `ALERT_PRICE_CRON_TOKEN`).

API 403 + `"Geçersiz istek"` alıyorsanız ilk kontrol: frontend `token` alanı `csrfToken` mi?

### Mağaza AJAX kalıbı (newsletter / alert-price ile aynı)

Hook şablonuna **inline `<script>` yazmayın**; `assets/js/` dosyasında jQuery event delegation kullanın:

```smarty
<form class="ornek-form" data-api-url="{$api_url|escape}" method="post" action="#">
```

```javascript
$(document).on('submit', '.ornek-form', function (e) {
	e.preventDefault();
	var $form = $(this);
	$.ajax({
		url: $form.data('api-url'),
		method: 'POST',
		dataType: 'json',
		data: {
			token: typeof csrfToken !== 'undefined' ? csrfToken : '',
			email: $.trim($form.find('[name="email"]').val())
		}
	});
});
```

`csrfToken` header'da yüklendiği için ayrıca şablona token yazmaya gerek yoktur.

### Settings ve domain — sık yapılan hata

| Doğru | Yanlış |
|-------|--------|
| `Settings::get('DOMAIN')` | `Settings::get('domain')` (küçük harf — boş döner) |
| `global $domain` (config/settings.php'den) | URL'yi elle `/urun/slug` ile uydurmak |
| `$product['url']` veya `Product::getLink($product)` | `$product['slug']` (products tablosunda yok) |

Ürün URL formatı `.htaccess` ile belirlenir: `{kategori}/{marka}/{id}-{link}` — `Product::getById()` zaten `url` alanını `enrich()` ile ekler.

---

## 9. Özel mağaza sayfası (front route)

```php
public array $routes = [
	'ornek-sayfa' => 'front/page.php',
];
```

`index.php` container bulamazsa `Module::resolveFrontRoute($container)` dener.

**front/page.php** kalıbı:

```php
<?php
if (!defined('IN_SCRIPT')) {
	exit;
}

// $smarty, $domain, $cart, $customer, $isLoggedIn, $token zaten yüklü

$pageTitle = 'Örnek Sayfa';
$css = false;
$js = false;

$smarty->assign([
	'ornekData' => 'değer',
]);

// $skipPageRender = true;  // 404 gibi özel durumlarda Page::add atlanır
```

Route dosyası include edildikten sonra `index.php` normal akışla `$page->add($container, ...)` çağırır. `$container` route slug'ıdır; tema şablonu `templates/{tema}/{slug}.tpl` olmalı **veya** `$noLayout = true` ile özel render yapılmalı.

Ödeme sayfası örneği: `modules/sanalpos/front/payment.php` — checkout verisini işler, kart formu gösterir.

---

## 10. Ödeme modülü

### Tip A — Sipariş sonrası ödeme (havale, kapıda ödeme)

```php
public bool $isPayment = true;
public string $paymentMethodId = 'bank_transfer';
public string $paymentMethodLabel = 'Havale / EFT';
public bool $paysBeforeOrder = false;
```

**order_payment.tpl** — checkout'ta radio:

```smarty
<div class="payment-option mb-2">
	<label class="d-flex gap-2 align-items-start border rounded p-3 w-100">
		<input type="radio" name="payment_method" value="bank_transfer"{if $formData.payment_method == 'bank_transfer'} checked{/if}>
		<span>
			<strong>Havale / EFT</strong>
			<small class="d-block text-muted">Açıklama</small>
		</span>
	</label>
</div>
```

`value` = `$paymentMethodId` ile **aynı** olmalı.

```php
public function processPayment(array $order): array
{
	return [
		'success' => true,
		'redirect' => '',      // dolu ise müşteri buraya gider
		'message' => '',
	];
}
```

### Tip B — Sipariş öncesi ödeme (sanal POS)

```php
public bool $paysBeforeOrder = true;

public function getPaymentPageUrl(): string
{
	global $domain;
	return $domain . 'odeme-karti';
}

public array $routes = [
	'odeme-karti' => 'front/payment.php',
];
```

Akış:
1. Checkout → `Order::place()` ödeme modülünü görür
2. `paysBeforeOrder = true` → sipariş oluşturulmaz, veri session'da bekler
3. Müşteri `getPaymentPageUrl()` adresine yönlendirilir
4. Ödeme onaylanınca `Order::placePending()` siparişi oluşturur

Yardımcılar: `Order::hasPendingPayment()`, `Order::clearPendingPayment()`

---

## 11. Veritabanı

### install.sql

```sql
CREATE TABLE IF NOT EXISTS `ornek_items` (
  `id_item` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### uninstall.sql

```sql
DROP TABLE IF EXISTS `ornek_items`;
```

### SQL çalıştırma

```php
public function install(): bool
{
	return $this->runSqlFile('install.sql');
}
```

### DB erişim kalıpları

```php
DB::getRowSafe('tablo', 'id = ?', [$id]);
DB::getValue('SELECT COUNT(*) FROM tablo WHERE active = 1');
DB::insert('tablo', ['kolon' => 'değer']);          // son insert id
DB::update('tablo', ['kolon' => 'v'], 'id = :where_id', ['where_id' => $id]);
DB::execute('DELETE FROM tablo WHERE id = ?', [$id]);
DB::getRow('tablo', 'id = 1', 'kolon');             // kısa syntax
```

### Ayar saklama (Settings)

```php
Settings::get('ORNEK_AYAR');           // string, yoksa ''
Settings::set('ORNEK_AYAR', 'değer');  // kalıcı ayar
```

Modüle özel ayar anahtarları **BÜYÜK HARF + modül prefix** kullanın: `SLIDER_PROMO_TITLE`, `BANKWIRE_IBAN`

---

## 12. Dahili hook'lar (registerHook)

`boot()` içinde:

```php
public function boot(): void
{
	Module::registerHook('smarty.assign', function ($smarty) {
		if ($smarty) {
			$smarty->assign('ornekGlobal', 'değer');
		}
	});

	Module::registerHook('head.assets', function (&$assets) {
		$assets['css'][] = 'https://cdn.example.com/extra.css';
	});

	Module::registerHook('order.placed', function ($order) {
		// Sipariş oluşturulduktan sonra
	});
}
```

| Hook | Açıklama |
|------|----------|
| `smarty.assign` | Tüm mağaza şablonlarına ek değişken |
| `head.assets` | CSS/JS dizilerine ekleme (`&$assets`) |
| `footer.html` | Eski yöntem (display hook tercih edin) |
| `admin.menu` | Admin sol menüsüne link ekler (`registerAdminMenuLink()` veya `registerHook`) |

### Admin sol menüsü

Modül yapılandırma sayfasını sol menüde göstermek için `boot()` içinde:

```php
public function boot(): void
{
	$this->registerAdminMenuLink('Blog', 'catalog', 95);
}
```

| Parametre | Açıklama |
|-----------|----------|
| `$label` | Menü metni (İngilizce anahtar → `adminT`) |
| `$group` | `general`, `catalog` veya `system` |
| `$position` | Grup içi sıra (küçük = üstte) |

Manuel hook:

```php
Module::registerHook('admin.menu', function (array &$items): void {
	$items[] = [
		'label' => 'Blog',
		'url' => Admin::url('module-blog'),
		'slug' => 'module-blog',
		'group' => 'catalog',
		'position' => 95,
		'badge' => 0,
	];
});
```
| `order.placed` | Sipariş sonrası |

---

## 13. Mağazada kullanılabilir global değişkenler

`config/settings.php` tüm sayfalara atar:

```php
$domain, $cart, $customer, $isLoggedIn, $token (csrf),
$menuCategories, $favoriteCount, $notificationCount,
$hooks (display hook HTML'leri), $moduleAssets (css/js URL'leri)
```

Modül hook şablonlarında da ana şablon değişkenlerine erişilebilir (parent Smarty).

---

## 14. Güvenlik kontrol listesi

- [ ] Dosya başında `IN_SCRIPT` / `IN_ADMIN` guard
- [ ] Admin formlarında `$adminToken` + `hash_equals`
- [ ] Mağaza API'de `$_SESSION['csrf_token']` doğrulama
- [ ] Kullanıcı girdisi: `trim`, `escape` (Smarty), `strip_tags` (HTML istemiyorsanız)
- [ ] SQL: prepared statements (`DB::execute($sql, [$param])`)
- [ ] Dosya yükleme: `is_uploaded_file`, `getimagesize`, uzantı kontrolü
- [ ] `isLoggedIn` kontrolü (gerekli endpoint'lerde)

---

## 15. PHP 7.4 uyumluluk

Kullanmayın (PHP 8+):

- `str_starts_with()` → `strpos($s, $n) === 0`
- `str_ends_with()` → `substr($s, -strlen($n)) === $n`
- `match` ifadesi → `switch`
- Named arguments, union types, constructor property promotion

---

## 16. Kurulum sonrası

1. `modules/{ad}/` klasörünü oluştur
2. Admin → Modüller → modül görünür → **Kur**
3. `defaultDisplayHooks` otomatik atanır
4. Gerekirse modül detayından hook'ları düzenle
5. **Etkinleştir** (kurulumda varsayılan aktif)

Programatik:

```php
Module::install('ornek');
Module::setActive('ornek', true);
Module::setDisplayHooks('ornek', ['footer']);
```

---

## 17. Tam örnek: "Ücretsiz Kargo Bandı" modülü

Aşağıdaki prompt'u bir yapay zekâya vererek modül ürettirebilirsiniz.

### İstenen özellikler

- Ad: `shipping-banner`
- Ürün sayfasında `product_inf` hook'unda "X TL üzeri kargo bedava" bandı
- Admin'de eşik tutarı ayarı
- Veritabanı gerekmez (Settings kullan)

### modules/shipping-banner/shipping-banner.php

```php
<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class ShippingBannerModule extends ModuleBase
{
	public string $name = 'shipping-banner';
	public string $title = 'Kargo Bandı';
	public string $version = '1.0.0';
	public string $description = 'Ürün sayfasında ücretsiz kargo bilgisi';
	public string $author = 'FShop';

	public array $displayHooks = [
		'product_inf' => 'Ürün başlık altı bilgi bandı',
	];
	public array $defaultDisplayHooks = ['product_inf'];

	public array $frontStylesheets = ['banner.css'];

	public function install(): bool
	{
		if (Settings::get('SHIPPING_BANNER_MIN') === '') {
			Settings::set('SHIPPING_BANNER_MIN', Settings::get('FREE_SHIPPING_MIN') ?: '500');
		}
		return true;
	}

	public function uninstall(): bool
	{
		return true;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;
		$flash = '';

		if (Tools::isSubmit('saveBanner')) {
			$postToken = (string) Tools::getValue('token');
			if (hash_equals($adminToken, $postToken)) {
				Settings::set('SHIPPING_BANNER_MIN', (string) max(0, (int) Tools::getValue('min_amount')));
				$flash = 'Kaydedildi';
			}
		}

		$smarty->assign([
			'minAmount' => Settings::get('SHIPPING_BANNER_MIN'),
			'flash' => $flash,
		]);
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'product_inf') {
			return null;
		}

		$min = (float) Settings::get('SHIPPING_BANNER_MIN');
		$html = $this->renderFrontTemplate('product_inf', [
			'minAmount' => $min,
			'minFormatted' => Tools::displayPrice($min),
		]);

		return $html !== '' ? $html : null;
	}
}
```

### assets/templates/admin/admin.tpl

```smarty
{if $flash}<div class="alert alert-info">{$flash|escape}</div>{/if}
<form method="post" class="admin-panel p-3" style="max-width:400px">
	<input type="hidden" name="saveBanner" value="1">
	<input type="hidden" name="token" value="{$adminToken}">
	<label class="form-label">Ücretsiz kargo eşiği (TL)</label>
	<input type="number" name="min_amount" class="form-control" value="{$minAmount|escape}" min="0">
	<button type="submit" class="btn btn-dark mt-3">Kaydet</button>
</form>
```

### assets/templates/front/product_inf.tpl

```smarty
<div class="shipping-banner mt-2">
	<small class="text-success">{$minFormatted|escape} ve üzeri siparişlerde kargo bedava</small>
</div>
```

### assets/css/banner.css

```css
.shipping-banner { padding: .35rem .75rem; background: #f0fdf4; border-radius: 4px; }
```

---

## 18. Yapay zekâ için modül üretim prompt şablonu

Aşağıdaki metni kopyalayıp doldurun:

```
FShop e-ticaret projesi için modül yaz.

Kurallar: modules/MODULE_DEVELOPER_GUIDE.md dosyasındaki tüm kurallara uy.
PHP 7.4 uyumlu ol. ModuleBase extend et. Tema dosyasına dokunma.

Modül bilgileri:
- name (slug): {ornek-modul}
- title: {Görünen Ad}
- description: {ne yapar}

Özellikler:
- Display hook'lar: {footer, product_tab, ...}
- API action'lar: {action => açıklama}
- Ödeme modülü mü: {evet/hayır}
- Veritabanı tabloları: {varsa şema}
- Admin ayarları: {Settings anahtarları veya tablo alanları}

Üretilecek dosyalar:
1. modules/{ad}/{ad}.php
2. install.sql / uninstall.sql (gerekirse)
3. assets/templates/admin/admin.tpl
4. assets/templates/front/{hook}.tpl (her hook için)
5. assets/css/, assets/js/ (gerekirse)
6. api/{action}.php (gerekirse)
7. front/{sayfa}.php (route varsa)

Mevcut modül örnekleri: whatsapp (basit), newsletter (API), reviews (ürün sekmesi), bankwire (ödeme), slider (admin CRUD).
```

---

## 19. Hata ayıklama

| Sorun | Çözüm |
|-------|-------|
| Modül listede görünmüyor | Klasör adı = dosya adı = `$name`; sınıf adı doğru mu? |
| Kur butonu hata veriyor | `install.sql` syntax; `install()` false dönüyor mu? |
| Hook çıkmıyor | Modül kurulu ve aktif mi? Hook atanmış mı? (Admin → modül detay) |
| Admin 404 | URL `/admin/module-{ad}` olmalı |
| API 404 | Modül aktif mi? `apiActions` doğru mu? |
| **API 403 "Geçersiz istek"** | **Frontend `csrfToken` gönderiyor mu? Cron/admin token karıştırılmış olabilir** |
| **Cron URL Forbidden (403)** | **`/modules/.../cron.php` doğrudan erişilemez; `/api/module.php?m=...&action=cron&token=` kullanın** |
| CSS yüklenmiyor | Dosya `assets/css/` altında mı? `$frontStylesheets` doğru mu? |
| Ödeme seçeneği yok | `$isPayment = true`, `$paymentMethodId` dolu, hook `order_payment` atanmış mı? |
| AJAX "Bir hata oluştu" + boş yanıt | Smarty şablonunda PHP kodu (`Settings::get`) kullanılmış olabilir; Network sekmesinde yanıta bakın |

---

## 20. Referans modüller

| Modül | Öğrenilecek konu |
|-------|------------------|
| `whatsapp` | Basit footer hook + SQL |
| `newsletter` | API + footer form |
| `reviews` | Ürün sekmesi + API + admin listeleme |
| `slider` | Admin CRUD + görsel yükleme + özel hook |
| `bankwire` | Ödeme + order_confirmation |
| `paytr` | Sipariş öncesi iframe ödeme |
| `iyzico` | iyzico Checkout Form + webhook |
| `cashondelivery` | Minimal ödeme modülü |
| `sanalpos` | paysBeforeOrder + front route |
| `socials` | product_detail hook |
| `alert-price` | Ürün hook + API + cron (CSRF örneği: newsletter ile aynı kalıp) |

---

## 21. Özet kontrol listesi

Yeni modül teslim etmeden önce:

- [ ] `modules/{ad}/{ad}.php` var, sınıf `{Ad}Module extends ModuleBase`
- [ ] `$name` klasör adıyla eşleşiyor
- [ ] `install()` ve `uninstall()` implement edildi
- [ ] `displayHooks` + `defaultDisplayHooks` tanımlı
- [ ] `renderDisplayHook()` ilgili hook'ları işliyor
- [ ] Front şablonlar `assets/templates/front/` altında
- [ ] Admin şablon `assets/templates/admin/admin.tpl`
- [ ] Admin formlarda token kontrolü
- [ ] API'de CSRF kontrolü
- [ ] Mağaza AJAX'ta global `csrfToken` kullanılıyor (cron/admin token değil)
- [ ] Hook şablonunda inline script yerine `assets/js/` + `data-api-url`
- [ ] Ürün linki için `$product['url']` veya `Product::getLink()`
- [ ] `Settings::get('DOMAIN')` veya `global $domain` (küçük `domain` anahtarı yok)
- [ ] PHP 7.4 uyumlu
- [ ] Global Smarty'ye `assign` + `clearAssign` yapılmıyor (`renderFrontTemplate` kullan)

---

*Bu belge FShop `core/Module.php`, `core/ModuleBase.php` ve mevcut modül kaynak kodlarından üretilmiştir.*

Soru sor modülü örnek kodları Bu çalışan bir modüldür bu modül mimarisi dikkate alabilirsin.

<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class QuestionModule extends ModuleBase
{
	public string $name = 'question';
	public string $title = 'Soru Sor';
	public string $version = '1.0.0';
	public string $description = 'Ürün hakkında soru alma ve cevaplama';
	public string $author = 'FShop';

	public array $displayHooks = [
		'product_tab' => 'Ürün Tabı',
		'product_tab_content' => 'Ürün sayfası',
	];

	public array $defaultDisplayHooks = ['product_tab', 'product_tab_content'];

	public array $frontStylesheets = ['question.css'];
	public array $frontScripts = ['question.js'];

	public array $apiActions = [
		'submit' => 'api/submit.php',
	];

	public function install(): bool
	{
		return $this->runSqlFile('install.sql');
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		$table = DB::execute("SHOW TABLES LIKE 'product_questions'");

		if (empty($table)) {
			$this->runSqlFile('install.sql');
		}
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';

		if (Tools::isSubmit('questionAction')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$id = (int) Tools::getValue('id_question');
				$action = (string) Tools::getValue('action');

				switch ($action) {
					case 'answer':
						$result = self::saveAnswer($id, (string) Tools::getValue('answer'));
						break;
					case 'hide':
						$result = self::setActive($id, false);
						break;
					case 'publish':
						$result = self::setActive($id, true);
						break;
					case 'delete':
						$result = self::delete($id);
						break;
					default:
						$result = ['success' => false, 'message' => 'Geçersiz işlem'];
				}

				$flash = $result['message'];
			}
		}

		$filter = (string) Tools::getValue('filter', 'pending');
		$currentPage = max(1, (int) Tools::getValue('page'));
		$perPage = 30;
		$total = self::countAdmin($filter);
		$pagination = Pagination::build(
			$total,
			$currentPage,
			$perPage,
			Admin::url($this->getAdminSlug()) . '?filter=' . rawurlencode($filter)
		);

		$smarty->assign([
			'questions' => self::getAdminList($filter, $perPage, $pagination['offset']),
			'pagination' => $pagination,
			'filter' => $filter,
			'pendingCount' => self::countAdmin('pending'),
			'flash' => $flash,
		]);
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if (!in_array($hook, ['product_tab', 'product_tab_content'], true)) {
			return null;
		}

		$idProduct = (int) ($context['id_product'] ?? 0);

		if ($idProduct <= 0) {
			return null;
		}

		global $domain;

		$questionCount = self::countPublished($idProduct);

		$html = $this->renderFrontTemplate($hook, [
			'id_product' => $idProduct,
			'questions' => self::getPublishedForProduct($idProduct),
			'questionCount' => $questionCount,
			'isLoggedIn' => Customer::isLoggedIn(),
			'questionApiUrl' => rtrim($domain, '/') . '/api/module.php?m=question&action=submit',
			'askerName' => Customer::isLoggedIn() ? (Customer::getCurrent()['user_full_name'] ?? '') : '',
		]);

		if ($hook === 'product_tab_content' && Customer::isLoggedIn()) {
			$_SESSION['question_form_started'] = time();
		}

		return $html !== '' ? $html : null;
	}

	public static function submit(int $idProduct, string $question, ?int $idUser = null, array $security = []): array
	{
		if (!$idUser || !Customer::isLoggedIn() || Customer::getId() !== $idUser) {
			return [
				'success' => false,
				'message' => 'Soru sormak için giriş yapmalısınız',
				'login_required' => true,
			];
		}

		$botCheck = self::validateAntiBot($security);
		if ($botCheck !== null) {
			return $botCheck;
		}

		$idProduct = (int) $idProduct;
		$question = trim(strip_tags($question));

		if ($idProduct <= 0 || !Product::getById($idProduct)) {
			return ['success' => false, 'message' => 'Ürün bulunamadı'];
		}

		if ($question === '' || Tools::strlen($question) < 10) {
			return ['success' => false, 'message' => 'Soru en az 10 karakter olmalı'];
		}

		if (Tools::strlen($question) > 1000) {
			return ['success' => false, 'message' => 'Soru en fazla 1000 karakter olabilir'];
		}

		if (self::looksLikeSpam($question)) {
			return ['success' => true, 'message' => 'Sorunuz alındı. Cevaplandığında burada yayınlanacak.'];
		}

		$user = Customer::getCurrent();

		if (!$user || (int) $user['id_user'] !== $idUser) {
			return ['success' => false, 'message' => 'Oturum geçersiz'];
		}

		$authorName = trim((string) ($user['user_full_name'] ?? ''));

		if ($authorName === '') {
			$authorName = 'Müşteri';
		}

		$recentCount = (int) DB::getValue(
			'SELECT COUNT(*) FROM product_questions WHERE id_user = ? AND date_add > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
			[$idUser]
		);

		if ($recentCount >= 5) {
			return ['success' => false, 'message' => 'Saatte en fazla 5 soru gönderebilirsiniz'];
		}

		$id = DB::insert('product_questions', [
			'id_product' => $idProduct,
			'id_user' => $idUser,
			'author_name' => mb_substr($authorName, 0, 128),
			'question' => $question,
			'answer' => '',
			'active' => 0,
		]);

		if ($id) {
			$_SESSION['question_last_submit'] = time();
			unset($_SESSION['question_form_started']);
		}

		return $id
			? ['success' => true, 'message' => 'Sorunuz alındı. Cevaplandığında burada yayınlanacak.']
			: ['success' => false, 'message' => 'Soru kaydedilemedi'];
	}

	public static function saveAnswer(int $id, string $answer): array
	{
		$row = DB::getRowSafe('product_questions', 'id_question = ?', [$id]);

		if (!$row) {
			return ['success' => false, 'message' => 'Soru bulunamadı'];
		}

		$answer = trim(strip_tags($answer));

		if ($answer === '' || Tools::strlen($answer) < 3) {
			return ['success' => false, 'message' => 'Cevap en az 3 karakter olmalı'];
		}

		$updated = DB::update(
			'product_questions',
			[
				'answer' => $answer,
				'active' => 1,
				'date_answer' => date('Y-m-d H:i:s'),
			],
			'id_question = :id_question',
			['id_question' => $id]
		);

		if ($updated === false) {
			return ['success' => false, 'message' => 'Cevap kaydedilemedi'];
		}

		return ['success' => true, 'message' => 'Cevap kaydedildi ve yayınlandı'];
	}

	public static function getPublishedForProduct(int $idProduct, int $limit = 50): array
	{
		$rows = DB::execute(
			'SELECT * FROM product_questions
			 WHERE id_product = ? AND active = 1 AND answer <> \'\'
			 ORDER BY date_answer DESC, date_add DESC
			 LIMIT ' . (int) $limit,
			[$idProduct]
		) ?: [];

		return array_map([self::class, 'formatRow'], $rows);
	}

	public static function countPublished(int $idProduct): int
	{
		return (int) DB::getValue(
			'SELECT COUNT(*) FROM product_questions WHERE id_product = ? AND active = 1 AND answer <> \'\'',
			[$idProduct]
		);
	}

	public static function getAdminList(string $filter, int $limit, int $offset): array
	{
		$sql = 'SELECT q.*, p.product_name
			FROM product_questions q
			INNER JOIN products p ON p.id_product = q.id_product
			WHERE 1=1';
		$params = [];

		if ($filter === 'pending') {
			$sql .= ' AND (q.answer = \'\' OR q.active = 0)';
		} elseif ($filter === 'answered') {
			$sql .= ' AND q.answer <> \'\' AND q.active = 1';
		}

		$sql .= ' ORDER BY q.id_question DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		$rows = DB::execute($sql, $params) ?: [];

		return array_map(static function (array $row) {
			$row['date_formatted'] = Tools::formatDate3($row['date_add']);
			$row['answer_formatted'] = !empty($row['date_answer']) ? Tools::formatDate3($row['date_answer']) : '';

			return $row;
		}, $rows);
	}

	public static function countAdmin(string $filter = 'all'): int
	{
		$sql = 'SELECT COUNT(*) FROM product_questions WHERE 1=1';

		if ($filter === 'pending') {
			$sql .= ' AND (answer = \'\' OR active = 0)';
		} elseif ($filter === 'answered') {
			$sql .= ' AND answer <> \'\' AND active = 1';
		}

		return (int) DB::getValue($sql);
	}

	public static function setActive(int $id, bool $active): array
	{
		$row = DB::getRowSafe('product_questions', 'id_question = ?', [$id]);

		if (!$row) {
			return ['success' => false, 'message' => 'Soru bulunamadı'];
		}

		if ($active && trim((string) $row['answer']) === '') {
			return ['success' => false, 'message' => 'Yayınlamak için önce cevap yazın'];
		}

		DB::update('product_questions', ['active' => $active ? 1 : 0], 'id_question = :id_question', [
			'id_question' => $id,
		]);

		return ['success' => true, 'message' => $active ? 'Soru yayında' : 'Soru gizlendi'];
	}

	public static function delete(int $id): array
	{
		DB::execute('DELETE FROM product_questions WHERE id_question = ?', [$id]);

		return ['success' => true, 'message' => 'Soru silindi'];
	}

	private static function formatRow(array $row): array
	{
		$row['date_formatted'] = Tools::formatDate3($row['date_add']);
		$row['answer_formatted'] = !empty($row['date_answer']) ? Tools::formatDate3($row['date_answer']) : '';
		$row['author_masked'] = Tools::maskName($row['author_name']);

		return $row;
	}

	private static function validateAntiBot(array $security): ?array
	{
		if (trim((string) ($security['website'] ?? '')) !== '') {
			return ['success' => true, 'message' => 'Sorunuz alındı. Cevaplandığında burada yayınlanacak.'];
		}

		$started = (int) ($_SESSION['question_form_started'] ?? 0);
		$elapsed = $started > 0 ? time() - $started : 0;

		if ($started <= 0 || $elapsed < 3) {
			return ['success' => false, 'message' => 'Lütfen formu doldurduktan birkaç saniye sonra gönderin'];
		}

		if ($elapsed > 7200) {
			return ['success' => false, 'message' => 'Form süresi doldu. Sayfayı yenileyip tekrar deneyin'];
		}

		$lastSubmit = (int) ($_SESSION['question_last_submit'] ?? 0);
		if ($lastSubmit > 0 && time() - $lastSubmit < 60) {
			return ['success' => false, 'message' => 'Çok sık soru gönderiyorsunuz. Lütfen bir dakika bekleyin'];
		}

		return null;
	}

	private static function looksLikeSpam(string $text): bool
	{
		if (preg_match_all('/https?:\/\//i', $text) >= 2) {
			return true;
		}

		if (preg_match('/\b(viagra|cialis|casino|porn|xxx|click here|buy now)\b/i', $text)) {
			return true;
		}

		return false;
	}
}


#19 Tools sınıfında kullanabileceğin fonksiyonlar
Tools::displayPrice(5.25) -> İçine yazılı değeri TRY formatında gösterir ör : ₺5,25
Tools::formatNumber(8.111111) -> içine yazılı değeri virgülden sonra 2 hane olacak şekilde düzenler örnek : 8.11
Tools::maskName('Ramazan Benek') -> içine yazılı değerin sadece ilk harfini gösterir diğerlerini gizler örnek : R** B**
Tools::timeAgo('2026-01-01 10:10:10') -> içine yazılı tarihin ne kadar zaman önce olduğunu söyler örnek : 25dk önce
Tools::deleteTags('<b>Deneme</b>') -> preg_replace fonksiyonu ve içerisindeki düzenli ifade (regex), metni tarayarak sadece şunların kalmasına izin verir:\p{L}: Tüm dillerdeki harfler (A-Z, a-z, ç, ş, ğ vb.)\p{N}: Tüm rakamlar (0-9)\s: Boşluk karakterleri\-_: Tire (-) ve alt tire (_) işaretleriBu karakterlerin dışındaki her şey (büyüktür/küçüktür işaretleri, noktalama işaretleri, özel semboller) silinir. Örnek : bDenemeb 
Tools::createSlug('ana sayfa') -> girilen metni linke çevirir örnek : ana-sayfa

#Validate Clası bu class içindeki verinin türüne göre true ya da false döner

Validate::isInt() -> içindeki verinin tamsayı olup olmadığına bakar true veya false döner
Validate::isFloat() -> decimal sayımı diye bakar
Validate::isUrl('https://fyazilim.com') -> içindeki değerin url olup olmadığına bakar
Validate::isCleanHtml() -> içindeki değerde <script> <iframe> onsumbit, onclick gibi arkaplanda çalışan kodların var olup olmadığına bakar
Validate::isName() -> içindeki değerin harf tire ve nokta dışında başka karakter olup olmadığına bakar
Validate::isGeneric() -> preg_match('/^[a-zA-Z0-9\s->]*$/u', $name) ile gelen veriyi denetler
Validate::isGenericName() -> return empty($name) || preg_match('/^[^<={}]*$/u', $name);
Validate::isMd5() -> içindeki değerin md5 olup olmadığına bakar
Validate::isDate() -> içindeki değerin tarih olup olmadığına bakar
Validate::isEmail() -> İçindeki değerin email olup olmadığına bakar
Validate::isPhoneNumber() -> içindeki değerin telefon numarası olup olmadığına bakar
Validate::isUserName() -> return preg_match('/^[a-zA-Z0-9.\-_]+$/', $username); 

#Diğer yardımcı kodlar
Ürün resmlerini almak : $images = Product::getImages($idProduct); // Array
Ürüne ait bilgileri almak : $product = Product::getById($idProduct); // Array
Ürün favorilere ekli mi : Favorite::isFavorite($idProduct); //true false
Sepeti almak : $cart = Cart::getSummary(); //Array
Müşteri siparişlerini çekmek : Order::getUserOrders(Customer::getId()); //Array
Giriş yapmış kullanıcı id'sini almak : Customer::getId(); //Integer
Kullanıcıya ait varsayılan adresi çekmek : Address::getDefault($idUser); //Integer
Sipariş bilgilerini almak : Order::getByIdForUser($idOrder, Customer::getId()); //Array


#Mail Gönderme
Mail sınıfı ile yapılır
Mail::send(string $to, string $subject, string $bodyHtml); Bu değişkenleri kullanarak mail gönderim sağlanabilir.

#Sayfalama - Pagination
public static function build(int $total, int $page, int $perPage, string $baseUrl, array $query = []): array
	{
		$totalPages = max(1, (int) ceil($total / $perPage));
		$page = max(1, min($page, $totalPages));
		$offset = ($page - 1) * $perPage;

		$makeUrl = function (int $p) use ($baseUrl, $query) {
			$params = $query;

			if ($p > 1) {
				$params['page'] = $p;
			} else {
				unset($params['page']);
			}

			$params = array_filter($params, static function ($value) {
				return $value !== null && $value !== '';
			});

			$qs = http_build_query($params);

			return $baseUrl . ($qs !== '' ? '?' . $qs : '');
		};

		$pages = [];
		$start = max(1, $page - 2);
		$end = min($totalPages, $page + 2);

		for ($i = $start; $i <= $end; $i++) {
			$pages[] = [
				'num' => $i,
				'url' => $makeUrl($i),
				'current' => $i === $page,
			];
		}

		return [
			'page' => $page,
			'per_page' => $perPage,
			'total' => $total,
			'total_pages' => $totalPages,
			'offset' => $offset,
			'has_prev' => $page > 1,
			'has_next' => $page < $totalPages,
			'prev_url' => $makeUrl($page - 1),
			'next_url' => $makeUrl($page + 1),
			'pages' => $pages,
		];
	}
Kullanım : Pagination::build(100, 2, 10, 'kategori', array $query = []);

#admin Sayfa yapısı Admin::url(...)
Kullanım : Admin::url('messages'); -> çıktı bu şekilde olur https://sitelinki/adminlinki/messages

Domain linki almak için Settings::get('DOMAIN'); kullan

Eğer bir kategoriye ait ürünleri çekmek istersen : Product::getActiveList($idCategory, $perPage, $pagination['offset'], $sort);
Ürün grid'i için PHP'den tema yolunu geçirin; modül şablonunda: `{include file=$productGridTpl products=$products}`

```php
// renderDisplayHook içinde
$theme = Settings::get('THEME') ?: 'default';
$this->renderFrontTemplate('product', [
    'products' => $products,
    'productGridTpl' => $theme . '/productGrid.tpl',
]);
```

`./productGrid.tpl` yalnızca tema şablonlarında çalışır; modül `assets/templates/front/` altından yüklenir.

Product grid yapısı
<div class="row g-3 product-grid-premium">
{foreach $products as $p}
	<div class="col-6 col-md-4 col-lg-3">
		<div class="catalog-card h-100 text-center">
			{if $p.has_discount}
			<span class="catalog-badge">İndirim</span>
			{/if}
			{if !$p.in_stock}
			<span class="catalog-badge catalog-badge-muted">Tükendi</span>
			{/if}
			<a href="{$p.url}" class="catalog-image d-block">
				<img src="{$p.image_url}" alt="{$p.product_name|escape}" class="img-fluid" loading="lazy">
			</a>
			{if $p.review_count > 0}
				<div class="catalog-rating d-flex align-items-center justify-content-center gap-1">
					<div class="review-stars review-stars--sm" style="--rating: {$p.rating}">
						<span class="review-stars-track" aria-hidden="true"></span>
						<span class="review-stars-fill" aria-hidden="true"></span>
					</div>
					<span class="small text-muted">({$p.review_count})</span>
				</div>
			{/if}
			<div class="catalog-body p-2">
				<a href="{$p.url}" class="catalog-name d-block">{$p.product_name|escape|truncate:40:"":true:true}</a>
				
				<div class="d-flex align-items-center gap-3 productDiscount">
					{if $p.has_discount}<div class="discount-badge">%{Tools::getDiscount($p.old_price, $p.price)}</div>{/if}
					<div>
						{if $p.has_discount}<div class="old-price">{$p.old_price_formatted}</div>{/if}
						<div class="current-price">{$p.price_formatted}</div>
					</div>
				</div>
				<div class="catalog-actions d-flex gap-1 mt-2">
					{if $p.in_stock}
						<button type="button" class="btn btn-dark btn-sm addtocart flex-grow-1" data-id="{$p.id_product}" title="Sepete ekle">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-basket-icon lucide-shopping-basket"><path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4"/><path d="M4.5 15.5h15"/><path d="m5 11 4-7"/><path d="m9 11 1 9"/></svg>
							Sepete Ekle
						</button>
					{else}
						<a href="{$p.url}" class="btn btn-outline-dark btn-sm flex-grow-1">Detay</a>
					{/if}
				</div>
				{if isset($showFavoriteRemove) && $showFavoriteRemove}
				<button type="button" class="btn btn-link btn-sm text-danger remove-favorite mt-1" data-id="{$p.id_product}">Listeden Kaldır</button>
				{/if}
			</div>
		</div>
	</div>
{/foreach}
</div>
Pagination::resolveSort()

Desteklenen değerler:

newest
price_asc
price_desc
name_asc
discount

$product = Product::getById(1);

[
	'id_product' => 1,
	'id_category' => 5,
	'id_brand' => 2,
	'product_name' => 'Örnek',
	'url' => '...',
	'image_url' => '...'
]

## Settings Anahtarları

Settings::get('THEME') => Bu hangi temanın aktif olduğunu gösterir anadizin/templates/AKTİF TEMA/…
Settings::get('DOMAIN') => site linkini verir
Settings::get('SITE_NAME') => site adını verir

FShop Modül Geliştirme Kuralları

- Bu doküman dışında fonksiyon uydurma.
- Burada belirtilmeyen sınıf ve metodları kullanma.
- Emin değilsen eksik olan kısmı yorum satırı olarak belirt.
- Tema dosyalarını değiştirme.
- Core dosyalarını değiştirme.
- Modül kendi klasörü içerisinde çalışmalıdır.
