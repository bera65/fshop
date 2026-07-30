{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">{$flash|escape}</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}

{if $orderEditDiff}
<div class="alert {if $orderEditDiff.difference > 0}alert-warning{elseif $orderEditDiff.difference < 0}alert-info{else}alert-secondary{/if} mb-4">
	<strong>{'Order total changed'|adminT}</strong>
	<div class="mt-2 small">
		<div class="d-flex justify-content-between" style="max-width:360px"><span>{'Previous total'|adminT}</span><span>{$orderEditDiff.old_total_formatted}</span></div>
		<div class="d-flex justify-content-between" style="max-width:360px"><span>{'New total'|adminT}</span><span>{$orderEditDiff.new_total_formatted}</span></div>
		<div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2" style="max-width:360px">
			{if $orderEditDiff.difference > 0}
			<span>{'Extra to collect'|adminT}</span><span>+{$orderEditDiff.difference_formatted}</span>
			{elseif $orderEditDiff.difference < 0}
			<span>{'Amount to refund / credit'|adminT}</span><span>−{$orderEditDiff.difference_formatted}</span>
			{else}
			<span>{'No difference'|adminT}</span><span>{$orderEditDiff.difference_formatted}</span>
			{/if}
		</div>
	</div>
</div>
{/if}

<div class="row g-4">
	<div class="col-lg-8">
		<div class="admin-panel mb-4">
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
				<h2 class="h6 mb-0">{'Order Information'|adminT}</h2>
				<div class="d-flex gap-2">
					{if $canEditOrder}
					<button type="button" class="btn btn-sm btn-outline-primary" id="toggleOrderEdit">{'Edit order'|adminT}</button>
					{/if}
					<a href="{$adminUrl}order-print?id={$order.id_order}&amp;auto=1" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener">{'Print'|adminT}</a>
				</div>
			</div>
			<div class="row g-2 small" id="orderInfoReadonly">
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

		<div class="admin-panel mb-4" id="orderProductsReadonly">
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
								{if $item.variation_label|default:''}<div class="small text-muted">{$item.variation_label|escape}</div>{/if}
								{if $item.is_virtual}
								<div class="small mt-2 text-muted">
									<span class="badge bg-info">{$item.virtual_kind_label|escape}</span>
								</div>
								{/if}
								{if $item.license_keys|default:[]}
								<div class="mt-2 p-2 bg-light border border-info rounded small font-monospace">
									<div class="fw-bold mb-1 text-dark">{'Sold License Keys:'|adminT}</div>
									{foreach $item.license_keys as $key}
										<div class="text-break text-dark border-bottom border-light-subtle py-1">
											{if is_array($key)}
												{$key.license_key|escape}
											{else}
												{$key|escape}
											{/if}
										</div>
									{/foreach}
								</div>
								{/if}
							</td>
							<td>{if $item.qty_label|default:''}{$item.qty_label|escape}{else}{$item.qty}{/if}</td>
							<td>{$item.price_formatted}</td>
							<td>{$item.total_formatted}</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>

		{if $canEditOrder}
		<form method="post" id="orderEditForm" class="admin-panel" style="display:none;">
			<input type="hidden" name="saveOrderEdit" value="1">
			<input type="hidden" name="token" value="{$adminToken}">

			<h2 class="h6 mb-3">{'Edit order'|adminT}</h2>

			<div class="row g-3 mb-4">
				<div class="col-md-6">
					<label class="form-label small">{'Customer'|adminT}</label>
					<input type="text" name="customer_name" class="form-control form-control-sm" value="{$order.customer_name|escape}" required>
				</div>
				<div class="col-md-3">
					<label class="form-label small">{'Phone'|adminT}</label>
					<input type="text" name="customer_phone" class="form-control form-control-sm" value="{$order.customer_phone|escape}" required>
				</div>
				<div class="col-md-3">
					<label class="form-label small">{'Email'|adminT}</label>
					<input type="email" name="customer_email" class="form-control form-control-sm" value="{$order.customer_email|default:''|escape}">
				</div>
				<div class="col-md-4">
					<label class="form-label small">{'Company'|adminT}</label>
					<input type="text" name="company_name" class="form-control form-control-sm" value="{$order.company_name|default:''|escape}">
				</div>
				<div class="col-md-4">
					<label class="form-label small">{'Tax office'|adminT}</label>
					<input type="text" name="tax_office" class="form-control form-control-sm" value="{$order.tax_office|default:''|escape}">
				</div>
				<div class="col-md-4">
					<label class="form-label small">{'Tax no / ID'|adminT}</label>
					<input type="text" name="tax_number" class="form-control form-control-sm" value="{$order.tax_number|default:''|escape}">
				</div>
				<div class="col-md-4">
					<label class="form-label small">{'City'|adminT}</label>
					<input type="text" name="address_city" class="form-control form-control-sm" value="{$order.address_city|escape}" required>
				</div>
				<div class="col-md-4">
					<label class="form-label small">{'District'|adminT}</label>
					<input type="text" name="address_district" class="form-control form-control-sm" value="{$order.address_district|escape}" required>
				</div>
				<div class="col-md-4">
					<label class="form-label small">{'Shipping fee'|adminT}</label>
					<input type="text" name="shipping" id="orderEditShipping" class="form-control form-control-sm" value="{$order.shipping|escape}">
				</div>
				<div class="col-12">
					<label class="form-label small">{'Address'|adminT}</label>
					<textarea name="address_text" class="form-control form-control-sm" rows="2" required>{$order.address_text|escape}</textarea>
				</div>
				<div class="col-12">
					<label class="form-label small">{'Note'|adminT}</label>
					<textarea name="note" class="form-control form-control-sm" rows="2">{$order.note|escape}</textarea>
				</div>
			</div>

			<h3 class="h6 mb-2">{'Products'|adminT}</h3>
			<div class="table-responsive mb-3">
				<table class="table table-sm align-middle" id="orderEditLinesTable">
					<thead>
						<tr>
							<th>{'Product'|adminT}</th>
							<th style="width:90px">{'Qty'|adminT}</th>
							<th style="width:110px">{'Unit'|adminT}</th>
							<th style="width:110px">{'Line'|adminT}</th>
							<th style="width:48px"></th>
						</tr>
					</thead>
					<tbody id="orderEditLinesBody">
						{foreach $order.items as $idx => $item}
						<tr class="order-edit-line" data-idx="{$idx}">
							<td>
								<input type="hidden" name="items[{$idx}][id_product]" value="{$item.id_product}">
								<input type="hidden" name="items[{$idx}][id_variation]" value="{$item.id_variation|default:0}">
								<input type="hidden" name="items[{$idx}][variation_label]" value="{$item.variation_label|default:''|escape}">
								<input type="text" name="items[{$idx}][product_name]" class="form-control form-control-sm" value="{$item.product_name|escape}">
								{if $item.variation_label|default:''}<div class="small text-muted mt-1">{$item.variation_label|escape}</div>{/if}
							</td>
							<td><input type="number" name="items[{$idx}][qty]" class="form-control form-control-sm order-edit-qty" value="{$item.qty|escape}" min="0.001" step="any"></td>
							<td><input type="text" name="items[{$idx}][price]" class="form-control form-control-sm order-edit-price" value="{$item.price|escape}"></td>
							<td class="order-edit-line-total small fw-semibold">{$item.total_formatted}</td>
							<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger order-edit-remove" title="{'Remove'|adminT}">&times;</button></td>
						</tr>
						{/foreach}
					</tbody>
				</table>
			</div>

			<div class="mb-4 position-relative">
				<label class="form-label small">{'Add product'|adminT}</label>
				<input type="search" id="orderProductSearch" class="form-control form-control-sm" placeholder="{'Search by name, SKU or barcode…'|adminT}" autocomplete="off">
				<div id="orderProductSearchResults" class="list-group position-absolute w-100 shadow-sm" style="z-index:20;display:none;max-height:240px;overflow:auto;"></div>
			</div>

			<div class="row g-3 mb-4">
				<div class="col-md-4">
					<label class="form-label small">{'Order discount type'|adminT}</label>
					<select name="manual_discount_type" id="orderDiscountType" class="form-select form-select-sm">
						<option value=""{if $order.manual_discount_type|default:'' == ''} selected{/if}>{'None'|adminT}</option>
						<option value="fixed"{if $order.manual_discount_type|default:'' == 'fixed'} selected{/if}>{'Fixed amount'|adminT}</option>
						<option value="percent"{if $order.manual_discount_type|default:'' == 'percent'} selected{/if}>{'Percent %'|adminT}</option>
					</select>
				</div>
				<div class="col-md-4">
					<label class="form-label small">{'Discount value'|adminT}</label>
					<input type="text" name="manual_discount_value" id="orderDiscountValue" class="form-control form-control-sm" value="{$order.manual_discount_value|default:'0'|escape}">
				</div>
			</div>

			<div class="border rounded p-3 bg-light mb-3" id="orderEditTotalsPreview"
				data-old-total="{$order.total|escape}"
				data-coupon="{$order.coupon_discount|default:0|escape}"
				data-promotion="{$order.promotion_discount|default:0|escape}"
				data-payment="{$order.payment_discount|default:0|escape}">
				<div class="d-flex justify-content-between small mb-1"><span>{'Subtotal'|adminT}</span><span id="previewSubtotal">{$order.subtotal_formatted}</span></div>
				{if $order.coupon_discount > 0}
				<div class="d-flex justify-content-between small text-success mb-1"><span>{'Coupon'|adminT}</span><span>-{$order.coupon_discount_formatted}</span></div>
				{/if}
				{if $order.promotion_discount > 0}
				<div class="d-flex justify-content-between small text-success mb-1"><span>{'Promotion'|adminT}</span><span>-{$order.promotion_discount_formatted}</span></div>
				{/if}
				{if $order.payment_discount > 0}
				<div class="d-flex justify-content-between small text-success mb-1"><span>{'Payment discount'|adminT}</span><span>-{$order.payment_discount_formatted}</span></div>
				{/if}
				<div class="d-flex justify-content-between small text-success mb-1"><span>{'Order discount'|adminT}</span><span id="previewManualDiscount">-{$order.manual_discount_formatted|default:'0'}</span></div>
				<div class="d-flex justify-content-between small mb-1"><span>{'Shipping'|adminT}</span><span id="previewShipping">{$order.shipping_formatted}</span></div>
				<div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2"><span>{'New total'|adminT}</span><span id="previewNewTotal">{$order.total_formatted}</span></div>
				<div class="d-flex justify-content-between small mt-2"><span>{'Previous total'|adminT}</span><span>{$order.total_formatted}</span></div>
				<div class="d-flex justify-content-between fw-semibold mt-1" id="previewDiffRow"><span>{'Difference'|adminT}</span><span id="previewDiff">0</span></div>
			</div>

			<div class="d-flex flex-wrap gap-2">
				<button type="submit" class="btn btn-dark btn-sm">{'Save order changes'|adminT}</button>
				<button type="button" class="btn btn-outline-secondary btn-sm" id="cancelOrderEdit">{'Cancel'|adminT}</button>
			</div>
		</form>
		{/if}
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
			{if $order.manual_discount|default:0 > 0}
			<p class="mb-1 d-flex justify-content-between text-success"><span>{'Order discount'|adminT}{if $order.manual_discount_type == 'percent'} ({$order.manual_discount_value|escape}%){/if}</span><span>-{$order.manual_discount_formatted}</span></p>
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

		<div class="admin-panel mt-4">
			<h2 class="h6 mb-3">{'Invoice'|adminT}</h2>
			{if $order.has_invoice|default:false}
			<p class="mb-2">
				<a href="{$order.invoice_view_url|escape}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark">
					{$order.invoice_label|escape} ↗
				</a>
			</p>
			<p class="small text-muted mb-3">
				{if $order.invoice_type == 'file'}{'Uploaded file'|adminT}{else}{'External link'|adminT}{/if}
			</p>
			<form method="post" class="mb-3" onsubmit="return confirm('{'Delete invoice?'|adminT}');">
				<input type="hidden" name="deleteInvoice" value="1">
				<input type="hidden" name="token" value="{$adminToken}">
				<button type="submit" class="btn btn-sm btn-outline-danger">{'Delete invoice'|adminT}</button>
			</form>
			<hr class="my-3">
			{/if}
			<form method="post" enctype="multipart/form-data">
				<input type="hidden" name="saveInvoice" value="1">
				<input type="hidden" name="token" value="{$adminToken}">
				<div class="mb-3">
					<label class="form-label small">{'Display name'|adminT}</label>
					<input type="text" name="invoice_name" class="form-control form-control-sm" value="{$order.invoice_name|default:''|escape}" placeholder="{'Invoice'|adminT}" maxlength="128">
				</div>
				<div class="mb-3">
					<label class="form-label small">{'Upload file (PDF, JPG, PNG, WEBP)'|adminT}</label>
					<input type="file" name="invoice_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
				</div>
				<div class="mb-3">
					<label class="form-label small">{'Or external URL'|adminT}</label>
					<input type="url" name="invoice_url" class="form-control form-control-sm" value="{if $order.invoice_type|default:'' == 'url'}{$order.invoice_url|escape}{/if}" placeholder="https://...">
				</div>
				<button type="submit" class="btn btn-dark btn-sm w-100">{'Save invoice'|adminT}</button>
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

{if $canEditOrder}
<script>
(function () {
	var form = document.getElementById('orderEditForm');
	var readonlyProducts = document.getElementById('orderProductsReadonly');
	var toggleBtn = document.getElementById('toggleOrderEdit');
	var cancelBtn = document.getElementById('cancelOrderEdit');
	var body = document.getElementById('orderEditLinesBody');
	var searchInput = document.getElementById('orderProductSearch');
	var searchResults = document.getElementById('orderProductSearchResults');
	var previewBox = document.getElementById('orderEditTotalsPreview');
	var searchUrl = '{$orderProductSearchUrl|escape:'javascript'}';
	var token = '{$adminToken|escape:'javascript'}';
	var lineIndex = {$order.items|@count};
	var searchTimer = null;
	var currencyFmt = function (n) {
		try {
			return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(n);
		} catch (e) {
			return (Math.round(n * 100) / 100).toFixed(2);
		}
	};

	function parseNum(v) {
		return parseFloat(String(v || '0').replace(',', '.')) || 0;
	}

	function setEditMode(on) {
		if (!form) return;
		form.style.display = on ? '' : 'none';
		if (readonlyProducts) readonlyProducts.style.display = on ? 'none' : '';
		if (on) recalcPreview();
	}

	if (toggleBtn) toggleBtn.addEventListener('click', function () { setEditMode(true); });
	if (cancelBtn) cancelBtn.addEventListener('click', function () { setEditMode(false); searchResults.style.display = 'none'; });

	function recalcPreview() {
		if (!previewBox || !body) return;
		var subtotal = 0;
		body.querySelectorAll('.order-edit-line').forEach(function (row) {
			var qty = parseNum(row.querySelector('.order-edit-qty').value);
			var price = parseNum(row.querySelector('.order-edit-price').value);
			var line = Math.round(qty * price * 100) / 100;
			subtotal += line;
			var cell = row.querySelector('.order-edit-line-total');
			if (cell) cell.textContent = currencyFmt(line);
		});
		subtotal = Math.round(subtotal * 100) / 100;
		var coupon = parseNum(previewBox.getAttribute('data-coupon'));
		var promotion = parseNum(previewBox.getAttribute('data-promotion'));
		var payment = parseNum(previewBox.getAttribute('data-payment'));
		var shipping = parseNum(document.getElementById('orderEditShipping').value);
		var dtype = document.getElementById('orderDiscountType').value;
		var dval = parseNum(document.getElementById('orderDiscountValue').value);
		var manual = 0;
		if (dtype === 'percent' && dval > 0) manual = Math.round(subtotal * (Math.min(100, dval) / 100) * 100) / 100;
		else if (dtype === 'fixed' && dval > 0) manual = Math.min(subtotal, dval);
		var newTotal = Math.max(0, Math.round((subtotal - coupon - promotion - payment - manual + shipping) * 100) / 100);
		var oldTotal = parseNum(previewBox.getAttribute('data-old-total'));
		var diff = Math.round((newTotal - oldTotal) * 100) / 100;
		document.getElementById('previewSubtotal').textContent = currencyFmt(subtotal);
		document.getElementById('previewManualDiscount').textContent = '-' + currencyFmt(manual);
		document.getElementById('previewShipping').textContent = currencyFmt(shipping);
		document.getElementById('previewNewTotal').textContent = currencyFmt(newTotal);
		var diffEl = document.getElementById('previewDiff');
		if (diff > 0) diffEl.textContent = '+' + currencyFmt(diff) + ' ({'extra'|adminT|escape:'javascript'})';
		else if (diff < 0) diffEl.textContent = '−' + currencyFmt(Math.abs(diff));
		else diffEl.textContent = currencyFmt(0);
	}

	if (body) {
		body.addEventListener('input', function (e) {
			if (e.target.classList.contains('order-edit-qty') || e.target.classList.contains('order-edit-price')) recalcPreview();
		});
		body.addEventListener('click', function (e) {
			var btn = e.target.closest('.order-edit-remove');
			if (!btn) return;
			var row = btn.closest('tr');
			if (row) row.remove();
			recalcPreview();
		});
	}

	['orderEditShipping', 'orderDiscountType', 'orderDiscountValue'].forEach(function (id) {
		var el = document.getElementById(id);
		if (el) el.addEventListener('input', recalcPreview);
		if (el) el.addEventListener('change', recalcPreview);
	});

	function addProductLine(item) {
		var idx = lineIndex++;
		var tr = document.createElement('tr');
		tr.className = 'order-edit-line';
		tr.innerHTML =
			'<td>' +
				'<input type="hidden" name="items[' + idx + '][id_product]" value="' + item.id_product + '">' +
				'<input type="hidden" name="items[' + idx + '][id_variation]" value="0">' +
				'<input type="hidden" name="items[' + idx + '][variation_label]" value="">' +
				'<input type="text" name="items[' + idx + '][product_name]" class="form-control form-control-sm" value="' + String(item.product_name || '').replace(/"/g, '&quot;') + '">' +
			'</td>' +
			'<td><input type="number" name="items[' + idx + '][qty]" class="form-control form-control-sm order-edit-qty" value="1" min="0.001" step="any"></td>' +
			'<td><input type="text" name="items[' + idx + '][price]" class="form-control form-control-sm order-edit-price" value="' + item.price + '"></td>' +
			'<td class="order-edit-line-total small fw-semibold">' + currencyFmt(item.price) + '</td>' +
			'<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger order-edit-remove">&times;</button></td>';
		body.appendChild(tr);
		recalcPreview();
	}

	function runSearch(q) {
		if (q.length < 2) {
			searchResults.style.display = 'none';
			searchResults.innerHTML = '';
			return;
		}
		fetch(searchUrl + '?token=' + encodeURIComponent(token) + '&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				var items = (data && data.items) || [];
				if (!items.length) {
					searchResults.innerHTML = '<div class="list-group-item small text-muted">{"No products found"|adminT|escape:"javascript"}</div>';
					searchResults.style.display = '';
					return;
				}
				searchResults.innerHTML = items.map(function (it) {
					return '<button type="button" class="list-group-item list-group-item-action py-2" data-id="' + it.id_product + '" data-name="' + String(it.product_name).replace(/"/g, '&quot;') + '" data-price="' + it.price + '">' +
						'<div class="fw-semibold small">' + it.product_name + '</div>' +
						'<div class="small text-muted">' + (it.stock_code || '') + ' · ' + it.price_formatted + '</div></button>';
				}).join('');
				searchResults.style.display = '';
			})
			.catch(function () { searchResults.style.display = 'none'; });
	}

	if (searchInput) {
		searchInput.addEventListener('input', function () {
			clearTimeout(searchTimer);
			searchTimer = setTimeout(function () { runSearch(searchInput.value.trim()); }, 250);
		});
	}

	if (searchResults) {
		searchResults.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-id]');
			if (!btn) return;
			addProductLine({
				id_product: parseInt(btn.getAttribute('data-id'), 10),
				product_name: btn.getAttribute('data-name'),
				price: parseNum(btn.getAttribute('data-price'))
			});
			searchInput.value = '';
			searchResults.style.display = 'none';
			searchResults.innerHTML = '';
		});
	}

	document.addEventListener('click', function (e) {
		if (!searchResults.contains(e.target) && e.target !== searchInput) {
			searchResults.style.display = 'none';
		}
	});
})();
</script>
{/if}
