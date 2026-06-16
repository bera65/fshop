{if $pagination && $pagination.total_pages > 1}
<nav class="catalog-pagination mt-4" aria-label="Sayfalama">
	<ul class="pagination justify-content-center flex-wrap mb-0">
		{if $pagination.has_prev}
		<li class="page-item">
			<a class="page-link" href="{$pagination.prev_url|escape}">Önceki</a>
		</li>
		{/if}
		{foreach $pagination.pages as $pageItem}
		<li class="page-item{if $pageItem.current} active{/if}">
			{if $pageItem.current}
			<span class="page-link">{$pageItem.num}</span>
			{else}
			<a class="page-link" href="{$pageItem.url|escape}">{$pageItem.num}</a>
			{/if}
		</li>
		{/foreach}
		{if $pagination.has_next}
		<li class="page-item">
			<a class="page-link" href="{$pagination.next_url|escape}">Sonraki</a>
		</li>
		{/if}
	</ul>
	<p class="text-center text-muted small mt-2 mb-0">Sayfa {$pagination.page} / {$pagination.total_pages}</p>
</nav>
{/if}
