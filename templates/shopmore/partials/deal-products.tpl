{assign var="listProducts" value=$products|default:[]}

{if $listProducts|@count > 0}

<div class="sm-deals-row">

	{foreach $listProducts as $p}

	<article class="sm-product-card{if $p@first} is-featured{/if}">

		{if $p.has_discount}

		<span class="sm-product-card__badge">-%{Tools::getDiscount($p.old_price, $p.price)}</span>

		{/if}

		<button type="button" class="sm-product-card__fav like-button toggle-favorite" data-id="{$p.id_product}" aria-label="{'Favorites'|translate}">

			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>

		</button>

		<a href="{$p.url|escape}" class="sm-product-card__image" title="{$p.product_name|escape}">

			{if $p@first}

			<img src="{$p.image_url|escape}" alt="{$p.product_name|escape}" fetchpriority="high" decoding="async">

			{else}

			<img src="{$p.image_url|escape}" alt="{$p.product_name|escape}" loading="lazy" decoding="async">

			{/if}

		</a>

		<div class="sm-product-card__body">

			<a href="{$p.url|escape}" class="sm-product-card__name">{$p.product_name|escape}</a>

			<div class="sm-product-card__price">

				<span class="sm-product-card__price-current">{$p.price_formatted}</span>

				{if $p.has_discount}<span class="sm-product-card__price-old">{$p.old_price_formatted}</span>{/if}

			</div>

			{if $p.review_count > 0}

			<div class="sm-product-card__rating">★ {$p.rating|string_format:"%.1f"} ({$p.review_count})</div>

			{/if}

		</div>

	</article>

	{/foreach}

</div>

{/if}

