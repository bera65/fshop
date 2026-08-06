<div class="fl-container fl-home">
	<section class="fl-hero">
		{if $hooks.home_slider}
		<div class="fl-hero-hook">
			{$hooks.home_slider nofilter}
		</div>
		{elseif $featuredProducts|@count}
		{assign var="heroProduct" value=$featuredProducts[0]}
		<div class="fl-hero-fallback">
			<div>
				<span class="fl-section__eyebrow">{'Best Seller'|translate}</span>
				<h2>{$heroProduct.product_name|escape}</h2>
				<p>{$heroProduct.price_formatted}</p>
				<a href="{$heroProduct.url|escape}" class="fl-btn-primary">{'View'|translate}</a>
			</div>
			<img src="{$heroProduct.image_url|escape}" alt="{$heroProduct.product_name|escape}" fetchpriority="high">
		</div>
		{/if}
	</section>

	{if $dealProducts|@count}
	<section class="fl-section">
		<div class="fl-section__head">
			<div>
				<h2 class="fl-section__title">{'Specilas'|translate}</h2>
				<p class="fl-section__sub">{$dealProducts|@count} {'products'|translate}</p>
			</div>
			<a href="{$domain}special" class="fl-section__link">
				{'View All'|translate}
				<span class="fl-section__link-icon" aria-hidden="true">→</span>
			</a>
		</div>
		{include file='./productList.tpl' products=$dealProducts id='dealProducts'}
	</section>
	{/if}

	{if $featuredProducts|@count}
	<section class="fl-section">
		{assign var="cName" value='Specilas'|translate}
		{include file='./partials/sm-category-products-section.tpl' title=$cName products=$featuredProducts listId=0}
	</section>
	{/if}

	{if $hooks.home_promo_slider}
	<section class="fl-section">
		{$hooks.home_promo_slider nofilter}
	</section>
	{/if}

	{foreach $categoryBlocks as $block}
	{include file='./partials/sm-category-products-section.tpl' title=$block.category.category_name url=$block.url products=$block.products listId=$block.category.id_category}
	{/foreach}

	{if $hooks.home_bottom}
	<section class="fl-section">
		{$hooks.home_bottom nofilter}
	</section>
	{/if}
</div>
