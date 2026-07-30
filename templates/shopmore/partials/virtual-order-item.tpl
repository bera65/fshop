{if $item.is_virtual}
<div class="account-virtual-delivery mt-2 pt-2 border-top">
	<span class="badge bg-info">{$item.virtual_kind_label|escape}</span>
	{if $item.delivery_pending}
	<div class="alert alert-warning small mt-2 mb-0">{'Virtual delivery pending'|translate}</div>
	{elseif $item.has_download}
	<div class="mt-2 d-flex flex-wrap align-items-center gap-2">
		<a href="{$item.download_url|escape}" class="prime-btn prime-btn--primary prime-btn--sm">{'Download file'|translate}</a>
		{if $item.virtual_delivery}<span class="small text-muted">{$item.virtual_delivery|escape}</span>{/if}
	</div>
	{elseif $item.virtual_kind == 'license' && $item.delivery_lines|@count}
	<div class="mt-3 p-3 bg-light border border-info rounded">
		<strong class="d-block mb-2 text-dark">{'Your License Keys'|translate}:</strong>
		{foreach $item.delivery_lines as $idx => $key}
		<div class="d-flex align-items-center gap-2 mt-2">
			<code class="form-control form-control-sm text-dark bg-white font-monospace" readonly id="lic_{$item.id_order_detail}_{$idx}">{$key|escape}</code>
			<button type="button" class="prime-btn prime-btn--outline prime-btn--sm text-nowrap" onclick="navigator.clipboard.writeText(document.getElementById('lic_{$item.id_order_detail}_{$idx}').innerText); this.innerText='Kopyalandı!'; setTimeout(() => this.innerText='Kopyala', 2000);">{'Copy'|translate}</button>
		</div>
		{/foreach}
	</div>
	{elseif $item.virtual_kind == 'text' && $item.virtual_delivery}
	<div class="mt-2 small">{$item.virtual_delivery|escape|nl2br nofilter}</div>
	{/if}
</div>
{/if}
