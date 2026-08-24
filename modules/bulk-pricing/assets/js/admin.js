(function () {
	'use strict';

	function updateHint() {
		var mode = document.getElementById('adjustMode');
		var hint = document.getElementById('adjustValueHint');

		if (!mode || !hint) {
			return;
		}

		if (mode.value === 'fixed') {
			hint.textContent = 'Örn. satış fiyatına 50 TL zam için 50 yazın.';
		} else {
			hint.textContent = 'Örn. satış fiyatına %10 zam için 10 yazın.';
		}
	}

	function bindApplyConfirm() {
		var btn = document.querySelector('.js-bulk-pricing-apply');

		if (!btn) {
			return;
		}

		btn.addEventListener('click', function (event) {
			var checkedFields = document.querySelectorAll('#bulkPricingForm input[name^="field_"]:checked').length;

			if (checkedFields === 0) {
				event.preventDefault();
				if (window.AdminToast) {
					AdminToast.show('En az bir fiyat alanı seçin.', 'warning');
				}
				return;
			}

			if (btn.dataset.adminConfirmed === '1') {
				return;
			}

			event.preventDefault();
			var message = 'Seçili filtrelere uyan tüm ürünlerin fiyatları güncellenecek. Devam edilsin mi?';
			var ask = window.AdminConfirm && AdminConfirm.ask
				? AdminConfirm.ask({ message: message })
				: Promise.resolve(false);

			ask.then(function (ok) {
				if (!ok) {
					return;
				}

				btn.dataset.adminConfirmed = '1';
				var form = btn.form || btn.closest('form');
				if (form && typeof form.requestSubmit === 'function') {
					form.requestSubmit(btn);
				} else if (form) {
					form.submit();
				}
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		updateHint();
		bindApplyConfirm();

		var mode = document.getElementById('adjustMode');

		if (mode) {
			mode.addEventListener('change', updateHint);
		}
	});
})();
