<div class="row g-3 product-grid-premium">
{foreach $products as $p}
	<div class="col-6 col-md-4 col-lg-3">
		<div class="catalog-card h-100 text-center">
			{if $p.label}
			<span class="catalog-badge catalog-badge-label">{$p.label|escape}</span>
			{/if}
			{if $p.has_discount}
			<span class="catalog-badge">İndirim</span>
			{/if}
			{if !$p.in_stock}
			<span class="catalog-badge catalog-badge-muted">Tükendi</span>
			{/if}
			<a href="{$p.url}" class="catalog-image d-block">
				<img src="{$p.image_url}" alt="{$p.product_name|escape}" class="img-fluid" loading="lazy">
			</a>
			{if $p.review_count > 0}
				<div class="catalog-rating d-flex align-items-center justify-content-center gap-1">
					<div class="review-stars review-stars--sm" style="--rating: {$p.rating}">
						<span class="review-stars-track" aria-hidden="true"></span>
						<span class="review-stars-fill" aria-hidden="true"></span>
					</div>
					<span class="small text-muted">({$p.review_count})</span>
				</div>
			{/if}
			<div class="catalog-body p-2">
				<a href="{$p.url}" class="catalog-name d-block">{$p.product_name|escape|truncate:40:"":true:true}</a>
				
				<div class="d-flex align-items-center gap-3 productDiscount">
					{if $p.has_discount}<div class="discount-badge">%{Tools::getDiscount($p.old_price, $p.price)}</div>{/if}
					<div>
						{if $p.has_discount}<div class="old-price">{$p.old_price_formatted}</div>{/if}
						<div class="current-price">{$p.price_formatted}</div>
					</div>
				</div>
				<div class="catalog-actions d-flex gap-1 mt-2">
					{if $p.in_stock}
						<button type="button" class="btn btn-dark btn-sm addtocart flex-grow-1" data-id="{$p.id_product}" title="Sepete ekle">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-basket-icon lucide-shopping-basket"><path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4"/><path d="M4.5 15.5h15"/><path d="m5 11 4-7"/><path d="m9 11 1 9"/></svg>
							Sepete Ekle
						</button>
					{else}
						<a href="{$p.url}" class="btn btn-outline-dark btn-sm flex-grow-1">Detay</a>
					{/if}
				</div>
				{if isset($showFavoriteRemove) && $showFavoriteRemove}
				<button type="button" class="btn btn-link btn-sm text-danger remove-favorite mt-1" data-id="{$p.id_product}">Listeden Kaldır</button>
				{/if}
			</div>
		</div>
	</div>
{/foreach}
</div>
