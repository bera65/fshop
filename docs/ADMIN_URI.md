# Admin URL gizleme (`ADMIN_URI`)

FShop’da yönetim paneli **fiziksel olarak** her zaman `admin/` klasöründedir. Public URL slug’ını güvenlik için değiştirebilirsiniz (PrestaShop tarzı obscurity).

## Kurulum

1. `config/env.php` dosyasına ekleyin (veya `env.example.php`’den kopyalayın):

```php
'ADMIN_URI' => 'bo_9xK2m7',
```

Kurallar:
- Yalnızca harf, rakam, `_`, `-`
- `blog`, `api`, `marka` gibi mağaza yollarıyla çakışmayan benzersiz bir slug seçin
- Klasörü (`admin/`) rename **etmeyin**

2. Panele **yeni URL** ile bir kez girin, örn. `https://site.com/bo_9xK2m7/`  
   (alt klasörde: `https://localhost/fshop/bo_9xK2m7/`)

3. İlk admin isteğinde `.htaccess` içindeki `# BEGIN FSHOP_ADMIN` bloğu otomatik güncellenir.
   - Eski `/admin` engeli yalnızca `/{REWRITE_BASE}/admin` isteğini keser (`templates/admin/css` etkilenmez).
   - Internal rewrite (`admin/index.php`) `THE_REQUEST` sayesinde 404 olmaz.

4. `ADMIN_URI` ≠ `admin` ise tarayıcıdan gelen eski `/admin` adresi **404** döner.

5. Yeni adresi yer imlerine kaydedin.

## Teknik

| Parça | Rol |
|-------|-----|
| `Admin::uri()` | Env’den sanitize edilmiş slug |
| `Admin::url()` / `$adminUrl` | Tüm panel linkleri |
| `Admin::syncHtaccessRewrite()` | Rewrite kurallarını yazar |

Asıl güvenlik: güçlü şifre, login rate limit, HTTPS, mümkünse IP kısıtı. Gizli URL yalnızca bot gürültüsünü azaltır.
