{if $productCount|default:0 > 0 && $sortOptions|@count}
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
	<p class="text-muted small mb-0">{$productCount} {'products'|translate}</p>
	<form method="get" action="{$catalogBaseUrl}" class="d-flex align-items-center gap-2">
		{if isset($catalogQuery.q) && $catalogQuery.q != ''}
		<input type="hidden" name="q" value="{$catalogQuery.q|escape}">
		{elseif isset($searchQuery) && $searchQuery != ''}
		<input type="hidden" name="q" value="{$searchQuery|escape}">
		{/if}
		<select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
			{foreach $sortOptions as $sortKey => $sortLabel}
			<option value="{$sortKey}"{if $sort == $sortKey} selected{/if}>{$sortLabel|escape}</option>
			{/foreach}
		</select>
	</form>
</div>
{/if}
