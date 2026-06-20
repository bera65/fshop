# FriSay

Açık kaynak PHP e-ticaret altyapısı (Smarty, Bootstrap, PDO/MySQL).
Frisay is a modern open-source e-commerce platform built with PHP, Smarty and MySQL, designed as a lightweight alternative to PrestaShop and OpenCart.

<<<<<<< HEAD
=======
# Demo
- Site : https://fyazilim.com/fshop/
- Admin : https://fyazilim.com/fshop/admin/
- KA: admin@fyazilim.com
- ŞF: admin123

## Gereksinimler

- PHP 7.4+ (GD, PDO MySQL, mbstring)
- MySQL 5.7+ / MariaDB 10.3+
- Apache `mod_rewrite` etkin

## Kurulum (önerilen)

1. Dosyaları sunucuya yükleyin.
2. Boş bir MySQL veritabanı oluşturun (ör. `fshop`).
3. Tarayıcıda kurulum sihirbazını açın:
   - Kök dizin: `https://siteadresiniz.com/install/`
   - Alt klasör: `https://siteadresiniz.com/frisay/install/`
4. Adımları izleyin:
   - Sistem gereksinimleri
   - Veritabanı bilgileri
   - Site adresi + admin hesabı
   - İsteğe bağlı demo veriler
5. Kurulum bitince `config/env.php` ve `config/installed.lock` oluşur.

### Manuel kurulum (alternatif)

```bash
mysql -u root -p fshop < install/schema.sql
mysql -u root -p fshop < install/seed_demo.sql
cp config/env.example.php config/env.php
```

Ardından `config/env.php` ve admin şifresini düzenleyin.

## Döviz cron

USD/EUR/altın fiyatlı ürünler için periyodik güncelleme:

```
GET /api/cron.php?action=currency&token=SHOP_TOKEN
```

`SHOP_TOKEN` değerini Admin → Ayarlar veya `settings` tablosundan alın. Kurulum son ekranda örnek URL gösterilir.

## Web API (uzaktan entegrasyon)

**Tam kılavuz:** [docs/WEBAPI.md](docs/WEBAPI.md) — curl / PowerShell / Postman örnekleri, sipariş ve ürün test senaryoları.

Admin → **Ayarlar** → **Web API** bölümünden API anahtarı oluşturun.

**Kimlik doğrulama** (birini kullanın):

- Header: `X-API-Key: ANAHTARINIZ`
- Header: `Authorization: Bearer ANAHTARINIZ`
- Query: `?api_key=ANAHTARINIZ`

**Base URL:** `https://siteadresiniz.com/api/v1/` (WAMP: `http://localhost/fshop/api/v1/`)

### Siparişler

| İşlem | Method | Endpoint |
|-------|--------|----------|
| Liste | GET | `/api/v1/orders?page=0&size=30&status=1` |
| Tarih filtresi | GET | `date_from=2026-06-01&date_to=2026-06-17` veya `startDate` / `endDate` (ms) |
| Detay | GET | `/api/v1/orders/{id}` |
| Güncelle | PATCH | `/api/v1/orders/{id}` body: `status`, `cargoCompany`, `trackingNumber` |

Liste yanıtı: `totalElements`, `totalPages`, `page` (0 tabanlı), `size`, `content[]`

Her siparişte: `orderNumber`, `shipmentAddress`, `invoiceAddress`, `lines[]`, `cargoCompany`, `trackingNumber`, `packageTotalPrice`, ...

Checkout’ta fatura alanları: firma adı, vergi dairesi, vergi no / TCKN.

### Kategoriler ve markalar

| İşlem | Method | Endpoint |
|-------|--------|----------|
| Kategori listesi | GET | `/api/v1/categories` |
| Marka listesi | GET | `/api/v1/brands` |

Ürün eklerken `category_id` yerine `category` veya `category_name`, `brand_id` yerine `brand` veya `brand_name` gönderebilirsiniz. Yoksa otomatik oluşturulur.

```json
{
  "name": "Yeni Ürün",
  "category": "Vitamin & Takviye",
  "brand": "Nutrof",
  "price": 199.90,
  "stock": 10,
  "active": 1
}
```

### Ürünler

| İşlem | Method | Endpoint |
|-------|--------|----------|
| Liste | GET | `/api/v1/products?page=1&q=arama` |
| Detay | GET | `/api/v1/products/{id}` |
| Ekle | POST | `/api/v1/products` |
| Güncelle | PATCH | `/api/v1/products/{id}` |
| Hızlı güncelle | PATCH | `/api/v1/products/{id}/quick` (sadece fiyat, stok, active) |
| Sil | DELETE | `/api/v1/products/{id}` |
| Görsel | POST | `/api/v1/products/{id}/image` (dosya, `image_url` veya base64) |

`description_html`: HTML ürün açıklaması. `active`: `1` aktif, `0` pasif.

**Örnek ürün ekleme (JSON):**

```json
{
  "name": "Yeni Ürün",
  "category_id": 1,
  "brand_id": 1,
  "price": 199.90,
  "stock": 50,
  "active": 1,
  "barcode": "8690000000999",
  "stock_code": "URN001"
}
```

Alternatif alan adları: `product_name`, `id_category`, `id_brand`, `product_link` (slug).

## Yerel geliştirme (WAMP)

1. Projeyi `www/frisay/` altına koyun.
2. `http://localhost/frisay/install/` adresinden kurun.
3. `.htaccess` içindeki `RewriteBase /fshop/` kurulumda otomatik ayarlanır.

## Canlıya alma kontrol listesi

- [ ] `APP_ENV=production`, `APP_DEBUG=false` (`config/env.php`)
- [ ] `SHOP_TOKEN` değiştirildi
- [ ] Admin şifresi güçlü
- [ ] HTTPS aktif
- [ ] `install/` klasörüne web erişimi kapatıldı (isteğe bağlı)
- [ ] Düzenli yedek: veritabanı + `img/products/`

## Temalar

- `templates/default` — mağaza ön yüzü
- `templates/admin` — yönetim paneli

## Modül sistemi

Modüller `modules/` klasöründen yüklenir.

| Belge | Açıklama |
|-------|----------|
| [modules/README.md](modules/README.md) | Kısa özet |
| [modules/MODULE_DEVELOPER_GUIDE.md](modules/MODULE_DEVELOPER_GUIDE.md) | Tam modül geliştirici rehberi |
| [AGENTS.md](AGENTS.md) | AI / Cursor agent giriş noktası |
| [AI_CONTEXT.md](AI_CONTEXT.md) | Proje mimarisi özeti (yapay zeka bağlamı) |

## Roadmap

- [x] Product Management
- [x] Order Management
- [x] REST API
- [x] Theme System
- [ ] Marketplace
- [ ] Mobile App
- [ ] Docker Support
- [ ] Multi Store

## Lisans

Açık kaynak — proje sahibinin belirlediği lisans geçerlidir.
