{if $products|@count > 0}

<div class="same-category-products mt-5">

	<h3 class="mb-4">
		{$title|escape}
	</h3>

	{include file=$productListTpl products=$products}

</div>

{/if}
