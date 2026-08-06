{assign var=smSearchInputId value=$inputId|default:'t4SearchInput'}
{assign var=ph value=$placeholder|default:''}
{if $ph == ''}
	{assign var=ph value=$defaultPlaceholder|default:'Search product..'}
{/if}
<div class="t4-widget-search sm-search-wrap">
	<div class="sm-header-search t4-search" data-sm-search>
		<form class="sm-search t4-search__form" action="{$domain}search" method="get" role="search" autocomplete="off">
			<input
				type="search"
				name="q"
				id="{$smSearchInputId|escape}"
				class="t4-search__input"
				placeholder="{$ph|escape}"
				value="{$searchQuery|default:''|escape}"
				autocomplete="off"
				aria-label="{$ph|escape}"
				data-sm-search-input
			>
			<button type="submit" class="t4-search__btn" aria-label="{$searchLabel|default:'Search'|escape}">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
			</button>
		</form>
		<div class="sm-header-search__suggest" data-sm-search-results role="listbox" hidden></div>
	</div>
</div>
