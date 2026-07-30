{assign var="p" value=$product}
<div class="card restaurant-card product-card">
	<a href="{$p.url|escape}" class="product-card__image-link">
		{if $p.has_discount}
		<span class="promo-badge">%{Tools::getDiscount($p.old_price, $p.price)} {'Discount'|translate}</span>
		{elseif $p.label}
		<span class="promo-badge">{$p.label|escape}</span>
		{/if}
		{if !$p.in_stock}
		<span class="promo-badge promo-badge--muted">{'Out Of Stock'|translate}</span>
		{/if}
		<img src="{$p.image_url|escape}" class="card-img-top" alt="{$p.product_name|escape}" loading="lazy">
	</a>
	<div class="card-body">
		<div class="d-flex justify-content-between align-items-start mb-2">
			<h5 class="card-title fw-bold mb-0">
				<a href="{$p.url|escape}" class="text-dark text-decoration-none">{$p.product_name|escape}</a>
			</h5>
			{if $p.review_count > 0}
			<span class="badge-rating"><i class="bi bi-star-fill me-1"></i>{$p.rating_label|default:$p.rating}</span>
			{/if}
		</div>
		<p class="small text-muted mb-2">{$p.category_name|escape}{if $p.brand_name} · {$p.brand_name|escape}{/if}</p>
		<hr class="my-2 opacity-10">
		<div class="d-flex justify-content-between align-items-center small">
			<div>
				{if $p.has_discount}<div class="text-muted text-decoration-line-through">{$p.old_price_formatted}</div>{/if}
				<div class="fw-bold text-dark fs-6">{$p.price_formatted}</div>
			</div>
			{if $p.in_stock}
			<button type="button" class="btn btn-sm btn-primary product-quick-open" data-id="{$p.id_product}" title="{'Add To Cart'|translate}">
				<i class="bi bi-bag-plus"></i>
			</button>
			{else}
			<span class="text-muted">{'Out Of Stock'|translate}</span>
			{/if}
		</div>
	</div>
</div>
