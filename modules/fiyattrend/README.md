# FiyatTrend Modülü

[FiyatTrend](https://fiyattrend.com) üzerinde ürün fiyat karşılaştırması için **Google Merchant (RSS 2.0)** uyumlu XML feed üretir.

## Kurulum

1. Admin → **Modüller** → **FiyatTrend** → **Kur**
2. **Yapılandır** sayfasına gidin (`/admin/module-fiyattrend`)
3. **XML Feed URL**'yi kopyalayın
4. [fiyattrend.com/panel](https://fiyattrend.com/panel) adresinden kayıt olun / giriş yapın
5. **Mağaza Ekle** → XML feed linkini yapıştırın

## Feed URL

```
https://SITENIZ.com/api/module.php?m=fiyattrend&action=feed&token=TOKEN
```

Token, feed'e yetkisiz erişimi engeller. Admin panelden yenilenebilir.

## XML formatı

Google Merchant Center ile aynı şablon:

- `g:id`, `title`, `link`, `description`
- `g:image_link`, `g:price`, `g:availability`, `g:condition`
- `g:brand`, `g:gtin`, `g:mpn`, `g:product_type`
- `g:shipping` (TR)

## Ayarlar

| Ayar | Açıklama |
|------|----------|
| Feed aktif | Kapalıyken URL 503 döner |
| Stok dışı ürünler | Stok 0 olanları dahil et / etme |
| Para birimi | TRY / USD / EUR |
| Önbellek | Dakika cinsinden (varsayılan 360) |
| Hariç kategoriler | Virgülle ayrılmış `id_category` listesi |

## Google Shopping modülü ile fark

- **FiyatTrend** — fiyattrend.com paneline özel kurulum rehberi ve feed endpoint
- **Google Shopping** — Google Merchant Center için ayrı token ve ayarlar

İkisi birlikte kurulabilir; feed URL'leri farklıdır.

## API

| Endpoint | Açıklama |
|----------|----------|
| `action=feed&token=...` | Public XML (FiyatTrend çeker) |
| `action=preview` | Admin — ilk 5 ürün JSON |
| `action=regenerate` | Admin POST — önbelleği yenile |
