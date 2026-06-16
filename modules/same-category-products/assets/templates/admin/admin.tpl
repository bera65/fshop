{if $flash}
<div class="alert alert-info">
	{$flash|escape}
</div>
{/if}

<form method="post" class="admin-panel p-3" style="max-width:600px">

	<input type="hidden" name="saveSameCategoryProducts" value="1">
	<input type="hidden" name="token" value="{$adminToken}">

	<div class="mb-3">
		<label class="form-label">Başlık</label>
		<input
			type="text"
			name="title"
			class="form-control"
			value="{$title|escape}">
	</div>

	<div class="mb-3">
		<label class="form-label">Gösterilecek Ürün Sayısı</label>
		<input
			type="number"
			name="limit"
			min="1"
			max="24"
			class="form-control"
			value="{$limit|escape}">
	</div>

	<div class="mb-3">
		<label class="form-label">Sıralama</label>

		<select name="sort" class="form-select">
			<option value="newest" {if $sort == 'newest'}selected{/if}>Yeni Eklenenler</option>
			<option value="price_asc" {if $sort == 'price_asc'}selected{/if}>Fiyat Artan</option>
			<option value="price_desc" {if $sort == 'price_desc'}selected{/if}>Fiyat Azalan</option>
		</select>
	</div>

	<button type="submit" class="btn btn-dark">
		Kaydet
	</button>

</form>