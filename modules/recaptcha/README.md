# Google reCAPTCHA Modülü

İletişim formu, müşteri giriş/kayıt (sayfa + modal) ve admin giriş ekranında Google reCAPTCHA doğrulaması sağlar.

## Kurulum adımları

### 1. Google reCAPTCHA anahtarlarını alın

1. [Google reCAPTCHA Admin](https://www.google.com/recaptcha/admin) adresine gidin.
2. **+** ile yeni site ekleyin.
3. **Etiket:** Mağaza adınız (ör. `FShop Mağaza`).
4. **reCAPTCHA türü:**
   - **v3 (önerilen):** Kullanıcıya kutu göstermez; arka planda skor üretir.
   - **v2:** “Ben robot değilim” kutusu gösterir.
5. **Domains** alanına sitenizi ekleyin:
   - Canlı: `fyazilim.com`, `www.fyazilim.com`
   - Yerel test: `localhost`
6. Kaydedin; **Site Key** ve **Secret Key** değerlerini kopyalayın.

> v3 ve v2 anahtarları birbirinin yerine kullanılamaz. Admin panelinde seçtiğiniz sürümle aynı türde anahtar oluşturun.

### 2. Modülü FShop’a kurun

1. `modules/recaptcha/` klasörünün projede olduğundan emin olun.
2. Admin panele girin → **Modüller**.
3. **Google reCAPTCHA** satırında **Kur** (veya **Yükle**) tıklayın.
4. Kurulum sonrası **Yapılandır** (veya `/admin/module-recaptcha`) sayfasını açın.

### 3. Ayarları yapın

| Alan | Açıklama |
|------|----------|
| reCAPTCHA etkin | Modülü açar/kapatır |
| Site Key | Google’dan aldığınız public anahtar |
| Secret Key | Google’dan aldığınız gizli anahtar |
| Sürüm | v3 (önerilen) veya v2 |
| v3 skor eşiği | 0.5 varsayılan; bot şüphesi yüksekse 0.6–0.7 deneyin |
| Form seçenekleri | İletişim / giriş / kayıt / admin — ayrı ayrı açılabilir |

**Kaydet**’e basın.

### 4. Test edin

| Form | URL |
|------|-----|
| İletişim | `/contact` |
| Giriş | `/login` veya header’daki giriş modalı |
| Kayıt | `/register` veya modal |
| Admin | `/admin/login` |

Başarılı kurulumda v3’te ekstra kutu görünmez; v2’de form üstünde reCAPTCHA kutusu çıkar.

## Sorun giderme

| Belirti | Çözüm |
|---------|--------|
| CAPTCHA hiç görünmüyor | Modül kurulu ve **etkin** mi? Site/Secret key doğru mu? |
| “Captcha verification failed” | Secret key yanlış veya domain Google Console’da kayıtlı değil |
| v3’te sürekli red | Skor eşiğini 0.4’e düşürün; localhost’ta skor düşük olabilir |
| Admin girişte çalışmıyor | **Admin giriş** kutusu işaretli mi? |

## Teknik notlar

- Doğrulama `form.captcha.validate` hook’u ile yapılır.
- Token alanları: `recaptcha_token` (v3) veya `g-recaptcha-response` (v2).
- Modül tema dosyalarına dokunmadan `{$hooks.contact_form}` vb. hook’larla widget ekler.

## Kaldırma

Admin → Modüller → **Kaldır**. `recaptcha_settings` tablosu silinir; tema hook satırları zararsız kalır (boş çıktı).
