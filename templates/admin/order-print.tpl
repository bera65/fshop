<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{'Order #'|adminT}{$order.reference|escape} — {'Print'|adminT}</title>
	<style>
		* { box-sizing: border-box; }
		body {
			font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
			font-size: 13px;
			color: #222;
			margin: 0;
			padding: 24px;
			background: #f5f5f5;
		}
		.print-sheet {
			max-width: 800px;
			margin: 0 auto;
			background: #fff;
			border: 1px solid #ddd;
			padding: 28px 32px;
		}
		.print-toolbar {
			max-width: 800px;
			margin: 0 auto 16px;
			display: flex;
			gap: 8px;
		}
		.print-toolbar button {
			padding: 8px 16px;
			border: 1px solid #363a41;
			background: #363a41;
			color: #fff;
			border-radius: 4px;
			cursor: pointer;
			font-size: 13px;
		}
		.print-toolbar button.secondary {
			background: #fff;
			color: #363a41;
		}
		.print-head {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 16px;
			margin-bottom: 24px;
			padding-bottom: 16px;
			border-bottom: 2px solid #222;
		}
		.print-head h1 {
			font-size: 20px;
			margin: 0 0 4px;
		}
		.print-head .meta {
			font-size: 12px;
			color: #666;
		}
		.print-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 16px;
			margin-bottom: 20px;
		}
		.print-block h2 {
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #888;
			margin: 0 0 8px;
		}
		.print-block p { margin: 0 0 4px; line-height: 1.45; }
		table.print-items {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
		}
		table.print-items th,
		table.print-items td {
			border: 1px solid #ddd;
			padding: 8px 10px;
			text-align: left;
		}
		table.print-items th {
			background: #f8f9fa;
			font-size: 11px;
			text-transform: uppercase;
		}
		.print-totals {
			margin-left: auto;
			width: 280px;
		}
		.print-totals .row {
			display: flex;
			justify-content: space-between;
			padding: 4px 0;
		}
		.print-totals .row.total {
			font-weight: 700;
			font-size: 15px;
			border-top: 2px solid #222;
			margin-top: 8px;
			padding-top: 8px;
		}
		.print-foot {
			margin-top: 28px;
			padding-top: 12px;
			border-top: 1px solid #eee;
			font-size: 11px;
			color: #888;
			text-align: center;
		}
		@media print {
			body { background: #fff; padding: 0; }
			.print-toolbar { display: none !important; }
			.print-sheet { border: 0; padding: 0; max-width: none; }
		}
	</style>
</head>
<body>
	<div class="print-toolbar">
		<button type="button" onclick="window.print()">{'Print'|adminT}</button>
		<button type="button" class="secondary" onclick="window.close()">{'Close'|adminT}</button>
	</div>

	<div class="print-sheet">
		<div class="print-head">
			<div>
				<h1>{$printSiteName|escape}</h1>
				<div class="meta">{'Order slip'|adminT}</div>
			</div>
			<div class="meta text-end">
				<strong>#{$order.reference|escape}</strong><br>
				{$order.date_formatted|escape}<br>
				{'Status:'|adminT} {$order.status_label|escape}
			</div>
		</div>

		<div class="print-grid">
			<div class="print-block">
				<h2>{'Customer'|adminT}</h2>
				<p><strong>{$order.customer_name|escape}</strong></p>
				<p>{$order.customer_phone|escape}</p>
				{if $order.customer_email}<p>{$order.customer_email|escape}</p>{/if}
			</div>
			<div class="print-block">
				<h2>{'Delivery address'|adminT}</h2>
				<p>{$order.address_city|escape} / {$order.address_district|escape}</p>
				<p>{$order.address_text|escape}</p>
				{if $order.company_name}<p>{'Company:'|adminT} {$order.company_name|escape}</p>{/if}
				{if $order.tax_number}<p>{'Tax/ID no:'|adminT} {$order.tax_number|escape}</p>{/if}
			</div>
		</div>

		{if $order.note}
		<div class="print-block" style="margin-bottom:16px;">
			<h2>{'Order note'|adminT}</h2>
			<p>{$order.note|escape}</p>
		</div>
		{/if}

		<table class="print-items">
			<thead>
				<tr>
					<th>{'Product'|adminT}</th>
					<th style="width:60px;">{'Qty'|adminT}</th>
					<th style="width:100px;">{'Unit'|adminT}</th>
					<th style="width:100px;">{'Total'|adminT}</th>
				</tr>
			</thead>
			<tbody>
				{foreach $order.items as $item}
				<tr>
					<td>{$item.product_name|escape}</td>
					<td>{if $item.qty_label|default:''}{$item.qty_label|escape}{else}{$item.qty}{/if}{if $item.measure_label|default:''}<div style="font-size:11px;color:#666;">{$item.measure_label|escape}</div>{/if}</td>
					<td>{$item.price_formatted}</td>
					<td>{$item.total_formatted}</td>
				</tr>
				{/foreach}
			</tbody>
		</table>

		<div class="print-totals">
			<div class="row"><span>{'Subtotal'|adminT}</span><span>{$order.subtotal_formatted}</span></div>
			<div class="row"><span>{'Shipping'|adminT}</span><span>{$order.shipping_formatted}</span></div>
			<div class="row total"><span>{'Total'|adminT}</span><span>{$order.total_formatted}</span></div>
			<div class="row" style="margin-top:8px;font-size:12px;color:#666;">
				<span>{'Payment'|adminT}</span><span>{$order.payment_label|escape}</span>
			</div>
		</div>

		{if $order.cargo_company || $order.tracking_number}
		<div class="print-block" style="margin-top:20px;">
			<h2>{'Shipping'|adminT}</h2>
			{if $order.cargo_company}<p>{'Company:'|adminT} {$order.cargo_company|escape}</p>{/if}
			{if $order.tracking_number}<p>{'Tracking no:'|adminT} {$order.tracking_number|escape}</p>{/if}
		</div>
		{/if}

		<div class="print-foot">
			{$printSiteName|escape}
			{if $printContactPhone} · {$printContactPhone|escape}{/if}
			{if $printContactEmail} · {$printContactEmail|escape}{/if}
		</div>
	</div>

	{if $printAuto}
	<script>window.addEventListener('load', function () { window.print(); });</script>
	{/if}
</body>
</html>
