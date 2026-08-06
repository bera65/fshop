<div class="prime-container prime-page prime-cart">



	<div class="prime-cart__head">

		<h1 class="prime-page__title mb-0">{'My Cart'|translate}</h1>

		{if !$cart.empty}

		<p class="prime-cart__count text-muted mb-0">{$cart.count} {'products'|translate}</p>

		{/if}

	</div>



	{if $cart.empty}



	<div class="prime-cart-empty">

		<div class="prime-cart-empty__icon" aria-hidden="true">

			<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>

		</div>

		<h2 class="prime-cart-empty__title">{'Cart is empty'|translate}</h2>

		<p class="prime-cart-empty__text">Alışverişe başlayarak sepetinizi doldurabilirsiniz.</p>

		<a href="{$domain}" class="prime-btn prime-btn--primary">{'Start shopping'|translate}</a>

	</div>

	<section class="prime-cart-section">
		<div class="prime-cart-section__head">
			<h2 class="prime-cart-section__title">Beğendiğim Ürünler</h2>
			{if $isLoggedIn && $favoriteProducts|@count > 0}
			<a href="{$domain}favorites" class="prime-cart-section__link">Tümünü gör</a>
			{/if}
		</div>
		{if !$isLoggedIn}
		<div class="prime-cart-note">
			<p class="mb-2">Favori ürünlerinizi görmek için giriş yapın.</p>
			<a href="{$domain}login" class="prime-btn prime-btn--outline prime-btn--sm">Giriş Yap</a>
		</div>
		{elseif $favoriteProducts|@count > 0}
		{include file='./productList.tpl' products=$favoriteProducts id='cartFavoritesEmpty'}
		{else}
		<div class="prime-cart-note">
			<p class="mb-0">Henüz favori ürününüz yok.</p>
		</div>
		{/if}
	</section>



	{else}



	<div class="prime-cart-layout">

		<div class="prime-cart-layout__main">

			<section class="prime-cart-box" aria-label="Sepet ürünleri">

				<div class="prime-cart-box__head">

					<h2 class="prime-cart-box__title">Sepetteki Ürünler</h2>

				</div>

				<div class="prime-cart-list" id="cartPageList">

					{foreach $cart.items as $item}

					<article class="prime-cart-card cart-item" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}" data-max-qty="{$item.max_qty|default:$item.stock|default:99}">

						<a href="{$item.url}" class="prime-cart-card__thumb cart-item-image">

							<img src="{$item.image_url}" alt="{$item.product_name|escape}">

						</a>

						<div class="prime-cart-card__body cart-item-info">

							<a href="{$item.url}" class="prime-cart-card__name cart-item-name">{$item.product_name|escape}</a>

							{if $item.variation_label}

							<p class="prime-cart-card__meta">{$item.variation_label|escape}</p>

							{/if}

							<div class="prime-cart-card__unit cart-item-price">{$item.price_formatted}</div>

							<div class="prime-cart-card__actions cart-item-actions">

								<div class="prime-cart-qty">

									<button type="button" class="cart-qty-btn" data-action="decrease" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}" aria-label="Azalt">−</button>

									<span class="cart-qty-value">{$item.qty}</span>

									<button type="button" class="cart-qty-btn" data-action="increase" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}" aria-label="Artır">+</button>

								</div>

								<button type="button" class="prime-cart-card__remove cart-remove-btn" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}">{'Delete'|translate}</button>

							</div>

						</div>

						<div class="prime-cart-card__total cart-item-total">

							<span class="prime-cart-card__total-label">Toplam</span>

							<strong>{$item.line_total_formatted}</strong>

						</div>

					</article>

					{/foreach}

				</div>

			</section>



			{if $recommendedProducts|@count > 0}

			<section class="prime-cart-section">

				<div class="prime-cart-section__head">

					<h2 class="prime-cart-section__title">Önerilen Ürünler</h2>

					<p class="prime-cart-section__desc">Sepetinizdeki ürünlerin kategorilerinden seçtiklerimiz</p>

				</div>

				{include file='./productList.tpl' products=$recommendedProducts id='cartRecommended'}

			</section>

			{/if}



			<section class="prime-cart-section">

				<div class="prime-cart-section__head">

					<h2 class="prime-cart-section__title">Beğendiğim Ürünler</h2>

					{if $isLoggedIn && $favoriteProducts|@count > 0}

					<a href="{$domain}favorites" class="prime-cart-section__link">Tümünü gör</a>

					{/if}

				</div>

				{if !$isLoggedIn}

				<div class="prime-cart-note">

					<p class="mb-2">Favori ürünlerinizi görmek için giriş yapın.</p>

					<a href="{$domain}login" class="prime-btn prime-btn--outline prime-btn--sm">Giriş Yap</a>

				</div>

				{elseif $favoriteProducts|@count > 0}

				{include file='./productList.tpl' products=$favoriteProducts id='cartFavorites'}

				{else}

				<div class="prime-cart-note">

					<p class="mb-2">Henüz favori ürününüz yok.</p>

					<a href="{$domain}" class="prime-btn prime-btn--outline prime-btn--sm">Alışverişe Başla</a>

				</div>

				{/if}

			</section>

		</div>



		<aside class="prime-cart-summary" aria-label="Sipariş özeti">

			<div class="prime-cart-summary__card">

				<h2 class="prime-cart-summary__title">Sipariş Özeti</h2>



				<div class="prime-cart-summary__row">

					<span>{'Cart Total'|translate}</span>

					<span id="cartPageSubtotal">{$cart.subtotal_formatted|default:$cart.total_formatted}</span>

				</div>

				{if $cart.promotion_lines|@count}
				<div id="cartPromotionLines">
					{foreach $cart.promotion_lines as $promoLine}
					<div class="prime-cart-summary__row prime-cart-summary__row--promo">
						<span>{$promoLine.name|escape}</span>
						<span>-{$promoLine.discount_formatted}</span>
					</div>
					{/foreach}
				</div>
				{elseif $cart.has_promotion}
				<div id="cartPromotionLines">
				<div class="prime-cart-summary__row prime-cart-summary__row--promo">
					<span>{$cart.promotion_name|escape}</span>
					<span id="cartPagePromotion">-{$cart.promotion_discount_formatted}</span>
				</div>
				</div>
				{else}
				<div id="cartPromotionLines"></div>
				<div class="prime-cart-summary__row prime-cart-summary__row--promo d-none">
					<span id="cartPagePromotionName"></span>
					<span id="cartPagePromotion"></span>
				</div>
				{/if}

				<div class="prime-cart-summary__row">

					<span>{'Cargo'|translate}</span>

					<span id="cartPageShipping">{if $cart.shipping|default:0 > 0}{$cart.shipping_formatted}{else}{'Free'|translate}{/if}</span>

				</div>

				<div class="prime-cart-summary__row prime-cart-summary__row--total cart-total">

					<span>{'Total'|translate}</span>

					<strong id="cartPageTotal">{$cart.grand_total_formatted|default:$cart.total_formatted}</strong>

				</div>



				{if $freeShippingMin > 0}

				<div class="prime-cart-summary__ship">

					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>

					<span>{$freeShippingMin|escape} TL üzeri {'Free shipping over'|translate|lower}</span>

				</div>

				{/if}



				<a href="{$domain}checkout" class="prime-btn prime-btn--accent w-100 prime-cart-summary__checkout">Ödemeye Geç</a>

				<button type="button" class="prime-btn prime-btn--outline w-100 mt-2" id="cartPageClearBtn">{'Empty the cart'|translate}</button>

				<a href="{$domain}" class="prime-cart-summary__continue">Alışverişe devam et</a>

			</div>

		</aside>

	</div>



	{/if}



</div>

