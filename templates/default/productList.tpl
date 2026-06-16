{assign var="listProducts" value=$products|default:[]}
{if $listProducts|@count == 0 && isset($product)}
	{assign var="listProducts" value=$product}
{/if}

{if $listProducts|@count > 0}
<div class="product-list-scroll-wrap">
	<button type="button" class="product-list-nav product-list-nav--prev" aria-label="Önceki ürünler">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
	</button>
	<div class="product-list-scroll" tabindex="-1" aria-label="Ürün listesi">
		<div class="product-list-track">
			{foreach $listProducts as $p}
			<div class="product-list-item">
				<div class="catalog-card h-100 text-center">
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
						<div class="d-flex align-items-center gap-3 productDiscount justify-content-center">
							{if $p.has_discount}<div class="discount-badge">%{Tools::getDiscount($p.old_price, $p.price)}</div>{/if}
							<div>
								{if $p.has_discount}<div class="old-price">{$p.old_price_formatted}</div>{/if}
								<div class="current-price">{$p.price_formatted}</div>
							</div>
						</div>
						<div class="catalog-actions d-flex gap-1 mt-2">
							{if $p.in_stock}
							<button type="button" class="btn btn-dark btn-sm addtocart flex-grow-1" data-id="{$p.id_product}" title="Sepete ekle">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
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
	</div>
	<button type="button" class="product-list-nav product-list-nav--next" aria-label="Sonraki ürünler">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
	</button>
</div>
{/if}
