{include file='admin/marketplace/_nav.tpl'}

<div class="admin-panel p-3 mb-3">
	<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
		<div>
			<h2 class="h5 mb-1">Pazaryeri Logları</h2>
			<p class="text-muted small mb-0">Yeni sipariş, stok ve fiyat olaylarının kronolojik kaydı.</p>
		</div>
	</div>

	<form method="get" action="{$logsUrl|escape}" class="row g-2 mb-3 align-items-end">
		<div class="col-md-2">
			<label class="form-label small mb-1">Pazaryeri</label>
			<select name="platform" class="form-select form-select-sm">
				<option value="all"{if $marketplaceLogPlatform == 'all'} selected{/if}>Tümü</option>
				<option value="trendyol"{if $marketplaceLogPlatform == 'trendyol'} selected{/if}>Trendyol</option>
				<option value="hepsiburada"{if $marketplaceLogPlatform == 'hepsiburada'} selected{/if}>Hepsiburada</option>
				<option value="n11"{if $marketplaceLogPlatform == 'n11'} selected{/if}>N11</option>
			</select>
		</div>
		<div class="col-md-3">
			<label class="form-label small mb-1">Olay</label>
			<select name="event_type" class="form-select form-select-sm">
				<option value="all"{if $marketplaceLogEventType == 'all'} selected{/if}>Tümü</option>
				{foreach $marketplaceLogTypes as $typeKey => $typeLabel}
				<option value="{$typeKey|escape}"{if $marketplaceLogEventType == $typeKey} selected{/if}>{$typeLabel|escape}</option>
				{/foreach}
			</select>
		</div>
		<div class="col-md-2">
			<label class="form-label small mb-1">Başlangıç</label>
			<input type="date" name="start_date" class="form-control form-control-sm" value="{$marketplaceLogStartDate|escape}">
		</div>
		<div class="col-md-2">
			<label class="form-label small mb-1">Bitiş</label>
			<input type="date" name="end_date" class="form-control form-control-sm" value="{$marketplaceLogEndDate|escape}">
		</div>
		<div class="col-md-2">
			<label class="form-label small mb-1">Ara</label>
			<input type="text" name="q" class="form-control form-control-sm" placeholder="Sipariş / stok kodu" value="{$marketplaceLogQuery|escape}">
		</div>
		<div class="col-md-auto">
			<button type="submit" class="btn btn-sm btn-dark">Filtrele</button>
			<a href="{$logsUrl|escape}" class="btn btn-sm btn-outline-secondary">Temizle</a>
		</div>
	</form>

	{if !$marketplaceLogs|@count}
	<p class="text-muted mb-0">Henüz log kaydı yok.</p>
	{else}
	<div class="table-responsive">
		<table class="table table-sm table-hover align-middle mb-0">
			<thead>
				<tr>
					<th style="width:140px">Tarih</th>
					<th style="width:200px">Olay</th>
					<th style="width:140px">Referans</th>
					<th>Açıklama</th>
					<th style="width:110px">Platform</th>
				</tr>
			</thead>
			<tbody>
				{foreach $marketplaceLogs as $log}
				<tr>
					<td class="text-nowrap small">{$log.date_formatted|escape}</td>
					<td class="small fw-semibold">{$log.event_label|escape}</td>
					<td class="small"><code>{$log.reference|escape}</code></td>
					<td class="small">{$log.message|escape}</td>
					<td class="small text-muted">{$log.platform_label|escape}</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>

	{if isset($pagination) && $pagination.total_pages > 1}
		{include file='admin/plugin/pagination.tpl'}
	{/if}
	{/if}
</div>
