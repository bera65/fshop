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
	hideCartAdded();
	$('#cartOverlay, #cartPanel').addClass('show');
	$('#cartOverlay').attr('aria-hidden', 'false');
	$('body').addClass('cart-modal-open');
}

function hideCart() {
	$('#cartOverlay, #cartPanel').removeClass('show');
	$('#cartOverlay').attr('aria-hidden', 'true');
	if (!$('#cartAddedModal').hasClass('show')) {
		$('body').removeClass('cart-modal-open');
	}
}

function showCartAdded(data, idProduct, idVariation) {
	hideCart();

	var items = data.items || [];
	var item = null;
	var i;
	var pid = parseInt(idProduct, 10) || 0;
	var vid = parseInt(idVariation, 10) || 0;

	for (i = 0; i < items.length; i++) {
		if (parseInt(items[i].id_product, 10) === pid && (parseInt(items[i].id_variation, 10) || 0) === vid) {
			item = items[i];
			break;
		}
	}
	if (!item && items.length) {
		item = items[items.length - 1];
	}

	var count = data.count || 0;
	var countTpl = cartT('cartItemsCount') || 'There are %d items in your cart.';
	var label = cartT('productLabel') || 'Product';
	var name = item ? item.product_name : '';
	var price = item ? (item.price_formatted || '') : '';
	var image = item ? (item.image_url || '') : '';

	$('#cartAddedTitle').text('');
	$('.sm-cart-added__lead').text(cartT('productAddedLead'));
	$('#cartAddedName').text(label + ': ' + name);
	$('#cartAddedPrice').text(price);
	$('#cartAddedImage').attr('src', image).attr('alt', name);
	$('#cartAddedCount').text(countTpl.replace('%d', String(count)));
	$('#cartAddedGoCart').text(cartT('goToCart'));
	$('#cartAddedContinue').text(cartT('continueShopping'));

	$('#cartAddedModal').prop('hidden', false).addClass('show');
	$('#cartAddedOverlay').addClass('show').attr('aria-hidden', 'false');
	$('body').addClass('cart-modal-open');
}

function hideCartAdded() {
	$('#cartAddedModal').removeClass('show').prop('hidden', true);
	$('#cartAddedOverlay').removeClass('show').attr('aria-hidden', 'true');
	if (!$('#cartPanel').hasClass('show')) {
		$('body').removeClass('cart-modal-open');
	}
}

function renderCartItems(items) {
	if (!items || !items.length) {
		return (
			'<div class="sm-cart-empty">' +
				'<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>' +
				'<p>' + escapeHtml(cartT('empty')) + '</p>' +
				'<a href="' + domain + '" class="sm-cart-btn sm-cart-btn--primary">' + escapeHtml(cartT('startShopping')) + '</a>' +
			'</div>'
		);
	}

	return items.map(function (item) {
		var cartKey = item.cart_key || '';
		var qtyStep = item.qty_step || 1;
		var qtyText = (item.sale_unit === 'm2')
			? (Math.round(parseFloat(item.qty) * 1000) / 1000).toString()
			: item.qty;
		return (
			'<div class="sm-cart-item cart-item" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-cart-key="' + escapeHtml(cartKey) + '" data-max-qty="' + (item.max_qty || item.stock || 99) + '" data-qty-step="' + qtyStep + '">' +
				'<a href="' + item.url + '" class="sm-cart-item__thumb cart-item-image">' +
					'<img src="' + item.image_url + '" alt="' + escapeHtml(item.product_name) + '">' +
				'</a>' +
				'<div class="sm-cart-item__content cart-item-info">' +
					'<div class="sm-cart-item__top">' +
						'<div class="sm-cart-item__details">' +
							'<a href="' + item.url + '" class="sm-cart-item__name cart-item-name">' + escapeHtml(item.product_name) + '</a>' +
							'<button type="button" class="sm-cart-item__remove cart-remove-btn" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-cart-key="' + escapeHtml(cartKey) + '">' +
								escapeHtml(cartT('remove')) +
							'</button>' +
						'</div>' +
						'<div class="sm-cart-item__price cart-item-price">' + item.price_formatted + '</div>' +
					'</div>' +
					'<div class="sm-cart-item__bottom">' +
						'<span class="sm-cart-item__line cart-item-total">' + escapeHtml(cartT('total')) + ': ' + item.line_total_formatted + '</span>' +
						'<div class="sm-cart-item__qty cart-item-actions">' +
							'<button type="button" class="cart-qty-btn" data-action="decrease" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-cart-key="' + escapeHtml(cartKey) + '" aria-label="' + escapeHtml(cartT('decrease')) + '">-</button>' +
							'<span class="cart-qty-value">' + qtyText + '</span>' +
							'<button type="button" class="cart-qty-btn" data-action="increase" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-cart-key="' + escapeHtml(cartKey) + '" aria-label="' + escapeHtml(cartT('increase')) + '">+</button>' +
						'</div>' +
					'</div>' +
				'</div>' +
			'</div>'
		);
	}).join('');
}

function renderCartPageItems(items) {
	if (!items || !items.length) {
		return '';
	}

	return items.map(function (item) {
		var variationMeta = item.variation_label
			? '<p class="prime-cart-card__meta">' + escapeHtml(item.variation_label) + '</p>'
			: '';
		var cartKey = item.cart_key || '';
		var qtyStep = item.qty_step || 1;
		var qtyText = (item.sale_unit === 'm2')
			? (Math.round(parseFloat(item.qty) * 1000) / 1000).toString()
			: item.qty;

		return (
			'<article class="prime-cart-card cart-item" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-cart-key="' + escapeHtml(cartKey) + '" data-max-qty="' + (item.max_qty || item.stock || 99) + '" data-qty-step="' + qtyStep + '">' +
				'<a href="' + item.url + '" class="prime-cart-card__thumb cart-item-image">' +
					'<img src="' + item.image_url + '" alt="' + escapeHtml(item.product_name) + '">' +
				'</a>' +
				'<div class="prime-cart-card__body cart-item-info">' +
					'<a href="' + item.url + '" class="prime-cart-card__name cart-item-name">' + escapeHtml(item.product_name) + '</a>' +
					variationMeta +
					'<div class="prime-cart-card__unit cart-item-price">' + item.price_formatted + '</div>' +
					'<div class="prime-cart-card__actions cart-item-actions">' +
						'<div class="prime-cart-qty">' +
							'<button type="button" class="cart-qty-btn" data-action="decrease" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-cart-key="' + escapeHtml(cartKey) + '" aria-label="' + escapeHtml(cartT('decrease')) + '">-</button>' +
							'<span class="cart-qty-value">' + qtyText + '</span>' +
							'<button type="button" class="cart-qty-btn" data-action="increase" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-cart-key="' + escapeHtml(cartKey) + '" aria-label="' + escapeHtml(cartT('increase')) + '">+</button>' +
						'</div>' +
						'<button type="button" class="prime-cart-card__remove cart-remove-btn" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-cart-key="' + escapeHtml(cartKey) + '">' + escapeHtml(cartT('remove')) + '</button>' +
					'</div>' +
				'</div>' +
				'<div class="prime-cart-card__total cart-item-total">' +
					'<span class="prime-cart-card__total-label">' + escapeHtml(cartT('total')) + '</span>' +
					'<strong>' + item.line_total_formatted + '</strong>' +
				'</div>' +
			'</article>'
		);
	}).join('');
}

function updateCartUI(data) {
	var count = data.count || 0;

	if ($('#cartPageList').length) {
		if (!data.items || !data.items.length) {
			location.reload();
			return;
		}
		$('#cartPageList').html(renderCartPageItems(data.items));
	}

	if ($('#cartBody').length) {
		$('#cartBody').html(renderCartItems(data.items));
	}

	$('#cartSubtotal, #cartPageSubtotal').text(data.subtotal_formatted || data.total_formatted || '');
	var $promoLines = $('#cartPromotionLines');
	if ($promoLines.length) {
		if (data.promotion_lines && data.promotion_lines.length) {
			$promoLines.html(data.promotion_lines.map(function (line) {
				return '<div class="prime-cart-summary__row prime-cart-summary__row--promo"><span>' +
					$('<div>').text(line.name || '').html() +
					'</span><span>-' + (line.discount_formatted || '') + '</span></div>';
			}).join(''));
		} else if ((data.promotion_discount || 0) > 0) {
			$promoLines.html(
				'<div class="prime-cart-summary__row prime-cart-summary__row--promo"><span>' +
				$('<div>').text(data.promotion_name || '').html() +
				'</span><span>-' + (data.promotion_discount_formatted || '') + '</span></div>'
			);
		} else {
			$promoLines.empty();
		}
	} else if (data.promotion_discount > 0) {
		$('.prime-cart-summary__row--promo').removeClass('d-none');
		$('#cartPagePromotionName').text(data.promotion_name || '');
		$('#cartPagePromotion').text('-' + (data.promotion_discount_formatted || ''));
	} else {
		$('.prime-cart-summary__row--promo').addClass('d-none');
	}
	$('#cartShipping, #cartPageShipping').text(data.shipping_formatted || cartT('free'));
	$('#cartTotal, #cartPageTotal').text(data.grand_total_formatted || data.total_formatted || '');

	$('#cartCount, #cartCountLabel, #items, #mobileCartBadge, .sm-cart-count-badge').text(count);
	if (count > 0) {
		$('#cartCount, #items, #mobileCartBadge, .sm-cart-count-badge').removeClass('d-none').show();
		$('#cartSummary, #cartFooter').prop('hidden', false);
		$('#cartClearBtn, #cartPageClearBtn').show();
	} else {
		$('#cartCount, #items, #mobileCartBadge, .sm-cart-count-badge').addClass('d-none').hide();
		$('#cartSummary, #cartFooter').prop('hidden', true);
		$('#cartClearBtn, #cartPageClearBtn').hide();
		if ($('#cartPageList').length && !$('#cartPageList').children().length) {
			location.reload();
		}
	}
}

function cartRequest(action, idProduct, qty, idVariation, extra) {
	extra = extra || {};
	var payload = {
		action: action,
		id_product: idProduct || 0,
		id_variation: idVariation || 0,
		qty: qty || 1,
		token: csrfToken
	};

	if (extra.cart_key) payload.cart_key = extra.cart_key;
	if (extra.width) payload.width = extra.width;
	if (extra.length) payload.length = extra.length;
	if (extra.options && typeof extra.options === 'object') {
		payload.options = JSON.stringify(extra.options);
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
				if (extra.redirectToCart) {
					window.location.href = (typeof domain === 'string' ? domain : '/') + 'cart';
					return;
				}
				showCartAdded(data, idProduct, idVariation);
			}
		} else {
			showToast(data.message || cartT('genericError'), 'danger');
		}
	}).fail(function () {
		showToast(cartT('connectionError'), 'danger');
	});
}

function buildAddToCartPayload($btn) {
	var idProduct = $btn.data('id');
	var idVariation = parseInt($btn.data('variation'), 10) || 0;
	var qty = 1;
	var qtyInput = document.getElementById('qty-input');
	var extra = {};
	var saleUnit = qtyInput ? (qtyInput.dataset.saleUnit || 'piece') : 'piece';

	if ($btn.hasClass('requires-measure') || saleUnit === 'm2') {
		var widthEl = document.getElementById('m2-width');
		var lengthEl = document.getElementById('m2-length');
		var width = widthEl ? parseFloat(String(widthEl.value || '').replace(',', '.')) || 0 : 0;
		var length = lengthEl ? parseFloat(String(lengthEl.value || '').replace(',', '.')) || 0 : 0;

		if (width <= 0 || length <= 0) {
			showToast(cartT('enterMeasure') || 'LÃ¼tfen en ve boy girin', 'danger');
			return null;
		}

		extra.width = width;
		extra.length = length;
		qty = width * length;
	} else if (qtyInput) {
		qty = parseFloat(String(qtyInput.value || '1').replace(',', '.')) || 1;
	}

	if ($btn.hasClass('requires-variation') && idVariation <= 0) {
		showToast(cartT('selectVariation') || 'Lütfen seçenekleri belirleyin', 'danger');
		return null;
	}

	var options = {};
	var optRoot = document.getElementById('productOptions');
	if (optRoot) {
		var groups = optRoot.querySelectorAll('.product-option-group');
		for (var i = 0; i < groups.length; i++) {
			var groupEl = groups[i];
			var inp = groupEl.querySelector('.product-option-input');
			var groupName = (inp && inp.getAttribute('data-group')) || groupEl.getAttribute('data-group-name') || '';
			var value = inp ? String(inp.value || '').trim() : '';
			if (!groupName) continue;
			if (groupEl.getAttribute('data-required') === '1' && value === '') {
				var labelEl = groupEl.querySelector('.product-variation-name');
				var label = labelEl ? labelEl.textContent.trim() : groupName;
				showToast((label || groupName) + ' seçin', 'danger');
				return null;
			}
			if (value !== '') {
				options[groupName] = value;
			}
		}
	}

	if (Object.keys(options).length) {
		extra.options = options;
	}

	return {
		idProduct: idProduct,
		idVariation: idVariation,
		qty: qty,
		extra: extra
	};
}

$(document).on('click', '.addtocart', function () {
	var payload = buildAddToCartPayload($(this));
	if (!payload) return;
	cartRequest('add', payload.idProduct, payload.qty, payload.idVariation, payload.extra);
});

$(document).on('click', '.buynow', function () {
	var payload = buildAddToCartPayload($(this));
	if (!payload) return;
	payload.extra.redirectToCart = true;
	cartRequest('add', payload.idProduct, payload.qty, payload.idVariation, payload.extra);
});

$(document).on('click', '.cart-qty-btn', function () {
	var idProduct = $(this).data('id');
	var idVariation = parseInt($(this).data('variation'), 10) || 0;
	var cartKey = $(this).data('cart-key') || $(this).closest('.cart-item').data('cart-key') || '';
	var action = $(this).data('action');
	var $item = $(this).closest('.cart-item');
	var step = parseFloat($item.data('qty-step')) || 1;
	var currentQty = parseFloat(String($item.find('.cart-qty-value').text() || '1').replace(',', '.')) || 1;
	var maxQty = parseFloat($item.data('max-qty')) || 99;
	var newQty = action === 'increase' ? currentQty + step : currentQty - step;

	if (action === 'increase' && newQty > maxQty + 0.0001) {
		showToast(cartT('stockLimit') + ' (' + maxQty + ')', 'danger');
		return;
	}

	cartRequest('update', idProduct, newQty, idVariation, { cart_key: cartKey });
});

$(document).on('click', '.cart-remove-btn', function () {
	var cartKey = $(this).data('cart-key') || $(this).closest('.cart-item').data('cart-key') || '';
	cartRequest('remove', $(this).data('id'), 1, parseInt($(this).data('variation'), 10) || 0, { cart_key: cartKey });
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

$(document).on('click', '.js-show-cart', function (e) {
	e.preventDefault();
	showCart();
});

$(document).on('click', '.cartHide', function () {
	hideCart();
});

$(document).on('click', '#cartOverlay', function () {
	hideCart();
});

$(document).on('click', '#cartAddedContinue, #cartAddedClose, #cartAddedOverlay', function () {
	hideCartAdded();
});

$(document).on('click', '#cartAddedGoCart', function () {
	hideCartAdded();
});

$(document).on('keydown', function (e) {
	if (e.key !== 'Escape') {
		return;
	}
	if ($('#cartAddedModal').hasClass('show')) {
		hideCartAdded();
		return;
	}
	if ($('#cartPanel').hasClass('show')) {
		hideCart();
	}
});

function showToast(message, cl) {
	if (!message) {
		return;
	}

	var type = (cl === 'error' || cl === 'danger') ? 'danger'
		: (cl === 'warning' ? 'warning'
		: (cl === 'info' ? 'info' : 'success'));

	var $toast = $('#tostAlert');
	if (!$toast.length) {
		window.alert(message);
		return;
	}

	var title = $toast.attr('data-title-' + type) || (type === 'danger' ? 'Error!' : 'Success!');
	$toast
		.removeClass('is-success is-danger is-warning is-info danger success hide')
		.addClass('is-' + type + ' show')
		.css({ 'z-index': 20000, display: 'flex', opacity: 1 });
	$toast.find('.sm-toast__title').text(title);
	$toast.find('.sm-toast__message, .toast-body').html(message);

	try {
		var toast = bootstrap.Toast.getOrCreateInstance($toast[0], { delay: 3500 });
		toast.show();
	} catch (err) {
		window.alert(message);
	}
}
window.showToast = showToast;

$(document).on('click', '.toggle-favorite', function (e) {
	e.preventDefault();
	e.stopPropagation();

	var $btn = $(this);
	var idProduct = parseInt($btn.data('id'), 10) || 0;
	if (!idProduct || typeof favoriteApiUrl === 'undefined') {
		return;
	}

	if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
		showToast('Favorilere eklemek için giriş yapın', 'danger');
		if (typeof domain === 'string') {
			window.location.href = domain + 'login';
		}
		return;
	}

	$.ajax({
		url: favoriteApiUrl,
		method: 'POST',
		dataType: 'json',
		data: {
			action: 'toggle',
			id_product: idProduct,
			token: csrfToken
		}
	}).done(function (data) {
		if (!data || !data.success) {
			if (data && data.login_required && typeof domain === 'string') {
				showToast(data.message || 'Favorilere eklemek için giriş yapın', 'danger');
				window.location.href = domain + 'login';
				return;
			}
			showToast((data && data.message) || (typeof cartT === 'function' ? cartT('genericError') : 'Hata'), 'danger');
			return;
		}

		var active = !!data.is_favorite;
		$('.toggle-favorite[data-id="' + idProduct + '"]').toggleClass('active', active);

		$('.toggle-favorite[data-id="' + idProduct + '"]').each(function () {
			var $badge = $(this).find('.product-fav-count');
			if (!$badge.length) {
				return;
			}

			var count = parseInt($badge.attr('data-count'), 10) || 0;
			count = active ? count + 1 : Math.max(0, count - 1);
			$badge.attr('data-count', count);

			if (count > 0) {
				$badge.text(count + '+').removeClass('d-none');
			} else {
				$badge.text('').addClass('d-none');
			}
		});

		showToast(data.message || (active ? 'Favorilere eklendi' : 'Favorilerden kaldırıldı'), 'success');
	}).fail(function () {
		showToast(typeof cartT === 'function' ? cartT('connectionError') : 'Bağlantı hatası', 'danger');
	});
});

$('.priceAllertButton').click(function () {
	var idProduct = $(this).data('id');
	var price = $(this).data('price');
	$('#selectedProductId').val(idProduct);
	$('#selectedPrice').val(price);
});

$(document).on('click', '.priceAllertButton', function () {
	var idProduct = $(this).data('id');
	var price = $(this).data('price');
	$('#selectedProductId').val(idProduct);
	$('#selectedPrice').val(price);
});

// Theme fallback when alert-price module JS is missing/cached
$(document).on('submit', '#alertPrice', function (e) {
	if (window.__fshopAlertPriceBound) {
		return;
	}

	e.preventDefault();

	var $form = $(this);
	var url = $form.attr('data-api-url') || $form.data('api-url');
	var productId = $form.find('[name="selectedProductId"]').val();
	var $messageBox = $('#alertPriceMessage');
	var $submitBtn = $form.find('button[type="submit"]');
	var modalEl = document.getElementById('priceModal');
	var email = $.trim($form.find('[name="userEmail"]').val() || '');
	var price = $form.find('[name="price"]').val();
	var token = (window.csrfToken || (typeof csrfToken !== 'undefined' ? csrfToken : '') || $form.find('[name="token"]').val() || '');

	function notify(message, ok) {
		if (typeof window.showToast === 'function') {
			window.showToast(message, ok ? 'success' : 'danger');
		} else if (typeof showToast === 'function') {
			showToast(message, ok ? 'success' : 'danger');
		} else {
			window.alert(message);
		}

		if ($messageBox.length) {
			$messageBox
				.removeClass('d-none alert-success alert-danger')
				.addClass(ok ? 'alert alert-success' : 'alert alert-danger')
				.text(message)
				.show();
		}
	}

	if (!url || !token) {
		notify('Sayfayı yenileyip tekrar deneyin.', false);
		return;
	}

	$submitBtn.prop('disabled', true);

	$.ajax({
		url: url,
		method: 'POST',
		dataType: 'json',
		headers: {
			'X-CSRF-TOKEN': token,
			'X-Requested-With': 'XMLHttpRequest'
		},
		data: {
			token: token,
			idProduct: productId,
			email: email,
			price: price
		}
	}).done(function (data) {
		var ok = !!(data && data.success);
		notify((data && data.message) || (ok ? 'Talebiniz alındı.' : 'İşlem başarısız.'), ok);
		if (ok) {
			$form[0].reset();
			if (modalEl && typeof bootstrap !== 'undefined') {
				setTimeout(function () {
					var modal = bootstrap.Modal.getInstance(modalEl);
					if (modal) modal.hide();
				}, 1600);
			}
		}
	}).fail(function (xhr) {
		var message = (xhr.responseJSON && xhr.responseJSON.message)
			? xhr.responseJSON.message
			: (xhr.status === 403 ? 'Gecersiz istek. Sayfayı yenileyip tekrar deneyin.' : 'Bir hata oluştu.');
		notify(message, false);
	}).always(function () {
		$submitBtn.prop('disabled', false);
	});
});