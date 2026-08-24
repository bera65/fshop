{if $flash}
<div class="alert alert-info py-2">{$flash|escape}</div>
{/if}

<ul class="nav nav-tabs mb-3">
	<li class="nav-item">
		<a class="nav-link{if $tab == 'settings'} active{/if}" href="{$adminUrl}module-bizimhesap?tab=settings">Ayarlar</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $tab == 'recent'} active{/if}" href="{$adminUrl}module-bizimhesap?tab=recent">Son Faturalar</a>
	</li>
</ul>

{if $tab == 'settings' || $tab == ''}
<div class="admin-panel p-3" style="max-width: 640px;">
	<h2 class="h6 mb-3">BizimHesap API Ayarları</h2>
	<form method="post">
		<input type="hidden" name="saveBizimHesap" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="mb-3">
			<label class="form-label">Firma ID (firmId)</label>
			<input type="text" name="firm_id" class="form-control" value="{$bizimhesapFirmId|escape}" required>
			<div class="form-text">BizimHesap panelindeki firma / API kimliği</div>
		</div>
		<div class="mb-3 form-check">
			<input class="form-check-input" type="checkbox" name="auto_create" id="bhAuto" value="1"{if $bizimhesapAutoCreate} checked{/if}>
			<label class="form-check-label" for="bhAuto">Sipariş «Hazırlanıyor» olunca otomatik fatura gönder</label>
		</div>

		<button type="submit" class="btn btn-dark">Kaydet</button>
	</form>

	<div class="alert alert-light border small mt-3 mb-0">
		API: <code>https://bizimhesap.com/api/b2b/addinvoice</code>
	</div>
</div>
{/if}

{if $tab == 'recent'}
<div class="admin-panel p-3">
	<h2 class="h6 mb-3">Son Faturalar</h2>
	{if !$recent}
	<p class="text-muted small mb-0">Henüz kayıt yok.</p>
	{else}
	<div class="table-responsive">
		<table class="table table-sm table-hover align-middle mb-0">
			<thead>
				<tr>
					<th>Sipariş</th>
					<th>Müşteri</th>
					<th>Fatura No</th>
					<th>Durum</th>
					<th>Tarih</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$recent item=row}
				<tr>
					<td>
						<a href="{$adminUrl}order?id={$row.id_order}">{$row.reference|escape}</a>
					</td>
					<td class="small">{$row.customer_name|escape}</td>
					<td class="small">{$row.invoice_no|escape}</td>
					<td>
						{if $row.status == 'created'}
						<span class="badge text-bg-success">OK</span>
						{else}
						<span class="badge text-bg-danger">Hata</span>
						{/if}
					</td>
					<td class="small text-muted">{$row.date_add|escape}</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	{/if}
</div>
{/if}
