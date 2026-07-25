(function () {
	'use strict';

	function track(name, payload) {
		if (typeof fbq !== 'function') {
			return;
		}

		fbq('track', name, payload || {});
	}

	function getConfig() {
		return window.fshopFbPixel || null;
	}

	function runQueuedEvents() {
		var cfg = getConfig();

		if (!cfg || !cfg.active || !Array.isArray(cfg.events)) {
			return;
		}

		cfg.events.forEach(function (event) {
			if (event && event.name) {
				track(event.name, event.payload || {});
			}
		});
	}

	function bindAddToCart() {
		var cfg = getConfig();

		if (!cfg || !cfg.trackCart) {
			return;
		}

		document.addEventListener('click', function (event) {
			var btn = event.target.closest('.addtocart');

			if (!btn) {
				return;
			}

			var idProduct = parseInt(btn.getAttribute('data-id'), 10) || 0;
			var payload = {
				content_ids: idProduct > 0 ? [String(idProduct)] : [],
				content_type: 'product',
				currency: cfg.currency || 'TRY'
			};

			var priceEl = document.getElementById('productCurrentPrice');

			if (priceEl) {
				var value = parseFloat(priceEl.getAttribute('data-base-price') || '0');

				if (!isNaN(value) && value > 0) {
					payload.value = value;
				}
			}

			setTimeout(function () {
				track('AddToCart', payload);
			}, 0);
		}, true);
	}

	function trackCheckoutIfNeeded() {
		var cfg = getConfig();

		if (!cfg || !cfg.trackCheckout || !document.querySelector('.checkout-page')) {
			return;
		}

		if (typeof jQuery === 'undefined' || typeof cartApiUrl === 'undefined') {
			return;
		}

		jQuery.post(cartApiUrl, {
			action: 'get',
			token: typeof csrfToken !== 'undefined' ? csrfToken : ''
		}).done(function (data) {
			if (!data || !data.success) {
				return;
			}

			var contentIds = [];

			(data.items || []).forEach(function (item) {
				var id = parseInt(item.id_product, 10) || 0;

				if (id > 0) {
					contentIds.push(String(id));
				}
			});

			track('InitiateCheckout', {
				content_ids: contentIds,
				content_type: 'product',
				value: parseFloat(data.total || 0) || 0,
				currency: cfg.currency || 'TRY',
				num_items: (data.items || []).length
			});
		});
	}

	function init() {
		runQueuedEvents();
		bindAddToCart();
		trackCheckoutIfNeeded();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
