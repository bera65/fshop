# FShop — AI / Agent Rehberi

Bu dosya, Cursor, Copilot ve benzeri kod asistanları için FShop projesinin giriş noktasıdır.

**Modül veya özellik yazmadan önce mutlaka okuyun:**

1. [AI_CONTEXT.md](AI_CONTEXT.md) — mimari özet
2. [modules/MODULE_DEVELOPER_GUIDE.md](modules/MODULE_DEVELOPER_GUIDE.md) — modül yazım kuralları (tam referans)
3. [modules/README.md](modules/README.md) — modül sistemi kısa özet

---

## Proje özeti

FShop, PHP 7.4+ ile yazılmış açık kaynak bir e-ticaret altyapısıdır.

| Katman | Teknoloji |
|--------|-----------|
| Backend | PHP 7.4+, PDO/MySQL |
| Şablon | Smarty 5 |
| Admin UI | Bootstrap |
| Routing | Apache `mod_rewrite` + `?container=` parametresi |

**Önemli kural:** Modüller tema dosyalarına (`templates/default/`, `templates/admin/`) dokunmadan çalışır. Tüm modül şablonları `modules/{ad}/assets/templates/` altında kalır.

---

## Dizin yapısı

```
fshop/
├── index.php              # Mağaza giriş noktası
├── admin/index.php        # Yönetim paneli giriş noktası
├── config/
│   ├── env.php            # Ortam ayarları (gitignore — kurulumda oluşur)
│   ├── settings.php       # Bootstrap: core sınıflar, Smarty, session
│   ├── database.php       # DB:: sınıfı
│   └── page.php           # Page::add() — Smarty layout render
├── core/                  # İş mantığı sınıfları (Product, Order, Module, Cart…)
├── container/
│   ├── front/             # Mağaza sayfa controller'ları (home.php, product.php…)
│   └── admin/             # Admin sayfa controller'ları (dashboard.php, products.php…)
├── templates/
│   ├── default/           # Mağaza teması (.tpl)
│   └── admin/             # Admin teması (.tpl)
├── modules/               # Eklenti modüller
├── api/                   # JSON/cron endpoint'leri
├── install/               # Kurulum sihirbazı
└── img/products/          # Ürün görselleri (gitignore)
```

`controllers/` ve `models/` klasörü **yoktur**. Sayfa mantığı `container/`, veri erişimi `core/` sınıflarındadır.

---

## Routing

### Mağaza (`index.php`)

- SEO URL: `/kategori-slug/urun-slug-123` → `container=product`
- Genel: `/{sayfa}` → `container/{sayfa}.php` (ör. `/cart` → `container/front/cart.php`)
- Modül front route: `Module::resolveFrontRoute($container)` ile `modules/{ad}/front/` altından yüklenir

### Admin (`admin/index.php`)

- Public URL: `/{ADMIN_URI}/{sayfa}` → `container/admin/{sayfa}.php` (`ADMIN_URI` in `config/env.php`, default `admin`)
- Fiziksel klasör her zaman `admin/` — klasörü rename etme; slug’ı `ADMIN_URI` ile değiştir
- Modül yapılandırma: `/{ADMIN_URI}/module-{ad}` → modülün `adminPage()` metodu (otomatik)

### API

| Endpoint | Amaç |
|----------|------|
| `GET/POST /api/v1/...` | Web API (sipariş, ürün, kategori, marka, mesaj, bildirim) — bkz. [docs/WEBAPI.md](docs/WEBAPI.md) |
| `POST /api/module.php?m={modul}&action={islem}` | Modül API |
| `GET /api/cron.php?action=currency&token=SHOP_TOKEN` | Döviz fiyat güncelleme |

---

## Modül yazarken

### Zorunlu adımlar

1. `modules/MODULE_DEVELOPER_GUIDE.md` dosyasını oku
2. Benzer bir modüle bak:
   - Footer hook → `modules/whatsapp/`
   - Ürün sekmesi → `modules/reviews/`
   - Ödeme → `modules/paytr/` veya `modules/iyzico/` veya `modules/bankwire/`
   - Admin CRUD → `modules/slider/`
3. `modules/{ad}/{ad}.php` içinde `ModuleBase` extend et
4. Gerekirse `install.sql` / `uninstall.sql` ekle
5. Şablonları `assets/templates/admin/` ve `assets/templates/front/` altına koy

### İsimlendirme (kritik)

| Öğe | Kural | Örnek |
|-----|-------|-------|
| Klasör | küçük harf, tire | `my-module` |
| Dosya | `{ad}/{ad}.php` | `my-module/my-module.php` |
| Sınıf | PascalCase + `Module` | `MyModuleModule` |
| Admin URL | otomatik | `/{ADMIN_URI}/module-my-module` |

### Display hook'lar (mağaza şablonunda `{$hooks.*}`)

Tema dosyalarında tanımlı hook noktaları:

| Hook | Şablon |
|------|--------|
| `footer` | `templates/default/footer.tpl` |
| `home_slider` | `templates/default/header.tpl` |
| `main_menu` | Tema üst menü (`_mini/header*.tpl`) — kategori menüsü yerine |
| `home_promo_slider` | `templates/default/home.tpl` |
| `product`, `product_inf`, `product_detail` | `templates/default/product.tpl` |
| `product_tab`, `product_tab_content` | `templates/default/product.tpl` |
| `order_payment` | `templates/default/checkout.tpl` |
| `order_confirmation` | `templates/default/checkout-success.tpl` |

### Admin display hook'lar (`{$adminHooks.*}`)

| Hook | Şablon |
|------|--------|
| `admin_header` | `templates/admin/layout/header.tpl` — orta alan üstü (tüm sayfalar) |
| `admin_footer` | `templates/admin/layout/footer.tpl` — admin footer (tüm sayfalar) |
| `admin_product_button` | `templates/admin/product.tpl` — Kaydet butonu yanı |
| `admin_order_detail` | `templates/admin/order.tpl` — sipariş detay sağ sütun |
| `admin_dashboard_top` | `templates/admin/dashboard.tpl` — hoş geldin altı |
| `admin_dashboard_kpi` | `templates/admin/dashboard.tpl` — KPI kartları altı |
| `admin_dashboard_main_left` | `templates/admin/dashboard.tpl` — sol sütun (son siparişler altı) |
| `admin_dashboard_main_right` | `templates/admin/dashboard.tpl` — sağ sütun |
| `admin_dashboard_bottom` | `templates/admin/dashboard.tpl` — sayfa altı |

Admin şablonu: `modules/{ad}/assets/templates/admin/{hook}.tpl`

Modülde:

```php
public array $displayHooks = ['footer' => 'Açıklama'];
public array $defaultDisplayHooks = ['footer'];

public function renderDisplayHook(string $hook, array $context = []): ?string
{
    if ($hook !== 'footer') {
        return null;
    }
    return $this->renderFrontTemplate('footer', ['degisken' => 'deger']) ?: null;
}
```

### Dahili hook'lar (`boot()` içinde `Module::registerHook()`)

- `smarty.assign` — Smarty değişkenleri
- `head.assets` — CSS/JS
- `order.placed` — sipariş sonrası
- `product.updated` — ürün kaydedilince (admin + Web API)
- `order.updated` — sipariş güncellenince (admin + Web API)

### Ayarlar

Site ayarları `settings` tablosunda; `Settings::get('ANAHTAR')` / `Settings::set()` ile erişilir.

---

## Core sınıflar (sık kullanılan)

| Sınıf | Dosya | Görev |
|-------|-------|-------|
| `Product` | `core/Product.php` | Ürün CRUD, fiyat, görsel |
| `Order` | `core/Order.php` | Sipariş, checkout |
| `Cart` | `core/Cart.php` | Sepet |
| `Customer` | `core/Customer.php` | Müşteri oturumu |
| `Category` | `core/Category.php` | Kategori |
| `Module` | `core/Module.php` | Modül yükleme, hook, API dispatch |
| `ModuleBase` | `core/ModuleBase.php` | Modül temel sınıfı |
| `DB` | `config/database.php` | PDO sorguları |
| `Settings` | `config/config.php` | Ayarlar |
| `Tools` | `config/function.php` | `getValue()`, yardımcılar |

---

## Yeni özellik ekleme (modül dışı)

| Ne ekleniyor? | Nereye? |
|---------------|---------|
| Mağaza sayfası | `container/front/{sayfa}.php` + `templates/default/{sayfa}.tpl` |
| Admin sayfası | `container/admin/{sayfa}.php` + `templates/admin/{sayfa}.tpl` |
| Veritabanı kolonu | `core/Schema.php` veya ilgili core sınıfın `ensureSchema()` |
| Yeni tablo (kurulum) | `install/schema.sql` |

---

## Kod kuralları

- PHP 7.4+ uyumlu yaz (typed properties kullanılabilir)
- Mevcut kod stiline uy: tab indent, `require_once`, global `$smarty` / `$domain`
- Modülde `IN_SCRIPT` veya `IN_ADMIN` guard kullan
- Tema dosyalarını modül için değiştirme — display hook kullan
- Gizli bilgileri (`env.php`, token, SMTP) commit etme
- `config/env.php` ve `config/installed.lock` gitignore'da

---

## Örnek prompt (modül isteği)

```
FShop için bir modül yaz.
1. AGENTS.md ve modules/MODULE_DEVELOPER_GUIDE.md oku
2. modules/whatsapp/ modülünü referans al
3. Footer'da Instagram linki göster; link admin panelinden düzenlenebilsin
4. Tema dosyalarına dokunma
```

---

## Kurulum ve geliştirme

- Kurulum: `/install/` ( `config/env.php` yoksa)
- Yerel WAMP: `http://localhost/fshop/`
- Şema: `install/schema.sql`
- Demo veri: `install/seed_demo.sql`
