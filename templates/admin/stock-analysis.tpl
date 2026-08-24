{if $flash}
<div class="alert alert-{$flashType|default:'info'}">{$flash|escape}</div>
{/if}

<form method="get" action="{$adminUrl}stock-analysis" class="admin-toolbar row g-2 mb-3">
	<div class="col-md-3">
		<label class="form-label small mb-1">{'Sales period (days)'|adminT}</label>
		<input type="number" min="1" max="90" name="days" class="form-control form-control-sm" value="{$days}">
	</div>
	<div class="col-md-2 d-flex align-items-end">
		<button type="submit" class="btn btn-sm btn-dark">{'Filter'|adminT}</button>
	</div>
</form>

<div class="ps-panel mb-4">
	<div class="ps-panel__head">
		<h2>{'Stock analysis'|adminT}</h2>
	</div>
	<div class="ps-panel__body p-0">
		{if $lowStockRows|@count}
		<div class="table-responsive">
			<table class="table table-sm align-middle mb-0">
				<thead>
					<tr>
						<th>{'Product'|adminT}</th>
						<th>{'Daily sales'|adminT}</th>
						<th>{'Stock'|adminT}</th>
						<th>{'Stock lifetime'|adminT}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					{foreach $lowStockRows as $row}
					<tr>
						<td>
							<a href="{$row.edit_url|escape}">{$row.product_name|escape}</a>
							{if $row.stock_code}<br><small class="text-muted">{$row.stock_code|escape}</small>{/if}
						</td>
						<td>{$row.daily_sales|string_format:'%.2f'}</td>
						<td><span class="badge bg-warning text-dark">{$row.stock|string_format:"%.2f"}</span></td>
						<td>{$row.stock_lifetime_label|escape}</td>
						<td class="text-end">
							<form method="post" class="d-inline-flex gap-1 align-items-center">
								<input type="hidden" name="quickAddStock" value="1">
								<input type="hidden" name="token" value="{$adminToken}">
								<input type="hidden" name="id_product" value="{$row.id_product}">
								<input type="number" min="1" name="add_qty" class="form-control form-control-sm" style="width:80px" placeholder="+">
								<button type="submit" class="btn btn-sm btn-success">{'Add stock'|adminT}</button>
							</form>
						</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		{else}
		<p class="text-muted p-4 mb-0">{'No critical stock items found.'|adminT}</p>
		{/if}
	</div>
</div>

<div class="ps-panel">
	<div class="ps-panel__head">
		<h2>{'Best sellers out of stock'|adminT}</h2>
	</div>
	<div class="ps-panel__body p-0">
		{if $outOfStockRows|@count}
		<div class="table-responsive">
			<table class="table table-sm align-middle mb-0">
				<thead>
					<tr>
						<th>{'Product'|adminT}</th>
						<th>{'Sold (period)'|adminT}</th>
						<th>{'Daily sales'|adminT}</th>
						<th>{'Stock depleted'|adminT}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					{foreach $outOfStockRows as $row}
					<tr>
						<td>
							<a href="{$row.edit_url|escape}">{$row.product_name|escape}</a>
							{if $row.stock_code}<br><small class="text-muted">{$row.stock_code|escape}</small>{/if}
						</td>
						<td>{$row.sold_qty}</td>
						<td>{$row.daily_sales|string_format:'%.2f'}</td>
						<td>{$row.stock_empty_label|escape}</td>
						<td class="text-end">
							<form method="post" class="d-inline-flex gap-1 align-items-center">
								<input type="hidden" name="quickAddStock" value="1">
								<input type="hidden" name="token" value="{$adminToken}">
								<input type="hidden" name="id_product" value="{$row.id_product}">
								<input type="number" min="1" name="add_qty" class="form-control form-control-sm" style="width:80px" placeholder="+">
								<button type="submit" class="btn btn-sm btn-success">{'Add stock'|adminT}</button>
							</form>
						</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		{else}
		<p class="text-muted p-4 mb-0">{'No out-of-stock best sellers in this period.'|adminT}</p>
		{/if}
	</div>
</div>
