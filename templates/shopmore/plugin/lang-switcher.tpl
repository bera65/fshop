{if $langSwitcher|@count > 1}
<div class="lang-switcher dropdown">
	<button class="lang-switcher-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{'Language'|translate}">
		{foreach $langSwitcher as $langItem}
			{if $langItem.active}{$langItem.label|escape}{/if}
		{/foreach}
	</button>
	<ul class="dropdown-menu dropdown-menu-end lang-switcher-menu">
		{foreach $langSwitcher as $langItem}
		<li>
			<a class="dropdown-item{if $langItem.active} active{/if}" href="{$langItem.url|escape}" hreflang="{$langItem.code|escape}"{if $langItem.active} aria-current="true"{/if}>
				{$langItem.label|escape}
			</a>
		</li>
		{/foreach}
	</ul>
</div>
<style>
.lang-switcher { display: inline-block; position: relative; }
.lang-switcher-toggle {
	background: transparent;
	border: 0;
	padding: 0;
	font: inherit;
	color: inherit;
	cursor: pointer;
}
.lang-switcher-toggle::after { margin-left: 0.35rem; }
.lang-switcher-menu { min-width: 7rem; font-size: 0.875rem; }
.prime-topbar__right .lang-switcher { margin-right: 0.75rem; }
.top-menu .lang-switcher-toggle { font-size: 13px; color: var(--color-text-muted, inherit); }
.lang-switcher-pills { display: flex; flex-wrap: wrap; gap: 8px; }
.lang-switcher-pills__item {
	font-size: 12px;
	font-weight: 600;
	text-decoration: none;
	padding: 7px 14px;
	border-radius: 999px;
	background: #fff;
	border: 1px solid var(--prime-border, #e5e7eb);
	color: var(--prime-primary, #1a3b5c);
}
.lang-switcher-pills__item.is-active {
	background: var(--prime-primary, #1a3b5c);
	border-color: var(--prime-primary, #1a3b5c);
	color: #fff;
}
</style>
{/if}
