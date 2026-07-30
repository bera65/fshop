# FShop Web API — Kullanım Kılavuzu

JSON tabanlı REST API ile uzaktan **sipariş çekme**, **ürün ekleme/güncelleme/silme**, **kategori ve marka listeleme** yapabilirsiniz.

---

## 1. Kurulum (ilk adım)

1. Admin panele girin
2. Sol menüden **API** sayfasına gidin
3. **Web API aktif** olsun
4. **Yeni API oluştur** ile partner adı verin (ör. `Paroner`, `BizimHesap`)
5. Yetkileri işaretleyin:
   - Siparişleri oku / çek
   - Siparişleri güncelle
   - Ürünleri oku / ekle / düzenle / sil
   - Kategorileri oku, Markaları oku
6. Oluşan **API Key** değerini kopyalayın

> Her partner için ayrı anahtar açabilirsiniz. Örn. BizimHesap’a sadece «Siparişleri oku» verin; Paroner’a ürün + sipariş yetkisi verin.
>
> API kapalıysa `403 Web API kapalı` hatası alırsınız. Anahtar yoksa veya yetkisiz istekte `403` döner.

---

## 2. Temel bilgiler

| Öğe | Değer |
|-----|-------|
| **Base URL (yerel)** | `http://localhost/fshop/api/v1/` |
| **Base URL (canlı)** | `https://siteadresiniz.com/api/v1/` |
| **İçerik tipi** | `Content-Type: application/json` (POST/PATCH için) |
| **Yanıt formatı** | JSON (`UTF-8`) |

### Kimlik doğrulama

Aşağıdaki yöntemlerden **birini** kullanın:

```http
X-API-Key: BURAYA_API_ANAHTARINIZ
```

```http
Authorization: Bearer BURAYA_API_ANAHTARINIZ
```

```
?api_key=BURAYA_API_ANAHTARINIZ
```

### Hata kodları

| HTTP | Anlamı |
|------|--------|
| 200 | Başarılı |
| 201 | Oluşturuldu (yeni ürün) |
| 400 | Geçersiz istek (ör. sipariş durumu) |
| 403 | API kapalı veya geçersiz anahtar |
| 404 | Kayıt bulunamadı |
| 405 | Desteklenmeyen HTTP metodu |
| 422 | Doğrulama hatası (eksik alan vb.) |
| 503 | API anahtarı tanımlı değil |

Hata gövdesi örneği:

```json
{
  "success": false,
  "message": "Geçersiz API anahtarı"
}
```

---

## 3. Hızlı test (curl)

Aşağıdaki örneklerde `API_KEY` yerine kendi anahtarınızı yazın.

**Windows PowerShell / CMD** ve **Postman** ile aynı şekilde çalışır.

### Bağlantı testi — kategori listesi

```bash
curl -s -H "X-API-Key: API_KEY" "http://localhost/fshop/api/v1/categories"
```

### Sipariş listesi

```bash
curl -s -H "X-API-Key: API_KEY" "http://localhost/fshop/api/v1/orders?page=0&size=10"
```

### Ürün listesi

```bash
curl -s -H "X-API-Key: API_KEY" "http://localhost/fshop/api/v1/products?page=1&limit=10"
```

---

## 4. Siparişler

Sipariş yanıtları **Trendyol benzeri** yapıdadır: `totalElements`, `content[]`, her siparişte `shipmentAddress`, `invoiceAddress`, `lines[]`.

### Sipariş durum kodları

| Kod | Admin etiketi | API `status` adı |
|-----|---------------|------------------|
| 1 | Ödeme Bekliyor | `AwaitingPayment` |
| 2 | Hazırlanıyor | `Picking` |
| 3 | Kargoda | `Shipped` |
| 4 | Teslim Edildi | `Delivered` |
| 5 | İptal Edildi | `Cancelled` |

`status=0` veya parametre gönderilmezse **tüm durumlar** listelenir.

### GET — Sipariş listesi

```
GET /api/v1/orders
```

| Parametre | Açıklama | Varsayılan |
|-----------|----------|------------|
| `page` | Sayfa numarası (**0 tabanlı**) | `0` |
| `size` veya `limit` | Sayfa başına kayıt (max 100) | `30` |
| `status` | Durum kodu (1–5), `0` = hepsi | `0` |
| `date_from` | Başlangıç (`2026-06-01` veya `2026-06-01 10:00:00`) | — |
| `date_to` | Bitiş (`2026-06-17` veya tam datetime) | — |
| `startDate` | Başlangıç (Unix ms, Trendyol uyumlu) | — |
| `endDate` | Bitiş (Unix ms) | — |

**Örnek — son 7 gün, hazırlanan siparişler:**

```bash
curl -s -H "X-API-Key: API_KEY" \
  "http://localhost/fshop/api/v1/orders?page=0&size=20&status=2&date_from=2026-06-10&date_to=2026-06-17"
```

**Örnek yanıt (kısaltılmış):**

```json
{
  "totalElements": 2,
  "totalPages": 1,
  "page": 0,
  "size": 20,
  "content": [
    {
      "id": 15,
      "orderNumber": "FS-20260617-0015",
      "customerFirstName": "Ali",
      "customerLastName": "Yılmaz",
      "customerEmail": "ali@ornek.com",
      "customerPhone": "05551234567",
      "packageTotalPrice": 899.00,
      "paymentMethod": "bankwire",
      "paymentLabel": "Havale / EFT",
      "statusCode": 2,
      "status": "Picking",
      "statusLabel": "Hazırlanıyor",
      "commercial": false,
      "orderDate": 1718611200000,
      "shipmentAddress": {
        "firstName": "Ali",
        "lastName": "Yılmaz",
        "city": "İstanbul",
        "district": "Kadıköy",
        "address1": "Moda Cad. No:5",
        "fullAddress": "İstanbul / Kadıköy — Moda Cad. No:5",
        "phone": "05551234567",
        "countryCode": "TR"
      },
      "invoiceAddress": { "...": "..." },
      "lines": [
        {
          "lineId": 42,
          "contentId": 1,
          "productName": "SolNutrof Total 30 Kapsül",
          "quantity": 1,
          "stockCode": "SOLNUTROF30",
          "barcode": "8690000000011",
          "lineUnitPrice": 899.00,
          "lineAmount": 899.00,
          "vatRate": 20.0,
          "orderLineItemStatusName": "Picking"
        }
      ]
    }
  ]
}
```

Kurumsal siparişlerde `commercial: true`, `invoiceAddress.taxOffice` ve `taxNumber` dolu gelir.

### GET — Tek sipariş

```
GET /api/v1/orders/{id}
```

```bash
curl -s -H "X-API-Key: API_KEY" "http://localhost/fshop/api/v1/orders/15"
```

Liste ile aynı alanları tek nesne olarak döner (sarmalayıcı yok).

### PATCH — Sipariş güncelle (durum / kargo)

```
PATCH /api/v1/orders/{id}
Content-Type: application/json
```

En az bir alan gönderin: `status`, `cargoCompany`, `trackingNumber` (snake_case alternatifleri de kabul edilir).

**Sadece durum:**

```json
{"status": 2}
```

**Kargoya verildi + takip no (kargo entegrasyonu):**

```json
{
  "status": 3,
  "cargoCompany": "Yurtiçi Kargo",
  "trackingNumber": "YT123456789"
}
```

**Sadece takip numarası güncelle:**

```json
{
  "trackingNumber": "YT987654321"
}
```

```bash
curl -s -X PATCH -H "X-API-Key: API_KEY" -H "Content-Type: application/json" \
  -d "{\"status\":3,\"cargoCompany\":\"Yurtiçi Kargo\",\"trackingNumber\":\"YT123456789\"}" \
  "http://localhost/fshop/api/v1/orders/15"
```

| Alan | Açıklama |
|------|----------|
| `status` | 1 ödeme bekliyor · 2 hazırlanıyor · 3 kargoda · 4 teslim · 5 iptal |
| `cargoCompany` | Kargo firması adı |
| `trackingNumber` | Kargo takip numarası |

Liste ve detay yanıtında `cargoCompany` ve `trackingNumber` alanları döner.
Detayda ayrıca `invoice` alanı olabilir: `{ "type": "url"|"file", "url": "...", "name": "..." }` veya fatura yoksa `null`.

Başarılı yanıt:

```json
{
  "success": true,
  "message": "Sipariş güncellendi",
  "content": { "... güncel sipariş ..." }
}
```

### POST — Sipariş faturası (yalnızca URL)

```
POST /api/v1/orders/{id}/invoice
Content-Type: application/json
```

```json
{
  "url": "https://example.com/fatura.pdf",
  "name": "Fatura"
}
```

`name` isteğe bağlıdır. Dosya yükleme Web API üzerinden desteklenmez.

```bash
curl -s -X POST -H "X-API-Key: API_KEY" -H "Content-Type: application/json" \
  -d "{\"url\":\"https://example.com/fatura.pdf\",\"name\":\"Fatura\"}" \
  "http://localhost/fshop/api/v1/orders/15/invoice"
```

### DELETE — Sipariş faturasını kaldır

```
DELETE /api/v1/orders/{id}/invoice
```

```bash
curl -s -X DELETE -H "X-API-Key: API_KEY" \
  "http://localhost/fshop/api/v1/orders/15/invoice"
```

---

## 5. Ürünler

### GET — Ürün listesi

```
GET /api/v1/products
```

| Parametre | Açıklama | Varsayılan |
|-----------|----------|------------|
| `page` | Sayfa (**1 tabanlı** — siparişlerden farklı!) | `1` |
| `limit` | Sayfa başına (max 100) | `30` |
| `q` | Arama (ad, stok kodu, barkod) | — |
| `category` | Kategori ID filtresi | `0` |
| `brand` | Marka ID filtresi | `0` |
| `active` | `1` aktif, `0` pasif, `-1` hepsi | `-1` |

```bash
curl -s -H "X-API-Key: API_KEY" \
  "http://localhost/fshop/api/v1/products?page=1&limit=10&q=vitamin&active=1"
```

Yanıt:

```json
{
  "success": true,
  "data": [ { "id": 1, "name": "...", "price": 899, "stock": 120, "...": "..." } ],
  "meta": { "total": 5, "page": 1, "limit": 10, "pages": 1 }
}
```

### GET — Tek ürün (detaylı)

```
GET /api/v1/products/{id}
```

Detayda ek alanlar: `description`, `short_description`, `vat`, `doviz`, `images[]`, `cargo_day`, `label` vb.

```bash
curl -s -H "X-API-Key: API_KEY" "http://localhost/fshop/api/v1/products/1"
```

### POST — Yeni ürün ekle

```
POST /api/v1/products
Content-Type: application/json
```

**Zorunlu alanlar:** ürün adı, kategori, marka, fiyat.

Kategori/marka için **ID** veya **isim** gönderebilirsiniz. İsim yoksa otomatik oluşturulur.

**Örnek 1 — ID ile:**

```bash
curl -s -X POST -H "X-API-Key: API_KEY" -H "Content-Type: application/json" \
  -d "{\"name\":\"Test Ürün API\",\"category_id\":3,\"brand_id\":1,\"price\":149.90,\"stock\":25,\"active\":1,\"barcode\":\"8690000000999\",\"stock_code\":\"API-TEST-01\"}" \
  "http://localhost/fshop/api/v1/products"
```

**Örnek 2 — kategori/marka adı ile (önerilen entegrasyon):**

```json
{
  "name": "Omega 3 Balık Yağı 90 Kapsül",
  "category": "Vitamin & Takviye",
  "brand": "Nutrof",
  "price": 249.90,
  "old_price": 299.90,
  "stock": 50,
  "active": 1,
  "barcode": "8690000000888",
  "stock_code": "OMEGA3-90",
  "short_description": "Günlük omega-3 desteği",
  "description": "<p>90 kapsül, soğuk sıkım.</p>",
  "vat": 10,
  "cargo_day": 2,
  "label": "Yeni",
  "desi": 1
}
```

**Dövizli fiyat** (USD/EUR/TRY):

```json
{
  "name": "İthal Ürün",
  "category": "Elektronik",
  "brand": "TechnoMark",
  "doviz": "usd",
  "doviz_price": 29.99,
  "doviz_old_price": 34.99,
  "stock": 10,
  "active": 1
}
```

`doviz`: `try`, `usd`, `eur`, `xau`. TRY dışında kur otomatik hesaplanır.

**Alan adı eşlemeleri** (ikisi de kabul edilir):

| API (kısa) | İç alan |
|------------|---------|
| `name` | `product_name` |
| `slug` | `product_link` |
| `category_id` | `id_category` |
| `brand_id` | `id_brand` |
| `category` / `category_name` | otomatik ID çözümü |
| `brand` / `brand_name` | otomatik ID çözümü |

Başarılı yanıt: HTTP **201**

```json
{
  "success": true,
  "message": "Ürün eklendi",
  "data": { "id": 6, "name": "...", "...": "..." }
}
```

### PATCH — Hızlı güncelleme (fiyat / stok / durum)

Tam ürün gövdesi göndermeden yalnızca fiyat, stok ve aktiflik güncellemek için:

```
PATCH /api/v1/products/{id}/quick
```

Gönderdiğin alanlar güncellenir; diğerleri aynı kalır.

```json
{
  "price": 149.90,
  "old_price": 199.90,
  "stock": 25,
  "active": 1
}
```

`old_price` isteğe bağlıdır; gönderilmezse mevcut değer korunur. Pazaryeri entegrasyonları için `list_price` alanı da `old_price` ile aynı kabul edilir.

Sadece stok örneği:

```bash
curl -s -X PATCH -H "X-API-Key: API_KEY" -H "Content-Type: application/json" \
  -d "{\"stock\": 10}" \
  "http://localhost/fshop/api/v1/products/6/quick"
```

### PATCH — Ürün güncelle (tam)

```
PATCH /api/v1/products/{id}
```

> **Önemli:** Güncelleme tüm zorunlu alanları bekler (ad, kategori, marka). Kısmi güncelleme yoktur — önce `GET /products/{id}` ile mevcut veriyi alın, değiştirdiğiniz alanlarla birlikte tam gövde gönderin.

```bash
curl -s -X PATCH -H "X-API-Key: API_KEY" -H "Content-Type: application/json" \
  -d "{\"name\":\"Test Ürün API\",\"category_id\":3,\"brand_id\":1,\"price\":139.90,\"stock\":30,\"active\":1}" \
  "http://localhost/fshop/api/v1/products/6"
```

Sadece stok güncellemek için örnek akış:

```bash
# 1) Mevcut ürünü al
curl -s -H "X-API-Key: API_KEY" "http://localhost/fshop/api/v1/products/6"

# 2) Yanıttaki data ile birleştirip stock değiştirerek PATCH gönder
```

### DELETE — Ürün sil

```
DELETE /api/v1/products/{id}
```

```bash
curl -s -X DELETE -H "X-API-Key: API_KEY" "http://localhost/fshop/api/v1/products/6"
```

```json
{
  "success": true,
  "message": "Ürün silindi"
}
```

### POST — Ürün görseli yükle

```
POST /api/v1/products/{id}/image
```

**Multipart** (önerilen):

```bash
curl -X POST -H "X-API-Key: API_KEY" \
  -F "image=@C:/path/urun.jpg" \
  "http://localhost/fshop/api/v1/products/6/image"
```

**JSON base64:**

```json
{ "image_base64": "data:image/jpeg;base64,/9j/4AAQ..." }
```

**URL ile indir:**

```json
{ "image_url": "https://example.com/urun.jpg" }
```

```bash
curl -X POST -H "X-API-Key: API_KEY" -H "Content-Type: application/json" \
  -d "{\"image_url\":\"https://example.com/urun.jpg\"}" \
  "http://localhost/fshop/api/v1/products/6/image"
```

İlk yüklenen görsel otomatik kapak olur. JPG, PNG, WEBP desteklenir (max 5 MB).

**HTML açıklama:** `description` veya `description_html` alanına HTML gönderebilirsiniz:

```json
{
  "name": "Ürün Adı",
  "category": "Kozmetik",
  "brand": "Nutrof",
  "price": 199.90,
  "description_html": "<p><strong>Özellikler</strong></p><ul><li>Madde 1</li></ul>"
}
```

---

## 6. Kategoriler ve markalar

### GET — Kategoriler

```
GET /api/v1/categories?page=0&size=100&active=1
```

| Parametre | Varsayılan |
|-----------|------------|
| `page` | `0` (0 tabanlı) |
| `size` / `limit` | `100` (max 500) |
| `active` | `1` (sadece aktif); `0` = pasif |

```bash
curl -s -H "X-API-Key: API_KEY" "http://localhost/fshop/api/v1/categories"
```

```json
{
  "totalElements": 4,
  "content": [
    {
      "id": 3,
      "name": "Vitamin & Takviye",
      "slug": "vitamin-takviye",
      "parentId": 1,
      "parentName": "Sağlık",
      "active": true
    }
  ]
}
```

### GET — Markalar

```
GET /api/v1/brands?page=0&size=100&active=1
```

```bash
curl -s -H "X-API-Key: API_KEY" "http://localhost/fshop/api/v1/brands"
```

---

## 7. PowerShell örnekleri

PowerShell'de `Invoke-RestMethod` kullanabilirsiniz:

```powershell
$apiKey = "BURAYA_API_ANAHTARINIZ"
$base   = "http://localhost/fshop/api/v1"
$headers = @{ "X-API-Key" = $apiKey }

# Siparişler
Invoke-RestMethod -Uri "$base/orders?page=0&size=5" -Headers $headers

# Ürün ekle
$body = @{
  name     = "PowerShell Test Ürün"
  category = "Vitamin & Takviye"
  brand    = "Nutrof"
  price    = 99.90
  stock    = 5
  active   = 1
} | ConvertTo-Json

Invoke-RestMethod -Method POST -Uri "$base/products" -Headers $headers `
  -ContentType "application/json" -Body $body
```

---

## 8. Postman ile test

1. **Collection** oluşturun
2. Collection Variables:
   - `base_url` = `http://localhost/fshop/api/v1`
   - `api_key` = admin'den kopyaladığınız anahtar
3. Collection **Authorization** → Type: **API Key**
   - Key: `X-API-Key`
   - Value: `{{api_key}}`
   - Add to: **Header**
4. İstek örnekleri:
   - `GET {{base_url}}/orders?page=0&size=10`
   - `GET {{base_url}}/products?page=1`
   - `POST {{base_url}}/products` → Body → raw → JSON

---

## 9. Önerilen test senaryosu

Sırayla deneyin:

| # | İşlem | Beklenen |
|---|--------|----------|
| 1 | `GET /categories` | JSON liste, HTTP 200 |
| 2 | `GET /brands` | JSON liste, HTTP 200 |
| 3 | `GET /products?page=1` | Mevcut ürünler |
| 4 | `POST /products` | HTTP 201, yeni `id` |
| 5 | `GET /products/{id}` | Az önce eklenen ürün |
| 6 | `PATCH /products/{id}` | Fiyat/stok değişimi |
| 7 | `GET /orders?page=0` | Sipariş listesi |
| 8 | `PATCH /orders/{id}` | `status: 2` (hazırlanıyor) |
| 9 | `DELETE /products/{id}` | Test ürününü sil |

Yanlış anahtar ile istek atın → `403 Geçersiz API anahtarı` görmelisiniz.

---

## 10. Sık karşılaşılan sorunlar

| Sorun | Çözüm |
|-------|--------|
| `403 Web API kapalı` | Admin → Ayarlar → API aktif işaretli mi? |
| `503 API anahtarı yapılandırılmamış` | Anahtar oluştur butonuna basın |
| `404` rewrite hatası | `.htaccess` ve `mod_rewrite` açık mı? `RewriteBase /fshop/` doğru mu? |
| `422 Kategori ve marka seçin` | `category_id`/`brand_id` veya `category`/`brand` adı gönderin |
| `422 Ürün adı zorunludur` | PATCH'te tüm zorunlu alanları gönderin |
| Boş sipariş listesi | Mağazadan test siparişi verin veya `status=0` ile filtre kaldırın |

---

## 11. Endpoint özeti

| Kaynak | GET liste | GET tek | POST | PATCH | DELETE |
|--------|-----------|---------|------|-------|--------|
| `/orders` | ✓ | ✓ `/{id}` | — | ✓ `/{id}` | — |
| `/products` | ✓ | ✓ `/{id}` | ✓ | ✓ `/{id}` | ✓ `/{id}` |
| `/products/{id}/image` | — | — | ✓ (görsel) | — | — |
| `/products/{id}/quick` | — | — | — | ✓ (fiyat/stok/durum) | — |
| `/categories` | ✓ | — | — | — | — |
| `/brands` | ✓ | — | — | — | — |

Alternatif URL (rewrite olmadan):

```
http://localhost/fshop/api/webapi.php?route=orders&api_key=...
```
