{if !$configured}
<button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Shopier API anahtarı gerekli">Shopier</button>
{else}
<div class="shopier-product-actions d-inline-flex align-items-center gap-2 ms-2">
	{if isset($mapping.shopier_id) && $mapping.shopier_id != ''}
	<span class="badge text-bg-success shopier-status-badge">Shopier: {$mapping.shopier_id|escape}</span>
	{if $mapping.shopier_url}
	<a href="{$mapping.shopier_url|escape}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">Shopier'de Aç</a>
	{/if}
	<button type="button" class="btn btn-dark btn-sm shopier-sync-btn"
		data-id="{$id_product}" data-url="{$syncUrl|escape}">Güncelle</button>
	<button type="button" class="btn btn-outline-danger btn-sm shopier-delete-btn"
		data-id="{$id_product}" data-url="{$deleteUrl|escape}">Shopier'den Sil</button>
	{else}
	<button type="button" class="btn btn-primary btn-sm shopier-sync-btn"
		data-id="{$id_product}" data-url="{$syncUrl|escape}">Shopier'e Gönder</button>
	{/if}
	<span class="small text-muted shopier-action-msg"></span>
</div>
{/if}
<script>window.shopierAdminToken = {$adminToken|@json_encode nofilter};</script>
{literal}
<script>
(function () {
	function shopierPost(url, idProduct, msgEl, reload) {
		return fetch(url, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			credentials: 'same-origin',
			body: new URLSearchParams({
				id_product: idProduct,
				token: window.shopierAdminToken || window.adminToken || ''
			}).toString()
		}).then(function (r) { return r.json(); })
		.then(function (res) {
			if (msgEl) {
				msgEl.textContent = res.message || '';
				msgEl.className = 'small shopier-action-msg ' + (res.success ? 'text-success' : 'text-danger');
			}
			if (res.success && reload) {
				setTimeout(function () { location.reload(); }, 700);
			}
		}).catch(function () {
			if (msgEl) msgEl.textContent = 'İstek başarısız';
		});
	}

	document.querySelectorAll('.shopier-sync-btn').forEach(function (btn) {
		if (btn.dataset.shopierBound) return;
		btn.dataset.shopierBound = '1';
		btn.addEventListener('click', function () {
			var wrap = btn.closest('.shopier-product-actions');
			var msgEl = wrap ? wrap.querySelector('.shopier-action-msg') : null;
			btn.disabled = true;
			if (msgEl) msgEl.textContent = 'Gönderiliyor…';
			shopierPost(btn.getAttribute('data-url'), btn.getAttribute('data-id'), msgEl, true)
				.finally(function () { btn.disabled = false; });
		});
	});

	document.querySelectorAll('.shopier-delete-btn').forEach(function (btn) {
		if (btn.dataset.shopierBound) return;
		btn.dataset.shopierBound = '1';
		btn.addEventListener('click', function () {
			if (!window.confirm('Bu ürün Shopier mağazanızdan silinecek. Devam edilsin mi?')) return;
			var wrap = btn.closest('.shopier-product-actions');
			var msgEl = wrap ? wrap.querySelector('.shopier-action-msg') : null;
			btn.disabled = true;
			if (msgEl) msgEl.textContent = 'Siliniyor…';
			shopierPost(btn.getAttribute('data-url'), btn.getAttribute('data-id'), msgEl, true)
				.finally(function () { btn.disabled = false; });
		});
	});
})();
</script>
{/literal}
