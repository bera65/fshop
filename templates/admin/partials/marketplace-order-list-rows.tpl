{foreach $marketplaceOrders as $ord}
{assign var=rowId value=$ord.row_key}
<tr class="adm-order-row mp-order-row" data-order-id="{$rowId|escape}" data-order="{$ord.detail_json|escape}" role="button" tabindex="0">
	<td class="adm-order-select-cell" onclick="event.stopPropagation()">
		{if $mpOrderListMode|default:'full' != 'dashboard'}
		<input type="checkbox" class="adm-order-select form-check-input mp-order-check mp-order-row-check" value="{$ord.platform|escape}::{$ord.order_number|escape}::{$ord.shipment_package_id|escape}" aria-label="{'Select'|adminT}">
		{/if}
		<div class="dropdown d-inline-block mp-order-menu">
			<button type="button" class="adm-order-btn-icon js-mp-menu-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="{'Menu'|adminT}">
				<i data-lucide="ellipsis-vertical"></i>
			</button>
			<ul class="dropdown-menu adm-order-menu mp-order-menu-drop">
				{if $mpOrderListMode|default:'full' != 'dashboard'}
				<li>
					<a class="dropdown-item" href="{$adminUrl|escape}marketplace-order-print?auto=1&amp;platform={$ord.platform|escape}&amp;order_number={$ord.order_number|escape}&amp;package_id={$ord.shipment_package_id|escape}" target="_blank" rel="noopener">
						<i data-lucide="barcode"></i> Kargo barkodu yazdır
					</a>
				</li>
				<li>
					<button type="button" class="dropdown-item js-mp-refresh-order">
						<i data-lucide="truck"></i> Güncel kargo bilgisi al
					</button>
				</li>
				<li><hr class="dropdown-divider"></li>
				<li>
					<button type="button" class="dropdown-item text-danger js-mp-delete-order">
						<i data-lucide="trash-2"></i> Siparişi sil
					</button>
				</li>
				<li>
					<button type="button" class="dropdown-item text-danger js-mp-cancel-order">
						<i data-lucide="x-circle"></i> Siparişi iptal et
					</button>
				</li>
				{else}
				<li>
					<a class="dropdown-item" href="{$adminUrl|escape}marketplace-orders?open=1&amp;platform={$ord.platform|escape}&amp;order_number={$ord.order_number|escape}&amp;package_id={$ord.shipment_package_id|escape}">
						<i data-lucide="file-text"></i> {'Details'|adminT}
					</a>
				</li>
				<li>
					<a class="dropdown-item" href="{$adminUrl|escape}marketplace-order-print?auto=1&amp;platform={$ord.platform|escape}&amp;order_number={$ord.order_number|escape}&amp;package_id={$ord.shipment_package_id|escape}" target="_blank" rel="noopener">
						<i data-lucide="printer"></i> {'Print'|adminT}
					</a>
				</li>
				{/if}
			</ul>
		</div>
		<button type="button" class="adm-order-btn-icon adm-order-expand-btn" data-order-expand="{$rowId|escape}" title="{'Details'|adminT}" aria-label="{'Details'|adminT}">
			<span class="adm-order-caret"><i data-lucide="chevron-right"></i></span>
		</button>
	</td>
	<td>
		{include file='admin/partials/channel-badge.tpl'
			channelType='marketplace'
			channelPlatform=$ord.platform
			channelLabel=$ord.platform_label
			channelIconFile=$ord.platform_icon_file
			channelIcon=$ord.platform_icon}
	</td>
	<td>
		<div class="fw-semibold">{$ord.date_list|escape}</div>
	</td>
	<td class="adm-order-action-cell text-center" onclick="event.stopPropagation()">
		<div class="adm-order-aksiyon-icons">
			{if $ord.cargo_tracking_link}
			<a class="adm-order-icon-tip adm-order-ship adm-order-ship--{$ord.ship_tone|escape} is-trackable"
				href="{$ord.cargo_tracking_link|escape}" target="_blank" rel="noopener"
				data-tip="{'Tracking'|adminT}">
				<i data-lucide="truck"></i>
			</a>
			{else}
			<span class="adm-order-icon-tip adm-order-ship adm-order-ship--{$ord.ship_tone|escape}" data-tip="{'No tracking'|adminT}">
				<i data-lucide="truck"></i>
			</span>
			{/if}

			{if $ord.is_packed}
			<span class="adm-order-icon-tip adm-order-packed is-active" data-tip="{$ord.status|escape}">
				<span class="adm-order-packed-glyph">
					<i data-lucide="package"></i>
					<span class="adm-order-packed-check"><i data-lucide="check"></i></span>
				</span>
			</span>
			{else}
			<span class="adm-order-icon-tip adm-order-packed is-muted" data-tip="{'Not packed'|adminT}">
				<span class="adm-order-packed-glyph"><i data-lucide="package"></i></span>
			</span>
			{/if}

			<a class="adm-order-icon-tip adm-order-label{if $ord.cargo_tracking_number} is-pending{else} is-muted{/if}"
				href="{$adminUrl|escape}marketplace-order-print?auto=1&amp;platform={$ord.platform|escape}&amp;order_number={$ord.order_number|escape}&amp;package_id={$ord.shipment_package_id|escape}"
				target="_blank" rel="noopener"
				data-tip="{'Print'|adminT}">
				<span class="adm-order-packed-glyph"><i data-lucide="printer"></i></span>
			</a>

			{if $ord.cargo_provider && $ord.cargo_provider != '—'}
			<span class="adm-order-icon-tip adm-order-cargo-co" data-tip="{$ord.cargo_provider|escape}">
				<i data-lucide="warehouse"></i>
			</span>
			{else}
			<span class="adm-order-icon-tip adm-order-cargo-co is-muted" data-tip="{'No cargo company'|adminT}">
				<i data-lucide="warehouse"></i>
			</span>
			{/if}

			<span class="adm-order-icon-tip adm-order-invoice is-muted" data-tip="{'No invoice'|adminT}">
				<span class="adm-order-packed-glyph"><i data-lucide="file-text"></i></span>
			</span>
		</div>
	</td>
	<td>
		{if $mpOrderListMode|default:'full' != 'dashboard'}
		<button type="button" class="adm-order-link js-mp-open-detail border-0 bg-transparent p-0">{$ord.order_number|escape}</button>
		{else}
		<a class="adm-order-link" href="{$adminUrl|escape}marketplace-orders?open=1&amp;platform={$ord.platform|escape}&amp;order_number={$ord.order_number|escape}&amp;package_id={$ord.shipment_package_id|escape}">{$ord.order_number|escape}</a>
		{/if}
	</td>
	<td>
		<span class="adm-status-pill adm-status-pill--{$ord.status_pill|escape}">{$ord.status|escape}</span>
	</td>
	<td>
		{if $mpOrderListMode|default:'full' != 'dashboard'}
		<button type="button" class="adm-order-link js-mp-open-detail border-0 bg-transparent p-0 fw-semibold">{$ord.customer_name|escape}</button>
		{else}
		<div class="fw-semibold">{$ord.customer_name|escape}</div>
		{/if}
		{if $ord.customer_sub}<div class="adm-order-mini-muted">{$ord.customer_sub|escape}</div>{/if}
	</td>
	<td><span class="adm-order-mini-muted">{$ord.payment_label|escape}</span></td>
	<td class="text-nowrap">{$ord.cost_formatted|default:'0,00 ₺'}</td>
	<td class="fw-bold text-nowrap">{$ord.total_price|escape}</td>
</tr>
<tr class="adm-order-detail-row" id="adm-order-detail-{$rowId|escape}" hidden>
	<td colspan="10">
		<div class="adm-order-detail">
			{if $ord.items|@count}
			<table class="table adm-order-lines mb-0">
				<thead>
					<tr>
						<th style="width:56px"></th>
						<th>{'Stock code'|adminT}</th>
						<th>{'Status'|adminT}</th>
						<th>{'Product'|adminT}</th>
						<th>{'Qty'|adminT}</th>
					</tr>
				</thead>
				<tbody>
					{foreach $ord.items as $line}
					<tr>
						<td>
							{if $line.image}
							<img class="adm-order-line-thumb" src="{$line.image|escape}" alt="" width="40" height="40">
							{else}
							<span class="adm-order-line-thumb d-inline-block bg-light"></span>
							{/if}
						</td>
						<td>
							{if $line.sku}
							<span class="adm-order-link">{$line.sku|escape}</span>
							{else}—{/if}
						</td>
						<td><span class="adm-status-pill adm-status-pill--{$ord.status_pill|escape}">{$ord.status|escape}</span></td>
						<td><div class="fw-semibold adm-order-product-title">{$line.name|escape}</div></td>
						<td>{$line.quantity|escape}</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
			{else}
			<p class="text-muted small mb-0 p-2">{'No line items'|adminT}</p>
			{/if}
		</div>
	</td>
</tr>
{/foreach}
