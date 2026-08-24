{assign var="p" value=$product}
<div class="menu-product-card">
	<a href="{$p.url|escape}" class="menu-product-card__image" title="{$p.product_name|escape}">
		{if $p.label}
		<span class="menu-product-card__tag menu-product-card__tag--new">{$p.label|escape}</span>
		{elseif $p.has_discount}
		<span class="menu-product-card__tag">%{Tools::getDiscount($p.old_price, $p.price)}</span>
		{/if}
		{if !$p.in_stock}
		<span class="menu-product-card__tag menu-product-card__tag--muted">{'Out Of Stock'|translate}</span>
		{/if}
		<img src="{$p.image_url|escape}" alt="{$p.product_name|escape}" loading="lazy">
	</a>
	<div class="menu-product-card__body">
		<div class="menu-product-card__top">
			<h3 class="menu-product-card__title">
				<a href="{$p.url|escape}" class="text-decoration-none text-dark">{$p.product_name|escape}</a>
			</h3>
			{if $p.list_excerpt}
			<p class="menu-product-card__desc">{$p.list_excerpt|truncate:72:'…'|escape}</p>
			{/if}
			{if $p.review_count > 0}
			<div class="menu-product-card__rating">
				<i class="bi bi-hand-thumbs-up-fill"></i>
				<span>%{((($p.rating|default:0) * 20)|round)} {'Liked'|translate} ({$p.review_count} {'Review(s)'|translate})</span>
			</div>
			{/if}
		</div>
		<div class="menu-product-card__footer">
			<div class="menu-product-card__price-wrap">
				{if $p.has_discount}<div class="menu-product-card__old-price">{$p.old_price_formatted}</div>{/if}
				<div class="menu-product-card__price">{$p.price_formatted}</div>
			</div>
			{if $p.in_stock}
			<button type="button" class="btn menu-product-card__btn product-quick-open" data-id="{$p.id_product}">
				{'Add To Cart'|translate}
			</button>
			{else}
			<span class="small text-muted">{'Out Of Stock'|translate}</span>
			{/if}
		</div>
	</div>
</div>
