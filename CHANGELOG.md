# Changelog

All notable changes to FShop are documented here.  
Version numbers follow [Semantic Versioning](https://semver.org/).

| Durum | Sürüm |
|--------|--------|
| **Yayında (canlı)** | **2.4.1** |
| **Geliştirme / bir sonraki yayın** | **2.5.0** (henüz yayınlanmadı) |
| **İleride** | 3.0.0 |

2.4.1’den bu yana yapılan tüm değişiklikler **2.5.0** altında toplanır. 2.5.0 yayınlandıktan sonra yeni işler 2.5.x (yama) veya 2.6.0 (küçük özellik) olarak devam eder; **3.0.0** ayrı bir büyük sürüm planıdır.

---
## [2.5.0] — Unreleased

### Added

#### New admin panel design
- **Modern dashboard** — KPI cards, chart area, operations summary, and refreshed typography (`dash-*` layout)
- **Updated admin CSS** — panel cards, tables, toast notifications, and mobile-friendly page headers
- **Lucide icons** — consistent icon set on dashboard and menu actions

#### Admin accounts and security
- **My Account** (`/admin/account`) — update profile (name, email) and change password
- **Multiple admins** — create admins, activate/deactivate accounts, and delete users from one page (at least one active admin required)
- **Custom admin URL** — hide the panel path via `ADMIN_URI` in `config/env.php`; dashboard warning when default `/admin` is used; guide: [docs/ADMIN_URI.md](docs/ADMIN_URI.md)

#### AI Assistant — broader coverage
- **Admin header AI bar** — context-aware actions on dashboard, orders, cancellations/returns, blog, CMS, SEO, product edit, and **translations**
- **Product copy** — improve title, short/long description, and SEO fields in one action
- **SEO / CMS / Blog** — generate meta and content based on the current page
- **Orders & panel summary** — AI summaries on list and detail views

#### UI Translations
- **Storefront / Admin panel tabs** — manage `lang/` (storefront, `translate`) and `lang/admin/` (admin, `adminT`) separately
- **Add new key** — register strings missing from `en.php` or `lang/admin/en.php` from the panel
- **Translate with AI** — on the translations page: fill empty rows in the target language or polish English source (`translate-ui` API)

#### AI Assistant — module reports (installed/uninstalled with the module)
- Module menu: **AI Assistant** → reports + API settings (settings tab)
- **Sales reports** — date + channel (store, POS, Trendyol) revenue summary
- **Product SEO reports** — meta and content analysis
- **Cancel / return reports** — rates, lost revenue, and prevention suggestions

#### Product Set (bundle) module
- **Set / bundle products** — sell multiple products as one pack from the admin product editor (type “Set”)
- Component picker on the product form; stock follows the lowest component; cart expands into child lines on checkout
- Optional fixed pack price override; set contents shown on the product page (`product-set` module)

#### Reviews module — post-delivery invite
- **Review reminder e-mails** — after order status **Delivered**, a reminder is queued and sent **X days later** (configurable delay)
- Admin tab: invite template, delay days, optional personal coupon (% off) attached to the e-mail
- Send queue UI and cron: `/api/module.php?m=reviews&action=cron&token=SHOP_TOKEN`

#### XML Import module
- **XML product import** — upload a file or paste a feed URL; match existing products by stock code, barcode, or name (update or create)
- Optional image refresh on update; import limit for test runs; last import summary
- **Cron / auto refresh** — scheduled feed URL via module API cron endpoint (`xml-import` module, Catalog menu)

#### Mobile App (PWA) module
- **Configurable PWA** — app name, short name, description, theme/background colors, orientation, and icons from the admin panel (`mobil-app` module)
- **Dynamic manifest & service worker** — `manifest.json` and `sw.js` are generated from site settings (correct `scope` / `start_url` for subfolder installs)
- **Mobile menu install action** — “Download mobile app” in the drawer; Chrome install prompt on Android, Safari “Add to Home Screen” hint on iOS
- **Offline page** — basic offline fallback cached by the service worker

#### Live admin notifications
- **Live new order alerts** — while the admin panel is open, new orders trigger a toast (with optional sound) and update the Orders menu badge
- **Live new message alerts** — new contact form messages trigger a toast and update the Messages badge
- **Background polling** — `/{ADMIN_URI}/live-poll` every 30 seconds; sidebar/header badges stay in sync without a full page reload
- **Click-through** — toasts link to Orders or Messages

#### Google reCAPTCHA module (`recaptcha`)
- **Form protection** — contact, customer login/register (page + auth modal), and admin login
- **v2 & v3** — checkbox (“I’m not a robot”) or invisible score-based verification; per-form enable/disable in admin
- **Core hook** — `form.captcha.validate` + `core/Captcha.php`; CSP updated for `google.com` / `gstatic.com` reCAPTCHA scripts
- Admin: `/admin/module-recaptcha` — Site Key, Secret Key, version, v3 score threshold

#### Recent Viewed Products module (`recent-viewed`)
- **Recently viewed list** — tracks product detail views in a cookie; shows a compact home-page block (image left, name + price right)
- **No core changes** — uses `product_detail` hook (silent tracking) and `home_promo_slider` hook (display on fyazilim, blue, restoran themes)
- Admin: title, display count, cookie storage limit

#### Facebook Pixel module (`facebook-pixel`)
- **Meta Pixel tracking** — PageView, ViewContent (product), AddToCart, InitiateCheckout, Purchase events
- Admin toggles per event; Pixel ID from Meta Events Manager
- Loads via `head.top` + `footer` + `product_detail` hooks; CSP updated for `connect.facebook.net`

#### Instagram Gallery module (`instagram-gallery`)
- **Homepage photo grid** — manual image URLs with optional Instagram post links; profile button
- **Easy upload** — JPG/PNG/WEBP from computer or phone; no “inspect element” image URL needed
- **Instagram post import** — paste `instagram.com/p/...` or `/reel/...` link; thumbnail fetched automatically via oEmbed
- New display hook **`home_bottom`** — rendered at the bottom of home.tpl (fyazilim, blue, restoran)
- Admin CRUD for gallery items

#### FiyatTrend module (`fiyattrend`)
- **Price comparison feed** — Google Merchant (RSS 2.0) compatible XML for [fiyattrend.com](https://fiyattrend.com)
- **Secure feed URL** — token-protected endpoint for FiyatTrend panel XML import
- Admin: feed preview/regenerate, currency, stock filters, category exclusions; setup guide links to [fiyattrend.com/panel](https://fiyattrend.com/panel)

#### Tax rates (VAT) — admin management
- **`taxes` table + `core/Tax.php`** — add, edit, delete, and set default VAT rates from admin (`/admin/taxes`)
- **Product editor** — KDV dropdown loads from defined rates (replaces fixed 1/10/20 list)
- First run seeds **1%, 10%, 20%**; changing a rate updates products using the old rate
- **Fresh install** — `install/patch_taxes.sql` runs automatically after `schema.sql` in the installer

#### Performance & PageSpeed (storefront)
- **Static asset caching** — `.htaccess` sets 1-year browser cache for CSS, JS, images, and fonts (`Cache-Control: immutable`)
- **Asset versioning** — `Performance::versionedUrl()` and Smarty `|asset_url` append `?v={version}.{mtime}` so long cache is safe after updates
- **LCP helper** — `core/Lcp.php` resolves the largest-content image per page (home slider/banner, product, category)
- **LCP preload** — `<link rel="preload" as="image" fetchpriority="high">` in theme headers when an LCP URL is known

#### Core catalog filtering
- **`CatalogFilter`** — category pages auto-derive filters from products in the category: brand, price range, stock status, discount yes/no, and variation facets (JSON-aware SQL)
- **Theme filter UI** — updated `catalogFilters.tpl` / `catalogToolbar.tpl` on fyazilim, blue, nova, and restoran themes (no module hook required on category pages)

#### Shopmore theme (`templates/shopmore/`)
- **New storefront theme** — mobile-first layout inspired by modern app-style commerce: purple accent, rounded cards, pill search bar, category circles, horizontal deal row, trust bar, desktop icon nav, mobile bottom navigation
- **Homepage** — hero slider hook, category shortcuts, discounted products with countdown, best sellers, category block
- **Category pages** — core `CatalogFilter` sidebar + mobile offcanvas
- Activate from **Admin → Templates → Shopmore**

#### Abandoned cart module (`abandoned-cart`)
- **Cart tracking** — syncs from `cart.changed`; marks converted on `order.placed`
- **Admin page** — abandoned/active/converted list, manual reminder modal (optional coupon)
- **Cron** — `/api/cron.php?action=abandoned-cart&token=SHOP_TOKEN` for automatic reminders

#### Stock analysis (core admin)
- **`/admin/stock-analysis`** — Sales menu: daily sales rate, estimated stock lifetime, critical and out-of-stock best sellers
- **Quick stock add** — inline stock update from the analysis table
- **`stock_empty_at`** column on products (via `Product::ensureSchema()`)

#### Module admin improvements
- **KPI cards** on `/admin/modules` — total, installed, active, inactive, not installed counts
- **ZIP upload** — upload modal with security notice and FriSay store link; extracts to `modules/`; post-upload redirect highlights the new module for install

#### Alert module (`alert`)
- **Admin e-mail alerts** (toggleable) — new order notification; critical stock when quantity drops below a configurable threshold
- **Back in stock (storefront)** — “Notify me when back in stock” form on out-of-stock product pages (`product_detail` hook); e-mail sent when stock returns
- **Admin panel notifications** — order and critical-stock entries in `admin_notifications`
- **Subscription list** — admin tab for pending/sent customer stock-alert requests

#### Install package & CI
- **Install wizard restored** — `install/index.php`, `schema.sql`, `seed_demo.sql`, `tools/verify-install.php`
- **GitHub Actions** — `.github/workflows/verify-install.yml` runs install verification on push/PR

#### Built-in Marketplace (Pazaryeri) — core
- **Core Pazaryeri menu** — Products, Orders, Q&A, and Settings under admin (no Trendyol module required for day-to-day use)
- **Trendyol as first platform** — product sync, price/stock updates, order import, questions; Hepsiburada / N11 shown as “coming soon” tabs
- **Code layout** — `core/Marketplace.php`, `core/MarketplaceAdmin.php`, `core/marketplace/*`, `api/marketplace.php`, admin templates `marketplace-*.tpl`
- **Trendyol settings simplified** — API credentials only (Merchant ID, API Key, Secret); **FiyatTrend** token on its own settings tab
- **Per-product mapping** — brand, category, and attributes chosen on the product / Pazaryeri panel (no global default category/brand or category-map UI)
- **Order import → auto-link** — when importing orders, matching FShop products without a Trendyol link get linked (barcode + order price) and appear under Pazaryeri → Products
- **Legacy module** — `modules/trendyol` kept as a deprecated stub that redirects to core Pazaryeri URLs

#### Customer browser notifications (`customer-notify` + OneSignal)
- **Web push via OneSignal** — order status and admin broadcasts can reach the customer’s browser even when the site tab is closed
- **Consent banner** — logged-in customers see a Yes/No prompt; `OneSignal.login(userId)` binds `external_id` for targeted push
- **Admin** — “Bildirim Gönder” broadcast, history, and **Tarayıcı Bildirimi** settings (modes: shipped only / all status + broadcasts / broadcast only; App ID, Safari Web ID, REST API Key; test push)
- **Local queue fallback** — if OneSignal REST is not configured, pending pushes can still be delivered while the customer is on the site (poll)
- **Core wiring** — `Notification::dispatchWebPush()` calls `CustomerNotifyPush` when the module is enabled (order status + admin send), not only the old `mobil-app` Web Push path

### Changed

- **Site version** — `FShop::VERSION` 2.5.0 (admin footer)
- **Marketplace** — Trendyol product/order/Q&A flows moved from module UI into core Pazaryeri; settings no longer store cargo company, delivery defaults, or category map as primary config
- **Customer notifications** — browser push is driven by `customer-notify` + OneSignal; in-app `user_notifications` and e-mail behaviour unchanged
- **AI Assistant module** — dashboard side panel removed; reports and settings on the module page; admin APIs fixed to load `Admin.php`
- **Translations page** — English as source language; target language picker; paginated editing and missing-translation filter
- **Module front assets** — CSS/JS load only when declared in `$frontStylesheets` / `$frontScripts` or via `head.assets` hook; empty array no longer auto-loads every file in `assets/css/` (avoids loading admin-only styles on the storefront)
- **Theme asset URLs** — fyazilim, blue, and restoran headers/footers use versioned CSS/JS links
- **CSS bundle (optional)** — Admin → Performance → “CSS birleştirme” merges theme stylesheets into one cached file (`cache/assets/css/`); module CSS stays separate
- **Hero slider (PWA / LCP)** — first slide uses a real `<img>` with `fetchpriority="high"` instead of CSS `background-image` only
- **New hook `home_bottom`** — homepage bottom section for modules (e.g. Instagram gallery)
- **Product & catalog images** — main product image and first grid/slider card load eagerly with `fetchpriority="high"`; remaining images keep `loading="lazy"`
- **`Product::patchQuick()`** — fires `product.updated` when stock is updated (alert module and stock hooks run from quick stock edits)
- **Module upload redirect** — double redirect after ZIP extract so the module list is fresh before search/filter highlights the uploaded module
- **`catalog-filters` module removed** — redundant with core `CatalogFilter`; uninstall the module from admin if it was previously installed

### Fixed

- **Marketplace API** — `Admin.php` loaded from `api/marketplace.php` with the correct project root (subdir installs like `/new/` no longer resolve one level above the shop)
- **OneSignal** — double `init` (“SDK already initialized”) no longer aborts `login(external_id)`; nested API `errors` arrays no longer trigger “Array to string conversion”
- **Browser push delivery** — order status changes and “Bildirim Gönder” now reach OneSignal via `Notification::dispatchWebPush()` (previously only the Test Push button called OneSignal directly)
- AI report APIs — missing `Admin.php` include for `Admin::isLoggedIn()` (“Could not connect to server” error)
- Carrier add/edit — Smarty translation placeholder breaking `{literal}` JS block (`cargos.tpl`)
- **Mobile App (PWA) icons** — uploaded PNG icons from admin are served correctly; `manifest.php` / `pwa-icon.php` read `mobil_app_settings` paths instead of always generating the blue “F” placeholder
- **PWA production** — standalone `manifest.php`, `pwa-icon.php`, and `sw.php` endpoints (no full bootstrap) to avoid HTTP 500 on shared hosting
- **reCAPTCHA admin login** — widget and JS now assigned from `login.php` (bootstrap timing); v2 script load order fixed in `front.js`
- **Captcha helper** — `core/Captcha.php` + safe fallback in `fshop_validate_captcha()` when class file missing on partial deploys
- **Alert module admin** — Smarty `intval` modifier removed; subscriptions pagination uses shared admin template
- **Alert module storefront** — “Back in stock” email field editable for logged-in customers (removed incorrect `readonly`)
- **Category filters** — Smarty undefined index on variation filter groups (`selected` set in PHP)
- **Admin language switcher** — session now starts before `AdminLang::handleSwitchRequest()` so login/header TR/EN selection persists
- **Alert module on checkout** — admin order e-mail no longer requires `Admin` class on storefront (`adminPanelUrl` helper)
- **Alert back-in-stock** — sends when product is saved in stock with pending subscriptions (not only 0→stock transition edge cases)
- **Virtual products (My Account)** — order detail shows download link, license keys, and text delivery after payment; pending-payment notice for bank transfer orders; fulfillment retried when viewing paid orders
- **Admin products list** — product ID column on `/admin/products`
- **Demo mode** — module ZIP upload blocked when `DEMO_MODE` is enabled
- **Restoran theme** — category page catalog filters (sidebar + mobile offcanvas) aligned with core `CatalogFilter`

---
## [2.4.1] — 2026-07-18 (current production release)

### Security

- Deny web access: `storage/`, `cache/`, `core/`, `container/`, `lang/` (`.htaccess`)
- `fx-pricing` refresh requires `SHOP_TOKEN` (cron) or admin CSRF
- HTML allowlist sanitizer (`Security::sanitizeHtml`) on product/CMS/blog/home-text save
- Admin module APIs: `Admin::requireModuleApiAuth()` (session + CSRF) for trendyol, shopier, backup-pro, bifatura, etc.
- Cron tip: always use `token=SHOP_TOKEN` or header `X-Cron-Token`

### Added

#### Configurable admin URL
- **`ADMIN_URI`** in `config/env.php` — public admin path (filesystem folder stays `admin/`)
- Example: `'ADMIN_URI' => 'bo_9xK2m7'` → `/bo_9xK2m7/` (bookmark the new URL)
- `.htaccess` sync via `Admin::syncHtaccessRewrite()` on each admin request / install
- When URI ≠ `admin`, legacy `/admin` returns 404
- All panel links use `Admin::url()` / `$adminUrl` (no hardcoded `/admin/` in URL builders)
- Guide: [docs/ADMIN_URI.md](docs/ADMIN_URI.md)

#### Export Excel modülü
- **export-excel** — `admin_header` üzerinde ürün içe/dışa aktarma ve sipariş dışa aktarma
- Ürünler / Siparişler sayfalarında mevcut filtrelerle Excel indirme
- Ürün içe aktarma mevcut `Product::importFromExcel` mantığını kullanır

#### ftheme-edit — Canlı tema düzenleyici
- **WordPress-style customizer** — admin entry shows only “Canlı Düzenle”; sidebar left, storefront preview (iframe) right
- **Click-to-edit** — editable text regions on home/footer/cookie banner (`data-ftheme` hooks in fyazilim theme)
- **Homepage blocks** — reorder up/down, add/remove block types: slider, featured, promo, categories, home text, custom HTML, **banner** (image, link, width %; consecutive banners render in one row)
- **Banner media picker** — “Medyadan seç” via admin media library modal
- **Renkler panel** — only theme variables that actually affect the storefront; `colors.css` aliases for legacy `style.css` / cart variables
- **CSS / JS panel** — edit `custom.css` and `custom.js` without touching theme core files
- **Live preview** — `postMessage` sync for text, colors, blocks, and custom CSS (no full reload for every change)
- **JSON publish** — “Yayınla” saves via fetch; fixes invalid payload errors with large block JSON

#### fyazilim header — arama
- **header0** — full search bar between logo and cart (desktop); dedicated mobile search row below header
- **header1** — search icon next to cart; on desktop search replaces the nav row; on mobile search opens full-width below the header row
- **Autocomplete** — product suggestions with image, name, category, and price (`/api/search-suggest.php`, min. 2 characters)
- Shared partial: `templates/fyazilim/plugin/header-search.tpl`

### Changed

- **Site version** — `FShop::VERSION` 2.4.1 (admin footer)
- **ftheme-edit colors** — removed non-working editor entries (`fy-primary` as “button color”, unused `fy-footer-mid`); button color tied to `fy-gradient`; cart/catalog aliases follow editable gradient
- **Reviews invite** (see 2.4.0) — e-mail copy clarifies: after **delivered** status, wait **X days**, then invite to review with optional **%Y personal coupon**
- **Ürünler admin** — Excel içe/dışa aktarma butonları kaldırıldı; `export-excel` modülüne taşındı

### Fixed

- `api/search-suggest.php` — missing bootstrap (`IN_SCRIPT` / `settings.php`); autocomplete now returns product JSON
- ftheme-edit Smarty path for product slider partial inside block loop
- ftheme-edit customizer save and live block preview (`syncBlocks` postMessage)
- Media library modal z-index above customizer overlay
- `--surface` wired to page background; `--font-family` alias in `custom.css`

---

## [2.4.0] — 2026-07-17

### Added

#### Admin layout hooks
- `admin_header` — content area top on all admin pages (`{$adminHooks.admin_header}`)
- `admin_footer` — above version footer on all admin pages (`{$adminHooks.admin_footer}`)

#### AI Assistant 1.2 — scoped header bar
- AI bar only on: dashboard, orders, cancellations, returns, blog posts, CMS edit, SEO, product edit
- Product AI moved to header (product side panel hook removed)
- Blog: idea input → write/edit post; CMS/SEO write actions kept
- Module page is settings-only (reports removed)

#### AI Assistant 1.1 — header bar
- Removed left-menu link; module lives in **admin header** when installed/active
- Contextual actions: summarize page, write SEO metas, write CMS content, analyze dashboard
- Product edit still uses the side panel (`admin_product_button`); header bar hidden on product page
- Settings/reports remain at `/admin/module-ai-assistant`

#### Reviews invite (yorum daveti)
- After an order is **delivered**, wait **X days** then e-mail the customer to review purchased products
- Admin-editable subject/body with placeholders (`{customer_name}`, `{products_list}`, `{coupon_code}`, …)
- Optional **personal coupon** (bound to customer) created when the invite is sent
- Cron: `/api/module.php?m=reviews&action=cron&token=SHOP_TOKEN`
- Queue UI under **Modules → Ürün Yorumları → Gönderim kuyruğu**

#### Customer-bound coupons (core)
- `coupons.id_user` — optional customer binding; validation requires login and matching account
- Admin coupon form: customer selector; list shows assigned customer
- `Coupon::createPersonal()` for one-time personal codes (used by reviews invite)

#### Product Set (bundle) module
- **Pack product type** — sell multiple physical products as one set (e.g. newborn kit)
- Stock = minimum of component stocks; price = sum of components (optional fixed override)
- Add to cart expands into separate component lines; order stock deducts from children
- Admin product editor: type “Set (paket)” + component picker

#### Point of Sale (POS) module
- **Full-screen kasa terminal** — PIN or admin session; product grid, barcode scan, cart, cash / card / transfer checkout
- **Admin menu** — “Point of Sale” link under General (`/admin/pos`)
- **Kasa durumu** — today’s sales by payment type (cash, card, transfer, pending transfer) with grand total
- **Sales receipt** — thermal-style receipt modal after checkout (customer, items, payment, order ref + CODE39 barcode, print)
- **Customer on POS** — search, visitor default, create customer from pay flow
- **Admin customers** — “Add customer” modal on customer list (`Customer::createByAdmin`)
- **Stock rules** — hide out-of-stock products; optionally allow selling when stock is zero
- **Payment adjustments** — per-method discount or commission for credit card and bank transfer (e.g. 3% transfer discount, 5% card commission)
- **Module settings** — store label, order status, PIN lock, external card terminal URL (with current-domain presets), auto fullscreen on open

#### Smart Campaign module
- **Order status triggers** — campaigns can fire on placed / updated order status (e.g. shipped, delivered)
- Hooks: `order.placed`, `order.updated`

#### System e-mail layout
- **Three-part template** for all `Mail::send()` messages: editable header + dynamic body + editable footer
- Admin **Settings → E-posta** tab with header/footer editors and live preview iframe
- Keys: `MAIL_HEADER`, `MAIL_FOOTER`

#### Store & settings
- **Tabbed admin settings** — General, Contact, E-mail, Orders, Returns
- **Store maintenance mode** — toggle shop active/inactive, custom message, IP whitelist (`StoreStatus`, `SHOP_ACTIVE`, `SHOP_MAINTENANCE_*`)
- **Centralized contact info** — address, hours, social links in Settings → Contact; storefront contact page reads from settings

#### Admin & customers
- **Customer contact modal** on customer detail — message + WhatsApp (Wapio if configured, else `wa.me`) or e-mail (`CustomerContact`)

#### Themes
- **Theme schema options** — `theme.schema.json` driven colors/options for **fyazilim** and **blue** themes
- Social link blocks and option-driven CSS/JS wired in storefront templates

### Changed

- **POS UX** — viewport-fixed layout (no page scroll); 5-column product grid; compact stats; barcode field auto-focus; add-to-cart beep
- **POS modals** — customer picker stacks above pay modal (no need to close payment first)
- **POS receipt print** — dedicated print area so full receipt (not only barcode) goes to printer
- **Site version** — `FShop::VERSION` 2.4.0 (admin footer)

### Fixed

- POS stacked modals (customer behind payment overlay)
- POS external card URL defaulting to localhost — admin presets use current site domain
- POS receipt print showing only barcode in print preview

---

## [2.3.0] — 2026-07-16

### Added

- **Admin i18n** — English default admin language; all admin UI strings via `adminT` / `{'Key'|adminT}` with `lang/admin/en.php` and `lang/admin/tr.php`
- **Admin language switcher** — session-based TR/EN toggle in admin header

### Changed

- **Admin notifications** — page UI and notification titles/messages translated; legacy Turkish records mapped at display time
- **Site version** — `FShop::VERSION` 2.3.0 (admin footer)

---

## [2.2.0] — 2026-07-15

### Added

- **Login with email or phone** — customers can sign in using either identifier (`Customer::login`, auth API / login form)
- **`main_menu` display hook** — editable top navigation; **main-menu** module (custom links, category, CMS, blog)
- **Blog module** — installable posts list (`/blog`), categories (`/blog/kategori/{slug}-{id}`), detail (`/blog/{slug}-{id}`) with admin CRUD, TinyMCE + media library
- **Admin media picker** — reusable `media-picker.js` for TinyMCE / form fields (besides product attach)
- **Admin product media library** — “Dosya yöneticisi” modal; browse `img/`, upload to `img/media/`, multi-select, attach to product (`MediaLibrary`, `/api/admin-media.php`)
- **Admin product editor redesign** — sticky header, card sections, sticky price/media sidebar (`product-editor.css`)
- **AJAX product images** — cover / delete without full page reload; multi-file upload endpoint
- **Admin confirm modal** — shared “Vazgeç / Evet, Onayla” dialog for product, category, and brand delete
- **Bankwire payment discount** — `BANKWIRE_DISCOUNT_PERCENT` (havale indirimi) applied at checkout when bank transfer is selected
- **Checkout cargo selection** — customer picks carrier; fee from cargo rules (session + AJAX `set_cargo` / `set_payment`)
- **Order contact attachments** — optional file upload on “Bu sipariş hakkında soru sor” (`img/contact/`, `/api/contact-file.php`)
- **Admin customer password reset** — set customer password from customer detail page
- **Admin customer profile edit** — name, phone, and email editable by admin

### Changed

- Checkout shipping: global `FREE_SHIPPING_MIN` / `SHIPPING_FEE` settings removed from flow; rates come only from **Kargolar**
- Bankwire order confirmation UI — larger amount display, copy buttons for IBAN / holder / reference
- Customer account order detail — clearer breakdown (subtotal, promotions, coupon, payment discount, shipping, cargo, total)
- Admin product image workflow — open media library first instead of immediate file-input upload
- Admin set-password rules — minimum 8 characters (complexity optional for admin resets; e.g. `12345678` allowed)

### Fixed

- New product form warning: undefined `cost` key
- Media library “Bağlantı hatası” — clean JSON responses from `/api/admin-media.php` (output buffer / bootstrap clash)
- Returns admin list fatal: `ReturnRequest::adminUrl()` → `adminLink()` / `Admin::url()`
- Admin customer password change appearing to save while rejecting numeric-only passwords

---

## [2.1.0] — 2026-07-10

### Added

- **Theme gallery** — card-based theme list with activate, preview, edit, ZIP upload, and theme copy
- **`theme.schema.json`** — per-theme customization (options + colors) for Nova and Blue
- **Performance panel** (`/admin/performance`) — template cache toggle, cache clear, gzip, HTML minify, debug mode, optional guest page cache
- **Dashboard news** — RSS feed from [frisay.com/rss.xml](https://frisay.com/rss.xml) with local fallback (`data/frisay-news.json`, `/rss.xml`)
- **Site version** — `FShop::VERSION` (2.1.0) shown in admin footer
- **Cart promotions** — admin “Sepet Kampanyaları” (`nth_item`, `buy_x_pay_y`), auto-applied at cart/checkout
- **Category sidebar filters** — subcategories, brands, price range (Nova + Blue)
- **Blue cart page** — full cart layout with summary, recommended products, favorites
- **Related products** — sidebar block on product pages (category → brand → store fallback)
- **Shopier module** — REST API product sync (create / update / delete)
- **Discount timer module** — scheduled product discounts with countdown
- **Product extra text module** — global text on all product pages via module settings
- **CHANGELOG.md** and streamlined English **README.md**

### Changed

- Admin **Temalar** split into gallery + dedicated customize page (`/admin/theme-customize`)
- Module list search/filter fixed (Bootstrap `d-flex` conflict)
- Debug and error display can be controlled from admin (overrides `env.php` when set)

### Fixed

- Admin coupons page 500 when `CartPromotion` class was not bootstrapped
- Theme color saving now respects per-theme schema (Blue / Nova variables)

---

## [2.0.0] — 2026-06

### Added

- 4-step **install wizard** (`/install/`) with requirements check, DB setup, admin account, optional demo data
- **Blue** and **Nova** storefront themes; theme customizer (colors, fonts, header variants)
- **Module system** — install / enable / configure from admin; display hooks and internal hooks
- **REST Web API** — orders, products, categories, brands ([docs/WEBAPI.md](docs/WEBAPI.md))
- **Bilingual** storefront and admin (Turkish / English)
- **Multi-currency** products with cron price refresh (`/api/cron.php?action=currency`)
- **Virtual / digital products** with secure download tokens
- **Customer accounts** — registration, login, orders, favorites, addresses
- **Coupons**, CMS pages, contact form, SEO fields, Schema.org on products
- **Excel product import**, order print view, dashboard KPIs and charts
- Bundled modules: PayTR, bank wire, cash on delivery, reviews, slider, WhatsApp, newsletter, Google Analytics / Shopping, and more

### Changed

- Architecture: `container/` + `core/` + Smarty (no Laravel/Symfony)
- Admin UI refreshed (Bootstrap, sidebar layout)

---

## [1.0.0] — 2025

### Added

- Initial open-source release of the FShop e-commerce codebase
- Basic catalog, cart, checkout, and admin product/order management
- `default` storefront theme and `templates/admin` back office

---

[2.5.0]: https://github.com/bera65/fshop/compare/v2.4.1...v2.5.0
[2.4.1]: https://github.com/bera65/fshop/compare/v2.4.0...v2.4.1
[2.4.0]: https://github.com/bera65/fshop/compare/v2.3.0...v2.4.0
[2.3.0]: https://github.com/bera65/fshop/compare/v2.2.0...v2.3.0
[2.2.0]: https://github.com/bera65/fshop/compare/v2.1.0...v2.2.0
[2.1.0]: https://github.com/bera65/fshop/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/bera65/fshop/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/bera65/fshop/releases/tag/v1.0.0
