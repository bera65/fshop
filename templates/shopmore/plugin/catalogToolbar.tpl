<div class="catalog-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
	<p class="text-muted small mb-0">{$productCount} {'products'|translate}</p>
	{if $productCount > 0 && $sortOptions|@count}
	<form method="get" action="{$catalogBaseUrl}" class="catalog-sort-form d-flex align-items-center gap-2">
		{if isset($catalogQuery.q) && $catalogQuery.q != ''}
		<input type="hidden" name="q" value="{$catalogQuery.q|escape}">
		{elseif isset($searchQuery) && $searchQuery != ''}
		<input type="hidden" name="q" value="{$searchQuery|escape}">
		{/if}
		{if isset($catalogFilterQuery.subcat)}
		<input type="hidden" name="subcat" value="{$catalogFilterQuery.subcat|escape}">
		{/if}
		{if isset($catalogFilterQuery.brand)}
		<input type="hidden" name="brand" value="{$catalogFilterQuery.brand|escape}">
		{/if}
		{if isset($catalogFilterQuery.price_min)}
		<input type="hidden" name="price_min" value="{$catalogFilterQuery.price_min|escape}">
		{/if}
		{if isset($catalogFilterQuery.price_max)}
		<input type="hidden" name="price_max" value="{$catalogFilterQuery.price_max|escape}">
		{/if}
		{if isset($catalogFilterQuery.stock)}
		<input type="hidden" name="stock" value="{$catalogFilterQuery.stock|escape}">
		{/if}
		{if isset($catalogFilterQuery.discount)}
		<input type="hidden" name="discount" value="{$catalogFilterQuery.discount|escape}">
		{/if}
		{if isset($catalogFilterQuery.var) && $catalogFilterQuery.var|@count}
		{foreach $catalogFilterQuery.var as $varKey => $varVal}
		<input type="hidden" name="var[{$varKey|escape}]" value="{$varVal|escape}">
		{/foreach}
		{/if}
		<label class="small text-muted mb-0" for="catalogSort"></label>
		<select name="sort" id="catalogSort" class="form-select form-select-sm" onchange="this.form.submit()">
			{foreach $sortOptions as $sortKey => $sortLabel}
			<option value="{$sortKey}"{if $sort == $sortKey} selected{/if}>{$sortLabel|escape}</option>
			{/foreach}
		</select>
	</form>
	{/if}
</div>
