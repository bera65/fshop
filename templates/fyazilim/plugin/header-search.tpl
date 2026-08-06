{* Header arama — header0 / header1 ortak *}
{assign var=fySearchInputId value=$fySearchInputId|default:'fyHeaderSearchInput'}
<div class="fy-header-search" data-fy-search>
	<form class="fy-header-search__form" action="{$domain}search" method="get" role="search" autocomplete="off">
		<label class="visually-hidden" for="{$fySearchInputId|escape}">{'Search product..'|translate}</label>
		<svg class="fy-header-search__icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
		<input
			type="search"
			name="q"
			id="{$fySearchInputId|escape}"
			class="fy-header-search__input"
			placeholder="{'Search product..'|translate}"
			value="{$searchQuery|default:''|escape}"
			autocomplete="off"
			data-fy-search-input
		>
		<button type="submit" class="fy-header-search__submit visually-hidden">{'Search'|translate}</button>
	</form>
	<div class="fy-header-search__suggest" data-fy-search-results role="listbox" aria-label="{'Search'|translate}" hidden></div>
</div>
