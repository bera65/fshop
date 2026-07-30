<div class="marketplace-admin">
{if $flash}
<div class="alert alert-info py-2">{$flash|escape}</div>
{/if}

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
	<div class="d-flex flex-wrap gap-2">
		<a href="{$productsUrl|escape}" class="btn btn-sm {if $marketplacePage == 'products'}btn-dark{else}btn-outline-secondary{/if}">{'Products'|adminT}</a>
		<a href="{$ordersUrl|escape}" class="btn btn-sm {if $marketplacePage == 'orders'}btn-dark{else}btn-outline-secondary{/if}">{'Orders'|adminT}</a>
		<a href="{$questionsUrl|escape}" class="btn btn-sm {if $marketplacePage == 'questions'}btn-dark{else}btn-outline-secondary{/if}">{'Question'|adminT}</a>
		<a href="{$logsUrl|escape}" class="btn btn-sm {if $marketplacePage == 'logs'}btn-dark{else}btn-outline-secondary{/if}">Loglar</a>
		<a href="{$settingsUrl|escape}" class="btn btn-sm {if $marketplacePage == 'settings'}btn-dark{else}btn-outline-secondary{/if}">{'Settings'|adminT}</a>
		<a href="{$helpUrl|escape}" class="btn btn-sm {if $marketplacePage == 'help'}btn-dark{else}btn-outline-secondary{/if}">{'Help'|adminT}</a>
	</div>
</div>
