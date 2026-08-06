<div class="prime-filter__head d-flex justify-content-between align-items-center">
	<h2 class="prime-filter__title mb-0">Filtreler</h2>
	{if $catalogFilter.hasActive}
	<a href="{$catalogFilter.clearUrl|escape}" class="small text-decoration-none">Temizle</a>
	{/if}
</div>

<form method="get" action="{$catalogBaseUrl|escape}" class="prime-filter-form mt-3" id="catalogFilterForm">
	{if $sort && $sort != 'newest'}
	<input type="hidden" name="sort" value="{$sort|escape}">
	{/if}

	{if $filterSubcategories|@count > 0}
	<div class="prime-filter__group">
		<h3 class="prime-filter__label">Kategori</h3>
		<ul class="prime-filter-list">
			<li>
				<a href="{$filterSubcategoryAllUrl|escape}" class="prime-filter-link{if !$catalogFilter.subCategoryId} is-active{/if}">Tümü</a>
			</li>
			{foreach $filterSubcategories as $sub}
			<li>
				<a href="{$sub.filter_url|escape}" class="prime-filter-link{if $catalogFilter.subCategoryId == $sub.id_category || $category.id_category == $sub.id_category} is-active{/if}">{$sub.category_name|escape}</a>
			</li>
			{/foreach}
		</ul>
	</div>
	{/if}

	{if $filterBrands|@count > 0}
	<div class="prime-filter__group">
		<h3 class="prime-filter__label">Marka</h3>
		<ul class="prime-filter-list">
			<li>
				<label class="prime-filter-check">
					<input type="radio" name="brand" value=""{if !$catalogFilter.brandId} checked{/if} onchange="this.form.submit()">
					<span>Tümü</span>
				</label>
			</li>
			{foreach $filterBrands as $brand}
			<li>
				<label class="prime-filter-check">
					<input type="radio" name="brand" value="{$brand.id_brand}"{if $catalogFilter.brandId == $brand.id_brand} checked{/if} onchange="this.form.submit()">
					<span>{$brand.brand_name|escape} <small class="text-muted">({$brand.product_count|default:0})</small></span>
				</label>
			</li>
			{/foreach}
		</ul>
	</div>
	{/if}

	{foreach $filterVariations as $varGroup}
	<div class="prime-filter__group">
		<h3 class="prime-filter__label">{$varGroup.group|escape}</h3>
		<ul class="prime-filter-list">
			<li>
				<label class="prime-filter-check">
					<input type="radio" name="var[{$varGroup.group|escape}]" value=""{if $varGroup.selected == ''} checked{/if} onchange="this.form.submit()">
					<span>Tümü</span>
				</label>
			</li>
			{foreach $varGroup.values as $varValue}
			<li>
				<label class="prime-filter-check">
					<input type="radio" name="var[{$varGroup.group|escape}]" value="{$varValue.value|escape}"{if $varGroup.selected == $varValue.value} checked{/if} onchange="this.form.submit()">
					<span>{$varValue.value|escape} <small class="text-muted">({$varValue.count})</small></span>
				</label>
			</li>
			{/foreach}
		</ul>
	</div>
	{/foreach}

	{if $filterStock.in_stock > 0 || $filterStock.out_of_stock > 0}
	<div class="prime-filter__group">
		<h3 class="prime-filter__label">Stok durumu</h3>
		<ul class="prime-filter-list">
			<li>
				<label class="prime-filter-check">
					<input type="radio" name="stock" value=""{if !$catalogFilter.stockStatus} checked{/if} onchange="this.form.submit()">
					<span>Tümü</span>
				</label>
			</li>
			{if $filterStock.in_stock > 0}
			<li>
				<label class="prime-filter-check">
					<input type="radio" name="stock" value="in_stock"{if $catalogFilter.stockStatus == 'in_stock'} checked{/if} onchange="this.form.submit()">
					<span>Stokta var <small class="text-muted">({$filterStock.in_stock})</small></span>
				</label>
			</li>
			{/if}
			{if $filterStock.out_of_stock > 0}
			<li>
				<label class="prime-filter-check">
					<input type="radio" name="stock" value="out_of_stock"{if $catalogFilter.stockStatus == 'out_of_stock'} checked{/if} onchange="this.form.submit()">
					<span>Stokta yok <small class="text-muted">({$filterStock.out_of_stock})</small></span>
				</label>
			</li>
			{/if}
		</ul>
	</div>
	{/if}

	{if $filterDiscount.yes > 0 || $filterDiscount.no > 0}
	<div class="prime-filter__group">
		<h3 class="prime-filter__label">İndirim</h3>
		<ul class="prime-filter-list">
			<li>
				<label class="prime-filter-check">
					<input type="radio" name="discount" value=""{if !$catalogFilter.discount} checked{/if} onchange="this.form.submit()">
					<span>Tümü</span>
				</label>
			</li>
			{if $filterDiscount.yes > 0}
			<li>
				<label class="prime-filter-check">
					<input type="radio" name="discount" value="yes"{if $catalogFilter.discount == 'yes'} checked{/if} onchange="this.form.submit()">
					<span>İndirimli <small class="text-muted">({$filterDiscount.yes})</small></span>
				</label>
			</li>
			{/if}
			{if $filterDiscount.no > 0}
			<li>
				<label class="prime-filter-check">
					<input type="radio" name="discount" value="no"{if $catalogFilter.discount == 'no'} checked{/if} onchange="this.form.submit()">
					<span>İndirimsiz <small class="text-muted">({$filterDiscount.no})</small></span>
				</label>
			</li>
			{/if}
		</ul>
	</div>
	{/if}

	<div class="prime-filter__group">
		<h3 class="prime-filter__label">Fiyat</h3>
		<div class="row g-2">
			<div class="col-6">
				<input type="number" name="price_min" class="form-control form-control-sm" placeholder="Min" min="0" step="1" value="{if $catalogFilter.priceMin !== null}{$catalogFilter.priceMin|string_format:'%.0f'}{/if}">
			</div>
			<div class="col-6">
				<input type="number" name="price_max" class="form-control form-control-sm" placeholder="Max" min="0" step="1" value="{if $catalogFilter.priceMax !== null}{$catalogFilter.priceMax|string_format:'%.0f'}{/if}">
			</div>
		</div>
		{if $filterPriceRange.max > 0}
		<div class="form-text">{$filterPriceRange.min|string_format:'%.0f'} – {$filterPriceRange.max|string_format:'%.0f'} TL</div>
		{/if}
		<button type="submit" class="btn btn-sm btn-primary w-100 mt-2">Uygula</button>
	</div>
</form>
