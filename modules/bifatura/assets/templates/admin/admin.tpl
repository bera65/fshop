{if $flash}
<div class="alert alert-info py-2">{$flash|escape}</div>
{/if}
<script>window.bifaturaAdminToken = {$adminToken|@json_encode nofilter};</script>

<ul class="nav nav-tabs mb-3">
	<li class="nav-item">
		<a class="nav-link{if $tab == 'settings'} active{/if}" href="{$adminUrl}module-bifatura?tab=settings">Ayarlar</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $tab == 'inbox'} active{/if}" href="{$adminUrl}module-bifatura?tab=inbox">Gelen Kutusu</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $tab == 'recent'} active{/if}" href="{$adminUrl}module-bifatura?tab=recent">Son Faturalar</a>
	</li>
</ul>

{if $tab == 'settings' || $tab == ''}
<div class="admin-panel p-3" style="max-width: 640px;">
	<h2 class="h6 mb-3">Bifatura API Ayarları</h2>
	<form method="post">
		<input type="hidden" name="saveBifatura" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="mb-3">
			<label class="form-label">API Key</label>
			<input type="text" name="api_key" class="form-control" value="{$bifaturaApiKey|escape}" required>
		</div>
		<div class="mb-3">
			<label class="form-label">Secret Key</label>
			<input type="text" name="sc_key" class="form-control" value="{$bifaturaScKey|escape}" required>
		</div>
		<div class="mb-3">
			<label class="form-label">Integration Key</label>
			<input type="text" name="in_key" class="form-control" value="{$bifaturaInKey|escape}" required>
		</div>
		<div class="mb-3">
			<label class="form-label">API Base URL</label>
			<input type="url" name="api_url" class="form-control" value="{$bifaturaApiUrl|escape}">
			<div class="form-text">Varsayılan: https://uygulama.edonustur.com/api/OutEBelgeV2/</div>
		</div>
		<div class="mb-3 form-check">
			<input class="form-check-input" type="checkbox" name="auto_create" id="bfAuto" value="1"{if $bifaturaAutoCreate} checked{/if}>
			<label class="form-check-label" for="bfAuto">Sipariş «Hazırlanıyor» olunca otomatik fatura oluştur</label>
		</div>

		<button type="submit" class="btn btn-dark">Kaydet</button>
	</form>

	<form method="post" class="mt-3">
		<input type="hidden" name="testBifaturaApi" value="1">
		<input type="hidden" name="token" value="{$adminToken}">
		<button type="submit" class="btn btn-outline-secondary btn-sm">API Bağlantısını Test Et</button>
		<div class="form-text">Kaydedilmiş Base URL + anahtarlarla gerçek bir istek atar; HTTP kodu ve yanıtı gösterir.</div>
	</form>

	{if $apiTestResult}
	<pre class="small bg-light border rounded p-2 mt-3" style="white-space:pre-wrap;">{$apiTestResult|escape}</pre>
	{/if}

	<div class="alert alert-light border small mt-3 mb-0">
		BirFatura / e-Dönüştür <code>OutEBelgeV2</code> kullanılır.
		Endpoint örneği: <code>…/SendBasicInvoiceFromModel</code>
	</div>
</div>
{/if}

{if $tab == 'inbox'}
<div class="admin-panel p-3">
	<h2 class="h6 mb-3">Gelen e-Faturalar</h2>
	<form method="post" class="row g-2 align-items-end mb-3">
		<input type="hidden" name="loadInbox" value="1">
		<input type="hidden" name="token" value="{$adminToken}">
		<div class="col-auto">
			<label class="form-label small mb-0">Başlangıç</label>
			<input type="date" name="start_date" class="form-control form-control-sm" value="{$inboxStart|escape}">
		</div>
		<div class="col-auto">
			<label class="form-label small mb-0">Bitiş</label>
			<input type="date" name="end_date" class="form-control form-control-sm" value="{$inboxEnd|escape}">
		</div>
		<div class="col-auto">
			<button type="submit" class="btn btn-sm btn-dark">Listele</button>
		</div>
	</form>

	{if $inboxItems|@count == 0}
	<p class="text-muted small mb-0">Kayıt yok veya henüz listelenmedi.</p>
	{else}
	<div class="table-responsive">
		<table class="table table-sm table-hover align-middle">
			<thead>
				<tr>
					<th>Tarih</th>
					<th>No</th>
					<th>Gönderen</th>
					<th>Tutar</th>
					<th>Tip</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{foreach $inboxItems as $item}
				<tr>
					<td>{$item.date|escape}</td>
					<td>{$item.no|escape}</td>
					<td>{$item.sender|escape}</td>
					<td>{$item.amount|escape}</td>
					<td><span class="badge text-bg-secondary">{$item.systemType|escape}</span></td>
					<td>
						<button type="button" class="btn btn-sm btn-outline-primary bifatura-inbox-pdf"
							data-uuid="{$item.uuid|escape}"
							data-type="{$item.systemType|escape}"
							data-url="{$inboxPdfUrl|escape}">PDF</button>
					</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	{/if}
</div>
{literal}
<script>
document.querySelectorAll('.bifatura-inbox-pdf').forEach(function (btn) {
	btn.addEventListener('click', function () {
		btn.disabled = true;
		fetch(btn.getAttribute('data-url'), {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			credentials: 'same-origin',
			body: new URLSearchParams({
				uuid: btn.getAttribute('data-uuid'),
				system_type: btn.getAttribute('data-type'),
				token: window.bifaturaAdminToken || window.adminToken || ''
			}).toString()
		}).then(function (r) { return r.json(); })
		.then(function (res) {
			btn.disabled = false;
			if (res.success && res.pdfLink) window.open(res.pdfLink, '_blank');
			else alert(res.message || 'PDF alınamadı');
		}).catch(function () { btn.disabled = false; alert('İstek başarısız'); });
	});
});
</script>
{/literal}
{/if}

{if $tab == 'recent'}
<div class="admin-panel p-3">
	<h2 class="h6 mb-3">Son Oluşturulan Faturalar</h2>
	{if $recentInvoices|@count == 0}
	<p class="text-muted small mb-0">Henüz kayıt yok.</p>
	{else}
	<div class="table-responsive">
		<table class="table table-sm table-hover align-middle">
			<thead>
				<tr>
					<th>Sipariş</th>
					<th>Müşteri</th>
					<th>Fatura No</th>
					<th>Tip</th>
					<th>Durum</th>
					<th>Tarih</th>
				</tr>
			</thead>
			<tbody>
				{foreach $recentInvoices as $inv}
				<tr>
					<td><a href="{$adminUrl}order?id={$inv.id_order}">{$inv.reference|escape}</a></td>
					<td>{$inv.customer_name|escape}</td>
					<td>{$inv.invoice_no|escape}</td>
					<td>{$inv.system_type|escape}</td>
					<td>{$inv.status|escape}</td>
					<td>{$inv.date_add|escape}</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	{/if}
</div>
{/if}
