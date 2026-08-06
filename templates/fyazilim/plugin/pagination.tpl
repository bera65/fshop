{if $pagination && $pagination.total_pages > 1}
<nav class="prime-pagination" aria-label="Sayfalama">
	<ul>
		{if $pagination.has_prev}
		<li><a href="{$pagination.prev_url|escape}" class="prime-pagination__link">&laquo; Önceki</a></li>
		{/if}
		{foreach $pagination.pages as $pageItem}
		<li>
			{if $pageItem.current}
			<span class="prime-pagination__current">{$pageItem.num}</span>
			{else}
			<a href="{$pageItem.url|escape}" class="prime-pagination__link">{$pageItem.num}</a>
			{/if}
		</li>
		{/foreach}
		{if $pagination.has_next}
		<li><a href="{$pagination.next_url|escape}" class="prime-pagination__link">Sonraki &raquo;</a></li>
		{/if}
	</ul>
	<p class="prime-pagination__info">Sayfa {$pagination.page} / {$pagination.total_pages}</p>
</nav>
{/if}
