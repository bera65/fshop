{assign var="cartSubtotal" value=$cart.subtotal|default:$cart.total|default:0}
{assign var="cartShippingAmount" value=$cart.shipping|default:0}
{assign var="cartGrandTotal" value=$cart.grand_total|default:($cartSubtotal + $cartShippingAmount)}

<div class="blue-cart-overlay" id="cartOverlay" aria-hidden="true"></div>
<div class="blue-cart-modal" id="cartPanel" role="dialog" aria-modal="true" aria-labelledby="cartModalTitle">
	<div class="blue-cart-modal__dialog">
		<div class="blue-cart-modal__header">
			<h2 class="blue-cart-modal__title" id="cartModalTitle">{'Cart'|translate}</h2>
			<button type="button" class="blue-cart-modal__close cartHide" aria-label="{'Close'|translate}">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
			</button>
		</div>

		<div class="blue-cart-modal__body" id="cartBody">
			{if $cart.empty}
			<div class="blue-cart-empty">
				<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
				<p>{'Cart is empty'|translate}</p>
				<a href="{$domain}" class="btn btn-sm btn-primary">{'Start shopping'|translate}</a>
			</div>
			{else}
			{foreach $cart.items as $item}
			<div class="blue-cart-item cart-item" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}"{if $item.cart_key} data-cart-key="{$item.cart_key|escape}"{/if} data-max-qty="{$item.max_qty|default:$item.stock}">
				<a href="{$item.url}" class="blue-cart-item__thumb cart-item-image">
					<img src="{$item.image_url}" alt="{$item.product_name|escape}">
				</a>
				<div class="blue-cart-item__content cart-item-info">
					<div class="blue-cart-item__top">
						<div class="blue-cart-item__details">
							<a href="{$item.url}" class="blue-cart-item__name cart-item-name">{$item.product_name|escape}</a>
							{if $item.stock > 0}
							<span class="blue-cart-item__stock">{'In stock'|translate}</span>
							{else}
							<span class="blue-cart-item__stock blue-cart-item__stock--out">{'Out of Stock'|translate}</span>
							{/if}
							<button type="button" class="blue-cart-item__remove cart-remove-btn" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}"{if $item.cart_key} data-cart-key="{$item.cart_key|escape}"{/if}>
								{'Delete'|translate}
							</button>
						</div>
						<div class="blue-cart-item__price cart-item-price">{$item.price_formatted}</div>
					</div>
					<div class="blue-cart-item__bottom">
						<span class="blue-cart-item__line cart-item-total">{'Total'|translate}: {$item.line_total_formatted}</span>
						<div class="blue-cart-item__qty cart-item-actions">
							<button type="button" class="cart-qty-btn" data-action="decrease" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}"{if $item.cart_key} data-cart-key="{$item.cart_key|escape}"{/if} aria-label="{'Down'|translate}">−</button>
							<span class="cart-qty-value">{$item.qty}</span>
							<button type="button" class="cart-qty-btn" data-action="increase" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}"{if $item.cart_key} data-cart-key="{$item.cart_key|escape}"{/if} aria-label="{'Up'|translate}">+</button>
						</div>
					</div>
				</div>
			</div>
			{/foreach}
			{/if}
		</div>

		<div class="blue-cart-modal__summary" id="cartSummary"{if $cart.empty} hidden{/if}>
			<div class="blue-cart-summary-row">
				<span>{'Cart Total'|translate}</span>
				<span id="cartSubtotal">{$cart.subtotal_formatted|default:$cart.total_formatted}</span>
			</div>
			<div class="blue-cart-summary-row">
				<span>{'Cargo'|translate}</span>
				<span id="cartShipping">{if $cart.shipping|default:0 > 0}{$cart.shipping_formatted}{else}{'Free'|translate}{/if}</span>
			</div>
			<div class="blue-cart-summary-row blue-cart-summary-row--total">
				<span>{'Total'|translate}</span>
				<strong id="cartTotal">{$cart.grand_total_formatted|default:Tools::displayPrice($cartGrandTotal)}</strong>
			</div>
		</div>

		<div class="blue-cart-modal__footer" id="cartFooter"{if $cart.empty} hidden{/if}>
			<a href="{$domain}checkout" class="btn btn-primary blue-cart-checkout">{'Payment Now'|translate}</a>
			<button type="button" class="btn btn-outline-secondary" id="cartClearBtn">{'Empty the cart'|translate}</button>
		</div>
	</div>
</div>

<script type="text/javascript">
window.cartI18n = {$cartI18nJson nofilter};
</script>
