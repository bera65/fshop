{if $pagination && $pagination.total_pages > 1}
<nav class="pagination-nav mt-4" aria-label="{'Pagination'|translate}">
	<ul class="pagination justify-content-center">
		{if $pagination.has_prev}
		<li class="page-item"><a href="{$pagination.prev_url|escape}" class="page-link">&laquo;</a></li>
		{/if}
		{foreach $pagination.pages as $pageItem}
		<li class="page-item{if $pageItem.current} active{/if}">
			{if $pageItem.current}
			<span class="page-link">{$pageItem.num}</span>
			{else}
			<a href="{$pageItem.url|escape}" class="page-link">{$pageItem.num}</a>
			{/if}
		</li>
		{/foreach}
		{if $pagination.has_next}
		<li class="page-item"><a href="{$pagination.next_url|escape}" class="page-link">&raquo;</a></li>
		{/if}
	</ul>
</nav>
{/if}
