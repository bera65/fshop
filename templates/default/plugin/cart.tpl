<div class="cart" id="cartPanel">
	<div class="cartHeader">
		<span class="fs-6">Sepet <span class="text-muted">(<span id="cartCountLabel">{$cart.count}</span>)</span></span>
		<span class="btn cartHide">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-x-icon lucide-circle-x"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
		</span>
	</div>
	<div class="cartBody" id="cartBody">
		{if $cart.empty}
			<p class="cart-empty text-muted text-center py-4 mb-0">Sepetiniz boş</p>
		{else}
			{foreach $cart.items as $item}
			<div class="cart-item" data-id="{$item.id_product}">
				<a href="{$item.url}" class="cart-item-image">
					<img src="{$item.image_url}" alt="{$item.product_name|escape}">
				</a>
				<div class="cart-item-info">
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
		{/if}
	</div>
	<div class="cartFooter">
		<div class="cart-total d-flex justify-content-between align-items-center mb-2">
			<span class="text-muted">Toplam</span>
			<strong id="cartTotal">{$cart.total_formatted}</strong>
		</div>
		<a href="{$domain}checkout" class="btn btn-primary w-100 mb-2">Ödemeye Geç</a>
	</div>
</div>
