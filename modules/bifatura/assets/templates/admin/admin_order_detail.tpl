<div class="admin-panel mt-3 bifatura-panel">
<script>window.bifaturaAdminToken = {$adminToken|@json_encode nofilter};</script>
	<div class="admin-panel__head d-flex justify-content-between align-items-center">
		<h2 class="h6 mb-0">Bifatura e-Fatura</h2>
		{if isset($invoice.status) && $invoice.status == 'created'}
		<span class="badge text-bg-success">Faturalandı</span>
		{/if}
	</div>
	<div class="admin-panel__body">
		{if !$configured}
		<div class="alert alert-warning small mb-0">
			API anahtarları tanımlı değil.
			<a href="{$adminUrl}module-bifatura">Modül ayarları</a>
		</div>
		{elseif isset($invoice.invoice_no) && $invoice.invoice_no != ''}
		<div class="small mb-2">
			<strong>Fatura No:</strong> {$invoice.invoice_no|escape}<br>
			<strong>Tip:</strong> {$invoice.system_type|escape}<br>
			{if isset($invoice.ettn) && $invoice.ettn != ''}<strong>ETTN:</strong> <code class="small">{$invoice.ettn|escape}</code><br>{/if}
		</div>
		<div class="d-flex flex-wrap gap-2">
			{if isset($invoice.pdf_link) && $invoice.pdf_link != ''}
			<a href="{$invoice.pdf_link|escape}" target="_blank" class="btn btn-sm btn-outline-primary">PDF Aç</a>
			{/if}
			<button type="button" class="btn btn-sm btn-primary bifatura-pdf-btn"
				data-order="{$id_order}" data-url="{$pdfUrl|escape}">PDF Getir</button>
		</div>
		<div class="bifatura-msg small text-muted mt-2"></div>
		{elseif isset($invoice.status) && $invoice.status == 'failed'}
		<div class="alert alert-danger small mb-2">{$invoice.message|escape}</div>
		<button type="button" class="btn btn-sm btn-dark bifatura-create-btn"
			data-order="{$id_order}" data-url="{$createUrl|escape}">Tekrar Dene</button>
		<div class="bifatura-msg small text-muted mt-2"></div>
		{else}
		<p class="small text-muted mb-2">Bu sipariş için henüz e-fatura oluşturulmadı.</p>
		<button type="button" class="btn btn-sm btn-dark bifatura-create-btn"
			data-order="{$id_order}" data-url="{$createUrl|escape}">e-Fatura Oluştur</button>
		<div class="bifatura-msg small text-muted mt-2"></div>
		{/if}
	</div>
</div>

{literal}
<script>
(function () {
	function postJson(url, data, btn) {
		var msg = btn.closest('.bifatura-panel').querySelector('.bifatura-msg');
		btn.disabled = true;
		if (msg) msg.textContent = 'İşleniyor…';
		return fetch(url, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			credentials: 'same-origin',
			body: new URLSearchParams(Object.assign({
				token: window.bifaturaAdminToken || window.adminToken || ''
			}, data)).toString()
		}).then(function (r) { return r.json(); })
		.then(function (res) {
			btn.disabled = false;
			if (msg) msg.textContent = res.message || '';
			if (res.success && res.pdfLink) {
				window.open(res.pdfLink, '_blank');
			} else if (res.success) {
				setTimeout(function () { location.reload(); }, 800);
			}
		}).catch(function () {
			btn.disabled = false;
			if (msg) msg.textContent = 'İstek başarısız';
		});
	}

	document.querySelectorAll('.bifatura-create-btn').forEach(function (btn) {
		btn.addEventListener('click', function () {
			postJson(btn.getAttribute('data-url'), {id_order: btn.getAttribute('data-order')}, btn);
		});
	});
	document.querySelectorAll('.bifatura-pdf-btn').forEach(function (btn) {
		btn.addEventListener('click', function () {
			postJson(btn.getAttribute('data-url'), {id_order: btn.getAttribute('data-order')}, btn);
		});
	});
})();
</script>
{/literal}
