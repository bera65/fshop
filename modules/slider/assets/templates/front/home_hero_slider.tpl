{if $slides|@count > 0}
<div class="fshop-slider fshop-slider--hero" data-slider-type="hero">
	<div class="fshop-slider__track">
		{foreach $slides as $slide name=heroSlides}
		<div class="fshop-slider__slide{if $smarty.foreach.heroSlides.first} is-active{/if}" style="background-image:url('{$slide.image_url|escape}');">
			<div class="fshop-slider__overlay"></div>
			<div class="fshop-slider__content site-container">
				<div class="fshop-slider__text">
					{if $slide.subtitle}
					<span class="fshop-slider__kicker">{$slide.subtitle|escape}</span>
					{/if}
					{if $slide.title}
					<h2 class="fshop-slider__title">{$slide.title|escape}</h2>
					{/if}
					{if $slide.promo_items|@count > 0}
					<div class="fshop-slider__promo-boxes">
						{foreach $slide.promo_items as $promoLine}
						<div class="fshop-slider__promo-box">{$promoLine|escape}</div>
						{/foreach}
					</div>
					{/if}
					{if $slide.link_url}
					<a href="{$slide.link_url|escape}" class="fshop-slider__btn">{$slide.button_text|escape}</a>
					{/if}
				</div>
			</div>
		</div>
		{/foreach}
	</div>

	{if $slides|@count > 1}
	<button type="button" class="fshop-slider__arrow fshop-slider__arrow--prev" aria-label="Önceki slayt">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
	</button>
	<button type="button" class="fshop-slider__arrow fshop-slider__arrow--next" aria-label="Sonraki slayt">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
	</button>
	<div class="fshop-slider__counter"><span class="fshop-slider__current">1</span> / <span class="fshop-slider__total">{$slides|@count}</span></div>
	{/if}
</div>
{/if}
