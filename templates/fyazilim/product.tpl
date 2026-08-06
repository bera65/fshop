<div class="fy-product">

	{if $breadcrumb|@count}
	<nav class="fy-product__breadcrumb" aria-label="Breadcrumb">
		<ol class="fy-product__crumbs">
			{foreach $breadcrumb as $crumb name=crumbs}
			<li class="fy-product__crumb{if $crumb.url == ''} fy-product__crumb--active{/if}">
				{if $crumb.url != ''}
				<a href="{$crumb.url|escape}">{$crumb.name|escape}</a>
				{else}
				<span>{$crumb.name|escape}</span>
				{/if}
			</li>
			{/foreach}
		</ol>
	</nav>
	{/if}

	<div class="fy-product__hero">
		<div class="fy-product__gallery-col">
			<div class="fy-product__gallery-card productLeftCloumn">
				{if $productLabel}
				<span class="product-label-badge fy-product__label">{$productLabel|escape}</span>
				{/if}

				<div class="product-gallery" data-image-count="{$images|@count}">
					<div class="product-gallery__main fy-product__gallery-main">
						{if $images|@count > 1}
						<button type="button" class="product-gallery__nav product-gallery__nav--prev" aria-label="{'Previous image'|translate}">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
						</button>
						{/if}
						<img id="main-display" fetchpriority="high" decoding="async" data-bs-toggle="modal" data-bs-target="#imageModal" class="fy-product__main-img img-responsives cursorPointer" src="{$imageUrl|escape}" alt="{$productName|escape}" />
						{if $images|@count > 1}
						<button type="button" class="product-gallery__nav product-gallery__nav--next" aria-label="{'Next image'|translate}">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
						</button>
						{/if}
					</div>
					{if $images|@count > 1}
					<div class="product-gallery__thumbs fy-product__thumbs">
						{foreach $images as $img name=productGallery}
						<button type="button" class="product-gallery__thumb thumb-img{if $smarty.foreach.productGallery.first} active{/if}" data-image="{$img.url|escape}" data-index="{$smarty.foreach.productGallery.index}" aria-label="{$productName|escape}">
							<img loading="lazy" src="{$img.url|escape}" alt="{$productName|escape}">
						</button>
						{/foreach}
					</div>
					{/if}
				</div>

				<div class="fy-product__gallery-actions imageButtons">
					<button type="button" class="fy-product__icon-btn like-button toggle-favorite{if $isFavorite} active{/if}" data-id="{$product.id_product}" aria-label="Favori">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/></svg>
					</button>
					<button type="button" class="fy-product__icon-btn priceAllertButton alertButton" data-bs-toggle="modal" data-bs-target="#priceModal" data-id="{$product.id_product}" data-price="{$product.price}" aria-label="Fiyat alarmı">
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
					</button>
				</div>
			</div>
		</div>

		<div class="fy-product__buy-col productRightCloumn">
			<div class="fy-product__buy-card">
				<div class="fy-product__meta-row">
					{if $product.category_name}
					<a href="{$domain}{$product.category_link|escape}" class="fy-product__category">{$product.category_name|escape}</a>
					{/if}
					{if $inStock}
					<span class="fy-product__stock fy-product__stock--in">{'In stock'|translate}</span>
					{else}
					<span class="fy-product__stock fy-product__stock--out">{'Out of stock'|translate}</span>
					{/if}
				</div>

				<h1 class="fy-product__title">{$productName|escape}</h1>

				{if $hooks.product_inf}
				<div class="fy-product__rating">{$hooks.product_inf nofilter}</div>
				{/if}

				{if $shortDescription}
				<p class="fy-product__lead">{$shortDescription|escape}</p>
				{/if}

				<div class="fy-product__price-box">
					{if $oldPrice > 0}
					<span class="discount-badge fy-product__discount" id="productDiscountBadge">%{Tools::getDiscount($oldPrice, $price)}</span>
					{/if}
					<div class="fy-product__price-lines">
						{if $oldPrice > 0}
						<div class="old-price fy-product__old-price" id="productOldPrice">{Tools::displayPrice($oldPrice)}</div>
						{/if}
						<div class="current-price fy-product__price" id="productCurrentPrice" data-base-price="{$price}">{Tools::displayPrice($price)}</div>
					</div>
				</div>

				<div class="productCenter fy-product__actions">
					{if $hasVariations}
					<div class="product-variations fy-product__variations" id="productVariations" data-required-groups="{$variationGroups|@count}" data-select-hint="{'Select product options'|translate|escape}" data-out-hint="{'Out Of Stock'|translate|escape}">
						{foreach from=$variationGroups item=group name=varGroup}
						<div class="product-variation-group" role="radiogroup" aria-labelledby="variationLabel{$smarty.foreach.varGroup.index}">
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
						<p class="fy-product__variation-hint" id="variationHint">{'Select product options'|translate}</p>
						<input type="hidden" id="selectedVariationId" value="0">
					</div>
					<script type="application/json" id="variationItemsData">{$variationItemsJson nofilter}</script>
					{/if}

					<div class="fy-product__cart-row">
						{if $inStock}
						<div class="qty-picker fy-product__qty" id="qtyPicker">
							<button type="button" class="qty-btn" onclick="updateQty(-1)" aria-label="-">−</button>
							<input type="text" value="1" id="qty-input" class="qty-input" readonly data-max="{$stock}">
							<button type="button" class="qty-btn" onclick="updateQty(1)" aria-label="+">+</button>
						</div>
						<button type="button" class="fy-btn fy-btn--primary fy-product__add-btn cart-button addtocart{if $hasVariations} requires-variation{/if}" data-id="{$product.id_product}" data-variation="0"{if $hasVariations} disabled{/if}>
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
							{'Add To Cart'|translate}
						</button>
						{else}
						<button type="button" class="fy-btn fy-btn--secondary fy-product__add-btn" disabled>{'Out Of Stock'|translate}</button>
						{/if}
					</div>

					<ul class="fy-product__facts productMiniDetail">
						{if $brandName}
						<li>
							<span class="fy-product__fact-label">{'Brand'|translate}</span>
							<a href="{$brandUrl|escape}" title="{$brandName|escape}">{$brandName|escape}</a>
						</li>
						{/if}
						<li>
							<span class="fy-product__fact-label">{'Stock Code'|translate}</span>
							<span>{$stockCode|escape}</span>
						</li>
						<li>
							<span class="fy-product__fact-label">{'Cargo'|translate}</span>
							<span>{if $price >= $freeCargo}{'Free'|translate}{else}{Tools::displayPrice($cargoPrice)}{/if}</span>
						</li>
						<li>
							<span class="fy-product__fact-label">{'Cargo Day'|translate}</span>
							<span>{$cargoDay} {'Day(s)'|translate}</span>
						</li>
					</ul>

					{if $hooks.product_detail}
					<div class="product-detail-hook fy-product__detail-hook">
						{$hooks.product_detail nofilter}
					</div>
					{/if}
				</div>
			</div>
		</div>
	</div>

	<div class="fy-product__tabs-wrap">
		<ul class="fy-product__tabs nav nav-tabs" id="productTabs" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link active" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">
					{'Description'|translate}
				</button>
			</li>
			{if $productVideoEmbed}
			<li class="nav-item" role="presentation">
				<button class="nav-link" data-bs-toggle="tab" data-bs-target="#video" type="button" role="tab">
					{'Video'|translate}
				</button>
			</li>
			{/if}
			{if $hooks.product_tab}{$hooks.product_tab nofilter}{/if}
		</ul>

		<div class="tab-content fy-product__tab-panels">
			<div class="tab-pane fade show active" id="description" role="tabpanel">
				{if $description}
				<div class="fy-product__description">{$description nofilter}</div>
				{else}
				<p class="fy-product__empty text-muted mb-0">{'No description'|translate}</p>
				{/if}
			</div>
			<div class="tab-pane fade" id="video" role="tabpanel">
				{if $productVideoEmbed}
				<div class="product-video ratio ratio-16x9 fy-product__video">
					<iframe src="{$productVideoEmbed|escape}" title="{$productName|escape} videosu" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>
				</div>
				{/if}
			</div>
			{if $hooks.product_tab_content}{$hooks.product_tab_content nofilter}{/if}
		</div>
	</div>
	{*
	{if $relatedProducts|@count}
	<section class="fy-product__related">
		<div class="fy-product__related-head">
			<h2 class="fy-product__related-title">{$relatedProductsTitle|escape}</h2>
		</div>
		<div class="fy-product__related-grid">
			{foreach $relatedProducts as $rp}
			<a class="fy-product__related-card" href="{$rp.url|escape}" title="{$rp.product_name|escape}">
				<span class="fy-product__related-media">
					<img loading="lazy" src="{$rp.image_url|escape}" alt="{$rp.product_name|escape}" />
				</span>
				<span class="fy-product__related-body">
					<span class="fy-product__related-name">{$rp.product_name|truncate:48:'…'|escape}</span>
					<span class="fy-product__related-price">{$rp.price_formatted|escape}</span>
				</span>
			</a>
			{/foreach}
		</div>
	</section>
	{/if}
	*}
	{if $hooks.product}{$hooks.product nofilter}{/if}
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg">
		<div class="modal-content fy-product__modal">
			<div class="modal-header border-0">
				<h5 class="modal-title" id="imageModalLabel">{$productName|escape}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|translate}"></button>
			</div>
			<div class="modal-body p-0">
				<img id="modal-display" src="{$imageUrl|escape}" class="imageFull fy-product__modal-img" alt="{$productName|escape}" />
			</div>
		</div>
	</div>
</div>
