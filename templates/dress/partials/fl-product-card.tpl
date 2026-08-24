{assign var="p" value=$p}
<article class="fl-card">
	<div class="fl-card__media">
		<a href="{$p.url|escape}" class="fl-card__media-link" title="{$p.product_name|escape}">
			<span class="fl-card__badges">
				{if $p.label}
				<span class="fl-card__badge fl-card__badge--label">{$p.label|escape}</span>
				{/if}
				{if $p.has_discount}
				<span class="fl-card__badge fl-card__badge--sale">%{Tools::getDiscount($p.old_price, $p.price)}</span>
				{/if}
				{if !$p.in_stock}
				<span class="fl-card__badge fl-card__badge--oos">{'Out of Stock'|translate}</span>
				{/if}
			</span>
			{if $eager|default:false}
			<img src="{$p.image_url|escape}" alt="{$p.product_name|escape}" class="fl-card__img" fetchpriority="high" decoding="async">
			{else}
			<img src="{$p.image_url|escape}" alt="{$p.product_name|escape}" class="fl-card__img" loading="lazy" decoding="async">
			{/if}
			{if $p.in_stock}
			<span class="fl-card__ship">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
				{'Same day delivery'|translate}
			</span>
			{/if}
		</a>
		{if $p.in_stock}
			{if $p.has_variations|default:false}
			<a href="{$p.url|escape}" class="fl-card__cart fl-card__cart--link">
				{'Select options'|translate|default:'Seçenekleri seç'}
			</a>
			{else}
			<button type="button" class="fl-card__cart addtocart" data-id="{$p.id_product}" data-variation="0">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
				{'Add To Cart'|translate}
			</button>
			{/if}
		{/if}
	</div>
	<div class="fl-card__body">
		<a href="{$p.url|escape}" class="fl-card__title" title="{$p.product_name|escape}">{$p.product_name|escape}</a>
		{if $p.review_count|default:0 > 0}
		<div class="fl-card__rating" title="{$p.rating|string_format:'%.1f'} / 5 ({$p.review_count})">
			<span class="fl-card__stars" aria-hidden="true">
				{assign var="flStars" value=$p.rating|round:0}
				{for $i=1 to 5}
					{if $i <= $flStars}<span class="is-on">★</span>{else}<span class="is-off">☆</span>{/if}
				{/for}
			</span>
			<span class="fl-card__rating-val">{$p.rating|string_format:"%.1f"}</span>
			<span class="fl-card__rating-count">({$p.review_count})</span>
		</div>
		{/if}
		<div class="fl-card__prices">
			{if $p.has_discount}
			<span class="fl-card__old">{$p.old_price_formatted}</span>
			{/if}
			<span class="fl-card__price">{$p.price_formatted}</span>
		</div>
	</div>
</article>
