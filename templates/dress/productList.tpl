{assign var="listProducts" value=$products|default:[]}
{if $listProducts|@count == 0 && isset($product)}
	{assign var="listProducts" value=$product}
{/if}
{if $listProducts|@count > 0}
<div class="fl-slider">
	<button type="button" class="fl-slider__nav fl-slider__nav--prev" onclick="scrollContent('{$id}', 'left')" aria-label="{'Previous'|translate}">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
	</button>
	<div class="fl-slider__track" id="{$id}">
		{foreach $listProducts as $p}
		<div class="fl-slider__item">
			{include file='./partials/fl-product-card.tpl' p=$p eager=false}
		</div>
		{/foreach}
	</div>
	<button type="button" class="fl-slider__nav fl-slider__nav--next" onclick="scrollContent('{$id}', 'right')" aria-label="{'Next'|translate}">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
	</button>
</div>
{/if}
