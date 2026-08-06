{assign var="listProducts" value=$products|default:[]}
{if $listProducts|@count == 0 && isset($product)}
	{assign var="listProducts" value=$product}
{/if}
{if $listProducts|@count > 0}
<div class="slider-wrapper">
	<button class="slider-nav-btn prev-btn" onclick="scrollContent('{$id}', 'left')">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
	</button>

	<div class="sicakFirsatlar d-flex flex-nowrap gap-3 overflow-auto pb-3" id="{$id}" style="scrollbar-width: none;">
		{foreach $listProducts as $p}
			<div class="item flex-shrink-0 produtDiv">
				<div class="panel product-card d-flex flex-column">
					{if $p.label}
						<span class="miniDiscount bg-dark">{$p.label|escape}</span>
					{/if}
					{if !$p.in_stock}
						<span class="miniDiscount bg-danger">{'Out of Stock'|translate}</span>
					{/if}
					<a class="product-image-wrapper d-flex align-items-center justify-content-center p-3" href="{$p.url}" title="{$p.product_name|escape}">
						<img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 200'%3E%3C/svg%3E" 
							 data-src="{$p.image_url}" 
							 class="product-image lazy img-fluid"
							 loading="lazy"
							 style="max-height: 100%; object-fit: contain;"
							 alt="{$p.product_name|escape}">
					</a>
					<div class="imageButtons">
						<button class="btn priceAllertButton alertButton" data-bs-toggle="modal" data-bs-target="#priceModal" data-id="{$p.id_product}" data-price="{$p.price}">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bell-icon lucide-bell"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
						</button>
					</div>
					<div class="product-info d-flex flex-column flex-grow-1">
						{if $p.review_count > 0}
							<div class="rating mb-2 d-flex align-items-center gap-1">
								<div class="text-warning">
								{for $i=1 to $p.rating|string_format:"%.0f"}
									<span>★</span> 
								{/for}
								{for $i=$p.rating to 4}
									<span class="text-muted">☆</span> 
								{/for}
								</div>
							</div>
						{/if}

						<a href="{$p.url}" title="{$p.product_name|escape}" class="text-decoration-none">
							<h4 class="product-title fw-bold text-dark mb-2">{$p.product_name|escape}</h4>
						</a>
						
						<div class="d-flex align-items-center gap-3 productDiscount justify-content-center">
							{if $p.has_discount}<div class="discount-badge">%{Tools::getDiscount($p.old_price, $p.price)}</div>{/if}
							<div>
								{if $p.has_discount}<div class="old-price">{$p.old_price_formatted}</div>{/if}
								<div class="current-price">{$p.price_formatted}</div>
							</div>
						</div>
						{if $p.in_stock}
							<button type="button" class="btn btn-details w-100 addtocart" data-id="{$p.id_product}">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-basket-icon lucide-shopping-basket"><path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4"/><path d="M4.5 15.5h15"/><path d="m5 11 4-7"/><path d="m9 11 1 9"/></svg>
								{'Add to Cart'|translate}
							</button>
						{else}
							<a href="{$p.url}" class="btn btn-details w-100">{'View'|translate}</a>
						{/if}
					</div>
				</div>
			</div>
		{/foreach}
	</div>
	<button class="slider-nav-btn next-btn" onclick="scrollContent('{$id}', 'right')">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
	</button>
</div>
{/if}