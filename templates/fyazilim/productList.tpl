{assign var="listProducts" value=$products|default:[]}
{assign var="cardStyle" value=$cardStyle|default:'software'}
{assign var="sliderId" value=$sliderId|default:'fySlider'}

{if $listProducts|@count > 0}
<div class="fy-slider" data-fy-slider="{$sliderId|escape}">
	<button type="button" class="fy-slider__nav fy-slider__nav--prev" data-fy-prev aria-label="Önceki">
		<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
	</button>
	<div class="fy-slider__track" data-fy-track>
		{foreach $listProducts as $p}
		<div class="fy-slider__item">
			<article class="fy-card{if $cardStyle == 'theme'} fy-card--theme{/if}">
				{if $p.label}
					<span class="fy-card__badge">{$p.label|escape}</span>
				{elseif $p.has_discount}
					<span class="fy-card__badge">%{Tools::getDiscount($p.old_price, $p.price)}</span>
				{elseif $p@index < 2}
					<span class="fy-card__badge">Yeni</span>
				{/if}

				<a class="fy-card__media" href="{$p.url}" title="{$p.product_name|escape}">
					{if $p@first}
					<img src="{$p.image_url}" alt="{$p.product_name|escape}" fetchpriority="high" decoding="async" width="80%" height="auto" />
					{else}
					<img src="{$p.image_url}" alt="{$p.product_name|escape}" loading="lazy" decoding="async" width="80%" height="auto" />
					{/if}
				</a>

				<div class="fy-card__body">
					<a href="{$p.url}" class="fy-card__title">{$p.product_name|escape}</a>
					<p class="fy-card__desc">
						{if $p.short_description}
							{$p.short_description|escape|truncate:70}
						{elseif $p.brand_name}
							{$p.brand_name|escape} · Frisay uyumlu çözüm
						{else}
							Frisay açık kaynak e-ticaret için hazır paket
						{/if}
					</p>
					<div class="fy-card__price">
						{if $p.has_discount}<del>{$p.old_price_formatted}</del>{/if}
						{$p.price_formatted}
					</div>
					<div class="fy-card__actions">
						{if $cardStyle == 'theme'}
							<a href="{$p.url}" class="fy-btn fy-btn--secondary fy-btn--sm">Demo</a>
							<a href="{$p.url}" class="fy-btn fy-btn--primary fy-btn--sm">Detay</a>
						{else}
							<a href="{$p.url}" class="fy-btn fy-btn--primary fy-btn--sm">İncele</a>
							{if $p.in_stock}
								<button type="button" class="fy-btn fy-btn--secondary fy-btn--sm addtocart" data-id="{$p.id_product}">Satın Al</button>
							{else}
								<a href="{$p.url}" class="fy-btn fy-btn--secondary fy-btn--sm">{'Out of Stock'|translate}</a>
							{/if}
						{/if}
					</div>
				</div>
			</article>
		</div>
		{/foreach}
	</div>
	<button type="button" class="fy-slider__nav fy-slider__nav--next" data-fy-next aria-label="Sonraki">
		<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
	</button>
</div>
{else}
<div class="fy-empty">Henüz ürün bulunmuyor.</div>
{/if}
