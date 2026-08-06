<div class="admin-panel mt-3">
	<div class="admin-panel__head d-flex align-items-center justify-content-between gap-2">
		<h2 class="h6 mb-0">Shipink</h2>
		{if !$cargoRow && $isConfigured && $id_order > 0}
		<form method="post" action="{$adminModuleUrl|escape}" class="m-0">
			<input type="hidden" name="token" value="{$adminToken|escape}">
			<input type="hidden" name="id_order" value="{$id_order|escape}">
			<button type="submit" name="sendOrder" value="1" class="btn btn-sm btn-dark">Shipink’e gönder</button>
		</form>
		{/if}
	</div>
	<div class="admin-panel__body">
		<p class="small text-muted mb-2">Sipariş ID: <strong>{$id_order}</strong></p>

		{if $cargoRow}
		<div class="alert alert-success small mb-2">
			Shipink’e aktarılmış.<br>
			{if $cargoRow.shipink_order_id}Sipariş: <strong>{$cargoRow.shipink_order_id|escape}</strong><br>{/if}
			{if $cargoRow.shipment_id}Gönderi: <strong>{$cargoRow.shipment_id|escape}</strong><br>{/if}
			{if $cargoRow.tracking_number}
			Takip: <strong>{$cargoRow.tracking_number|escape}</strong>
			{if $cargoRow.tracking_url}
			· <a href="{$cargoRow.tracking_url|escape}" target="_blank" rel="noopener">Takip et</a>
			{/if}
			<br>
			{/if}
			{if $cargoRow.carrier}Firma: {$cargoRow.carrier|escape}<br>{/if}
			{if $cargoRow.label_url}
			<a href="{$cargoRow.label_url|escape}" target="_blank" rel="noopener">Etiket PDF</a>
			{/if}
		</div>
		{if $cargoRow.shipment_id == ''}
		<form method="post" action="{$adminModuleUrl|escape}" class="m-0">
			<input type="hidden" name="token" value="{$adminToken|escape}">
			<input type="hidden" name="id_order" value="{$id_order|escape}">
			<button type="submit" name="sendOrder" value="1" class="btn btn-sm btn-outline-dark">Gönderiyi tekrar dene</button>
		</form>
		{/if}
		{elseif !$isConfigured}
		<div class="alert alert-warning small mb-0">
			Shipink ayarları eksik. <a href="{$adminModuleUrl|escape}">Modül yapılandırması</a>
		</div>
		{elseif $cargoError}
		<div class="alert alert-secondary small mb-0">{$cargoError|escape}</div>
		{elseif $orderPreview}
		<p class="small mb-2">Gönderilecek sipariş özeti hazır.</p>
		<pre class="small bg-light border rounded p-2 mb-0" style="max-height:200px;overflow:auto;">{$orderPreviewJson|escape}</pre>
		{else}
		<p class="small text-muted mb-0">Bu sipariş için Shipink verisi hazırlanamadı.</p>
		{/if}
	</div>
</div>
