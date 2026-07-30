<!DOCTYPE html>
<html lang="tr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Kargo Etiketi</title>
{literal}
	<style>
		*{ box-sizing:border-box; }
		body{
			margin:0;
			padding:16px;
			background:#e5e7eb;
			font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif;
			color:#111;
		}
		.toolbar{
			max-width:780px;
			margin:0 auto 14px;
			display:flex;
			gap:8px;
			flex-wrap:wrap;
		}
		.toolbar button{
			border:1px solid #111;
			background:#111;
			color:#fff;
			border-radius:8px;
			padding:9px 14px;
			font-weight:700;
			cursor:pointer;
		}
		.toolbar button.secondary{
			background:#fff;
			color:#111;
		}
		.label{
			max-width:780px;
			margin:0 auto 18px;
			background:#fff;
			border:1px solid #111;
			padding:18px 20px 16px;
			page-break-after:always;
			break-after:page;
		}
		.label:last-child{
			page-break-after:auto;
			break-after:auto;
		}
		.head{
			display:flex;
			justify-content:space-between;
			align-items:flex-start;
			gap:16px;
			padding-bottom:12px;
			border-bottom:2px solid #111;
			margin-bottom:12px;
		}
		.brand{
			display:flex;
			flex-direction:column;
			gap:8px;
			min-width:160px;
		}
		.brand-logo{
			display:block;
			max-width:170px;
			max-height:42px;
			width:auto;
			height:auto;
			object-fit:contain;
		}
		.brand-cargo{
			font-size:18px;
			font-weight:800;
			color:#1d4ed8;
			line-height:1.1;
		}
		.barcode-wrap{
			text-align:right;
			min-width:220px;
		}
		.barcode-wrap svg{
			max-width:100%;
			height:56px;
		}
		.barcode-code{
			font-size:12px;
			letter-spacing:.04em;
			margin-top:4px;
			font-weight:600;
		}
		.section{
			padding:12px 0;
			border-bottom:2px solid #111;
		}
		.section h2{
			margin:0 0 10px;
			font-size:15px;
			font-weight:800;
		}
		.row{
			display:grid;
			grid-template-columns:90px 1fr;
			gap:8px;
			margin-bottom:8px;
			font-size:13px;
			line-height:1.4;
		}
		.row .k{ font-weight:700; }
		.row .v{ font-weight:700; }
		.meta-line{
			display:flex;
			justify-content:space-between;
			gap:16px;
			flex-wrap:wrap;
			font-size:13px;
			font-weight:700;
		}
		.items{ padding-top:12px; }
		.item{
			display:grid;
			grid-template-columns:54px 42px 1fr;
			gap:10px;
			align-items:center;
			padding:10px 0;
			border-bottom:1px dashed #999;
		}
		.item:last-child{ border-bottom:0; }
		.item-img{
			width:54px;
			height:54px;
			border:1px solid #ddd;
			object-fit:contain;
			background:#fafafa;
		}
		.item-img-empty{
			width:54px;
			height:54px;
			border:1px solid #ddd;
			background:#f3f4f6;
		}
		.item-qty{ font-weight:800; font-size:14px; }
		.item-sku{ font-size:12px; color:#444; font-weight:700; }
		.item-name{ font-size:13px; font-weight:600; margin-top:2px; }
		@media print{
			body{ background:#fff; padding:0; }
			.toolbar{ display:none !important; }
			.label{
				max-width:none;
				border:0;
				margin:0;
				padding:8mm;
			}
		}
	</style>
{/literal}
</head>
<body>
	<div class="toolbar">
		<button type="button" onclick="window.print()">PDF / Yazdır</button>
		<button type="button" class="secondary" onclick="window.close()">Kapat</button>
	</div>

	{foreach $printOrders as $order}
	<section class="label">
		<div class="head">
			<div class="brand">
				<img class="brand-logo" src="{$domain}templates/admin/img/icons/{$order.platform|escape}-logo.png" alt="{$order.platform_label|escape}">
				<div class="brand-cargo">{$order.cargo_provider|escape}</div>
			</div>
			<div class="barcode-wrap">
				{$order.barcode_svg nofilter}
				<div class="barcode-code">{$order.barcode_value|escape}</div>
			</div>
		</div>

		<div class="section">
			<h2>Alıcı Bilgileri</h2>
			<div class="row"><div class="k">Ad/Soyad :</div><div class="v">{$order.customer_name|escape}</div></div>
			<div class="row"><div class="k">Adres :</div><div class="v">{$order.customer_address|escape}</div></div>
			<div class="row"><div class="k">Telefon :</div><div class="v">{if $order.customer_phone}{$order.customer_phone|escape}{else}&nbsp;{/if}</div></div>
		</div>

		<div class="section">
			<h2>Sipariş Bilgileri</h2>
			<div class="meta-line">
				<div>Sipariş No : {$order.order_number|escape}</div>
				<div>Sipariş Tarihi : {$order.date_day|escape}{if $order.date_time} {$order.date_time|escape}{/if}</div>
			</div>
		</div>

		<div class="items">
			{foreach $order.items as $it}
			<div class="item">
				<div class="item-qty">{$it.quantity} x</div>
				<div style="display: ruby;">
					{if $it.sku}<div class="item-sku">{$it.sku|escape}</div>{/if}
					<div class="item-name">{$it.name|escape}</div>
				</div>
			</div>
			{/foreach}
		</div>
	</section>
	{/foreach}

	{if $printAuto}
	<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 250); });</script>
	{/if}
</body>
</html>
