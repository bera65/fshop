# FShop AI Context

Bu dosya, yapay zeka asistanlarının FShop kod tabanını hızlıca anlaması için hazırlanmış özet bağlamdır.

**Tam modül referansı:** [modules/MODULE_DEVELOPER_GUIDE.md](modules/MODULE_DEVELOPER_GUIDE.md)  
**Agent giriş noktası:** [AGENTS.md](AGENTS.md)

---

## Project Type

Açık kaynak PHP e-ticaret platformu (Türkiye odaklı).  
GitHub: https://github.com/bera65/fshop

---

## Stack

| Bileşen | Sürüm / Not |
|---------|-------------|
| PHP | 7.4+ (GD, PDO MySQL, mbstring) |
| Veritabanı | MySQL 5.7+ / MariaDB 10.3+ |
| ORM | Yok — `DB::` PDO wrapper (`config/database.php`) |
| Template | Smarty 5 (`libs/Smarty.class.php`) |
| Admin UI | Bootstrap |
| Web server | Apache + `mod_rewrite` |

---

## Architecture (önemli)

FShop klasik MVC değil; **container + core** mimarisi kullanır:

```
HTTP isteği
    → index.php veya admin/index.php
    → config/settings.php (bootstrap)
    → container/{front|admin}/{sayfa}.php  (sayfa mantığı)
    → core/*.php                           (domain sınıfları)
    → templates/{default|admin}/*.tpl      (Smarty görünüm)
```

**`controllers/` ve `models/` klasörü yoktur.**

---

## Directory Structure

```
/config          Ortam, DB, bootstrap, Settings, Tools
/core            Product, Order, Cart, Module, Customer, Category…
/container/front Mağaza sayfa dosyaları (home, product, cart, checkout…)
/container/admin Admin sayfa dosyaları (dashboard, products, orders…)
/templates/default   Mağaza Smarty teması
/templates/admin     Yönetim paneli teması
/modules         Eklenti modüller (bağımsız, tema-dokunmasız)
/api             module.php, cron.php
/install         Kurulum sihirbazı + schema.sql + seed_demo.sql
/img/products    Ürün görselleri
/cache           Smarty derleme önbelleği
/libs            Smarty kütüphanesi
```

---

## Entry Points

| Dosya | Rol |
|-------|-----|
| `index.php` | Mağaza — `?container=` veya SEO URL |
| `admin/index.php` | Admin — `/admin/{sayfa}` |
| `api/module.php` | Modül JSON API |
| `api/cron.php` | Cron (döviz güncelleme vb.) |
| `config/install_gate.php` | Kurulum kontrolü (`env.php` yoksa `/install/` yönlendirir) |

---

## Routing

### Mağaza

- Ürün: `/{kategori}/{slug}-{id}` → `container=product`
- Marka: `/marka/{link}` → `container=brand`
- Sayfa: `/{sayfa}` → `container/front/{sayfa}.php`
- Modül sayfası: `modules/{ad}/front/` ( `Module::resolveFrontRoute` )

### Admin

- Public URL: `/{ADMIN_URI}/dashboard`, `/{ADMIN_URI}/products`, `/{ADMIN_URI}/module-whatsapp` (default `ADMIN_URI=admin`)
- Fiziksel klasör her zaman `admin/` — yalnızca public slug `config/env.php` → `ADMIN_URI` ile değişir
- Değişiklik sonrası panele bir kez girin (`.htaccess` otomatik senkron); eski `/admin` kapanır
- Modül config: otomatik `/{ADMIN_URI}/module-{modul-adi}`

### `.htaccess`

`RewriteBase` kurulumda ayarlanır (ör. `/fshop/`). Kurulum sihirbazı bunu günceller.
`# BEGIN FSHOP_ADMIN` … `# END FSHOP_ADMIN` bloğu `ADMIN_URI` rewrite kurallarını tutar.

---

## Templates

| Tema | Yol | Kullanım |
|------|-----|----------|
| Mağaza | `templates/default/` | `Page::add('home', …)` |
| Admin | `templates/admin/` | `AdminPage::add('dashboard', …)` |

Layout: `header.tpl` + `{sayfa}.tpl` + `footer.tpl`

Modül çıktıları tema içinde hook ile gösterilir: `{$hooks.footer nofilter}`

---

## Database

Şema: `install/schema.sql`

### Ana tablolar

| Tablo | İçerik |
|-------|--------|
| `products` | Ürünler (fiyat, döviz, stok, SEO…) |
| `categories` | Kategoriler |
| `brands` | Markalar |
| `orders` / `order_detail` | Siparişler |
| `customers` | Müşteriler |
| `settings` | Site ayarları (key-value) |
| `modules` | Kurulu modüller |
| `module_display_hooks` | Modül → hook eşlemesi |
| `coupons` | Kuponlar |
| `cms` | İçerik sayfaları |

### Ayarlar API

```php
Settings::get('SITE_NAME');
Settings::set('ANAHTAR', 'deger');
```

Ortam: `config/env.php` (`APP_ENV`, `APP_DEBUG`, DB bilgileri)

---

## Module System

Modüller `modules/{ad}/` altında yaşar. **Tema dosyalarına dokunulmaz.**

### Minimum yapı

```
modules/ornek/
  ornek.php              # class OrnekModule extends ModuleBase
  logo.png
  install.sql
  uninstall.sql
  assets/templates/admin/admin.tpl
  assets/templates/front/footer.tpl
  assets/css/
  assets/js/
  api/
```

### Sınıf adlandırma

`product-badge` → `ProductBadgeModule` (tireler kaldırılır, PascalCase + `Module`)

### Modül yetenekleri

- Admin yapılandırma: `adminPage()` + `assets/templates/admin/admin.tpl`
- Mağaza parçası: `renderDisplayHook()` + `displayHooks` / `defaultDisplayHooks`
- API: `api/{action}.php` → `/api/module.php?m={ad}&action={action}`
- Ödeme: `$isPayment = true`, `$paymentMethodId`, checkout hook'ları
- Özel sayfa: `modules/{ad}/front/{sayfa}.php` + `$routes`

### Referans modüller

| Modül | Ne için bakılır |
|-------|-----------------|
| `whatsapp` | Basit footer display hook |
| `reviews` | Ürün sekmesi hook + API |
| `slider` | Admin CRUD + home hook |
| `paytr` | Ödeme entegrasyonu |
| `alert-price` | Cron + ürün sayfası hook |
| `newsletter` | Footer form + API |

### Display hook noktaları (tema)

`footer`, `home_slider`, `home_promo_slider`, `product`, `product_inf`, `product_detail`, `product_tab`, `product_tab_content`, `order_payment`, `order_confirmation`

### Dahili hook'lar

`smarty.assign`, `head.assets`, `footer.html`, `admin.menu`, `order.placed`

---

## Currency System

Ürünlerde `doviz` (try/usd/eur/xau), `doviz_price`, `doviz_old_price` alanları.

Cron endpoint:

```
GET /api/cron.php?action=currency&token=SHOP_TOKEN
```

`SHOP_TOKEN` → `settings` tablosu. `Product::refreshCurrencyPrices()` BigPara API kullanır.

---

## Core Classes Quick Reference

| Sınıf | Görev |
|-------|-------|
| `Product` | Ürün listeleme, kaydetme, görsel, Excel import/export |
| `Order` | Sipariş oluşturma, durum |
| `Cart` | Sepet işlemleri |
| `Customer` | Giriş, kayıt, oturum |
| `Category` / `Brand` | Katalog |
| `Module` / `ModuleBase` | Modül yaşam döngüsü |
| `Coupon` | İndirim kuponları |
| `Seo` / `SchemaOrg` | Meta ve JSON-LD |
| `Theme` / `SiteAssets` | Tema renkleri, asset yönetimi |
| `Installer` | Kurulum sihirbazı |

---

## Security Notes

- `config/env.php` repoda yok (örnek: `config/env.example.php`)
- Admin oturumu: `Admin::isLoggedIn()`
- Müşteri oturumu: `Customer::isLoggedIn()`
- API/cron token doğrulaması kullan
- `clearSQL()` / prepared statements tercih et

---

## Installation

1. Boş MySQL veritabanı oluştur
2. `/install/` aç
3. DB + admin bilgilerini gir
4. `config/env.php` ve `config/installed.lock` oluşur

Yeniden kurulum: `config/env.php` silinip `/install/` tekrar açılır.

---

## AI Workflow Checklist

Modül veya özellik yazarken:

- [ ] `AGENTS.md` okundu
- [ ] `modules/MODULE_DEVELOPER_GUIDE.md` okundu
- [ ] Benzer modül incelendi
- [ ] Tema dosyası değiştirilmedi (hook kullanıldı)
- [ ] `ModuleBase` extend edildi, doğru sınıf adı
- [ ] `install.sql` / `uninstall.sql` eklendi (gerekirse)
- [ ] Gizli dosyalar commit edilmedi
