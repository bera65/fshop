# FShop sürüm güncelleme

Kanal sabittir: **GitHub Releases** → [`bera65/frisay`](https://github.com/bera65/frisay). Kullanıcı ayarı yoktur.

Release asset adı: **`frisay-2.5.6.zip`** veya **`fs-2.5.6.zip`** (eski `fshop-*.zip` de kabul edilir). Asset yoksa kaynak zipball kullanılır.

> WAMP notu: HTTPS için `core/cacert.pem` (Mozilla CA) pakette gelir; SSL hatası alırsanız bu dosyanın mevcut olduğundan emin olun.


## Mağaza sahibi (tek tık)

1. Yedek alın (DB + `config/env.php` + `img/`).
2. Admin → **Güncelleme** (menüde sarı rozet varsa yeni sürüm vardır).
3. **Şimdi güncelle** — paket iner, dosyalar kopyalanır (`env.php` / `img/` korunur), migration çalışır.

Admin paneli yaklaşık 12 saatte bir GitHub’ı sessizce kontrol eder; rozet otomatik çıkar. İsterseniz **Güncellemeleri denetle** ile anında kontrol edin.

Elle FTP attıysanız aynı ekranda **Veritabanı güncellemesini uygula** yeter.

## Siz (yayıncı): yeni sürüm nasıl çıkarılır?

Repo: https://github.com/bera65/frisay

1. Kodda `core/FShop.php` → `VERSION` artır (örn. `2.5.6`).
2. Gerekirse `upgrade/migrations/2.5.6.php` ekle (`up()` idempotent).
3. `CHANGELOG.md` güncelle; `install/schema.sql` içindeki `FS_VERSION` seed’ini eşitle.
4. `php tools/build-release.php` ile ZIP üret (**kökte** `index.php`, `core/`, `templates/`…; `config/env.php` ve `img/products` eklenmez). Çıktı: `dist/frisay-{sürüm}.zip`
5. GitHub → **Releases** → **Draft a new release**
   - Tag: `2.5.6` veya `v2.5.6`
   - Asset olarak `frisay-2.5.6.zip` veya `fs-2.5.6.zip` yükle (tercih edilir)
   - Asset yoksa installer zipball kullanır (yine çalışır)
6. Release’i **Publish** et.

Mağazalar bir sonraki kontrolde (veya “Denetle” ile) sürümü görür.

### ZIP’e koymayın

- `config/env.php`, `config/installed.lock`
- `img/products`, `img/returns`, `img/invoices`, müşteri medyası
- `cache/` içeriği, `.git/`

## Korumalı yollar (kurulumda üzerine yazılmaz)

`config/env.php`, `config/installed.lock`, `img/`, `cache/`

## Teknik

| Parça | Dosya |
|--------|--------|
| Kanal | `UpdateChecker::GITHUB_REPO = bera65/frisay` |
| Kurulum | `core/UpdateInstaller.php` |
| Migration | `core/Upgrade.php` |
| Staging | `cache/updates/` |
