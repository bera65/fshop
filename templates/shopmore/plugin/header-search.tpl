{assign var=smSearchInputId value=$smSearchInputId|default:'smSearchInput'}
<div class="sm-header-search" data-sm-search>
	<form class="sm-search" action="{$domain}search" method="get" role="search" autocomplete="off">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
		<input
			type="search"
			name="q"
			id="{$smSearchInputId|escape}"
			placeholder="{'Search product..'|translate}"
			value="{$searchQuery|default:''|escape}"
			autocomplete="off"
			data-sm-search-input
		>
	</form>
	<div class="sm-header-search__suggest" data-sm-search-results role="listbox" hidden></div>
</div>
