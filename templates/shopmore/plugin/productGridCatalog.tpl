<div class="blue-catalog-grid">
{foreach $products as $p}
	<article class="panel product-card blue-catalog-card d-flex flex-column">
		{if $p.label}
		<span class="miniDiscount bg-dark">{$p.label|escape}</span>
		{/if}
		{if !$p.in_stock}
		<span class="miniDiscount bg-danger">{'Out of Stock'|translate}</span>
		{/if}
		{if $p.has_discount}
		<span class="miniDiscount bg-primary">-%{Tools::getDiscount($p.old_price, $p.price)}</span>
		{/if}
		<a class="product-image-wrapper d-flex align-items-center justify-content-center p-3" href="{$p.url}" title="{$p.product_name|escape}">
			{if $p@first}
			<img src="{$p.image_url}" class="product-image img-fluid" fetchpriority="high" decoding="async" alt="{$p.product_name|escape}">
			{else}
			<img src="{$p.image_url}" class="product-image img-fluid" loading="lazy" decoding="async" alt="{$p.product_name|escape}">
			{/if}
		</a>
		<div class="product-info d-flex flex-column flex-grow-1 panelBody">
			<a href="{$p.url}" title="{$p.product_name|escape}" class="text-decoration-none">
				<h4 class="product-title fw-bold text-dark mb-2">{$p.product_name|escape}</h4>
			</a>
			<div class="d-flex align-items-center gap-3 productDiscount justify-content-center mt-auto">
				{if $p.has_discount}<div class="discount-badge">%{Tools::getDiscount($p.old_price, $p.price)}</div>{/if}
				<div>
					{if $p.has_discount}<div class="old-price">{$p.old_price_formatted}</div>{/if}
					<div class="current-price">{$p.price_formatted}</div>
				</div>
			</div>
			{if $p.in_stock}
			<button type="button" class="btn btn-details w-100 addtocart mt-2" data-id="{$p.id_product}">
				{'Add to Cart'|translate}
			</button>
			{else}
			<a href="{$p.url}" class="btn btn-details w-100 mt-2">{'Details'|translate}</a>
			{/if}
		</div>
	</article>
{/foreach}
</div>
