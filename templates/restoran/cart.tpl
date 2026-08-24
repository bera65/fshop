<div class="prime-container prime-page">

	<h1 class="prime-page__title">{'My Cart'|translate}</h1>



	{if $cart.empty}

	<div class="prime-empty">

		<i class="fa-solid fa-cart-shopping"></i>

		<p>{'Cart is empty'|translate}</p>

		<a href="{$domain}" class="prime-btn prime-btn--primary">{'Start shopping'|translate}</a>

	</div>

	{else}

	<div class="prime-cart-page">

		<div class="prime-cart-page__list" id="cartPageList">

			{foreach $cart.items as $item}

			<div class="prime-cart-page__item cart-item" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}"{if $item.cart_key} data-cart-key="{$item.cart_key|escape}"{/if} data-max-qty="{$item.max_qty|default:$item.stock|default:99}">

				<a href="{$item.url}" class="prime-cart-page__thumb cart-item-image">

					<img src="{$item.image_url}" alt="{$item.product_name|escape}">

				</a>

				<div class="prime-cart-page__info cart-item-info">

					<a href="{$item.url}" class="prime-cart-page__name cart-item-name">{$item.product_name|escape}</a>

					<div class="prime-cart-page__price cart-item-price">{$item.price_formatted}</div>

					<div class="prime-cart-page__qty cart-item-actions">

						<button type="button" class="cart-qty-btn" data-action="decrease" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}"{if $item.cart_key} data-cart-key="{$item.cart_key|escape}"{/if}>−</button>

						<span class="cart-qty-value">{$item.qty}</span>

						<button type="button" class="cart-qty-btn" data-action="increase" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}"{if $item.cart_key} data-cart-key="{$item.cart_key|escape}"{/if}>+</button>

						<button type="button" class="prime-cart-page__remove cart-remove-btn" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}"{if $item.cart_key} data-cart-key="{$item.cart_key|escape}"{/if}>{'Delete'|translate}</button>

					</div>

				</div>

				<div class="prime-cart-page__line cart-item-total">{$item.line_total_formatted}</div>

			</div>

			{/foreach}

		</div>



		<aside class="prime-cart-page__summary">

			<div class="prime-cart-page__total cart-total">

				<span>{'Cart Total'|translate}</span>

				<strong id="cartPageTotal">{$cart.total_formatted}</strong>

			</div>

			{if $freeShippingMin > 0}

			<p class="prime-cart-page__ship"><i class="fa-solid fa-truck-fast"></i> {$freeShippingMin|escape} TL {'Free shipping over'|translate}</p>

			{/if}

			<button type="button" class="prime-btn prime-btn--outline prime-btn--sm" id="cartPageClearBtn">{'Empty the cart'|translate}</button>

			<a href="{$domain}checkout" class="prime-btn prime-btn--accent w-100">{'Payment Now'|translate}</a>

		</aside>

	</div>

	{/if}

</div>

