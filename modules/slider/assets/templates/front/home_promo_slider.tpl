{if $slides|@count > 0}
<section class="fshop-slider-section fshop-slider-section--promo">
	<div class="site-container">
		<h2 class="fshop-slider-section__title">{$promoTitle|escape}</h2>
	</div>

	<div class="fshop-slider fshop-slider--promo" data-slider-type="promo" data-slider-per-view="2">
		<div class="fshop-slider__viewport site-container">
			<div class="fshop-slider__track">
				{foreach $slides as $slide}
				<div class="fshop-slider__slide">
					<a href="{$slide.link_url|default:'#'|escape}" class="fshop-slider__promo-card" style="background-image:url('{$slide.image_url|escape}');">
						<div class="fshop-slider__promo-overlay"></div>
						<div class="fshop-slider__promo-content">
							{if $slide.subtitle}
							<span class="fshop-slider__promo-kicker">{$slide.subtitle|escape}</span>
							{/if}
							{if $slide.title}
							<strong class="fshop-slider__promo-title">{$slide.title|escape}</strong>
							{/if}
							<span class="fshop-slider__promo-link">{$slide.button_text|escape} &gt;&gt;</span>
						</div>
					</a>
				</div>
				{/foreach}
			</div>
		</div>

		{if $slides|@count > 1}
		<button type="button" class="fshop-slider__arrow fshop-slider__arrow--prev" aria-label="Önceki">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
		</button>
		<button type="button" class="fshop-slider__arrow fshop-slider__arrow--next" aria-label="Sonraki">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
		</button>
		{/if}
	</div>
</section>
{/if}
