{include file='admin/marketplace/_nav.tpl'}

<div class="admin-panel p-3 mb-3">
	<h2 class="h5 mb-3">Trendyol siparişleri</h2>
	<div class="alert alert-light border small mb-3">
		Trendyol siparişleri bu listede görüntülenir; stok barkoda göre FShop ürününden düşülür.
		İlk eşleşmede sipariş satırındaki <strong>brüt satış fiyatı</strong> Trendyol fiyatı olarak kaydedilir.
		<br><br>
		<strong>Cron:</strong> <code class="user-select-all">{$cronOrdersUrl|escape}</code>
	</div>
	<form method="post" class="row g-2 align-items-end mb-3">
		<input type="hidden" name="syncTrendyolOrders" value="1">
		<input type="hidden" name="token" value="{$adminToken}">
		<div class="col-auto">
			<label class="form-label small mb-0">Başlangıç</label>
			<input type="date" name="start_date" class="form-control form-control-sm">
		</div>
		<div class="col-auto">
			<label class="form-label small mb-0">Bitiş</label>
			<input type="date" name="end_date" class="form-control form-control-sm">
		</div>
		<div class="col-auto">
			<button type="submit" class="btn btn-dark btn-sm">Siparişleri Çek</button>
		</div>
	</form>

	{if !$tyOrders|@count}
	<p class="text-muted small mb-0">Henüz sipariş yok.</p>
	{else}
	<div class="table-responsive">
		<table class="table table-sm table-hover align-middle mb-0">
			<thead>
				<tr>
					<th>Sipariş No</th>
					<th>Müşteri</th>
					<th>Durum</th>
					<th>Tutar</th>
					<th>Stok</th>
					<th>Kargo</th>
					<th>Tarih</th>
					<th>Kalemler</th>
				</tr>
			</thead>
			<tbody>
				{foreach $tyOrders as $ord}
				<tr>
					<td class="small fw-semibold">{$ord.order_number|escape}</td>
					<td class="small">{$ord.customer_name|escape}</td>
					<td><span class="badge text-bg-secondary">{$ord.status|escape}</span></td>
					<td class="small">{$ord.total_price|escape}</td>
					<td class="small">
						{if $ord.stock_deducted == 1}<span class="badge text-bg-success">Düşüldü</span>
						{elseif $ord.stock_deducted == 2}<span class="badge text-bg-warning">İade</span>
						{else}<span class="text-muted">—</span>{/if}
					</td>
					<td class="small">{$ord.cargo_provider|escape}{if $ord.cargo_tracking_number}<br><code>{$ord.cargo_tracking_number|escape}</code>{/if}</td>
					<td class="small text-muted">{$ord.order_date|escape}</td>
					<td class="small">
						{foreach $ord.lines as $line}
						<div>{$line.productName|default:$line.merchantSku|default:'—'|escape} × {$line.quantity|default:1}{if isset($line.lineGrossAmount) && $line.lineGrossAmount > 0} — {$line.lineGrossAmount|escape} TL{/if}</div>
						{/foreach}
					</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	{/if}
</div>

{include file='admin/marketplace/_close.tpl'}
