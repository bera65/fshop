{if $products|@count > 0}
<section class="prime-section prime-section--soft prime-related-section">
	<div class="prime-section__head">
		<h4 class="prime-section__title mb-3">{$title|escape}</h4>
	</div>
	{include file=$productListTpl products=$products id='sameCategoryProducts'}
</section>
{/if}
