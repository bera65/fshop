{if $hooks.home_slider}
<section id="slide" class="container">
	{$hooks.home_slider nofilter}
</section>
{/if}

{* Special Products *}
<section class="section-premium home-category-block mt-3">
	<div class="section-head">
		<div>
			<h2 class="section-title fs-3">{'Best Seller'|translate}</h2>
		</div>
	</div>
	{include file='./productList.tpl' products=$featuredProducts id=0}
</section>

{if $hooks.home_promo_slider}
	{$hooks.home_promo_slider nofilter}
{/if}

{foreach $categoryBlocks as $block}
<section class="section-premium home-category-block">
	<div class="section-head">
		<div>
			<h2 class="section-title fs-3">{$block.category.category_name|escape}</h2>
		</div>
		<a href="{$block.url}" class="btn">
			{'View All'|translate}
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-right-icon lucide-move-right"><path d="M18 8L22 12L18 16"/><path d="M2 12H22"/></svg>
		</a>
	</div>
	{include file='./productList.tpl' products=$block.products id=$block.category.id_category}
</section>
{break}
{/foreach}

{if $hooks.home_bottom}
<section class="section-premium home-category-block">
	{$hooks.home_bottom nofilter}
</section>
{/if}