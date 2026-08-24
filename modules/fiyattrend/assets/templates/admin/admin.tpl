{if $flash}
<div class="alert alert-{$flashType|default:'info'} alert-dismissible fade show" role="alert">
	{$flash|escape}
	<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
{/if}

<div class="row g-4">

	<div class="col-12">
		<div class="card border-0 shadow-sm ft-hero">
			<div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
				<div>
					<div class="ft-badge mb-2">FiyatTrend Entegrasyonu</div>
					<h2 class="h5 mb-1">Ürünlerinizi fiyat karşılaştırmasına açın</h2>
					<p class="text-muted small mb-0">Google Merchant formatında XML feed üretir. Mağaza kaydı ve XML linki gönderimi FiyatTrend panelinden yapılır.</p>
				</div>
				<a href="{$panelUrl|escape}" target="_blank" rel="noopener noreferrer" class="btn btn-ft">
					FiyatTrend Paneline Git
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
				</a>
			</div>
		</div>
	</div>

	<div class="col-12">
		<div class="card border-0 shadow-sm">
			<div class="card-header bg-white d-flex align-items-center gap-2">
				<span class="badge bg-success">XML FEED URL</span>
				<span class="fw-semibold">Bu linki FiyatTrend panelinde mağazanıza ekleyin</span>
			</div>
			<div class="card-body">
				<div class="input-group">
					<input type="text" class="form-control font-monospace" id="feedUrlInput" value="{$feedUrl|escape}" readonly>
					<button class="btn btn-outline-secondary" type="button" id="copyFeedUrl">Kopyala</button>
					<a href="{$feedUrl|escape}" target="_blank" class="btn btn-outline-primary">Önizle</a>
				</div>
				<div class="d-flex gap-4 mt-2 text-muted small">
					<span>Son yenileme: <strong id="lastRegen">{$lastRegen|escape}</strong></span>
					<span>Önbellek: <strong>{if $cacheExists}Mevcut{else}Yok{/if}</strong></span>
				</div>
				<div class="mt-3 d-flex flex-wrap gap-2">
					<button class="btn btn-dark btn-sm" id="btnRegen">
						<span id="regenSpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
						Feed'i Yenile
					</button>
					<button class="btn btn-outline-secondary btn-sm" id="btnPreview">Önizleme (İlk 5 Ürün)</button>
				</div>
			</div>
		</div>
	</div>

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

	<div class="col-lg-6">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-header bg-white fw-semibold">FiyatTrend Kurulum Adımları</div>
			<div class="card-body">
				<ol class="mb-0 small text-muted ft-steps">
					<li><a href="{$panelUrl|escape}" target="_blank" rel="noopener">fiyattrend.com/panel</a> adresinden kayıt olun veya giriş yapın.</li>
					<li>Panelden <strong>Mağaza Ekle</strong> ile e-ticaret sitenizi tanımlayın.</li>
					<li>Yukarıdaki <strong>XML Feed URL</strong>'yi kopyalayıp mağaza ayarlarına yapıştırın.</li>
					<li>Feed formatı <strong>Google Merchant (RSS 2.0)</strong> ile uyumludur — ek dönüşüm gerekmez.</li>
					<li>FiyatTrend ürünleri çekip fiyat karşılaştırmasında listeleyecektir.</li>
				</ol>
			</div>
		</div>
	</div>

	<div class="col-lg-6">
		<div class="card border-0 shadow-sm h-100">
			<div class="card-header bg-white fw-semibold">Feed Ayarları</div>
			<div class="card-body">
				<form method="post">
					<input type="hidden" name="saveFtSettings" value="1">
					<input type="hidden" name="token" value="{$adminToken}">

					<div class="row g-3">
						<div class="col-md-6">
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" name="ft_enabled" id="ftEnabled" value="1" {if $ftEnabled}checked{/if}>
								<label class="form-check-label fw-semibold" for="ftEnabled">Feed aktif</label>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" name="ft_include_outstock" id="ftIncludeOutstock" value="1" {if $ftIncludeOutstock}checked{/if}>
								<label class="form-check-label" for="ftIncludeOutstock">Stok dışı ürünleri dahil et</label>
							</div>
						</div>
						<div class="col-md-4">
							<label class="form-label">Para birimi</label>
							<select name="ft_currency" class="form-select">
								<option value="TRY" {if $ftCurrency == 'TRY'}selected{/if}>TRY</option>
								<option value="USD" {if $ftCurrency == 'USD'}selected{/if}>USD</option>
								<option value="EUR" {if $ftCurrency == 'EUR'}selected{/if}>EUR</option>
							</select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Ürün durumu</label>
							<select name="ft_condition" class="form-select">
								<option value="new" {if $ftCondition == 'new'}selected{/if}>Yeni</option>
								<option value="used" {if $ftCondition == 'used'}selected{/if}>İkinci el</option>
								<option value="refurbished" {if $ftCondition == 'refurbished'}selected{/if}>Yenilenmiş</option>
							</select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Önbellek (dakika)</label>
							<input type="number" name="ft_cache_ttl" class="form-control" value="{$ftCacheTtl|escape}" min="10" max="1440">
						</div>
						<div class="col-md-6">
							<label class="form-label">Varsayılan marka</label>
							<input type="text" name="ft_brand_fallback" class="form-control" value="{$ftBrandFallback|escape}" placeholder="Markası olmayan ürünler için">
						</div>
						<div class="col-md-6">
							<label class="form-label">Hariç kategori ID'leri</label>
							<input type="text" name="ft_exclude_cats" class="form-control" value="{$ftExcludeCats|escape}" placeholder="Örn: 5,12,38">
						</div>
						<div class="col-12">
							<button type="submit" class="btn btn-dark">Ayarları Kaydet</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>

	<div class="col-12">
		<div class="card border-0 shadow-sm border-warning">
			<div class="card-header bg-white fw-semibold text-warning">Token güvenliği</div>
			<div class="card-body">
				<p class="text-muted small mb-3">Feed URL'sindeki token, yetkisiz erişimi engeller. Token yenilerseniz FiyatTrend panelindeki XML linkini de güncellemeniz gerekir.</p>
				<form method="post" data-confirm-message="Token yenilenirse mevcut feed URL geçersiz olur. Devam edilsin mi?">
					<input type="hidden" name="regenToken" value="1">
					<input type="hidden" name="token" value="{$adminToken}">
					<button type="submit" class="btn btn-outline-warning btn-sm">Token'ı Yenile</button>
				</form>
			</div>
		</div>
	</div>

</div>

<script>
var ftApiBase = '{$domain|escape}api/module.php?m=fiyattrend&action=';
var csrfToken = '{$adminToken|escape:"javascript"}';

document.getElementById('copyFeedUrl').addEventListener('click', function () {
	var inp = document.getElementById('feedUrlInput');
	inp.select();
	document.execCommand('copy');
	this.textContent = 'Kopyalandı!';
	var self = this;
	setTimeout(function () { self.textContent = 'Kopyala'; }, 2000);
});

document.getElementById('btnRegen').addEventListener('click', function () {
	var spinner = document.getElementById('regenSpinner');
	var btn = this;
	spinner.classList.remove('d-none');
	btn.disabled = true;

	fetch(ftApiBase + 'regenerate', {
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

document.getElementById('btnPreview').addEventListener('click', function () {
	var btn = this;
	btn.disabled = true;
	btn.textContent = 'Yükleniyor…';

	fetch(ftApiBase + 'preview', {
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
		if (!data.success) {
			alert('Hata: ' + (data.message || 'Bilinmeyen hata'));
			return;
		}

		document.getElementById('previewTotal').textContent = 'Toplam: ' + data.total + ' ürün';
		var tbody = document.querySelector('#previewTable tbody');
		tbody.innerHTML = '';

		data.preview.forEach(function (p) {
			var tr = document.createElement('tr');
			tr.innerHTML =
				'<td class="font-monospace small">' + (p.id || '') + '</td>' +
				'<td>' + (p.title || '').substring(0, 50) + '</td>' +
				'<td class="text-nowrap">' + (p.price || p.sale_price || '') + '</td>' +
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
