{if $flash}
<div class="alert alert-info alert-dismissible fade show" role="alert">
    {$flash|escape}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
{/if}

<div class="row g-4">

    {* ── Feed URL kartı ── *}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex align-items-center gap-2">
                <span class="badge bg-success">FEED URL</span>
                <span class="fw-semibold">Google Merchant Center'a bu URL'yi ekleyin</span>
            </div>
            <div class="card-body">
                <div class="input-group">
                    <input type="text" class="form-control font-monospace" id="feedUrlInput" value="{$feedUrl|escape}" readonly>
                    <button class="btn btn-outline-secondary" type="button" id="copyFeedUrl">Kopyala</button>
                    <a href="{$feedUrl|escape}" target="_blank" class="btn btn-outline-primary">Önizle</a>
                </div>
                <div class="d-flex gap-4 mt-2 text-muted small">
                    <span>Son yenileme: <strong id="lastRegen">{$lastRegen|escape}</strong></span>
                    <span>Cache: <strong>{if $cacheExists}Mevcut{else}Yok{/if}</strong></span>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-dark btn-sm" id="btnRegen">
                        <span id="regenSpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                        Feed'i Yenile
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" id="btnPreview">
                        Önizleme (İlk 5 Ürün)
                    </button>
                </div>
            </div>
        </div>
    </div>

    {* ── Önizleme alanı ── *}
    <div class="col-12 d-none" id="previewSection">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <span class="fw-semibold">Feed Önizleme</span>
                <span class="badge bg-secondary ms-2" id="previewTotal"></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="previewTable">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Başlık</th>
                                <th>Fiyat</th>
                                <th>Stok</th>
                                <th>Marka</th>
                                <th>Bağlantı</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {* ── Ayarlar formu ── *}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Feed Ayarları</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="saveGsfSettings" value="1">
                    <input type="hidden" name="token" value="{$adminToken}">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="gsf_enabled" id="gsfEnabled" value="1" {if $gsfEnabled}checked{/if}>
                                <label class="form-check-label fw-semibold" for="gsfEnabled">Feed aktif</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="gsf_include_outstock" id="gsfIncludeOutstock" value="1" {if $gsfIncludeOutstock}checked{/if}>
                                <label class="form-check-label" for="gsfIncludeOutstock">Stok dışı ürünleri dahil et</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Para birimi</label>
                            <select name="gsf_currency" class="form-select">
                                <option value="TRY" {if $gsfCurrency == 'TRY'}selected{/if}>TRY — Türk Lirası</option>
                                <option value="USD" {if $gsfCurrency == 'USD'}selected{/if}>USD — Dolar</option>
                                <option value="EUR" {if $gsfCurrency == 'EUR'}selected{/if}>EUR — Euro</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Ürün durumu</label>
                            <select name="gsf_condition" class="form-select">
                                <option value="new"         {if $gsfCondition == 'new'}selected{/if}>Yeni</option>
                                <option value="used"        {if $gsfCondition == 'used'}selected{/if}>İkinci El</option>
                                <option value="refurbished" {if $gsfCondition == 'refurbished'}selected{/if}>Yenilenmiş</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Cache süresi (dakika)</label>
                            <input type="number" name="gsf_cache_ttl" class="form-control" value="{$gsfCacheTtl|escape}" min="10" max="1440">
                            <div class="form-text">Google günde 1–4 kez çeker. 360 dk önerilir.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Varsayılan marka adı</label>
                            <input type="text" name="gsf_brand_fallback" class="form-control" value="{$gsfBrandFallback|escape}" placeholder="Ürüne marka atanmamışsa kullanılır">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Hariç tutulacak kategori ID'leri</label>
                            <input type="text" name="gsf_exclude_cats" class="form-control" value="{$gsfExcludeCats|escape}" placeholder="Örnek: 5,12,38">
                            <div class="form-text">Virgülle ayırın. Bu kategorideki ürünler feed'e eklenmez.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Custom Label 0 <span class="text-muted">(isteğe bağlı)</span></label>
                            <input type="text" name="gsf_custom_label_0" class="form-control" value="{$gsfCustomLabel0|escape}" placeholder="Örnek: sezon2024">
                            <div class="form-text">Tüm ürünlere uygulanır. Google Ads kampanyalarında gruplayıcı olarak kullanın.</div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-dark">Ayarları Kaydet</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {* ── Token yenile ── *}
    <div class="col-12">
        <div class="card border-0 shadow-sm border-warning">
            <div class="card-header bg-white fw-semibold text-warning">⚠ Token Güvenliği</div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Feed URL'sindeki token değiştirilirse <strong>Google Merchant Center'daki URL'yi de güncellemeniz gerekir</strong>. Token, feed'e yetkisiz erişimi engeller.
                </p>
                <form method="post" data-confirm-message="Token yenilenirse mevcut feed URL geçersiz olur. Devam edilsin mi?">
                    <input type="hidden" name="regenToken" value="1">
                    <input type="hidden" name="token" value="{$adminToken}">
                    <button type="submit" class="btn btn-outline-warning btn-sm">Token'ı Yenile</button>
                </form>
            </div>
        </div>
    </div>

    {* ── Google rehberi ── *}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Google Merchant Center Entegrasyon Adımları</div>
            <div class="card-body">
                <ol class="mb-0 small text-muted">
                    <li>Yukarıdaki <strong>Feed URL</strong>'yi kopyalayın.</li>
                    <li><a href="https://merchants.google.com" target="_blank">Google Merchant Center</a>'a giriş yapın.</li>
                    <li><strong>Ürünler → Feed'ler → +</strong> butonuna tıklayın.</li>
                    <li>"Zamanlanmış Getirme" seçeneğini seçin, URL'yi yapıştırın.</li>
                    <li>Dil: Türkçe, Ülke: Türkiye, Para birimi: TRY seçin.</li>
                    <li>Getirme sıklığını <strong>Günlük</strong> olarak ayarlayın.</li>
                    <li>Kaydedin ve "Şimdi getir" ile test edin.</li>
                </ol>
            </div>
        </div>
    </div>

</div>

<script>
var gsfApiBase = '{$domain|escape}api/module.php?m=google-shopping&action=';
var csrfToken  = '{$adminToken|escape:"javascript"}';

// Kopyala
document.getElementById('copyFeedUrl').addEventListener('click', function () {
    var inp = document.getElementById('feedUrlInput');
    inp.select();
    document.execCommand('copy');
    this.textContent = 'Kopyalandı!';
    var self = this;
    setTimeout(function () { self.textContent = 'Kopyala'; }, 2000);
});

// Feed yenile
document.getElementById('btnRegen').addEventListener('click', function () {
    var spinner = document.getElementById('regenSpinner');
    spinner.classList.remove('d-none');
    this.disabled = true;
    var btn = this;

    fetch(gsfApiBase + 'regenerate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken
        },
        body: 'token=' + encodeURIComponent(csrfToken)
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        spinner.classList.add('d-none');
        btn.disabled = false;
        if (data.success) {
            document.getElementById('lastRegen').textContent = data.generated_at || '—';
            alert('✅ ' + data.message);
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(function () {
        spinner.classList.add('d-none');
        btn.disabled = false;
        alert('Bağlantı hatası');
    });
});

// Önizleme
document.getElementById('btnPreview').addEventListener('click', function () {
    var btn = this;
    btn.disabled = true;
    btn.textContent = 'Yükleniyor…';

    fetch(gsfApiBase + 'preview', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken
        },
        body: 'token=' + encodeURIComponent(csrfToken)
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        btn.disabled = false;
        btn.textContent = 'Önizleme (İlk 5 Ürün)';
        if (!data.success) { alert('Hata: ' + data.message); return; }

        document.getElementById('previewTotal').textContent = 'Toplam: ' + data.total + ' ürün';
        var tbody = document.querySelector('#previewTable tbody');
        tbody.innerHTML = '';

        data.preview.forEach(function (p) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="font-monospace small">' + (p.id || '') + '</td>' +
                '<td>' + (p.title || '').substring(0, 50) + '</td>' +
                '<td class="text-nowrap">' + (p.price || '') + '</td>' +
                '<td><span class="badge bg-' + (p.availability === 'in_stock' ? 'success' : 'secondary') + '">' + (p.availability || '') + '</span></td>' +
                '<td>' + (p.brand || '') + '</td>' +
                '<td><a href="' + (p.link || '#') + '" target="_blank" class="small">Aç</a></td>';
            tbody.appendChild(tr);
        });

        document.getElementById('previewSection').classList.remove('d-none');
    })
    .catch(function () {
        btn.disabled = false;
        btn.textContent = 'Önizleme (İlk 5 Ürün)';
        alert('Bağlantı hatası');
    });
});
</script>
