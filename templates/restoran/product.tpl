<div class="container page-section py-4">
	<div class="product-detail-card bg-white rounded-4 shadow-sm overflow-hidden mb-4">
		<div class="product-detail-card__header px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
			<h1 class="h5 fw-bold mb-0">{'Product Details'|translate}</h1>
			{if $isFavorite !== null}
			<button type="button" class="btn btn-sm btn-outline-secondary toggle-favorite{if $isFavorite} active{/if}" data-id="{$product.id_product}">
				<i class="bi bi-heart{if $isFavorite}-fill text-danger{/if}"></i>
			</button>
			{/if}
		</div>
		<div class="p-4">
			<div class="row g-4">
				<div class="col-md-4">
					<div class="product-detail-image-wrap">
						{if $productLabel}
						<span class="promo-badge">{$productLabel|escape}</span>
						{/if}
						<img src="{$imageUrl|escape}" alt="{$productName|escape}" class="img-fluid rounded-3 w-100" id="main-display">
					</div>
				</div>
				<div class="col-md-8">
					<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
						<div>
							<h2 class="h4 fw-bold mb-1">{$productName|escape}</h2>
							<p class="text-muted small mb-0">{$product.category_name|escape}{if $brandName} · {$brandName|escape}{/if}</p>
						</div>
						<div class="text-end">
							{if $oldPrice > 0}<div class="text-muted text-decoration-line-through small" id="productOldPrice">{Tools::displayPrice($oldPrice)}</div>{/if}
							<div class="h4 fw-bold text-primary mb-0" id="productCurrentPrice" data-base-price="{$price}">{Tools::displayPrice($price)}</div>
						</div>
					</div>
					{if $hooks.product_inf}<div class="mb-3">{$hooks.product_inf nofilter}</div>{/if}
					{include file='./plugin/productConfigurator.tpl'}
				</div>
			</div>
		</div>
	</div>

	<ul class="nav nav-tabs border-0 mb-0" id="productTabs">
		<li class="nav-item">
			<button class="nav-link active" data-bs-toggle="tab" data-bs-target="#description" type="button">{'Description'|translate}</button>
		</li>
		{if $productVideoEmbed}
		<li class="nav-item">
			<button class="nav-link" data-bs-toggle="tab" data-bs-target="#video" type="button">{'Video'|translate}</button>
		</li>
		{/if}
		{if $hooks.product_tab}{$hooks.product_tab nofilter}{/if}
	</ul>
	<div class="tab-content border rounded-bottom bg-white p-4 mb-4">
		<div class="tab-pane fade show active" id="description">
			{if $description}{$description nofilter}{else}<p class="text-muted mb-0">{'No description'|translate}</p>{/if}
		</div>
		{if $productVideoEmbed}
		<div class="tab-pane fade" id="video">
			<div class="ratio ratio-16x9">
				<iframe src="{$productVideoEmbed|escape}" title="{$productName|escape}" allowfullscreen loading="lazy"></iframe>
			</div>
		</div>
		{/if}
		{if $hooks.product_tab_content}{$hooks.product_tab_content nofilter}{/if}
	</div>

	{if $relatedProducts|@count}
	<section class="mb-4">
		<h3 class="h5 fw-bold mb-3">{'Other Products'|translate}</h3>
		<div class="row g-4">
			{foreach from=$relatedProducts item=rp}
			<div class="col-md-6 col-lg-3">
				{include file='./plugin/productCard.tpl' product=$rp}
			</div>
			{/foreach}
		</div>
	</section>
	{/if}
</div>
{if $hooks.product}{$hooks.product nofilter}{/if}
