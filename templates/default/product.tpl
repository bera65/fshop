<div class="row g-4 lg:g-5">
	<div class="col-12 col-lg-4">
		<div class="product-image-wrapper mb-3">
			<img id="main-display" src="{$imageUrl|escape}" class="img-fluid" alt="{$productName|escape}">
		</div>
		{if $images|@count > 1}
		<div class="product-thumbs d-flex flex-wrap gap-2">
			{foreach $images as $img name=thumbs}
			<img src="{$img.url|escape}" alt="{$productName|escape}" class="thumb-img{if $smarty.foreach.thumbs.first} active{/if}" data-image="{$img.url|escape}">
			{/foreach}
		</div>
		{/if}
	</div>

	<div class="col-12 col-lg-6">
		<div class="mb-3">
			<h1 class="fs-4">{$productName|escape}</h1>
			{if $productLabel}
			<span class="product-label-badge">{$productLabel|escape}</span>
			{/if}
			{if $hooks.product_inf}{$hooks.product_inf nofilter}{/if}
		</div>
		<div class="d-flex align-items-center gap-3 my-4">
			{if $oldPrice > 0}<div class="discount-badge">%{Tools::getDiscount($oldPrice, $price)}</div>{/if}
			<div>
				{if $oldPrice > 0}<div class="old-price">{Tools::displayPrice($oldPrice)}</div>{/if}
				<div class="current-price">{Tools::displayPrice($price)}</div>
			</div>
		</div>
		
		<table class="productTable mb-4">
			<tr>
				<td class="baslik">Havale Fiyatı</td>
				<td>: {Tools::displayPrice($price * ((100-$havale)/100))} (%{$havale} indirim)</td>
			</tr>
			<tr>
				<td class="baslik">Stok Kodu</td>
				<td>: {$stockCode}</td>
			</tr>
			<tr>
				<td class="baslik">Marka</td>
				<td>: <a href="{$brandUrl|escape}" class="d-inline-block">{$brandName|escape}</a></td>
			</tr>
			<tr>
				<td class="baslik">Kargo</td>
				<td>: {if $price >= $freeCargo}Ücretsiz{else}{Tools::displayPrice($cargoPrice)}{/if}</td>
			</tr>
			{if $cargoDay > 0}
			<tr>
				<td class="baslik">Termin</td>
				<td>: {$cargoDay} iş günü içinde kargoda</td>
			</tr>
			{/if}
			<tr>
				<td class="baslik">Stok Durumu</td>
				<td>: 
					{if $inStock}
					<span class="text-success">Stokta var {if $stock > 0 && $stock <= 10}(Son {$stock} adet){/if}</span>
					{else}
					<span class="text-danger">Tükendi</span>
					{/if}
				</td>
			</tr>
		</table>

		<div class="d-flex flex-wrap gap-2 mb-3">
			{if $inStock}
			<div class="qty-picker">
				<button type="button" class="qty-btn" onclick="updateQty(-1)">-</button>
				<input type="text" value="1" id="qty-input" class="qty-input" readonly data-max="{$stock}">
				<button type="button" class="qty-btn" onclick="updateQty(1)">+</button>
			</div>
			<button class="btn btn-dark cart-button addtocart d-flex align-items-center justify-content-center gap-2" data-id="{$product.id_product}">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-basket-icon lucide-shopping-basket"><path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4"/><path d="M4.5 15.5h15"/><path d="m5 11 4-7"/><path d="m9 11 1 9"/></svg>
				Sepete Ekle
			</button>
			{else}
			<button type="button" class="btn btn-secondary" disabled>Stokta Yok</button>
			{/if}
			<button type="button" class="btn btn-primary like-button toggle-favorite{if $isFavorite} active{/if}" data-id="{$product.id_product}" title="Favorilere ekle">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart-icon lucide-heart"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/></svg>
			</button>
		</div>
		{if $shortDescription}
			<p class="product-short-desc text-muted mb-0">{$shortDescription|escape}</p>
		{/if}
		<div id="hook_product_detail">{$hooks.product_detail nofilter}</div>
	</div>

	<div class="col-12 col-lg-2">
		<h6>Ürünün Kampanyaları</h6>
		<div class="panel">
			<b>{$freeCargo}</b> ve üzeri kargo bedava
		</div>
		<div class="panel">
			Havalede <b>%{$havale}</b> indirim
		</div>
		<div class="panel">
			Siparişler en geç <b>{$cargoDay}</b> gün içinde kargoda
		</div>
		<div class="panel">
			Orjinal ürün güvencesi
		</div>
	</div>
</div>
<ul class="nav nav-tabs justify-content-center" id="productTabs">
    <li class="nav-item" role="presentation">
        <button class="nav-link active"
                data-bs-toggle="tab"
                data-bs-target="#description"
                type="button">
            Açıklama
        </button>
    </li>
	{if $productVideoEmbed}
	<li class="nav-item" role="presentation">
        <button class="nav-link"
                data-bs-toggle="tab"
                data-bs-target="#video"
                type="button">
            Video
        </button>
    </li>
	{/if}
	{if $hooks.product_tab}{$hooks.product_tab nofilter}{/if}
</ul>
<div class="tab-content border border-top-0 p-3 bg-white">

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
    {if $hooks.product}{$hooks.product nofilter}{/if}

</div>

