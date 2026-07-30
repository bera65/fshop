<!-- Hero Section -->
<section class="hero-section">
	<div class="container">
		<div class="row align-items-center">
			<div class="col-lg-6 hero-content">
				<h1>{$siteName|escape} — <span style="color: var(--primary-color);">Lezzet</span> Kapında!</h1>
				<p class="lead text-muted mb-4">En sevdiğiniz yemekleri hızlıca sipariş edin, kapınıza gelsin.</p>

				<form class="location-input mb-3" action="{$domain}search" method="get">
					<div class="d-flex align-items-center flex-grow-1 px-2">
						<i class="bi bi-search text-danger fs-4 me-2"></i>
						<input type="search" name="q" class="form-control border-0 shadow-none" placeholder="{'Search product..'|translate}" value="{$searchQuery|default:''|escape}">
					</div>
					<button type="submit" class="btn btn-dark px-4 rounded-3">{'Search'|translate}</button>
				</form>
				{if $homeCategories|@count}
				<div class="small text-muted mt-2">
					<i class="bi bi-lightning-charge-fill text-warning"></i>
					{'Popular Categories'|translate}:
					{foreach from=$homeCategories item=catItem name=popCat}
						{if !$smarty.foreach.popCat.first}, {/if}
						<a href="{$catItem.url|escape}" class="text-muted text-decoration-none">{$catItem.category.category_name|escape}</a>
					{/foreach}
				</div>
				{/if}
			</div>
			<div class="col-lg-6 d-none d-lg-block text-center">
				<img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=80" alt="{$siteName|escape}" class="img-fluid rounded-circle shadow-lg hero-image">
			</div>
		</div>
	</div>
</section>

{if $hooks.home_slider}
<section class="container mb-4">
	{$hooks.home_slider nofilter}
</section>
{/if}

<!-- Categories -->
{if $homeCategories|@count}
<div class="container mb-5">
	<h4 class="fw-bold mb-4">Mutfak Kategorileri</h4>
	<div class="category-scroll">
		{foreach from=$homeCategories item=catItem}
		<a href="{$catItem.url|escape}" class="category-card text-decoration-none text-dark" style="min-width: 120px;">
			<span class="category-icon">{$catItem.icon}</span>
			<span class="fw-semibold">{$catItem.category.category_name|escape}</span>
		</a>
		{/foreach}
	</div>
</div>
{/if}

<!-- Top rated products -->
{if $topRatedProducts|@count}
<div class="container mb-5">
	<div class="d-flex justify-content-between align-items-center mb-4">
		<h4 class="fw-bold m-0">En Beğenilen Ürünler</h4>
		<a href="{if $homeCategories|@count}{$homeCategories[0].url|escape}{else}{$domain|escape}{/if}" class="text-decoration-none fw-semibold theme-link">{'View All'|translate} <i class="bi bi-chevron-right"></i></a>
	</div>
	<div class="row g-4">
		{foreach from=$topRatedProducts item=p}
		<div class="col-md-6 col-lg-4">
			{include file='./plugin/productCardList.tpl' product=$p}
		</div>
		{/foreach}
	</div>
</div>
{/if}

{if $hooks.home_promo_slider}
<div class="container mb-5">
	{$hooks.home_promo_slider nofilter}
</div>
{/if}

