{foreach $orders as $row}
<tr class="adm-order-row" data-order-id="{$row.id_order}" role="button" tabindex="0">
	<td class="adm-order-select-cell" onclick="event.stopPropagation()">
		{if $orderListMode|default:'full' != 'dashboard'}
		<input type="checkbox" class="adm-order-select form-check-input" value="{$row.id_order}" aria-label="{'Select'|adminT}">
		{/if}
		<div class="dropdown d-inline-block">
			<button type="button" class="adm-order-btn-icon" data-bs-toggle="dropdown" aria-expanded="false" title="{'Menu'|adminT}">
				<i data-lucide="ellipsis-vertical"></i>
			</button>
			<ul class="dropdown-menu adm-order-menu">
				<li>
					<a class="dropdown-item" href="{$adminUrl}order-print?id={$row.id_order}&amp;auto=1" target="_blank" rel="noopener">
						<i data-lucide="printer"></i> {'Print'|adminT}
					</a>
				</li>
				<li>
					<a class="dropdown-item" href="{$adminUrl}order?id={$row.id_order}">
						<i data-lucide="pencil"></i> {'Edit order'|adminT}
					</a>
				</li>
				<li>
					<a class="dropdown-item" href="{$adminUrl}order?id={$row.id_order}#adminOrderModules">
						<i data-lucide="book-up"></i> {'Invoice / cargo on order detail'|adminT}
					</a>
				</li>
				{if $row.tracking_url}
				<li>
					<a class="dropdown-item" href="{$row.tracking_url|escape}" target="_blank" rel="noopener">
						<i data-lucide="truck"></i> {'Tracking'|adminT}
					</a>
				</li>
				{/if}
				<li><hr class="dropdown-divider"></li>
				<li onclick="event.stopPropagation()">
					<form method="post" action="{$adminUrl}orders" class="m-0">
						<input type="hidden" name="deleteOrder" value="1">
						<input type="hidden" name="id" value="{$row.id_order}">
						<input type="hidden" name="token" value="{$adminToken}">
						<button type="submit" class="dropdown-item text-danger js-admin-confirm" data-confirm-title="{'Delete order'|adminT}" data-confirm-message="{'This order will be permanently deleted. Related items, messages and documents will be removed. Stock will be restored if it was deducted.'|adminT}">
							<i data-lucide="trash-2"></i> {'Delete order'|adminT}
						</button>
					</form>
				</li>
			</ul>
		</div>
		<button type="button" class="adm-order-btn-icon adm-order-expand-btn" data-order-expand="{$row.id_order}" title="{'Details'|adminT}" aria-label="{'Details'|adminT}">
			<span class="adm-order-caret"><i data-lucide="chevron-right"></i></span>
		</button>
	</td>
	<td>
		{include file='admin/partials/channel-badge.tpl'
			channelType=$row.channel
			channelLabel=$row.channel_label}
	</td>
	<td>
		<div class="fw-semibold">{$row.date_list|escape}</div>
	</td>
	<td class="adm-order-action-cell text-center" onclick="event.stopPropagation()">
		<div class="adm-order-aksiyon-icons">
			{if $row.tracking_url}
			<a class="adm-order-icon-tip adm-order-ship adm-order-ship--{$row.ship_tone|escape} is-trackable"
				href="{$row.tracking_url|escape}" target="_blank" rel="noopener"
				data-tip="{'Tracking'|adminT}" title="{'Tracking'|adminT}">
				<i data-lucide="truck"></i>
			</a>
			{else}
			<span class="adm-order-icon-tip adm-order-ship adm-order-ship--{$row.ship_tone|escape}" data-tip="{'No tracking'|adminT}">
				<i data-lucide="truck"></i>
			</span>
			{/if}

			{if $row.is_packed}
			<span class="adm-order-icon-tip adm-order-packed is-active" data-tip="{$row.status_label|escape}">
				<span class="adm-order-packed-glyph">
					<i data-lucide="package"></i>
					<span class="adm-order-packed-check"><i data-lucide="check"></i></span>
				</span>
			</span>
			{else}
			<span class="adm-order-icon-tip adm-order-packed is-muted" data-tip="{'Not packed'|adminT}">
				<span class="adm-order-packed-glyph">
					<i data-lucide="package"></i>
				</span>
			</span>
			{/if}

			{if $row.tracking_number}
			<a class="adm-order-icon-tip adm-order-label is-pending" href="{$adminUrl}order-print?id={$row.id_order}&amp;auto=1" target="_blank" rel="noopener" data-tip="{'Print'|adminT}">
				<span class="adm-order-packed-glyph">
					<i data-lucide="printer"></i>
				</span>
			</a>
			{else}
			<span class="adm-order-icon-tip adm-order-label is-muted" data-tip="{'No tracking'|adminT}">
				<span class="adm-order-packed-glyph">
					<i data-lucide="printer"></i>
				</span>
			</span>
			{/if}

			{if $row.cargo_name}
			<span class="adm-order-icon-tip adm-order-cargo-co" data-tip="{$row.cargo_name|escape}">
				{if $row.cargo_logo_url}
				<img src="{$row.cargo_logo_url|escape}" alt="{$row.cargo_name|escape}" width="14" height="14">
				{else}
				<i data-lucide="warehouse"></i>
				{/if}
			</span>
			{else}
			<span class="adm-order-icon-tip adm-order-cargo-co is-muted" data-tip="{'No cargo company'|adminT}">
				<i data-lucide="warehouse"></i>
			</span>
			{/if}

			{if $row.has_invoice|default:false}
			<a class="adm-order-icon-tip adm-order-invoice is-active" href="{if $row.invoice_view_url}{$row.invoice_view_url|escape}{else}{$adminUrl}order?id={$row.id_order}#adminOrderModules{/if}"{if $row.invoice_view_url} target="_blank" rel="noopener"{/if} data-tip="{'Invoice'|adminT}">
				<span class="adm-order-packed-glyph">
					<i data-lucide="file-text"></i>
					<span class="adm-order-packed-check"><i data-lucide="check"></i></span>
				</span>
			</a>
			{else}
			<span class="adm-order-icon-tip adm-order-invoice is-muted" data-tip="{'No invoice'|adminT}">
				<span class="adm-order-packed-glyph">
					<i data-lucide="file-text"></i>
				</span>
			</span>
			{/if}
		</div>
	</td>
	<td>
		<a href="{$adminUrl}order?id={$row.id_order}" class="adm-order-link" onclick="event.stopPropagation()">{$row.reference|escape}</a>
		{if $row.has_gift_wrap|default:false}
		<span class="text-danger ms-1" title="{'Gift wrap'|adminT}"><i data-lucide="gift" style="width:14px;height:14px;vertical-align:-2px"></i></span>
		{/if}
	</td>
	<td onclick="event.stopPropagation()">
		{if $orderListMode|default:'full' != 'dashboard' && isset($statusOptions) && $statusOptions|@count}
		<select class="ps-order-status-select ps-status-select--{$row.status_class} form-select form-select-sm"
			data-order-id="{$row.id_order}"
			data-current="{$row.status}"
			aria-label="{'Order status'|adminT}">
			{foreach $statusOptions as $statusId => $statusLabel}
			<option value="{$statusId}"{if $statusId == $row.status} selected{/if}>{$statusLabel|escape}</option>
			{/foreach}
		</select>
		{else}
		<span class="adm-status-pill adm-status-pill--{$row.status_class}">{$row.status_label|escape}</span>
		{/if}
	</td>
	<td>
		<div class="fw-semibold">{$row.customer_name|escape}</div>
		{if $row.location}<div class="adm-order-mini-muted">{$row.location|escape}</div>{/if}
	</td>
	<td>
		<span class="adm-order-mini-muted">{$row.payment_label|escape}</span>
	</td>
	<td class="text-nowrap">{$row.cost_formatted}</td>
	<td class="fw-bold text-nowrap">{$row.total_formatted}</td>
</tr>
<tr class="adm-order-detail-row" id="adm-order-detail-{$row.id_order}" hidden>
	<td colspan="10">
		<div class="adm-order-detail">
			{if $row.lines|@count}
			<table class="table adm-order-lines mb-0">
				<thead>
					<tr>
						<th style="width:56px"></th>
						<th>{'Stock code'|adminT}</th>
						<th>{'Status'|adminT}</th>
						<th>{'Product'|adminT}</th>
						<th>{'Qty'|adminT}</th>
						<th>{'VAT %'|adminT}</th>
						<th>{'VAT total'|adminT}</th>
						<th>{'Line total'|adminT}</th>
						<th>{'Cost'|adminT}</th>
					</tr>
				</thead>
				<tbody>
					{foreach $row.lines as $line}
					<tr>
						<td>
							<img class="adm-order-line-thumb" src="{$line.image_url|escape}" alt="{$line.product_name|escape}" width="40" height="40">
						</td>
						<td>
							{if $line.stock_code}
							<a href="{$adminUrl}products?q={$line.stock_code|escape:'url'}" class="adm-order-link" onclick="event.stopPropagation()">{$line.stock_code|escape}</a>
							{else}—{/if}
						</td>
						<td><span class="adm-status-pill adm-status-pill--{$row.status_class}">{$row.status_label|escape}</span></td>
						<td>
							<div class="fw-semibold adm-order-product-title">{$line.product_name|escape}</div>
							{if $line.barcode}<div class="adm-order-mini-muted">{$line.barcode|escape}</div>{/if}
						</td>
						<td>{$line.qty_formatted|escape}</td>
						<td>%{$line.vat_pct|string_format:"%.2f"}</td>
						<td>{$line.vat_total_formatted}</td>
						<td><strong>{$line.total_formatted}</strong></td>
						<td>{$line.line_cost_formatted}</td>
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
