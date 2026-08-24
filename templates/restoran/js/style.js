$(document).ready(function () {
	if (window.history.replaceState) {
		window.history.replaceState(null, null, window.location.href);
	}
});

function toggleSearch() {
	const searchOverlay = document.getElementById('searchOverlay');
	searchOverlay.classList.toggle('active');
	if (searchOverlay.classList.contains('active')) {
		searchOverlay.querySelector('input').focus();
	}
}

document.addEventListener('DOMContentLoaded', function () {
	if ('IntersectionObserver' in window) {
		const imageObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					const img = entry.target;
					loadImage(img);
					observer.unobserve(img);
				}
			});
		}, {
			rootMargin: '100px',
			threshold: 0.01
		});

		document.querySelectorAll('img.lazy').forEach(img => {
			imageObserver.observe(img);
		});
	} else {
		loadImagesOnScroll();
	}
});

function loadImage(img) {
	const src = img.getAttribute('data-src');
	if (!src) return;

	const newImg = new Image();
	newImg.onload = function () {
		img.src = src;
		img.classList.add('loaded');
		img.classList.remove('lazy');
	};
	newImg.onerror = function () {
		img.src = '/images/placeholder-error.jpg';
		img.classList.add('error');
	};
	newImg.src = src;
}

function scrollContent(id, direction) {
	const container = document.getElementById(id);
	const scrollAmount = 300;

	if (direction === 'left') {
		container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
	} else {
		container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
	}
}

function escapeHtml(text) {
	return $('<div>').text(text).html();
}

function cartT(key) {
	var i18n = window.cartI18n || {};

	return i18n[key] || key;
}

function showCart() {
	$('#cartOverlay, #cartPanel').addClass('show');
	$('body').addClass('cart-modal-open');
}

function hideCart() {
	$('#cartOverlay, #cartPanel').removeClass('show');
	$('body').removeClass('cart-modal-open');
}

function renderCartItems(items) {
	if (!items || !items.length) {
		return (
			'<div class="blue-cart-empty">' +
				'<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>' +
				'<p>' + escapeHtml(cartT('empty')) + '</p>' +
				'<a href="' + domain + '" class="btn btn-sm btn-primary">' + escapeHtml(cartT('startShopping')) + '</a>' +
			'</div>'
		);
	}

	return items.map(function (item) {
		var cartKeyAttr = item.cart_key ? ' data-cart-key="' + escapeHtml(item.cart_key) + '"' : '';
		var stockBadge = item.stock > 0
			? '<span class="blue-cart-item__stock">' + escapeHtml(cartT('inStock')) + '</span>'
			: '<span class="blue-cart-item__stock blue-cart-item__stock--out">' + escapeHtml(cartT('outOfStock')) + '</span>';

		return (
			'<div class="blue-cart-item cart-item" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '"' + cartKeyAttr + ' data-max-qty="' + (item.max_qty || item.stock || 99) + '">' +
				'<a href="' + item.url + '" class="blue-cart-item__thumb cart-item-image">' +
					'<img src="' + item.image_url + '" alt="' + escapeHtml(item.product_name) + '">' +
				'</a>' +
				'<div class="blue-cart-item__content cart-item-info">' +
					'<div class="blue-cart-item__top">' +
						'<div class="blue-cart-item__details">' +
							'<a href="' + item.url + '" class="blue-cart-item__name cart-item-name">' + escapeHtml(item.product_name) + '</a>' +
							stockBadge +
							'<button type="button" class="blue-cart-item__remove cart-remove-btn" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '"' + cartKeyAttr + '>' +
								'<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>' +
								escapeHtml(cartT('remove')) +
							'</button>' +
						'</div>' +
						'<div class="blue-cart-item__price cart-item-price">' + item.price_formatted + '</div>' +
					'</div>' +
					'<div class="blue-cart-item__bottom">' +
						'<span class="blue-cart-item__line cart-item-total">' + escapeHtml(cartT('total')) + ': ' + item.line_total_formatted + '</span>' +
						'<div class="blue-cart-item__qty cart-item-actions">' +
							'<button type="button" class="cart-qty-btn" data-action="decrease" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '"' + cartKeyAttr + ' aria-label="' + escapeHtml(cartT('decrease')) + '">−</button>' +
							'<span class="cart-qty-value">' + item.qty + '</span>' +
							'<button type="button" class="cart-qty-btn" data-action="increase" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '"' + cartKeyAttr + ' aria-label="' + escapeHtml(cartT('increase')) + '">+</button>' +
						'</div>' +
					'</div>' +
				'</div>' +
			'</div>'
		);
	}).join('');
}

function updateCartUI(data) {
	var count = data.count || 0;

	$('#cartBody, #cartPageList').each(function () {
		if ($(this).length) {
			$(this).html(renderCartItems(data.items));
		}
	});

	$('#cartSubtotal').text(data.subtotal_formatted || data.total_formatted || '');
	$('#cartShipping').text(data.shipping_formatted || cartT('free'));
	$('#cartTotal, #cartPageTotal').text(data.grand_total_formatted || data.total_formatted || '');

	$('#cartCount, #cartCountLabel, #items, #mobileCartBadge').text(count);
	if (count > 0) {
		$('#cartCount, #items, #mobileCartBadge').removeClass('d-none').show();
		$('#cartSummary, #cartFooter').prop('hidden', false);
		$('#cartClearBtn, #cartPageClearBtn').show();
	} else {
		$('#cartCount, #items, #mobileCartBadge').addClass('d-none').hide();
		$('#cartSummary, #cartFooter').prop('hidden', true);
		$('#cartClearBtn, #cartPageClearBtn').hide();
		if ($('#cartPageList').length && !$('#cartPageList').children().length) {
			location.reload();
		}
	}
}

function cartRequest(action, idProduct, qty, idVariation, cartKey, options) {
	var payload = {
		action: action,
		id_product: idProduct || 0,
		id_variation: idVariation || 0,
		qty: qty || 1,
		token: csrfToken
	};

	if (cartKey) {
		payload.cart_key = cartKey;
	}

	if (options && Object.keys(options).length) {
		payload.options = JSON.stringify(options);
	}

	return $.ajax({
		url: cartApiUrl,
		method: 'POST',
		dataType: 'json',
		data: payload
	}).done(function (data) {
		if (data.success) {
			updateCartUI(data);
			if (action === 'add') {
				showToast(data.message || '', 'success');
				showCart();
				var quickModal = document.getElementById('productQuickModal');
				if (quickModal) {
					var instance = bootstrap.Modal.getInstance(quickModal);
					if (instance) {
						instance.hide();
					}
				}
			}
		} else {
			showToast(data.message || cartT('genericError'), 'danger');
		}
	}).fail(function () {
		showToast(cartT('connectionError'), 'danger');
	});
}

$(document).on('click', '.addtocart', function () {
	var $btn = $(this);
	var idProduct = $btn.data('id');
	var idVariation = parseInt($btn.data('variation'), 10) || 0;
	var qty = 1;
	var qtyInput = $btn.closest('.product-configurator').find('#qty-input')[0] || document.getElementById('qty-input');
	var configurator = $btn.closest('.product-configurator')[0];
	var options = {};

	if (configurator && typeof configurator.getSelectedOptions === 'function') {
		options = configurator.getSelectedOptions();
	}

	if (qtyInput) {
		qty = parseInt(qtyInput.value, 10) || 1;
	}

	if ($btn.hasClass('requires-variation') && idVariation <= 0) {
		showToast(cartT('selectVariation') || 'Lütfen seçenekleri belirleyin', 'danger');
		return;
	}

	if ($btn.hasClass('requires-options') && Object.keys(options).length === 0) {
		showToast(cartT('selectVariation') || 'Lütfen seçenekleri belirleyin', 'danger');
		return;
	}

	cartRequest('add', idProduct, qty, idVariation, '', options);
});

$(document).on('click', '.cart-qty-btn', function () {
	var $btn = $(this);
	var idProduct = $btn.data('id');
	var idVariation = parseInt($btn.data('variation'), 10) || 0;
	var cartKey = $btn.data('cart-key') || $btn.closest('.cart-item').data('cart-key') || '';
	var action = $btn.data('action');
	var $item = $btn.closest('.cart-item');
	var currentQty = parseInt($item.find('.cart-qty-value').text(), 10) || 1;
	var maxQty = parseInt($item.data('max-qty'), 10) || 99;
	var newQty = action === 'increase' ? currentQty + 1 : currentQty - 1;

	if (action === 'increase' && newQty > maxQty) {
		showToast(cartT('stockLimit') + ' (' + maxQty + ')', 'danger');
		return;
	}

	cartRequest('update', idProduct, newQty, idVariation, cartKey);
});

$(document).on('click', '.cart-remove-btn', function () {
	var $btn = $(this);
	var cartKey = $btn.data('cart-key') || $btn.closest('.cart-item').data('cart-key') || '';
	cartRequest('remove', $btn.data('id'), 1, parseInt($btn.data('variation'), 10) || 0, cartKey);
});

$(document).on('click', '#cartClearBtn, #cartPageClearBtn', function () {
	if (!confirm(cartT('clearConfirm'))) {
		return;
	}

	$.ajax({
		url: cartApiUrl,
		method: 'POST',
		dataType: 'json',
		data: { action: 'clear', token: csrfToken }
	}).done(function (data) {
		updateCartUI(data);
		showToast(data.message || '', 'success');
		if ($('#cartPageList').length) {
			location.reload();
		}
	});
});

$(document).on('click', '.cartHide', function () {
	hideCart();
});

$(document).on('click', '#cartOverlay', function () {
	hideCart();
});

$(document).on('click', '.cart-open-btn', function (e) {
	e.preventDefault();
	showCart();
});

$(document).on('keydown', function (e) {
	if (e.key === 'Escape' && $('#cartPanel').hasClass('show')) {
		hideCart();
	}
});

function showToast(message, cl) {
	if (!message) {
		return;
	}

	cl = cl || 'success';
	$('#tostAlert').removeClass('danger success');
	$('#tostAlert').addClass(cl);
	var toastEl = document.getElementById('tostAlert');
	$(toastEl).find('.toast-body').html(message);
	var toast = new bootstrap.Toast(toastEl);
	toast.show();
}
$('.priceAllertButton').click( function(){
	var idProduct 	= $(this).data('id');
	var price 		= $(this).data('price');
	
	$('#selectedProductId').val(idProduct);
	$('#selectedPrice').val(price);
})