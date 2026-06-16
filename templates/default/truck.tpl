<h1 class="page-heading">Kargo Takip</h1>

<form method="get" action="{$domain}truck" class="catalog-search mb-4">
	<div class="input-group">
		<input type="text" name="reference" class="form-control text-uppercase" placeholder="Sipariş no: FS260604XXXX" value="{$reference|escape}" required>
		<button type="submit" class="btn btn-dark">Sorgula</button>
	</div>
	<p class="small text-muted mt-2 mb-0">Sipariş onay sayfasındaki sipariş numaranızı girin.</p>
</form>

{if $trackError}
<div class="alert alert-warning">{$trackError}</div>
{/if}

{if $trackResult}
<div class="border rounded p-4 bg-white mb-4">
	<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
		<div>
			<p class="text-muted small mb-1">Sipariş No</p>
			<p class="fw-bold fs-5 mb-0">#{$trackResult.reference}</p>
		</div>
		<span class="badge bg-secondary align-self-start">{$trackResult.status_label}</span>
	</div>
	<p class="mb-2"><strong>Tarih:</strong> {$trackResult.date_formatted}</p>

	{if isset($trackResult.total_formatted)}
	<p class="mb-2"><strong>Toplam:</strong> {$trackResult.total_formatted}</p>
	<p class="mb-3"><strong>Ödeme:</strong> {$trackResult.payment_label}</p>
	<a href="{$domain}siparis?id={$trackResult.id_order}" class="btn btn-sm btn-dark">Sipariş Detayı</a>
	{else}
	<p class="text-muted small mb-0">Detaylı bilgi için giriş yaparak siparişlerinizi görüntüleyebilirsiniz.</p>
	{/if}
</div>
{/if}

{if $recentOrders|@count}
<div class="border rounded p-4 bg-light">
	<h2 class="fs-6 mb-3">Son Siparişleriniz</h2>
	{foreach $recentOrders as $o}
	<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 border-bottom">
		<div>
			<strong>#{$o.reference}</strong>
			<span class="text-muted small ms-2">{$o.date_formatted}</span>
		</div>
		<div class="d-flex align-items-center gap-2">
			<span class="badge bg-secondary">{$o.status_label}</span>
			<a href="{$domain}truck?reference={$o.reference}" class="btn btn-sm btn-outline-dark">Takip</a>
		</div>
	</div>
	{/foreach}
</div>
{/if}
