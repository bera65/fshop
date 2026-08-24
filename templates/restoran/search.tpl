<div class="container page-section py-4">
	<h1 class="fw-bold mb-4">{'Search'|translate}</h1>
	{if $searchQuery != ''}
		{include file='./catalog.tpl'}
	{else}
		<div class="alert alert-warning">{'Search empty message'|translate}</div>
	{/if}
</div>
