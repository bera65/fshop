{if $fthemeHomeRenderUnits|default:[]|@count > 0}
{foreach $fthemeHomeRenderUnits as $unit}
{if $unit.type == 'banner_row'}
{include file='./partials/ftheme-banner-row.tpl' unit=$unit}
{else}
{include file='./partials/ftheme-block.tpl' block=$unit.block}
{/if}
{/foreach}
{else}
{if $hooks.home_slider}
<section class="fy-container">
	{$hooks.home_slider nofilter}
</section>
{/if}

<section class="fy-section fy-reveal">
	<div class="fy-container">
		<div class="fy-section__head">
			<h2>{$featureTitle}</h2>
			<p>{$featureDesc}</p>
		</div>
		{include file='./partials/product-slider.tpl' products=$featuredProducts cardStyle='software' sliderId='fyModules'}
	</div>
</section>

{if $hooks.home_promo_slider}
<section class="fy-section fy-section--soft">
	<div class="fy-container">
		{$hooks.home_promo_slider nofilter}
	</div>
</section>
{/if}

{foreach $categoryBlocks as $block name=catBlocks}
<section class="fy-section{if $smarty.foreach.catBlocks.iteration is odd} fy-section--soft{/if} fy-reveal">
	<div class="fy-container">
		<div class="fy-section__head">
			<h2>{$block.category.category_name|escape}</h2>
		</div>
		{include file='./partials/product-slider.tpl' products=$block.products cardStyle='theme' sliderId='fyThemes'|cat:$smarty.foreach.catBlocks.iteration}
	</div>
</section>
{if $smarty.foreach.catBlocks.iteration >= 2}{break}{/if}
{/foreach}

{if $homeText}
<section class="fy-section fy-home-text fy-reveal">
	<div class="fy-container fy-home-text__body">
		{$homeText nofilter}
	</div>
</section>
{/if}
{/if}

{if $hooks.home_bottom}
<section class="fy-section fy-section--soft fy-reveal">
	<div class="fy-container">
		{$hooks.home_bottom nofilter}
	</div>
</section>
{/if}
