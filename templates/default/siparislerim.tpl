<h1 class="page-heading">Siparişlerim</h1>

{if !$orders|@count}
	<p class="text-muted">Henüz siparişiniz bulunmuyor.</p>
	<a href="{$domain}" class="btn btn-dark btn-sm">Alışverişe Başla</a>
{else}
	<div class="order-list">
		{foreach $orders as $o}
		<div class="order-card border rounded p-3 mb-3 bg-white">
			<div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
				<div>
					<strong>#{$o.reference}</strong>
					<span class="text-muted small ms-2">{$o.date_formatted}</span>
				</div>
				<span class="badge bg-secondary">{$o.status_label}</span>
			</div>
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
				<div class="small text-muted">
					{$o.payment_label} · {$o.total_formatted}
				</div>
				<a href="{$domain}siparis?id={$o.id_order}" class="btn btn-sm btn-outline-dark">Detay</a>
			</div>
		</div>
		{/foreach}
	</div>
{/if}
