<div class="sm-cart-overlay" id="cartOverlay" aria-hidden="true"></div>
<div class="sm-cart-modal" id="cartPanel" role="dialog" aria-modal="true" aria-labelledby="cartModalTitle">
	<div class="sm-cart-dialog">
		<div class="sm-cart-dialog__header">
			<h2 class="sm-cart-dialog__title" id="cartModalTitle">{'Cart'|translate}</h2>
			<button type="button" class="sm-cart-dialog__close cartHide" aria-label="{'Close'|translate}">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
			</button>
		</div>

		<div class="sm-cart-dialog__body" id="cartBody">
			{if $cart.empty}
			<div class="sm-cart-empty">
				<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
				<p>{'Cart is empty'|translate}</p>
				<a href="{$domain}" class="sm-cart-btn sm-cart-btn--primary">{'Start shopping'|translate}</a>
			</div>
			{else}
			{foreach $cart.items as $item}
			<div class="sm-cart-item cart-item" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}" data-cart-key="{$item.cart_key|default:''|escape}" data-max-qty="{$item.max_qty|default:$item.stock}" data-qty-step="{$item.qty_step|default:1}">
				<a href="{$item.url}" class="sm-cart-item__thumb cart-item-image">
					<img src="{$item.image_url}" alt="{$item.product_name|escape}">
				</a>
				<div class="sm-cart-item__content cart-item-info">
					<div class="sm-cart-item__top">
						<div class="sm-cart-item__details">
							<a href="{$item.url}" class="sm-cart-item__name cart-item-name">{$item.product_name|escape}</a>
							<button type="button" class="sm-cart-item__remove cart-remove-btn" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}" data-cart-key="{$item.cart_key|default:''|escape}">
								{'Delete'|translate}
							</button>
						</div>
						<div class="sm-cart-item__price cart-item-price">{$item.price_formatted}</div>
					</div>
					<div class="sm-cart-item__bottom">
						<span class="sm-cart-item__line cart-item-total">{'Total'|translate}: {$item.line_total_formatted}</span>
						<div class="sm-cart-item__qty cart-item-actions">
							<button type="button" class="cart-qty-btn" data-action="decrease" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}" data-cart-key="{$item.cart_key|default:''|escape}" aria-label="{'Down'|translate}">-</button>
							<span class="cart-qty-value">{if $item.sale_unit|default:'piece' == 'm2'}{$item.qty|string_format:"%.3f"|regex_replace:'/\.?0+$/':''}{else}{$item.qty}{/if}</span>
							<button type="button" class="cart-qty-btn" data-action="increase" data-id="{$item.id_product}" data-variation="{$item.id_variation|default:0}" data-cart-key="{$item.cart_key|default:''|escape}" aria-label="{'Up'|translate}">+</button>
						</div>
					</div>
				</div>
			</div>
			{/foreach}
			{/if}
		</div>

		<div class="sm-cart-dialog__summary" id="cartSummary"{if $cart.empty} hidden{/if}>
			<div class="sm-cart-summary-row">
				<span>{'Cart Total'|translate}</span>
				<span id="cartSubtotal">{$cart.subtotal_formatted|default:$cart.total_formatted}</span>
			</div>
			<div class="sm-cart-summary-row">
				<span>{'Cargo'|translate}</span>
				<span id="cartShipping">{if $cart.shipping|default:0 > 0}{$cart.shipping_formatted}{else}{'Free'|translate}{/if}</span>
			</div>
			<div class="sm-cart-summary-row sm-cart-summary-row--total">
				<span>{'Total'|translate}</span>
				<strong id="cartTotal">{$cart.grand_total_formatted|default:Tools::displayPrice($cartGrandTotal)}</strong>
			</div>
		</div>

		<div class="sm-cart-dialog__footer" id="cartFooter"{if $cart.empty} hidden{/if}>
			<a href="{$domain}checkout" class="sm-cart-btn sm-cart-btn--primary sm-cart-btn--block">{'Payment Now'|translate}</a>
			<button type="button" class="sm-cart-btn sm-cart-btn--ghost sm-cart-btn--block" id="cartClearBtn">{'Empty the cart'|translate}</button>
		</div>
	</div>
</div>

<div class="sm-cart-overlay" id="cartAddedOverlay" aria-hidden="true"></div>
<div class="sm-cart-modal" id="cartAddedModal" role="dialog" aria-modal="true" hidden>
	<div class="sm-cart-dialog sm-cart-dialog--added">
		<div class="sm-cart-dialog__header">
			<h3 class="sm-cart-dialog__title">
				{'Cart'|translate}
			</h3>
			<button type="button" class="sm-cart-dialog__close" id="cartAddedClose" aria-label="{'Close'|translate}">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
			</button>
		</div>
		<div class="sm-cart-dialog__body sm-cart-added">
			<div class="sm-cart-added__icon text-success" aria-hidden="true">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
			</div>
			<p class="sm-cart-added__lead">{'Great! Product added to your cart.'|translate}</p>
			<div class="sm-cart-added__product">
				<img id="cartAddedImage" src="" alt="">
				<div>
					<div id="cartAddedName" class="sm-cart-added__name"></div>
					<div id="cartAddedPrice" class="sm-cart-added__price"></div>
				</div>
			</div>
			<p class="sm-cart-added__count" id="cartAddedCount"></p>
			<div class="sm-cart-added__actions">
				<a href="{$domain}cart" class="sm-cart-btn sm-cart-btn--primary" id="cartAddedGoCart">{'Go to cart'|translate}</a>
				<button type="button" class="sm-cart-btn sm-cart-btn--ghost" id="cartAddedContinue">{'Continue shopping'|translate}</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
window.cartI18n = {$cartI18nJson nofilter};
</script>
