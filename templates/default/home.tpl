{* Avantaj şeridi *}
<div class="perks-strip">
	<div class="row g-2">
		<div class="col-6 col-lg-3">
			<div class="perk">
				<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
				<span><strong>Ücretsiz Kargo</strong><small>{$freeShippingMin|escape} TL ve üzeri</small></span>
			</div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="perk">
				<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
				<span><strong>Güvenli Ödeme</strong><small>256-bit SSL koruması</small></span>
			</div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="perk">
				<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
				<span><strong>Kolay İade</strong><small>14 gün içinde ücretsiz</small></span>
			</div>
		</div>
		<div class="col-6 col-lg-3">
			<div class="perk">
				<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/></svg>
				<span><strong>Müşteri Desteği</strong><small>Hafta içi 09:00 - 18:00</small></span>
			</div>
		</div>
	</div>
</div>

{* Popüler ürünler *}
<section class="section-premium home-category-block">
	<div class="section-head">
		<div>
			<h2 class="section-title">Çok Satanlar</h2>
			<p class="section-subtitle">En beğenilen ürünleri keşfedin</p>
		</div>
		<a href="{$domain}special" class="btn btn-sm btn-outline-dark">Tümünü Gör</a>
	</div>
	{include file='./productList.tpl' products=$featuredProducts}
</section>

{* Kampanya slaytı (2'li) *}
{if $hooks.home_promo_slider}
{$hooks.home_promo_slider nofilter}
{/if}

{* Kategori blokları *}
{foreach $categoryBlocks as $block}
<section class="section-premium home-category-block">
	<div class="section-head">
		<div>
			<h2 class="section-title">{$block.category.category_name|escape}</h2>
			<p class="section-subtitle">{$block.category.category_name|escape} kategorisindeki ürünler</p>
		</div>
		<a href="{$block.url}" class="btn btn-sm btn-outline-dark">Tümünü Gör</a>
	</div>
	{include file='./productList.tpl' products=$block.products}
</section>
{break}
{/foreach}

{* SEO metni *}
<div class="seo-block">
	<h2>{$siteName|escape} ile Online Alışveriş</h2>
	<p>
		{$siteName|escape}, birbirinden özel ürünleri uygun fiyatlarla kapınıza getirir.
		Sezonun öne çıkan koleksiyonlarını inceleyebilir, kampanyalı ürünleri kaçırmadan güvenli ödeme
		seçenekleriyle satın alabilirsiniz. {$freeShippingMin|escape} TL ve üzeri siparişlerde kargo ücretsizdir;
		dilerseniz kapıda ödeme, havale/EFT veya kredi kartı ile ödeme yapabilirsiniz. Üye olarak
		siparişlerinizi kolayca takip edebilir, favori ürünlerinizi listenize ekleyebilirsiniz.
	</p>
</div>
