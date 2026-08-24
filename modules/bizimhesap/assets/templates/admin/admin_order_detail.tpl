<div class="admin-panel mt-3 bizimhesap-panel">
	<div class="admin-panel__head d-flex justify-content-between align-items-center">
		<h2 class="h6 mb-0">BizimHesap e-Fatura</h2>
		{if isset($invoice.status) && $invoice.status == 'created'}
		<span class="badge text-bg-success">Gönderildi</span>
		{/if}
	</div>
	<div class="admin-panel__body">
		{if !$configured}
		<div class="alert alert-warning small mb-0">
			Firma ID tanımlı değil.
			<a href="{$adminUrl}module-bizimhesap">Modül ayarları</a>
		</div>
		{elseif isset($invoice.status) && $invoice.status == 'created'}
		<div class="small mb-0">
			<strong>Fatura No:</strong> {$invoice.invoice_no|escape}<br>
			{if isset($invoice.external_id) && $invoice.external_id != ''}
			<strong>ID:</strong> <code class="small">{$invoice.external_id|escape}</code><br>
			{/if}
			<span class="text-muted">{$invoice.message|escape}</span>
		</div>
		{elseif isset($invoice.status) && $invoice.status == 'failed'}
		<div class="alert alert-danger small mb-2">{$invoice.message|escape}</div>
		<button type="button" class="btn btn-sm btn-dark bizimhesap-create-btn"
			data-order="{$id_order}" data-url="{$createUrl|escape}">Tekrar Dene</button>
		<div class="bizimhesap-msg small text-muted mt-2"></div>
		{else}
		<p class="small text-muted mb-2">Bu sipariş henüz BizimHesap'a gönderilmedi.</p>
		<button type="button" class="btn btn-sm btn-dark bizimhesap-create-btn"
			data-order="{$id_order}" data-url="{$createUrl|escape}">Fatura Gönder</button>
		<div class="bizimhesap-msg small text-muted mt-2"></div>
		{/if}
	</div>
</div>

{literal}
<script>
(function () {
	function postJson(url, data, btn) {
		var msg = btn.closest('.bizimhesap-panel').querySelector('.bizimhesap-msg');
		btn.disabled = true;
		if (msg) msg.textContent = 'İşleniyor…';
		return fetch(url, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			credentials: 'same-origin',
			body: new URLSearchParams(data).toString()
		}).then(function (r) { return r.json(); })
		.then(function (res) {
			btn.disabled = false;
			if (msg) msg.textContent = res.message || '';
			if (res.success) {
				setTimeout(function () { location.reload(); }, 800);
			}
		}).catch(function () {
			btn.disabled = false;
			if (msg) msg.textContent = 'İstek başarısız';
		});
	}

	document.querySelectorAll('.bizimhesap-create-btn').forEach(function (btn) {
		btn.addEventListener('click', function () {
			postJson(btn.getAttribute('data-url'), {id_order: btn.getAttribute('data-order')}, btn);
		});
	});
})();
</script>
{/literal}
