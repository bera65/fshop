{if $breadcrumb|@count}
<nav class="sm-breadcrumb" aria-label="Breadcrumb">
	<ol class="sm-breadcrumb__list">
		{foreach $breadcrumb as $crumb name=crumbs}
		<li class="sm-breadcrumb__item{if $crumb.url == ''} sm-breadcrumb__item--active{/if}">
			{if $crumb.url != ''}
			<a href="{$crumb.url|escape}">{$crumb.name|escape}</a>
			{else}
			<span aria-current="page">{$crumb.name|escape}</span>
			{/if}
		</li>
		{/foreach}
	</ol>
</nav>
{/if}
