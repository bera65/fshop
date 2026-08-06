<section class="rv-block fy-reveal">
	<div class="rv-block__head">
		<h2 class="rv-block__title">{$title|escape}</h2>
	</div>
	<div class="rv-list">
		{foreach $products as $product}
		<a href="{$product.url|escape}" class="rv-item">
			<div class="rv-item__media">
				<img src="{$product.image_url|escape}" alt="{$product.product_name|escape}" loading="lazy" width="72" height="72">
			</div>
			<div class="rv-item__body">
				<span class="rv-item__name">{$product.product_name|escape|truncate:30}</span>
				<span class="rv-item__price">
					{if $product.has_discount}<del class="rv-item__old">{$product.old_price_formatted|escape}</del>{/if}
					{$product.price_formatted|escape}
				</span>
			</div>
		</a>
		{/foreach}
	</div>
</section>
