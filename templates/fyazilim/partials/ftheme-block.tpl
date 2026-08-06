{assign var=blockId value=$block.id|default:''}
{assign var=blockType value=$block.type|default:'html'}

{if $blockType == 'slider'}
{if $hooks.home_slider}
<section class="fy-container ftheme-block-wrap" data-ftheme-block="{$blockId|escape}">
	{$hooks.home_slider nofilter}
</section>
{/if}

{elseif $blockType == 'featured'}
<section class="fy-section fy-reveal ftheme-block-wrap" data-ftheme-block="{$blockId|escape}">
	<div class="fy-container">
		<div class="fy-section__head">
			<h2 data-ftheme="feature-title">{$featureTitle}</h2>
			<p data-ftheme="feature-desc">{$featureDesc}</p>
		</div>
		{include file='./product-slider.tpl' products=$featuredProducts cardStyle='software' sliderId='fyModules'}
	</div>
</section>

{elseif $blockType == 'promo'}
{if $hooks.home_promo_slider}
<section class="fy-section fy-section--soft ftheme-block-wrap" data-ftheme-block="{$blockId|escape}">
	<div class="fy-container">
		{$hooks.home_promo_slider nofilter}
	</div>
</section>
{/if}

{elseif $blockType == 'categories'}
{assign var=catLimit value=$block.limit|default:2}
{foreach $categoryBlocks as $catBlock name=catBlocks}
{if $smarty.foreach.catBlocks.iteration > $catLimit}{break}{/if}
<section class="fy-section{if $smarty.foreach.catBlocks.iteration is odd} fy-section--soft{/if} fy-reveal ftheme-block-wrap" data-ftheme-block="{$blockId|escape}-{$smarty.foreach.catBlocks.iteration}">
	<div class="fy-container">
		<div class="fy-section__head">
			<h2>{$catBlock.category.category_name|escape}</h2>
		</div>
		{include file='./product-slider.tpl' products=$catBlock.products cardStyle='theme' sliderId='fyThemes'|cat:$smarty.foreach.catBlocks.iteration}
	</div>
</section>
{/foreach}

{elseif $blockType == 'home_text'}
{if $homeText}
<section class="fy-section fy-home-text fy-reveal ftheme-block-wrap" data-ftheme-block="{$blockId|escape}">
	<div class="fy-container fy-home-text__body">
		{$homeText nofilter}
	</div>
</section>
{/if}

{elseif $blockType == 'html'}
<section class="fy-section fy-reveal ftheme-block-wrap" data-ftheme-block="{$blockId|escape}">
	<div class="fy-container">
		{if $block.title|default:'' != ''}
		<div class="fy-section__head">
			<h2>{$block.title|escape}</h2>
		</div>
		{/if}
		<div class="ftheme-custom-html">
			{$block.content nofilter}
		</div>
	</div>
</section>
{/if}
