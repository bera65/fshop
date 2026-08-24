<div class="dash-page dash-analytics-page">
	{if $adminHooks.admin_dashboard_top}
	<div class="dash-hook dash-hook--top mb-3">
		{$adminHooks.admin_dashboard_top nofilter}
	</div>
	{/if}

	{* —— Top KPI strip —— *}
	<div class="dash-kpi-strip mb-3">
		<a href="{$adminUrl}products" class="dash-kpi-tile">
			<span class="dash-kpi-tile__icon dash-kpi-tile__icon--blue"><i data-lucide="wallet"></i></span>
			<span class="dash-kpi-tile__body">
				<span class="dash-kpi-tile__label">{'Inventory value'|adminT}</span>
				<strong class="dash-kpi-tile__value">{$analytics.kpi.inventory_value_formatted|escape}</strong>
			</span>
		</a>
		<a href="{$adminUrl}orders" class="dash-kpi-tile">
			<span class="dash-kpi-tile__icon dash-kpi-tile__icon--orange"><i data-lucide="shopping-bag"></i></span>
			<span class="dash-kpi-tile__body">
				<span class="dash-kpi-tile__label">{'Total orders'|adminT}</span>
				<strong class="dash-kpi-tile__value">{$analytics.kpi.orders_total|escape}</strong>
			</span>
		</a>
		<a href="{$adminUrl}products" class="dash-kpi-tile">
			<span class="dash-kpi-tile__icon dash-kpi-tile__icon--green"><i data-lucide="package"></i></span>
			<span class="dash-kpi-tile__body">
				<span class="dash-kpi-tile__label">{'Total products'|adminT}</span>
				<strong class="dash-kpi-tile__value">{$analytics.kpi.products_total|escape}</strong>
			</span>
		</a>
		<a href="{$adminUrl}marketplace-products" class="dash-kpi-tile">
			<span class="dash-kpi-tile__icon dash-kpi-tile__icon--teal"><i data-lucide="layers"></i></span>
			<span class="dash-kpi-tile__body">
				<span class="dash-kpi-tile__label">{'Total listings'|adminT}</span>
				<strong class="dash-kpi-tile__value">{$analytics.kpi.listings_total|escape}</strong>
			</span>
		</a>
		<a href="{$adminUrl}customers" class="dash-kpi-tile">
			<span class="dash-kpi-tile__icon dash-kpi-tile__icon--purple"><i data-lucide="users"></i></span>
			<span class="dash-kpi-tile__body">
				<span class="dash-kpi-tile__label">{'Customers'|adminT}</span>
				<strong class="dash-kpi-tile__value">{$analytics.kpi.customers_total|escape}</strong>
			</span>
		</a>
	</div>

	{if $adminHooks.admin_dashboard_kpi}
	<div class="dash-hook dash-hook--kpi mb-3">
		{$adminHooks.admin_dashboard_kpi nofilter}
	</div>
	{/if}

	{* —— Period filter —— *}
	<div class="dash-period-bar mb-3">
		<div class="dash-period-bar__left">
			<strong class="dash-period-bar__title">{'Data period'|adminT}</strong>
			<div class="dash-period-pills">
				{foreach $periodPills as $pill}
				<a href="{$adminUrl}dashboard?period={$pill.key|escape}" class="dash-period-pill{if $period == $pill.key} is-active{/if}">{$pill.label|adminT}</a>
				{/foreach}
			</div>
		</div>
		<span class="dash-period-bar__current">{$analytics.period_label|escape}</span>
	</div>

	{* —— Daily sales + KPI grid —— *}
	<div class="row g-3 mb-3">
		<div class="col-xl-6">
			<div class="dash-panel h-100">
				<div class="dash-panel__head">
					<div>
						<h2 class="dash-panel__title">{'Daily sales'|adminT} — {$analytics.period_label|escape}</h2>
						<p class="dash-panel__sub text-muted mb-0">{'Daily store and marketplace revenue'|adminT}</p>
					</div>
				</div>
				<div class="dash-panel__body">
					<div class="dash-chart-wrap">
						<canvas id="chartDailySales" height="280"></canvas>
					</div>
				</div>
			</div>
		</div>
		<div class="col-xl-6">
			<div class="dash-metric-grid">
				<a href="{$adminUrl}orders" class="dash-metric-card">
					<span class="dash-metric-card__label">{'Order count'|adminT}</span>
					<strong class="dash-metric-card__value">{$analytics.period_stats.orders|escape}</strong>
				</a>
				<a href="{$adminUrl}orders?status={$statusPending}" class="dash-metric-card">
					<span class="dash-metric-card__label">{'Awaiting shipment'|adminT}</span>
					<strong class="dash-metric-card__value">{$analytics.period_stats.awaiting_shipment|escape}</strong>
				</a>
				<div class="dash-metric-card">
					<span class="dash-metric-card__label">{'Units sold'|adminT}</span>
					<strong class="dash-metric-card__value">{$analytics.period_stats.sold_qty|escape}</strong>
				</div>
				<a href="{$adminUrl}orders?status={$statusShipped}" class="dash-metric-card">
					<span class="dash-metric-card__label">{'Shipped'|adminT}</span>
					<strong class="dash-metric-card__value">{$analytics.period_stats.shipped|escape}</strong>
				</a>
				<a href="{$adminUrl}orders?status={$statusProcessing}" class="dash-metric-card">
					<span class="dash-metric-card__label">{'Processing'|adminT}</span>
					<strong class="dash-metric-card__value">{$analytics.period_stats.processing|escape}</strong>
				</a>
				<div class="dash-metric-card dash-metric-card--wide">
					<span class="dash-metric-card__label">{'Period profit'|adminT}</span>
					<div class="dash-metric-card__rows">
						<div><span>{'Turnover'|adminT}</span><strong>{$analytics.period_stats.revenue_formatted|escape}</strong></div>
						<div><span>{'Profit'|adminT}</span><strong>{$analytics.period_stats.profit_formatted|escape}</strong></div>
						<div><span>{'Rate'|adminT}</span><strong>%{$analytics.period_stats.profit_rate|escape}</strong></div>
					</div>
				</div>
				<div class="dash-metric-card">
					<span class="dash-metric-card__label">{'Cancellations'|adminT}</span>
					<div class="dash-metric-card__rows">
						<div><span>{'Qty'|adminT}</span><strong>{$analytics.period_stats.cancel_count|escape}</strong></div>
						<div><span>{'Turnover'|adminT}</span><strong>{$analytics.period_stats.cancel_amount_formatted|escape}</strong></div>
						<div><span>{'Rate'|adminT}</span><strong>%{$analytics.period_stats.cancel_rate|escape}</strong></div>
					</div>
				</div>
				<div class="dash-metric-card">
					<span class="dash-metric-card__label">{'Returns'|adminT}</span>
					<div class="dash-metric-card__rows">
						<div><span>{'Qty'|adminT}</span><strong>{$analytics.period_stats.return_count|escape}</strong></div>
						<div><span>{'Turnover'|adminT}</span><strong>{$analytics.period_stats.return_amount_formatted|escape}</strong></div>
						<div><span>{'Rate'|adminT}</span><strong>%{$analytics.period_stats.return_rate|escape}</strong></div>
					</div>
				</div>
				<div class="dash-metric-card dash-metric-card--mini">
					<span class="dash-metric-card__label">+{'Products'|adminT}</span>
					<strong class="dash-metric-card__value">{$analytics.period_stats.products_added|escape}</strong>
				</div>
				<div class="dash-metric-card dash-metric-card--mini">
					<span class="dash-metric-card__label">+{'Listings'|adminT}</span>
					<strong class="dash-metric-card__value">{$analytics.period_stats.listings_added|escape}</strong>
				</div>
				<div class="dash-metric-card dash-metric-card--mini">
					<span class="dash-metric-card__label">{'Avg. basket'|adminT}</span>
					<strong class="dash-metric-card__value dash-metric-card__value--sm">{$analytics.period_stats.avg_basket_formatted|escape}</strong>
				</div>
				<div class="dash-metric-card dash-metric-card--mini">
					<span class="dash-metric-card__label">{'Avg. cost'|adminT}</span>
					<strong class="dash-metric-card__value dash-metric-card__value--sm">{$analytics.period_stats.avg_cost_formatted|escape}</strong>
				</div>
			</div>
		</div>
	</div>

	{* —— Marketplace questions —— *}
	<div class="dash-qna-strip mb-3">
		{foreach $analytics.questions as $q}
		<a href="{$q.url|escape}" class="dash-qna-card dash-qna-card--{$q.key|escape}">
			<span class="dash-qna-card__head">
				{if $q.icon_url|default:''}
				<img src="{$q.icon_url|escape}" alt="{$q.label|escape}" class="dash-qna-card__icon" width="28" height="28" loading="lazy" onerror="this.style.display='none'">
				{/if}
				<span class="dash-qna-card__label">{$q.label|escape} {'question'|adminT}</span>
			</span>
			<strong class="dash-qna-card__value">{$q.unanswered|escape}<span class="text-muted">/{$q.total|escape}</span></strong>
		</a>
		{/foreach}
	</div>

	{* —— Product performance —— *}
	<div class="dash-panel mb-3">
		<div class="dash-panel__head dash-panel__head--split">
			<div>
				<h2 class="dash-panel__title">{'Product performance'|adminT}</h2>
				<p class="dash-panel__sub text-muted mb-0">{'Period revenue and units sold'|adminT}</p>
			</div>
			<a href="{$adminUrl}products" class="btn btn-sm btn-outline-secondary">{'All products'|adminT}</a>
		</div>
		<div class="dash-panel__body">
			{if $analytics.product_performance|@count}
			<div class="dash-perf-grid">
				{foreach $analytics.product_performance as $prod}
				<a href="{$prod.edit_url|escape}" class="dash-perf-card">
					<img src="{$prod.image_url|escape}" alt="" class="dash-perf-card__img" width="56" height="56">
					<span class="dash-perf-card__info">
						<strong class="dash-perf-card__name">{$prod.product_name|escape}</strong>
						<span class="dash-perf-card__id">#{$prod.id_product|escape}</span>
						<span class="dash-perf-card__sales">{$prod.revenue_formatted|escape} / {$prod.sold_qty|escape} {'pcs'|adminT}</span>
						<span class="dash-perf-card__stock">{$prod.stock_formatted|escape} {'Stock'|adminT}</span>
					</span>
				</a>
				{/foreach}
			</div>
			{else}
			<p class="text-muted mb-0">{'No sales data yet.'|adminT}</p>
			{/if}
		</div>
	</div>

	{* —— Platform revenue waterfall (Orion) —— *}
	<div class="dash-panel dash-mp-sales-panel mb-4">
		<div class="dash-panel__head dash-panel__head--split dash-mp-sales-hd">
			<div class="dash-mp-sales-title">
				<span class="dash-mp-sales-ico"><i data-lucide="bar-chart-2"></i></span>
				<div>
					<h2 class="dash-panel__title mb-0">{'Sales revenue'|adminT}</h2>
					<p class="dash-panel__sub text-muted mb-0">{'Marketplace-based period revenue'|adminT}</p>
				</div>
			</div>
			<span class="dash-period-chip">{$analytics.period_range_label|escape}</span>
		</div>
		<div class="dash-panel__body">
			{if $analytics.mp_sales_chart.items|@count}
			<div class="dash-mp-chart-wrap">
				<canvas id="chartMpSales"></canvas>
			</div>
			{else}
			<p class="text-muted mb-0">{'No sales data yet.'|adminT}</p>
			{/if}
		</div>
	</div>

	{* —— Recent orders (ops) —— *}
	<div class="row g-4 mb-4">
		<div class="col-12">
			<div class="dash-panel">
				<div class="dash-panel__head dash-panel__head--split">
					<h2 class="dash-panel__title">{'Last 50 Orders'|adminT}</h2>
					<a href="{$adminUrl}orders" class="dash-panel__link">{'View all'|adminT}</a>
				</div>
				<div class="dash-panel__body p-0">
					{if $recentOrders|@count}
					<div class="table-responsive dash-orders-scroll adm-orders-shell">
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
								{include file='admin/partials/order-list-rows.tpl' orderListMode='dashboard'}
							</tbody>
						</table>
					</div>
					{else}
					<p class="text-muted p-4 mb-0">{'No orders yet.'|adminT}</p>
					{/if}
				</div>
			</div>

			{if $adminHooks.admin_dashboard_main_left}
			<div class="dash-hook dash-hook--main-left mt-4">
				{$adminHooks.admin_dashboard_main_left nofilter}
			</div>
			{/if}
		</div>
	</div>

	<div class="row g-4 mb-4">
		<div class="col-12">
			<div class="dash-panel">
				<div class="dash-panel__head dash-panel__head--split">
					<h2 class="dash-panel__title">{'Marketplace Orders'|adminT}</h2>
					<a href="{$adminUrl}marketplace-orders" class="dash-panel__link">{'View all'|adminT}</a>
				</div>
				<div class="dash-panel__body p-0">
					{if $recentMarketplaceOrders|@count}
					<div class="table-responsive dash-orders-scroll adm-orders-shell">
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
								{include file='admin/partials/marketplace-order-list-rows.tpl' marketplaceOrders=$recentMarketplaceOrders mpOrderListMode='dashboard'}
							</tbody>
						</table>
					</div>
					{else}
					<div class="mp-order-empty p-4 mb-0">{'No marketplace orders yet.'|adminT}</div>
					{/if}
				</div>
			</div>
		</div>
	</div>

	{if $adminHooks.admin_dashboard_main_right}
	<div class="row g-4 mb-4">
		<div class="col-12">
			<div class="dash-hook dash-hook--main-right">
				{$adminHooks.admin_dashboard_main_right nofilter}
			</div>
		</div>
	</div>
	{/if}

	{if $adminHooks.admin_dashboard_bottom}
	<div class="dash-hook dash-hook--bottom">
		{$adminHooks.admin_dashboard_bottom nofilter}
	</div>
	{/if}
</div>

<script>
window.__dashboardCharts = {
	dailySales: {$chartDailySales nofilter},
	mpSales: {$chartMpSales nofilter}
};

(function () {
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

	document.querySelectorAll('.adm-order-row').forEach(function (row) {
		row.addEventListener('click', function (e) {
			if (e.target.closest('a, button, input, select, .dropdown-menu, .adm-order-select-cell, .adm-order-action-cell')) {
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

	if (window.lucide) window.lucide.createIcons();
})();
</script>
