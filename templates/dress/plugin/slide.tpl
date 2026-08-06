<div class="dress-mobile-hero fshop-slider fshop-slider--hero dress-fallback-hero" style="background-image:url('{$img_dir}slide.png');">
	<div class="dress-mobile-hero__overlay fshop-slider__overlay"></div>
	<div class="dress-mobile-hero__content fshop-slider__content">
		<div class="fshop-slider__text">
			<span class="dress-mobile-hero__kicker fshop-slider__kicker">Büyük İndirim</span>
			<h2 class="dress-mobile-hero__title fshop-slider__title">Yeni Sezonda<br>Şıklığı Keşfedin</h2>
			<p class="dress-mobile-hero__lead fshop-slider__lead">{$siteName|escape} ile seçkin ürünlerde kampanyalı fırsatlar. {$freeShippingMin|escape} TL üzeri kargo bedava.</p>
			<div class="d-flex flex-wrap gap-2">
				{if $menuCategories|@count > 0}
				<a href="{$domain}{$menuCategories[0].category_link}" class="dress-mobile-hero__btn fshop-slider__btn">Keşfet</a>
				{/if}
			</div>
		</div>
	</div>
</div>
