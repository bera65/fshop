(function ($) {

	'use strict';



	var modalEl = document.getElementById('productQuickModal');

	var modalInstance = null;



	function escapeHtml(value) {

		return String(value)

			.replace(/&/g, '&amp;')

			.replace(/</g, '&lt;')

			.replace(/>/g, '&gt;')

			.replace(/"/g, '&quot;');

	}



	function buildVariationHtml(product) {

		if (!product.has_variations || !product.variation_groups || !product.variation_groups.length) {

			return '';

		}



		var html = '<div class="product-variations mb-3" id="productVariations">';

		var requiredLabel = escapeHtml((window.cartI18n && window.cartI18n.required) || 'Zorunlu');



		product.variation_groups.forEach(function (group) {

			html += '<div class="product-variation-group mb-3" role="radiogroup">';

			html += '<div class="product-variation-label d-flex justify-content-between align-items-center">';

			html += '<div><span class="product-variation-name fw-semibold">' + escapeHtml(group.name) + '</span>';

			html += '<span class="product-variation-selected text-muted" data-group-label="' + escapeHtml(group.name) + '"></span></div>';

			html += '<span class="badge bg-light text-muted border">' + requiredLabel + '</span></div>';

			html += '<div class="product-variation-options">';



			(group.values || []).forEach(function (val) {

				html += '<button type="button" role="radio" aria-checked="false" class="product-variation-option" data-group="' + escapeHtml(group.name) + '" data-value="' + escapeHtml(val) + '">' + escapeHtml(val) + '</button>';

			});



			html += '</div></div>';

		});



		html += '<p class="product-variation-summary small text-muted mb-1 d-none" id="variationSummary"></p>';

		html += '<p class="small text-muted mb-0" id="variationHint">' + escapeHtml((window.cartI18n && window.cartI18n.selectVariation) || 'Lütfen seçenekleri belirleyin') + '</p>';

		html += '<input type="hidden" id="selectedVariationId" value="0">';

		html += '</div>';

		html += '<script type="application/json" id="variationItemsData">' + JSON.stringify(product.variation_items || []) + '<\/script>';



		return html;

	}



	function buildOptionsHtml(product) {

		if (!product.has_options || !product.option_groups || !product.option_groups.length) {

			return '';

		}



		var html = '<div class="product-options mb-3" id="productOptions">';

		var requiredLabel = escapeHtml((window.cartI18n && window.cartI18n.required) || 'Zorunlu');

		var selectHint = escapeHtml((window.cartI18n && window.cartI18n.selectVariation) || 'Lütfen seçenekleri belirleyin');



		product.option_groups.forEach(function (group) {

			html += '<div class="product-option-group product-variation-group mb-3" role="radiogroup" data-required="' + (group.required ? '1' : '0') + '">';

			html += '<div class="product-variation-label d-flex justify-content-between align-items-center">';

			html += '<div><span class="product-variation-name fw-semibold">' + escapeHtml(group.name) + '</span>';

			html += '<span class="product-variation-selected text-muted product-option-selected" data-group-label="' + escapeHtml(group.name) + '"></span></div>';



			if (group.required) {

				html += '<span class="badge bg-light text-muted border">' + requiredLabel + '</span>';

			}



			html += '</div><div class="product-variation-options">';



			(group.values || []).forEach(function (val) {

				html += '<button type="button" role="radio" aria-checked="false" class="product-variation-option product-option-btn" data-group="' + escapeHtml(group.name) + '" data-value="' + escapeHtml(val) + '">' + escapeHtml(val) + '</button>';

			});



			html += '</div></div>';

		});



		html += '<p class="small text-muted mb-0" id="optionHint">' + selectHint + '</p>';

		html += '</div>';



		return html;

	}



	function renderModal(product) {

		var body = document.getElementById('productQuickModalBody');

		var hasVariations = !!product.has_variations;

		var hasOptions = !!product.has_options;

		var oldPriceHtml = product.has_discount && product.old_price_formatted

			? '<div class="text-muted text-decoration-line-through small" id="productOldPrice">' + escapeHtml(product.old_price_formatted) + '</div>'

			: '';

		var shortDesc = product.short_description

			? '<p class="text-muted small mb-3">' + escapeHtml(product.short_description) + '</p>'

			: '';

		var qtyHidden = hasVariations ? ' d-none' : '';

		var needsSelection = hasVariations || hasOptions;

		var addDisabled = needsSelection || !product.in_stock ? ' disabled' : '';

		var addClass = '';



		if (hasVariations) {

			addClass += ' requires-variation';

		}



		if (hasOptions) {

			addClass += ' requires-options';

		}



		body.innerHTML =

			'<div class="product-configurator" data-product-id="' + product.id_product + '" data-select-hint="' + escapeHtml((window.cartI18n && window.cartI18n.selectVariation) || '') + '" data-out-hint="' + escapeHtml((window.cartI18n && window.cartI18n.outOfStock) || '') + '">' +

				'<div class="row g-3 mb-3">' +

					'<div class="col-4"><img src="' + escapeHtml(product.image_url) + '" alt="" class="img-fluid rounded-3 w-100"></div>' +

					'<div class="col-8">' +

						'<h5 class="fw-bold mb-1">' + escapeHtml(product.product_name) + '</h5>' +

						'<p class="text-muted small mb-2">' + escapeHtml(product.category_name || '') + '</p>' +

						oldPriceHtml +

						'<div class="h5 fw-bold text-primary mb-0" id="productCurrentPrice" data-base-price="' + product.price + '">' + escapeHtml(product.price_formatted) + '</div>' +

					'</div>' +

				'</div>' +

				shortDesc +

				buildVariationHtml(product) +

				buildOptionsHtml(product) +

				'<div class="product-buy-bar border-top pt-3 d-flex flex-wrap align-items-center justify-content-between gap-3">' +

					(product.in_stock

						? '<div class="d-flex align-items-center gap-3">' +

							'<div class="qty-picker' + qtyHidden + '" id="qtyPicker">' +

								'<button type="button" class="qty-btn" data-qty-action="decrease">-</button>' +

								'<input type="text" value="1" id="qty-input" class="qty-input" readonly data-max="' + product.stock + '">' +

								'<button type="button" class="qty-btn" data-qty-action="increase">+</button>' +

							'</div>' +

							'<div class="small text-muted">' + escapeHtml((window.cartI18n && window.cartI18n.total) || 'Toplam') + ': <strong id="productTotalPrice">' + escapeHtml(product.price_formatted) + '</strong></div>' +

						'</div>' +

						'<button type="button" class="btn btn-primary px-4 addtocart' + addClass + '" data-id="' + product.id_product + '" data-variation="0"' + addDisabled + '>' +

							'<i class="bi bi-bag-plus me-1"></i>' + escapeHtml((window.cartI18n && window.cartI18n.addToCart) || 'Sepete Ekle') +

						'</button>'

						: '<button type="button" class="btn btn-secondary" disabled>' + escapeHtml((window.cartI18n && window.cartI18n.outOfStock) || 'Tükendi') + '</button>') +

				'</div>' +

			'</div>';



		if (window.ProductConfigurator) {

			ProductConfigurator.init(body.querySelector('.product-configurator') || body);

		}

	}



	function openQuickView(idProduct) {

		if (!modalEl || !window.productApiUrl) {

			return;

		}



		var body = document.getElementById('productQuickModalBody');

		body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';



		if (!modalInstance) {

			modalInstance = new bootstrap.Modal(modalEl);

		}



		modalInstance.show();



		$.getJSON(productApiUrl, { id: idProduct })

			.done(function (response) {

				if (!response || !response.success || !response.product) {

					body.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(response.message || 'Ürün yüklenemedi') + '</div>';

					return;

				}



				renderModal(response.product);

			})

			.fail(function () {

				body.innerHTML = '<div class="alert alert-danger mb-0">Ürün yüklenemedi</div>';

			});

	}



	$(document).on('click', '.product-quick-open', function (event) {

		event.preventDefault();

		event.stopPropagation();

		openQuickView(parseInt($(this).data('id'), 10));

	});



	window.ProductQuickView = { open: openQuickView };

})(jQuery);

