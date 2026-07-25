{if $flash}
<div class="alert alert-{$flashType|default:'success'} py-2">{$flash|escape}</div>
{/if}

<div class="admin-panel mb-4">
	<h2 class="h6 mb-3">Güven Bandı</h2>
	<p class="text-muted small">Ana sayfanın altında görünen avantaj kutuları (ücretsiz kargo, iade vb.). Metinleri buradan düzenleyin.</p>

	<form method="post">
		<input type="hidden" name="saveTrustBar" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="form-check form-switch mb-4">
			<input class="form-check-input" type="checkbox" name="enabled" value="1" id="tbEnabled"{if $settings.enabled} checked{/if}>
			<label class="form-check-label" for="tbEnabled">Güven bandı etkin</label>
		</div>

		{if $items|@count}
		<div class="table-responsive">
			<table class="table table-sm align-middle">
				<thead>
					<tr>
						<th style="width:52px">Sıra</th>
						<th>Başlık</th>
						<th>Alt metin</th>
						<th style="width:140px">İkon</th>
						<th style="width:70px">Aktif</th>
						<th style="width:70px"></th>
					</tr>
				</thead>
				<tbody>
					{foreach $items as $item name=tbRows}
					<tr>
						<td>
							<input type="hidden" name="items[{$smarty.foreach.tbRows.index}][id_item]" value="{$item.id_item}">
							<input type="number" name="items[{$smarty.foreach.tbRows.index}][position]" class="form-control form-control-sm" value="{$item.position|default:$smarty.foreach.tbRows.iteration}" min="1">
						</td>
						<td>
							<input type="text" name="items[{$smarty.foreach.tbRows.index}][title]" class="form-control form-control-sm" value="{$item.title|escape}" required>
						</td>
						<td>
							<input type="text" name="items[{$smarty.foreach.tbRows.index}][subtitle]" class="form-control form-control-sm" value="{$item.subtitle|escape}">
						</td>
						<td>
							<select name="items[{$smarty.foreach.tbRows.index}][icon]" class="form-select form-select-sm">
								{foreach $icons as $iconKey => $iconLabel}
								<option value="{$iconKey|escape}"{if $item.icon == $iconKey} selected{/if}>{$iconLabel|escape}</option>
								{/foreach}
							</select>
						</td>
						<td class="text-center">
							<input type="checkbox" name="items[{$smarty.foreach.tbRows.index}][active]" value="1"{if $item.active} checked{/if}>
						</td>
						<td>
							<a href="?delete_item={$item.id_item}&amp;token={$adminToken|escape:url}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Silinsin mi?')">Sil</a>
						</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		{else}
		<p class="text-muted">Henüz kutu yok. Aşağıdan ekleyin.</p>
		{/if}

		<button type="submit" class="btn btn-dark mt-2">Kaydet</button>
	</form>
</div>

<div class="admin-panel">
	<h2 class="h6 mb-3">Yeni kutu ekle</h2>
	<form method="post" class="row g-3 align-items-end">
		<input type="hidden" name="addTrustBarItem" value="1">
		<input type="hidden" name="token" value="{$adminToken}">
		<div class="col-md-4">
			<label class="form-label">Başlık</label>
			<input type="text" name="new_title" class="form-control" required>
		</div>
		<div class="col-md-4">
			<label class="form-label">Alt metin</label>
			<input type="text" name="new_subtitle" class="form-control">
		</div>
		<div class="col-md-3">
			<label class="form-label">İkon</label>
			<select name="new_icon" class="form-select">
				{foreach $icons as $iconKey => $iconLabel}
				<option value="{$iconKey|escape}">{$iconLabel|escape}</option>
				{/foreach}
			</select>
		</div>
		<div class="col-md-1">
			<button type="submit" class="btn btn-outline-dark w-100">Ekle</button>
		</div>
	</form>
</div>
