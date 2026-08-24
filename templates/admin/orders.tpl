{if $flash|default:''}
<div class="alert alert-{$flashType|default:'info'} py-2">{$flash|escape}</div>
{/if}

<form method="get" action="{$adminUrl}orders" class="adm-orders-filters admin-panel mb-3" id="admOrdersFilterForm">
	<div class="adm-orders-filters__hd">
		<div>
			<h2 class="h6 mb-1">{'Filters'|adminT}</h2>
			<p class="text-muted small mb-0">{'Click a row to expand products'|adminT}</p>
		</div>
		<div class="adm-orders-goal">
			<div class="adm-orders-goal__meta">
				<span>{$orderGoal.used|escape} / {$orderGoal.quota|escape} {'order goal'|adminT}</span>
				<strong>%{$orderGoal.pct|string_format:"%.2f"}</strong>
				<button type="button" class="adm-orders-goal__edit" data-bs-toggle="modal" data-bs-target="#orderGoalModal" title="{'Edit order goal'|adminT}" aria-label="{'Edit order goal'|adminT}">
					<i data-lucide="pencil"></i>
				</button>
			</div>
			<div class="adm-orders-goal__track">
				<span class="adm-orders-goal__fill" style="width: {$orderGoal.pct}%"></span>
			</div>
		</div>
	</div>

	<div class="adm-orders-filters__bd">
		<div class="adm-orders-filter-grid">
			<div class="adm-orders-fg">
				<label for="ord_reference">{'Order no'|adminT}</label>
				<input type="text" id="ord_reference" name="reference" class="form-control form-control-sm" value="{$orderFilters.reference|escape}" placeholder="{'Order no'|adminT}">
			</div>
			<div class="adm-orders-fg">
				<label for="ord_status">{'Status'|adminT}</label>
				<select id="ord_status" name="status" class="form-select form-select-sm">
					<option value="0"{if $statusFilter == 0} selected{/if}>{'All'|adminT}</option>
					{foreach $statusOptions as $statusId => $statusLabel}
					<option value="{$statusId}"{if $statusFilter == $statusId} selected{/if}>{$statusLabel|escape}</option>
					{/foreach}
				</select>
			</div>
			<div class="adm-orders-fg">
				<label for="ord_date_from">{'Start date'|adminT}</label>
				<input type="date" id="ord_date_from" name="date_from" class="form-control form-control-sm" value="{$orderFilters.date_from|escape}">
			</div>
			<div class="adm-orders-fg">
				<label for="ord_date_to">{'End date'|adminT}</label>
				<input type="date" id="ord_date_to" name="date_to" class="form-control form-control-sm" value="{$orderFilters.date_to|escape}">
			</div>

			<div class="adm-orders-fg">
				<label for="ord_customer">{'Customer'|adminT}</label>
				<input type="text" id="ord_customer" name="customer" class="form-control form-control-sm" value="{$orderFilters.customer|escape}" placeholder="{'Full name'|adminT}">
			</div>
			<div class="adm-orders-fg">
				<label for="ord_payment">{'Payment type'|adminT}</label>
				<select id="ord_payment" name="payment_method" class="form-select form-select-sm">
					<option value=""{if $orderFilters.payment_method == ''} selected{/if}>{'All'|adminT}</option>
					{foreach $paymentFilterOptions as $payKey => $payLabel}
					<option value="{$payKey|escape}"{if $orderFilters.payment_method == $payKey} selected{/if}>{$payLabel|escape}</option>
					{/foreach}
				</select>
			</div>
			<div class="adm-orders-fg">
				<label for="ord_sku">{'Stock code'|adminT}</label>
				<input type="text" id="ord_sku" name="sku" class="form-control form-control-sm" value="{$orderFilters.sku|escape}" placeholder="SKU">
			</div>
			<div class="adm-orders-fg">
				<label for="ord_product">{'Product name'|adminT}</label>
				<input type="text" id="ord_product" name="product_name" class="form-control form-control-sm" value="{$orderFilters.product_name|escape}" placeholder="{'Product name'|adminT}">
			</div>

			<div class="adm-orders-fg">
				<label for="ord_tracking">{'Shipping barcode'|adminT}</label>
				<input type="text" id="ord_tracking" name="tracking_number" class="form-control form-control-sm" value="{$orderFilters.tracking_number|escape}">
			</div>
			<div class="adm-orders-fg">
				<label for="ord_channel">{'Channel'|adminT}</label>
				<select id="ord_channel" name="channel" class="form-select form-select-sm">
					<option value="all"{if $orderFilters.channel == 'all'} selected{/if}>{'All channels'|adminT}</option>
					<option value="store"{if $orderFilters.channel == 'store'} selected{/if}>{'Store'|adminT}</option>
					<option value="pos"{if $orderFilters.channel == 'pos'} selected{/if}>POS</option>
				</select>
			</div>
			<div class="adm-orders-fg">
				<label for="ord_cargo">{'Contracted cargo'|adminT}</label>
				<select id="ord_cargo" name="cargo_company" class="form-select form-select-sm">
					<option value=""{if $orderFilters.cargo_company == ''} selected{/if}>{'All'|adminT}</option>
					{foreach $cargoFilterOptions as $cargoName}
					<option value="{$cargoName|escape}"{if $orderFilters.cargo_company == $cargoName} selected{/if}>{$cargoName|escape}</option>
					{/foreach}
				</select>
			</div>
			<div class="adm-orders-fg">
				<label for="ord_sort">{'Sorting'|adminT}</label>
				<select id="ord_sort" name="sort" class="form-select form-select-sm">
					<option value="date_desc"{if $orderFilters.sort == 'date_desc'} selected{/if}>{'Order date descending'|adminT}</option>
					<option value="date_asc"{if $orderFilters.sort == 'date_asc'} selected{/if}>{'Order date ascending'|adminT}</option>
					<option value="total_desc"{if $orderFilters.sort == 'total_desc'} selected{/if}>{'Total descending'|adminT}</option>
					<option value="total_asc"{if $orderFilters.sort == 'total_asc'} selected{/if}>{'Total ascending'|adminT}</option>
				</select>
			</div>
		</div>

		<div class="adm-orders-filter-actions">
			<button type="submit" class="btn btn-sm btn-dark">
				<i data-lucide="search" style="width:15px;height:15px"></i>
				{'Search'|adminT}
			</button>
			<a href="{$adminUrl}orders" class="btn btn-sm btn-outline-secondary">{'Clear'|adminT}</a>
		</div>
	</div>
</form>

<div class="adm-orders-bulk admin-panel mb-3">
	<div class="adm-orders-bulk__bd">
		<div class="adm-orders-bulk__left">
			<label class="adm-orders-bulk__check">
				<input type="checkbox" id="admOrdersSelectAll">
				<span>{'Apply to selected'|adminT}</span>
			</label>
			<button type="button" class="btn btn-sm btn-outline-secondary" id="admOrdersBulkPrint">
				<i data-lucide="printer" style="width:14px;height:14px"></i>
				{'Print selected'|adminT}
			</button>
		</div>
	</div>
</div>

<div class="admin-panel adm-orders-table-panel">
	<div class="adm-orders-table-panel__hd">
		<div>
			<h2 class="h6 mb-0">{'Order list'|adminT}</h2>
			<p class="text-muted small mb-0">{$ordersTotal} {'records'|adminT}</p>
		</div>
	</div>
	<div class="p-0">
		{if $orders|@count}
		<div class="table-responsive adm-orders-shell">
			<table class="table adm-orders-table mb-0 align-middle">
				<thead>
					<tr>
						<th style="width:96px"><span class="visually-hidden">{'Select'|adminT}</span></th>
						<th>{'Channel'|adminT}</th>
						<th>{'Order date'|adminT}</th>
						<th class="text-center" style="width:148px">{'Actions'|adminT}</th>
						<th>{'Order no'|adminT}</th>
						<th>{'Status'|adminT}</th>
						<th>{'Customer'|adminT}</th>
						<th>{'Payment'|adminT}</th>
						<th>{'Cost'|adminT}</th>
						<th>{'Total'|adminT}</th>
					</tr>
				</thead>
				<tbody>
					{include file='admin/partials/order-list-rows.tpl'}
				</tbody>
			</table>
		</div>
		{else}
		<p class="text-muted p-4 mb-0">{'No records found.'|adminT}</p>
		{/if}
	</div>
</div>

{if $pagination.total_pages > 1}
<div class="ps-pagination-wrap mt-3">
	{include file='admin/plugin/pagination.tpl'}
</div>
{/if}

<div class="modal fade" id="orderGoalModal" tabindex="-1" aria-labelledby="orderGoalModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-sm">
		<form method="post" class="modal-content">
			<input type="hidden" name="saveOrderGoal" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<div class="modal-header">
				<h5 class="modal-title" id="orderGoalModalLabel">{'Edit order goal'|adminT}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<label class="form-label" for="order_goal_target">{'Target order count'|adminT}</label>
				<input type="number" min="1" max="1000000" class="form-control" name="order_goal_target" id="order_goal_target" value="{$orderGoal.quota|escape}" required>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
				<button type="submit" class="btn btn-dark btn-sm">{'Save'|adminT}</button>
			</div>
		</form>
	</div>
</div>

<script>
(function () {
	var selectAll = document.getElementById('admOrdersSelectAll');
	var bulkPrint = document.getElementById('admOrdersBulkPrint');
	var printBase = '{$adminUrl|escape:'javascript'}order-print?auto=1&id=';

	function selectedIds() {
		return Array.prototype.map.call(document.querySelectorAll('.adm-order-select:checked'), function (el) {
			return el.value;
		});
	}

	function toggleDetail(id) {
		var row = document.querySelector('.adm-order-row[data-order-id="' + id + '"]');
		var detail = document.getElementById('adm-order-detail-' + id);
		if (!detail) return;
		var open = !detail.classList.contains('is-open');
		detail.classList.toggle('is-open', open);
		detail.hidden = !open;
		if (row) row.classList.toggle('is-open', open);
		if (window.lucide) window.lucide.createIcons();
	}

	if (selectAll) {
		selectAll.addEventListener('change', function () {
			document.querySelectorAll('.adm-order-select').forEach(function (cb) {
				cb.checked = selectAll.checked;
			});
		});
	}

	if (bulkPrint) {
		bulkPrint.addEventListener('click', function () {
			var ids = selectedIds();
			if (!ids.length) {
				if (window.AdminToast) {
					AdminToast.show('{'Select at least one order'|adminT|escape:'javascript'}', 'warning');
				}
				return;
			}
			ids.forEach(function (id, index) {
				window.setTimeout(function () {
					window.open(printBase + encodeURIComponent(id), '_blank');
				}, index * 350);
			});
		});
	}

	document.querySelectorAll('.adm-order-row').forEach(function (row) {
		row.addEventListener('click', function (e) {
			if (e.target.closest('a, button, input, select, .dropdown-menu, .adm-order-select-cell')) {
				return;
			}
			toggleDetail(row.getAttribute('data-order-id'));
		});
		row.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				toggleDetail(row.getAttribute('data-order-id'));
			}
		});
	});

	document.querySelectorAll('[data-order-expand]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.stopPropagation();
			toggleDetail(btn.getAttribute('data-order-expand'));
		});
	});

	if (window.lucide) {
		window.lucide.createIcons();
	}
})();
</script>
