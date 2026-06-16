<div class="hero-karaca">
	<div class="hero-inner">
		<div>
			<span class="hero-kicker">Yeni Sezon</span>
			<h2>Evinize şıklık katan<br>seçkin koleksiyonlar</h2>
			<p class="lead-copy">{$siteName|escape} ile yeni sezon ürünlerini keşfedin. {$freeShippingMin|escape} TL üzeri alışverişlerde kargo bedava.</p>
			<div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start">
				{if $menuCategories|@count > 0}
				<a href="{$domain}{$menuCategories[0].category_link}" class="btn btn-hero">Alışverişe Başla</a>
				{/if}
				<a href="{$domain}special" class="btn btn-hero-ghost">Kampanyalar</a>
			</div>
		</div>
		<div class="hero-visual text-center">
			<img class="img-fluid" src="{$img_dir}slide.png" alt="{$siteName|escape}" loading="lazy">
		</div>
	</div>
</div>
