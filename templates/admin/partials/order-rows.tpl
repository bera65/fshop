{foreach $orders as $row}
<tr>
	<td class="ps-order-thumb">
		<img src="{$row.thumb_url|escape}" alt="{$row.thumb_product|escape}" width="32" height="32">
	</td>
	<td class="ps-order-ref">
		<a href="{$adminUrl}order?id={$row.id_order}">{$row.reference|escape}</a>
		{if $row.has_gift_wrap|default:false}
		<span class="text-danger ms-1" title="{'Gift wrap'|adminT}" aria-label="{'Gift wrap'|adminT}">
			<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>
		</span>
		{/if}
	</td>
	<td class="ps-order-customer">{$row.customer_name|escape}</td>
	<td class="ps-order-location text-muted">{$row.location|escape}</td>
	<td class="ps-order-total fw-semibold">{$row.total_formatted}</td>
	<td class="ps-order-cargo">
		<div class="ps-cargo-cell">
			{if $row.tracking_url}
			<a href="{$row.tracking_url|escape}" class="ps-cargo-track" target="_blank" rel="noopener" title="{'Tracking'|adminT}">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
			</a>
			{/if}
			{if $row.cargo_logo_url}
			<img src="{$row.cargo_logo_url|escape}" alt="{$row.cargo_name|escape}" class="ps-cargo-logo">
			{elseif $row.cargo_name}
			<span class="ps-cargo-name">{$row.cargo_name|escape}</span>
			{else}
			<span class="text-muted">—</span>
			{/if}
		</div>
	</td>
	<td class="ps-order-status">
		{if isset($statusOptions) && $statusOptions|@count}
		<select class="ps-order-status-select ps-status-select--{$row.status_class}"
			data-order-id="{$row.id_order}"
			data-current="{$row.status}"
			aria-label="{'Order status'|adminT}">
			{foreach $statusOptions as $statusId => $statusLabel}
			<option value="{$statusId}"{if $statusId == $row.status} selected{/if}>{$statusLabel|escape}</option>
			{/foreach}
		</select>
		{else}
		<span class="ps-status-badge ps-status-badge--{$row.status_class}">{$row.status_label|escape}</span>
		{/if}
	</td>
	<td class="ps-order-date text-muted small">{$row.date_full}</td>
	<td class="ps-order-actions">
		<a href="{$adminUrl}order?id={$row.id_order}" class="ps-action-btn" title="{'View'|adminT}">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
		</a>
		<a href="{$adminUrl}order-print?id={$row.id_order}&amp;auto=1" class="ps-action-btn" target="_blank" rel="noopener" title="{'Print'|adminT}">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
		</a>
	</td>
</tr>
{/foreach}
