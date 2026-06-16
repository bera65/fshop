# FShop

Açık kaynak PHP e-ticaret altyapısı (Smarty, Bootstrap, PDO/MySQL).

DEMO : https://fyazilim.com/fshop/

## Gereksinimler

- PHP 7.4+ (GD, PDO MySQL, mbstring)
- MySQL 5.7+ / MariaDB 10.3+
- Apache `mod_rewrite` etkin

## Kurulum (önerilen)

1. Dosyaları sunucuya yükleyin.
2. Boş bir MySQL veritabanı oluşturun (ör. `fshop`).
3. Tarayıcıda kurulum sihirbazını açın:
   - Kök dizin: `https://siteadresiniz.com/install/`
   - Alt klasör: `https://siteadresiniz.com/fshop/install/`
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

## Yerel geliştirme (WAMP)

1. Projeyi `www/fshop/` altına koyun.
2. `http://localhost/fshop/install/` adresinden kurun.
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

Modüller `modules/` klasöründen yüklenir. Dokümantasyon: [modules/README.md](modules/README.md)

## Lisans

Açık kaynak — proje sahibinin belirlediği lisans geçerlidir.
