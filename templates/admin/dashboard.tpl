<div class="dash-page">
	<div class="dash-hero mb-3">
		<div class="dash-hero__text">
			<p class="dash-hero__eyebrow">{'Overview'|adminT}</p>
			<h1 class="dash-hero__title">{'Dashboard'|adminT}</h1>
			<p class="dash-hero__sub">
				{'Welcome'|adminT}{if $adminUser}, {$adminUser.full_name|escape}{/if}. {'Track store performance and recent orders here.'|adminT}
			</p>
		</div>
		<div class="dash-hero__actions">
			<a href="{$adminUrl}product" class="btn btn-primary btn-sm">
				<i data-lucide="plus"></i> {'New Product'|adminT}
			</a>
			<a href="{$adminUrl}orders" class="btn btn-outline-primary btn-sm">
				<i data-lucide="shopping-bag"></i> {'Orders'|adminT}
			</a>
			<a href="{$domain}" class="btn btn-outline-dark btn-sm" target="_blank" rel="noopener">
				<i data-lucide="external-link"></i> {'View Store'|adminT}
			</a>
			<a href="{$adminUrl}settings" class="btn btn-outline-dark btn-sm">
				<i data-lucide="settings"></i> {'Settings'|adminT}
			</a>
		</div>
	</div>

	{if $adminHooks.admin_dashboard_top}
	<div class="dash-hook dash-hook--top">
		{$adminHooks.admin_dashboard_top nofilter}
	</div>
	{/if}

	<div class="row g-3 mb-4">
		<div class="col-xl-3 col-md-6">
			<a href="{$adminUrl}orders" class="dash-kpi dash-kpi--revenue">
				<div class="dash-kpi__icon"><i data-lucide="trending-up"></i></div>
				<div class="dash-kpi__body">
					<span class="dash-kpi__label">{'Today Revenue'|adminT}</span>
					<strong class="dash-kpi__value">{Tools::displayPrice($stats.revenue_today)}</strong>
					{if $revenueTrend != 0}
					<span class="dash-kpi__trend {if $revenueTrend > 0}is-up{else}is-down{/if}">
						{if $revenueTrend > 0}<i data-lucide="arrow-up-right"></i>{else}<i data-lucide="arrow-down-right"></i>{/if}
						{if $revenueTrend > 0}+{/if}{$revenueTrend}% {'% vs yesterday'|adminT}
					</span>
					{else}
					<span class="dash-kpi__trend">{'Yesterday:'|adminT} {Tools::displayPrice($stats.revenue_yesterday)}</span>
					{/if}
				</div>
			</a>
		</div>
		<div class="col-xl-3 col-md-6">
			<a href="{$adminUrl}orders" class="dash-kpi dash-kpi--orders">
				<div class="dash-kpi__icon"><i data-lucide="shopping-bag"></i></div>
				<div class="dash-kpi__body">
					<span class="dash-kpi__label">{'Today Orders'|adminT}</span>
					<strong class="dash-kpi__value">{$stats.orders_today}</strong>
					{if $ordersTrend != 0}
					<span class="dash-kpi__trend {if $ordersTrend > 0}is-up{else}is-down{/if}">
						{if $ordersTrend > 0}<i data-lucide="arrow-up-right"></i>{else}<i data-lucide="arrow-down-right"></i>{/if}
						{if $ordersTrend > 0}+{/if}{$ordersTrend}% {'% vs yesterday'|adminT}
					</span>
					{else}
					<span class="dash-kpi__trend">{'Yesterday:'|adminT} {$stats.orders_yesterday}</span>
					{/if}
				</div>
			</a>
		</div>
		<div class="col-xl-3 col-md-6">
			<a href="{$adminUrl}customers" class="dash-kpi dash-kpi--customers">
				<div class="dash-kpi__icon"><i data-lucide="users"></i></div>
				<div class="dash-kpi__body">
					<span class="dash-kpi__label">{'Registered Customers'|adminT}</span>
					<strong class="dash-kpi__value">{$stats.users_total}</strong>
					{if $stats.users_today > 0}
					<span class="dash-kpi__trend is-up"><i data-lucide="arrow-up-right"></i> +{$stats.users_today} {'today'|adminT}</span>
					{else}
					<span class="dash-kpi__trend">{'Active accounts'|adminT}</span>
					{/if}
				</div>
			</a>
		</div>
		<div class="col-xl-3 col-md-6">
			<a href="{$adminUrl}messages" class="dash-kpi dash-kpi--messages">
				<div class="dash-kpi__icon"><i data-lucide="message-square"></i></div>
				<div class="dash-kpi__body">
					<span class="dash-kpi__label">{'Unread Messages'|adminT}</span>
					<strong class="dash-kpi__value">{$stats.messages_unread}</strong>
					<span class="dash-kpi__trend">{'Last 30 Days Revenue'|adminT}: {$stats.revenue_month_formatted}</span>
				</div>
			</a>
		</div>
	</div>

	{if $adminHooks.admin_dashboard_kpi}
	<div class="dash-hook dash-hook--kpi mb-4">
		{$adminHooks.admin_dashboard_kpi nofilter}
	</div>
	{/if}

	<div class="row g-4 mb-4">
		<div class="col-xl-8">
			<div class="dash-panel">
				<div class="dash-panel__head">
					<div>
						<h2 class="dash-panel__title">{'Daily Sales Trend'|adminT}</h2>
						<p class="dash-panel__sub text-muted mb-0">{'Revenue performance'|adminT}</p>
					</div>
					<div class="dash-chart-filters" id="dashChartFilters" role="tablist">
						<button type="button" class="dash-chart-filter" data-range="1">{'Today'|adminT}</button>
						<button type="button" class="dash-chart-filter is-active" data-range="7">7 {'days'|adminT}</button>
						<button type="button" class="dash-chart-filter" data-range="14">14 {'days'|adminT}</button>
						<button type="button" class="dash-chart-filter" data-range="30">30 {'days'|adminT}</button>
					</div>
				</div>
				<div class="dash-panel__body">
					<div class="dash-chart-wrap">
						<canvas id="chartDaily"></canvas>
					</div>
				</div>
			</div>
		</div>
		<div class="col-xl-4">
			<div class="dash-panel h-100">
				<div class="dash-panel__head">
					<h2 class="dash-panel__title">{'Operations'|adminT}</h2>
				</div>
				<div class="dash-panel__body">
					<div class="dash-ops">
						<a href="{$adminUrl}orders?status={$statusPending}" class="dash-ops__item">
							<div class="dash-ring" style="--p: {$opsRings.pending}">
								<span>{$stats.orders_pending}</span>
							</div>
							<div class="dash-ops__meta">
								<strong>{'Awaiting Approval'|adminT}</strong>
								<span>{'Pending Orders'|adminT}</span>
							</div>
						</a>
						<a href="{$adminUrl}orders?status={$statusProcessing}" class="dash-ops__item">
							<div class="dash-ring dash-ring--blue" style="--p: {$opsRings.processing}">
								<span>{$stats.orders_processing}</span>
							</div>
							<div class="dash-ops__meta">
								<strong>{'Processing'|adminT}</strong>
								<span>{'Preparing'|adminT}</span>
							</div>
						</a>
						<a href="{$adminUrl}orders?status={$statusShipped}" class="dash-ops__item">
							<div class="dash-ring dash-ring--green" style="--p: {$opsRings.shipped}">
								<span>{$stats.orders_cargo}</span>
							</div>
							<div class="dash-ops__meta">
								<strong>{'Shipped'|adminT}</strong>
								<span>7 {'days'|adminT}</span>
							</div>
						</a>
						<a href="{$adminUrl}products" class="dash-ops__item">
							<div class="dash-ring dash-ring--orange" style="--p: {$opsRings.stock}">
								<span>{$stats.products_low_stock}</span>
							</div>
							<div class="dash-ops__meta">
								<strong>{'low stock'|adminT}</strong>
								<span>{'Pending Actions'|adminT}</span>
							</div>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-4 mb-4">
		<div class="col-xl-4">
			<div class="dash-panel h-100">
				<div class="dash-panel__head">
					<h2 class="dash-panel__title">{'Recent Activity'|adminT}</h2>
				</div>
				<div class="dash-panel__body">
					{if $activityFeed|@count}
					<ul class="dash-timeline">
						{foreach $activityFeed as $item}
						<li class="dash-timeline__item dash-timeline__item--{$item.type|escape}">
							<span class="dash-timeline__dot"></span>
							<div class="dash-timeline__body">
								<strong>{$item.title|adminT|escape}</strong>
								<span>{$item.meta|escape}</span>
								{if $item.time}<em>{$item.time|escape}</em>{/if}
							</div>
						</li>
						{/foreach}
					</ul>
					{else}
					<p class="text-muted mb-0">{'No orders yet.'|adminT}</p>
					{/if}
				</div>
			</div>
		</div>
		<div class="col-xl-4">
			<div class="dash-panel h-100">
				<div class="dash-panel__head dash-panel__head--split">
					<h2 class="dash-panel__title">{'Stock Widget'|adminT}</h2>
					<a href="{$adminUrl}products" class="dash-panel__link">{'View all'|adminT}</a>
				</div>
				<div class="dash-panel__body p-0">
					{if $lowStockProducts|@count}
					<ul class="dash-stock">
						{foreach $lowStockProducts as $product}
						<li class="dash-stock__item">
							<img src="{$product.image_url|escape}" alt="" class="dash-stock__img">
							<div class="dash-stock__info">
								<a href="{$adminUrl}product?id={$product.id_product}" class="dash-stock__name">{$product.product_name|escape}</a>
								<span class="dash-stock__qty {if $product.stock <= 0}is-danger{elseif $product.stock <= 2}is-warning{/if}">
									{$product.stock} {'stock'|adminT}
								</span>
							</div>
						</li>
						{/foreach}
					</ul>
					{else}
					<p class="text-muted p-4 mb-0">{'No sales data yet.'|adminT}</p>
					{/if}
				</div>
			</div>
		</div>
		<div class="col-xl-4">
			<div class="dash-panel h-100">
				<div class="dash-panel__head">
					<h2 class="dash-panel__title">{'Top Selling Products'|adminT}</h2>
				</div>
				<div class="dash-panel__body p-0">
					{if $topProducts|@count}
					<ul class="dash-top-product">
						{foreach $topProducts as $idx => $product}
						<li class="dash-top-product__item">
							<span class="dash-top-product__rank">{$idx+1}</span>
							{if $product.image_url}
							<img src="{$product.image_url|escape}" alt="" class="dash-top-product__img">
							{/if}
							<div class="dash-top-product__info">
								<strong>{$product.product_name|escape}</strong>
								<span>{$product.sold_qty} {'sold'|adminT}{if $product.revenue_formatted} · {$product.revenue_formatted}{/if}</span>
							</div>
						</li>
						{/foreach}
					</ul>
					{else}
					<p class="text-muted p-4 mb-0">{'No sales data yet.'|adminT}</p>
					{/if}
				</div>
			</div>
		</div>
	</div>

	<div class="row g-4 mb-4">
		<div class="col-12">
			<div class="dash-panel">
				<div class="dash-panel__head dash-panel__head--split">
					<h2 class="dash-panel__title">{'Last 50 Orders'|adminT}</h2>
					<a href="{$adminUrl}orders" class="dash-panel__link">{'View all'|adminT}</a>
				</div>
				<div class="dash-panel__body p-0">
					{if $recentOrders|@count}
					<div class="table-responsive dash-orders-scroll">
						<table class="table dash-orders-table ps-orders-table mb-0">
							<tbody>
								{include file='admin/partials/order-rows.tpl'}
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
				<div class="dash-panel__body p-3 dash-mp-orders">
					{if $recentMarketplaceOrders|@count}
					<div class="mp-order-list">
						{foreach $recentMarketplaceOrders as $ord}
						<div class="mp-order-row mp-order-row--dash">
							<div>
								<img
									src="{$domain}templates/admin/img/icons/{$ord.platform_icon_file|escape}"
									alt="{$ord.platform_label|escape}"
									class="mp-order-icon"
									onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
								<span class="mp-order-icon-fallback {$ord.platform|escape}" style="display:none;">{$ord.platform_icon|escape}</span>
							</div>
							<div class="mp-order-col">
								<a
									href="{$adminUrl|escape}marketplace-orders?open=1&amp;platform={$ord.platform|escape}&amp;order_number={$ord.order_number|escape}&amp;package_id={$ord.shipment_package_id|escape}"
									class="mp-order-title-btn"
									style="text-decoration:none;display:block;"
								>
									<div class="mp-order-title">#{$ord.order_number|escape}</div>
								</a>
								<div class="mp-order-sub">{$ord.cargo_provider|escape}</div>
							</div>
							<div class="mp-order-col">
								<div class="mp-order-title">{$ord.customer_name|escape}</div>
								<div class="mp-order-sub">{$ord.customer_sub|escape}</div>
							</div>
							<div class="mp-order-col mp-order-col-price">
								<div class="mp-order-price">{$ord.total_price|escape}</div>
								<div class="mp-order-sub">Satış Tutarı</div>
							</div>
							<div class="mp-order-col mp-order-col-status">
								<span class="mp-order-status-pill {$ord.status_tone|escape}">
									<span>{$ord.status|escape}</span>
								</span>
							</div>
							<div class="mp-order-datetime mp-order-col-date">
								<div>{$ord.date_day|escape}</div>
								{if $ord.date_time}<div>{$ord.date_time|escape}</div>{/if}
							</div>
							<div class="mp-order-actions">
								<a
									href="{$adminUrl|escape}marketplace-order-print?auto=1&amp;platform={$ord.platform|escape}&amp;order_number={$ord.order_number|escape}&amp;package_id={$ord.shipment_package_id|escape}"
									target="_blank"
									rel="noopener"
									class="mp-order-action"
									title="Yazdır"
									aria-label="Yazdır"
								>
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
								</a>
								<a
									href="{$adminUrl|escape}marketplace-orders?open=1&amp;platform={$ord.platform|escape}&amp;order_number={$ord.order_number|escape}&amp;package_id={$ord.shipment_package_id|escape}"
									class="mp-order-action"
									title="Detay"
									aria-label="Detay"
								>
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h6"/></svg>
								</a>
							</div>
						</div>
						{/foreach}
					</div>
					{else}
					<div class="mp-order-empty">{'No marketplace orders yet.'|adminT}</div>
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
	daily: {$chartDaily nofilter},
	ops: {
		awaiting: {$stats.orders_awaiting_shipment},
		shipped: {$stats.orders_cargo}
	}
};
</script>
