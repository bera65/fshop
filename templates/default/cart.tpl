<h1 class="page-heading">Sepetim</h1>

{if $cart.empty}
	<div class="cart-page-empty text-center py-5">
		<p class="text-muted mb-3">Sepetinizde ürün bulunmuyor.</p>
		<a href="{$domain}" class="btn btn-dark">Alışverişe Başla</a>
	</div>
{else}
	<div class="cart-page-list" id="cartPageList">
		{foreach $cart.items as $item}
		<div class="cart-page-item cart-item" data-id="{$item.id_product}">
			<a href="{$item.url}" class="cart-item-image">
				<img src="{$item.image_url}" alt="{$item.product_name|escape}">
			</a>
			<div class="cart-item-info flex-grow-1">
				<a href="{$item.url}" class="cart-item-name">{$item.product_name}</a>
				<div class="cart-item-price">{$item.price_formatted}</div>
				<div class="cart-item-actions">
					<button type="button" class="cart-qty-btn" data-action="decrease" data-id="{$item.id_product}">-</button>
					<span class="cart-qty-value">{$item.qty}</span>
					<button type="button" class="cart-qty-btn" data-action="increase" data-id="{$item.id_product}">+</button>
					<button type="button" class="cart-remove-btn" data-id="{$item.id_product}">Kaldır</button>
				</div>
			</div>
			<div class="cart-item-total">{$item.line_total_formatted}</div>
		</div>
		{/foreach}
	</div>

	<div class="cart-page-summary mt-4 p-3 bg-light rounded">
		<div class="d-flex justify-content-between align-items-center mb-3">
			<span>Ara Toplam</span>
			<strong id="cartPageTotal">{$cart.total_formatted}</strong>
		</div>
		<button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="cartPageClearBtn">Sepeti Temizle</button>
		<a href="{$domain}cart" class="btn btn-outline-secondary btn-sm w-100 mb-2">Sepete Git</a>
		<a href="{$domain}checkout" class="btn btn-primary w-100">Ödemeye Geç</a>
	</div>
{/if}
