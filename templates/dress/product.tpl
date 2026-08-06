<div class="product-showcase">
	<div class="product-showcase__inner">
	<div class="row g-4">
		<div class="col-lg-6">
			<div class="product-gallery" data-image-count="{$images|@count}">
				{if $images|@count > 1}
				<div class="product-gallery__thumbs" role="list">
					{foreach $images as $img name=productGallery}
					<button type="button" class="product-gallery__thumb thumb-img{if $smarty.foreach.productGallery.first} active{/if}" data-image="{$img.url|escape}" data-index="{$smarty.foreach.productGallery.index}" aria-label="{$productName|escape}">
						<img loading="lazy" src="{$img.url|escape}" alt="{$productName|escape}">
					</button>
					{/foreach}
				</div>
				{/if}
				<div class="product-gallery__stage">
					<div class="product-gallery__main">
						{if $oldPrice > $price}
						<span class="product-gallery__discount" id="productImageDiscount">%{Tools::getDiscount($oldPrice, $price)} {'discount'|translate}</span>
						{elseif $productLabel}
						<span class="product-gallery__discount">{$productLabel|escape}</span>
						{/if}
						<button type="button" class="product-gallery__alert priceAllertButton alertButton" data-bs-toggle="modal" data-bs-target="#priceModal" data-id="{$product.id_product}" data-price="{$product.price}" aria-label="{'Price alert'|translate}" title="{'Price alert'|translate}">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
						</button>
						{if $images|@count > 1}
						<button type="button" class="product-gallery__nav product-gallery__nav--prev" aria-label="{'Previous image'|translate}">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
						</button>
						{/if}
						<img id="main-display" fetchpriority="high" decoding="async" data-bs-toggle="modal" data-bs-target="#imageModal" class="img-responsives cursorPointer" src="{$imageUrl|escape}" alt="{$productName|escape}" />
						{if $images|@count > 1}
						<button type="button" class="product-gallery__nav product-gallery__nav--next" aria-label="{'Next image'|translate}">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
						</button>
						{/if}
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6">
			<div class="product-purchase-card">
				<div class="product-heading">
					{if $brandName}<a class="product-brand-link" href="{$brandUrl|escape}">{$brandName|escape}</a>{/if}
					<h1>{$productName|escape}</h1>
					{if $hooks.product_inf}{$hooks.product_inf nofilter}{/if}
					{if $product.review_count|default:0 > 0}
					<div class="product-meta-row">
						<span class="product-meta-rating">{$product.rating_label|default:$product.rating|string_format:"%.1f"} ★</span>
						<span class="product-meta-link">{$product.review_count} {'Reviews'|translate}</span>
					</div>
					{/if}
				</div>

				<div class="product-price-panel">
					{if $oldPrice > $price}
					<div class="product-price-panel__top">
						<span class="old-price" id="productOldPrice">{Tools::displayPrice($oldPrice)}{if $saleUnit == 'm2'} / {'m²'|translate}{/if}</span>
						<span class="discount-badge" id="productDiscountBadge">%{Tools::getDiscount($oldPrice, $price)} {'discount'|translate}</span>
						<span class="product-price-save">{Tools::displayPrice($oldPrice - $price)} {'you save'|translate}</span>
					</div>
					{/if}
					<div class="product-price-panel__main">
						<div class="current-price" id="productCurrentPrice" data-base-price="{$price}">{Tools::displayPrice($price)}{if $saleUnit == 'm2'} <span class="price-unit">/ {'m²'|translate}</span>{/if}</div>
						<span class="product-price-vat">{'VAT included'|translate}</span>
					</div>
					{if $inStock}
					<div class="product-stock-status">
						<span class="product-stock-dot" aria-hidden="true"></span>
						<span>
							{'In stock'|translate}
							{if $saleUnit != 'm2' && $stock > 0 && $stock <= 20}
							 — <strong class="product-stock-low">{'last %d products!'|translate|replace:'%d':($stock|string_format:"%d")}</strong>
							{/if}
						</span>
					</div>
					{else}
					<div class="product-stock-status is-out">
						<span class="product-stock-dot" aria-hidden="true"></span>
						<span>{'Out Of Stock'|translate}</span>
					</div>
					{/if}
					{if $shortDescription}
						<p class="text-muted small mb-3 product-short-desc">{$shortDescription|escape}</p>
					{/if}
				</div>
				<div class="col-md-12">{include file='./plugin/productConfigurator.tpl'}</div>
				<div class="productCenter">
					{if $hasVariations}
					<div class="product-variations mb-3" id="productVariations" data-required-groups="{$variationGroups|@count}" data-select-hint="{'Select product options'|translate|escape}" data-out-hint="{'Out Of Stock'|translate|escape}">
						{foreach from=$variationGroups item=group name=varGroup}
						<div class="product-variation-group mb-3" role="radiogroup" aria-labelledby="variationLabel{$smarty.foreach.varGroup.index}">
							<div class="product-variation-label" id="variationLabel{$smarty.foreach.varGroup.index}">
								<span class="product-variation-name">{$group.name|escape}</span>
								<span class="product-variation-selected" data-group-label="{$group.name|escape}"></span>
							</div>
							<div class="product-variation-options">
								{foreach $group.values as $val}
								<button type="button" role="radio" aria-checked="false" class="product-variation-option" data-group="{$group.name|escape}" data-value="{$val|escape}">{$val|escape}</button>
								{/foreach}
							</div>
						</div>
						{/foreach}
						<p class="product-variation-summary small text-muted mb-1 d-none" id="variationSummary"></p>
						<p class="small text-muted mb-0" id="variationHint">{'Select product options'|translate}</p>
						<input type="hidden" id="selectedVariationId" value="0">
					</div>
					<script type="application/json" id="variationItemsData">{$variationItemsJson nofilter}</script>
					{/if}

					<div class="product-buy-block">
						{if $inStock}
						{if $saleUnit == 'm2'}
						<div class="m2-measure" id="m2Measure" data-min="{$saleQtyMin|escape}" data-step="{$saleQtyStep|escape}" data-price="{$price}" data-stock="{$stock}">
							<div class="m2-measure__fields">
								<label class="m2-measure__field">
									<span>{'Width (m)'|translate}</span>
									<input type="number" id="m2-width" class="form-control" min="0" step="0.01" inputmode="decimal" placeholder="0.00">
								</label>
								<label class="m2-measure__field">
									<span>{'Length (m)'|translate}</span>
									<input type="number" id="m2-length" class="form-control" min="0" step="0.01" inputmode="decimal" placeholder="0.00">
								</label>
							</div>
							<p class="m2-measure__summary small text-muted mb-0" id="m2Summary">{'Enter width and length'|translate}</p>
							<input type="hidden" id="qty-input" value="0" data-max="{$stock}" data-sale-unit="m2">
						</div>
						{/if}

						<div class="product-buy-main">
							{if $saleUnit != 'm2'}
							<div class="qty-picker" id="qtyPicker">
								<button type="button" class="qty-btn" onclick="updateQty(-1)" aria-label="{'Down'|translate}">−</button>
								<input type="text" value="1" id="qty-input" class="qty-input" readonly data-max="{$stock}" data-sale-unit="piece">
								<button type="button" class="qty-btn" onclick="updateQty(1)" aria-label="{'Up'|translate}">+</button>
							</div>
							{/if}
							<button type="button" class="btn btn-details cart-button addtocart d-flex align-items-center justify-content-center gap-2{if $hasVariations} requires-variation{/if}{if $saleUnit == 'm2'} requires-measure{/if}" data-id="{$product.id_product}" data-variation="0"{if $hasVariations} disabled{/if}>
								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4"/><path d="M4.5 15.5h15"/><path d="m5 11 4-7"/><path d="m9 11 1 9"/></svg>
								{'Add To Cart'|translate}
							</button>
							<button type="button" class="btn product-fav-btn like-button toggle-favorite{if $isFavorite} active{/if}" data-id="{$product.id_product}" aria-label="{'Add to favorites'|translate}">
								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/></svg>
								<span class="product-fav-count{if $favoriteCount|default:0 <= 0} d-none{/if}" data-count="{$favoriteCount|default:0}">{if $favoriteCount|default:0 > 0}{$favoriteCount}+{/if}</span>
							</button>
						</div>

						<button type="button" class="btn btn-buy-now buynow d-flex align-items-center justify-content-center gap-2{if $hasVariations} requires-variation{/if}{if $saleUnit == 'm2'} requires-measure{/if}" data-id="{$product.id_product}" data-variation="0"{if $hasVariations} disabled{/if}>
							{'Buy now'|translate}
						</button>
						{else}
						<button type="button" class="btn btn-secondary w-100" disabled>{'Out Of Stock'|translate}</button>
						{/if}
					</div>

					<ul class="product-service-list">
						<li>
							<span class="product-service-icon" aria-hidden="true">
								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
							</span>
							<div>
								<strong>{'Free Shipping'|translate}</strong>
								<span>{if $freeCargo > 0}{$freeCargo|string_format:"%.0f"} TL {'Free shipping orders over'|translate}{else}{'Free'|translate}{/if}</span>
							</div>
						</li>
						<li>
							<span class="product-service-icon" aria-hidden="true">
								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
							</span>
							<div>
								<strong>{'Estimated delivery'|translate} — {$cargoDay} {'Day(s)'|translate}</strong>
								<span>{'Cargo'|translate}: {if $price >= $freeCargo}{'Free'|translate}{else}{Tools::displayPrice($cargoPrice)}{/if}</span>
							</div>
						</li>
						<li>
							<span class="product-service-icon" aria-hidden="true">
								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>
							</span>
							<div>
								<strong>{'Stock Code'|translate}</strong>
								<span>{$stockCode|escape}</span>
							</div>
						</li>
					</ul>

					{if $hooks.product_detail}
					<div class="product-detail-hook mt-3">
						{$hooks.product_detail nofilter}
					</div>
					{/if}
					
				</div>
				
			</div>
		</div>
	</div>
</div>
</div>
<div class="product-content-tabs">
<ul class="nav nav-tabs" id="productTabs">
    <li class="nav-item" role="presentation">
        <button class="nav-link active"
                data-bs-toggle="tab"
                data-bs-target="#description"
                type="button">
            {'Description'|translate}
        </button>
    </li>
	{if $productVideoEmbed}
	<li class="nav-item" role="presentation">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#video"
                type="button">
            {'Video'|translate}
        </button>
    </li>
	{/if}
	{if $hooks.product_tab}{$hooks.product_tab nofilter}{/if}
</ul>
<div class="tab-content product-tab-content">

    <!-- Açıklama -->
    <div class="tab-pane fade show active" id="description">
        {if $description}
			{$description nofilter}
		{/if}
    </div>
	<div class="tab-pane" id="video">
        {if $productVideoEmbed}
			<div class="product-video ratio ratio-16x9 mt-3">
				<iframe src="{$productVideoEmbed|escape}" title="{$productName|escape} videosu" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>
			</div>
		{/if}
    </div>
    {if $hooks.product_tab_content}{$hooks.product_tab_content nofilter}{/if}
</div>
</div>
{if $hooks.product}{$hooks.product nofilter}{/if}

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-600 modal-dialog-centered">
    <div class="modal-content">
		<div class="modal-header">
        <h5 class="modal-title fs-5" id="imageModalLabel">{$productName|escape}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|translate}"></button>
      </div>
      <div class="modal-body">
        <img id="modal-display" src="{$imageUrl|escape}" class="imageFull" alt="{$productName|escape}" />
      </div>
    </div>
  </div>
</div>
