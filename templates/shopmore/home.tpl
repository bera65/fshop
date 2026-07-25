<div class="sm-container sm-home">
	{* Hero slider *}
	<section class="sm-hero">
		{if $hooks.home_slider}
		<div class="sm-hero-hook">
			{$hooks.home_slider nofilter}
		</div>
		{elseif $featuredProducts|@count}
		{assign var="heroProduct" value=$featuredProducts[0]}
		<div class="sm-hero-slide">
			<div class="sm-hero-slide__content">
				<div class="sm-hero-slide__tag">{'Best Seller'|translate}</div>
				<h2 class="sm-hero-slide__title">{$heroProduct.product_name|escape}</h2>
				<p class="sm-hero-slide__text">{$heroProduct.price_formatted}</p>
				<a href="{$heroProduct.url|escape}" class="sm-hero-slide__btn">{'View'|translate} →</a>
			</div>
			<div class="sm-hero-slide__media">
				<img src="{$heroProduct.image_url|escape}" alt="{$heroProduct.product_name|escape}" fetchpriority="high">
			</div>
		</div>
		{/if}
	</section>

	{* Category circles 
	{if $homeCategories|@count}
	<section class="sm-categories mt-3">
		<div class="sm-categories__scroll">
			{foreach $homeCategories as $catRow name=homeCats}
			{if $smarty.foreach.homeCats.iteration > 10}{break}{/if}
			<a href="{$catRow.url|escape}" class="sm-cat-circle">
				<div class="sm-cat-circle__icon">
					<img src="{$domain}img/category/{$catRow.category.id_category}.png" alt="{$catRow.category.category_name|escape}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
					<span class="sm-cat-circle__fallback">{$catRow.initial|escape}</span>
				</div>
				<div class="sm-cat-circle__label">{$catRow.category.category_name|escape}</div>
			</a>
			{/foreach}
		</div>
	</section>
	{/if}
*}
	{* Deals of the day *}
	{if $dealProducts|@count}
	<section class="sm-section mt-4">
		<div class="sm-section__head">
			<div>
				<h2 class="sm-section__title">{'Specilas'|translate}</h2>
				<div class="sm-countdown">
					<span>{'Ends in'|translate|default:'Bitiş'}:</span>
					<span class="sm-countdown__time" id="smDealCountdown">08 : 24 : 56</span>
				</div>
			</div>
			<a href="{$domain}special" class="sm-section__link">{'View All'|translate} →</a>
		</div>
		{include file='./partials/sm-category-products-section.tpl' products=$dealProducts}
	</section>
	{/if}

	{* Best sellers *}
	{if $featuredProducts|@count}
	<section class="sm-section">
		<div class="sm-section__head">
			<h2 class="sm-section__title">{'Best Seller'|translate}</h2>
			<a href="{$domain}special" class="sm-section__link">{'View All'|translate} →</a>
		</div>
		{include file='./partials/sm-category-products-section.tpl' products=$featuredProducts}
	</section>
	{/if}

	{if $hooks.home_promo_slider}
	<section class="sm-section">
		{$hooks.home_promo_slider nofilter}
	</section>
	{/if}

	{foreach $categoryBlocks as $block}
	{include file='./partials/sm-category-products-section.tpl' title=$block.category.category_name url=$block.url products=$block.products listId=$block.category.id_category}
	{break}
	{/foreach}

	{if $hooks.home_bottom}
	<section class="sm-section">
		{$hooks.home_bottom nofilter}
	</section>
	{/if}
</div>

<script>
(function () {
	var el = document.getElementById('smDealCountdown');
	if (!el) return;
	function pad(n) { return n < 10 ? '0' + n : '' + n; }
	function tick() {
		var now = new Date();
		var end = new Date();
		end.setHours(23, 59, 59, 999);
		var diff = Math.max(0, end - now);
		var h = Math.floor(diff / 3600000);
		var m = Math.floor((diff % 3600000) / 60000);
		var s = Math.floor((diff % 60000) / 1000);
		el.textContent = pad(h) + ' : ' + pad(m) + ' : ' + pad(s);
	}
	tick();
	setInterval(tick, 1000);
})();
</script>
