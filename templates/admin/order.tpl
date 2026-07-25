{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">
					{$flash|escape}
				</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}

<div class="row g-4">
	<div class="col-lg-8">
		<div class="admin-panel mb-4">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
	<h2 class="h6 mb-0">{'Order Information'|adminT}</h2>
	<a href="{$adminUrl}order-print?id={$order.id_order}&amp;auto=1" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener">
		<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
		{'Print'|adminT}
	</a>
</div>
			<div class="row g-2 small">
				<div class="col-md-6"><strong>{'Order no:'|adminT}</strong> {$order.reference|escape}</div>
				<div class="col-md-6"><strong>{'Date:'|adminT}</strong> {$order.date_formatted}</div>
				<div class="col-md-6"><strong>{'Customer:'|adminT}</strong> {$order.customer_name|escape}</div>
				<div class="col-md-6"><strong>{'Phone:'|adminT}</strong> {$order.customer_phone|escape}</div>
				{if $order.company_name}<div class="col-md-6"><strong>{'Company:'|adminT}</strong> {$order.company_name|escape}</div>{/if}
				{if $order.tax_office}<div class="col-md-6"><strong>{'Tax office:'|adminT}</strong> {$order.tax_office|escape}</div>{/if}
				{if $order.tax_number}<div class="col-md-6"><strong>{'Tax no / ID:'|adminT}</strong> {$order.tax_number|escape}</div>{/if}
				<div class="col-12"><strong>{'Address:'|adminT}</strong> {$order.address_city|escape} / {$order.address_district|escape} — {$order.address_text|escape}</div>
				{if $order.note}<div class="col-12"><strong>{'Note:'|adminT}</strong> {$order.note|escape}</div>{/if}
				{if $order.cargo_company}<div class="col-md-6"><strong>{'Shipping:'|adminT}</strong> {$order.cargo_company|escape}</div>{/if}
				{if $order.tracking_number}
				<div class="col-md-6">
					<strong>{'Tracking no:'|adminT}</strong> {$order.tracking_number|escape}
					{if $trackingUrl}
					<a href="{$trackingUrl|escape}" target="_blank" rel="noopener" class="ms-1 small">{'Open tracking ↗'|adminT}</a>
					{/if}
				</div>
				{/if}
			</div>
		</div>

		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Products'|adminT}</h2>
			<div class="table-responsive">
				<table class="table table-sm mb-0">
					<thead>
						<tr><th>{'Product'|adminT}</th><th>{'Qty'|adminT}</th><th>{'Unit'|adminT}</th><th>{'Total'|adminT}</th></tr>
					</thead>
					<tbody>
						{foreach $order.items as $item}
						<tr>
							<td>
								<div>{$item.product_name|escape}</div>
								{if $item.is_virtual}
								<div class="small mt-2 text-muted">
									<span class="badge bg-info">{$item.virtual_kind_label|escape}</span>
									{if $item.virtual_kind == 'license'}
										{if $item.license_keys|@count}
										<div class="mt-2">
											<strong class="text-dark">{'Issued licenses:'|adminT}</strong>
											{foreach $item.license_keys as $lic}
											<code class="d-block mt-1">{$lic.license_key|escape}</code>
											{if $lic.date_used}<span class="text-muted">({$lic.date_used|escape})</span>{/if}
											{/foreach}
										</div>
										{else}
										<div class="mt-1">{'No license assigned yet (payment may be pending).'|adminT}</div>
										{/if}
									{elseif $item.virtual_kind == 'download'}
										{if $item.has_download}
										<div class="mt-1">{'File'|adminT}: <strong>{if $item.virtual_delivery_admin}{$item.virtual_delivery_admin|escape}{else}{'Digital file'|adminT}{/if}</strong></div>
										{else}
										<div class="mt-1">{'Download not active yet.'|adminT}</div>
										{/if}
									{elseif $item.virtual_kind == 'text' && $item.virtual_delivery_admin}
										<div class="mt-1">{$item.virtual_delivery_admin|escape|nl2br nofilter}</div>
									{/if}
								</div>
								{/if}
							</td>
							<td>{$item.qty}</td>
							<td>{$item.price_formatted}</td>
							<td>{$item.total_formatted}</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="col-lg-4">
		<div class="admin-panel mb-4">
			<h2 class="h6 mb-3">{'Summary'|adminT}</h2>
			<p class="mb-1 d-flex justify-content-between"><span>{'Subtotal'|adminT}</span><span>{$order.subtotal_formatted}</span></p>
			{if $order.coupon_discount > 0}
			<p class="mb-1 d-flex justify-content-between text-success"><span>{'Coupon'|adminT}{if $order.coupon_code} ({$order.coupon_code|escape}){/if}</span><span>-{$order.coupon_discount_formatted}</span></p>
			{/if}
			{if $order.promotion_discount > 0}
			<p class="mb-1 d-flex justify-content-between text-success"><span>{if $order.promotion_name}{$order.promotion_name|escape}{else}{'Promotion'|adminT}{/if}</span><span>-{$order.promotion_discount_formatted}</span></p>
			{/if}
			{if $order.payment_discount > 0}
			<p class="mb-1 d-flex justify-content-between text-success"><span>{if $order.payment_discount_label}{$order.payment_discount_label|escape}{else}{'Payment discount'|adminT}{/if}</span><span>-{$order.payment_discount_formatted}</span></p>
			{/if}
			<p class="mb-1 d-flex justify-content-between"><span>{'Shipping'|adminT}</span><span>{$order.shipping_formatted}</span></p>
			<p class="mb-3 d-flex justify-content-between fw-bold"><span>{'Total'|adminT}</span><span>{$order.total_formatted}</span></p>
			<p class="mb-0 small text-muted">{'Payment:'|adminT} {$order.payment_label|escape}</p>
		</div>

		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Status and shipping'|adminT}</h2>
			<form method="post">
				<input type="hidden" name="updateStatus" value="1">
				<input type="hidden" name="token" value="{$adminToken}">
				<select name="status" class="form-select mb-3">
					{foreach $statusOptions as $statusId => $statusLabel}
					<option value="{$statusId}"{if $order.status == $statusId} selected{/if}>{$statusLabel|escape}</option>
					{/foreach}
				</select>
				<label class="form-label small mb-1">{'Carrier company'|adminT}</label>
				{if $cargoOptions}
				<select name="cargo_company" class="form-select mb-3">
					<option value="">{'— Select —'|adminT}</option>
					{foreach $cargoOptions as $cargo}
					<option value="{$cargo.name|escape}"{if $order.cargo_company == $cargo.name} selected{/if}>{$cargo.name|escape}</option>
					{/foreach}
				</select>
				{else}
				<input type="text" name="cargo_company" class="form-control mb-3" value="{$order.cargo_company|default:''|escape}" placeholder="{'e.g. domestic carrier'|adminT}">
				<div class="form-text mb-3"><a href="{$domain}admin/cargos">{'Shipping'|adminT}</a>{' menu to add carriers.'|adminT}</div>
				{/if}
				<label class="form-label small mb-1">{'Tracking number'|adminT}</label>
				<input type="text" name="tracking_number" class="form-control mb-2" value="{$order.tracking_number|default:''|escape}" placeholder="YT123456789">
				{if $trackingUrl}
				<p class="small mb-3"><a href="{$trackingUrl|escape}" target="_blank" rel="noopener">{'Tracking link:'|adminT} {$trackingUrl|escape}</a></p>
				{else}
				<p class="small text-muted mb-3">{'The tracking link is built by appending the number to the carrier tracking URL.'|adminT}</p>
				{/if}
				<button type="submit" class="btn btn-dark w-100">{'Save'|adminT}</button>
			</form>
		</div>
	</div>
</div>
{if $adminHooks.admin_order_detail}
<div class="admin-panel mt-4">
	{$adminHooks.admin_order_detail nofilter}
</div>
{/if}

<p class="mt-3"><a href="{$adminUrl}orders">{'← Back to orders'|adminT}</a></p>
