<div class="catalog-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
	<p class="text-muted small mb-0">{$productCount} ürün</p>
	{if $productCount > 0 && $sortOptions|@count}
	<form method="get" action="{$catalogBaseUrl}" class="catalog-sort-form d-flex align-items-center gap-2">
		{if isset($catalogQuery.q) && $catalogQuery.q != ''}
		<input type="hidden" name="q" value="{$catalogQuery.q|escape}">
		{elseif isset($searchQuery) && $searchQuery != ''}
		<input type="hidden" name="q" value="{$searchQuery|escape}">
		{/if}
		<label class="small text-muted mb-0" for="catalogSort">Sırala</label>
		<select name="sort" id="catalogSort" class="form-select form-select-sm" onchange="this.form.submit()">
			{foreach $sortOptions as $sortKey => $sortLabel}
			<option value="{$sortKey}"{if $sort == $sortKey} selected{/if}>{$sortLabel|escape}</option>
			{/foreach}
		</select>
	</form>
	{/if}
</div>
