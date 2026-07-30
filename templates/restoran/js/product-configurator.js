window.ProductConfigurator = (function () {
	'use strict';

	function optionKeys(obj) {
		return Object.keys(obj || {});
	}

	function init(root) {
		if (!root) {
			return;
		}

		var variationsRoot = root.querySelector('#productVariations');
		var optionsRoot = root.querySelector('#productOptions');
		var dataEl = root.querySelector('#variationItemsData');
		var items = [];

		if (dataEl) {
			try {
				items = JSON.parse(dataEl.textContent || '[]');
			} catch (e) {
				items = [];
			}
		}

		var groups = variationsRoot
			? Array.prototype.slice.call(variationsRoot.querySelectorAll('.product-variation-group'))
			: [];
		var optionGroups = optionsRoot
			? Array.prototype.slice.call(optionsRoot.querySelectorAll('.product-option-group'))
			: [];
		var requiredGroups = groups.length;
		var selected = {};
		var optionSelected = {};
		var hidden = root.querySelector('#selectedVariationId');
		var priceEl = root.querySelector('#productCurrentPrice') || document.getElementById('productCurrentPrice');
		var totalEl = root.querySelector('#productTotalPrice');
		var qtyInput = root.querySelector('#qty-input');
		var qtyPicker = root.querySelector('#qtyPicker');
		var addBtn = root.querySelector('.addtocart');
		var hint = root.querySelector('#variationHint');
		var optionHint = root.querySelector('#optionHint');
		var summaryEl = root.querySelector('#variationSummary');
		var basePrice = priceEl ? parseFloat(priceEl.getAttribute('data-base-price') || '0') : 0;
		var basePriceHtml = priceEl ? priceEl.innerHTML : '';
		var selectHint = root.getAttribute('data-select-hint') || 'Lütfen seçenekleri belirleyin';
		var outHint = root.getAttribute('data-out-hint') || 'Tükendi';

		root.getSelectedOptions = function () {
			return Object.assign({}, optionSelected);
		};

		function getQty() {
			return Math.max(1, parseInt(qtyInput && qtyInput.value ? qtyInput.value : '1', 10) || 1);
		}

		function updateTotal(match) {
			if (!totalEl) {
				return;
			}

			var unit = match ? match.price : basePrice;
			var total = unit * getQty();

			if (window.formatMoney) {
				totalEl.textContent = window.formatMoney(total);
			} else {
				totalEl.textContent = unit.toFixed(2);
			}
		}

		function optionsComplete() {
			if (optionGroups.length === 0) {
				return true;
			}

			for (var i = 0; i < optionGroups.length; i++) {
				var groupEl = optionGroups[i];

				if (groupEl.getAttribute('data-required') !== '1') {
					continue;
				}

				var btn = groupEl.querySelector('.product-option-btn');
				var group = btn ? btn.getAttribute('data-group') : '';

				if (!group || !optionSelected[group]) {
					return false;
				}
			}

			return true;
		}

		function refreshOptionStates() {
			if (!optionsRoot) {
				return;
			}

			optionsRoot.querySelectorAll('.product-option-btn').forEach(function (btn) {
				var group = btn.getAttribute('data-group');
				var value = btn.getAttribute('data-value');
				var isSelected = optionSelected[group] === value;
				var label = optionsRoot.querySelector('.product-option-selected[data-group-label="' + group + '"]');

				btn.classList.toggle('active', isSelected);
				btn.setAttribute('aria-checked', isSelected ? 'true' : 'false');

				if (label) {
					label.textContent = isSelected ? ': ' + value : '';
				}
			});
		}

		function findMatch() {
			var selectedKeys = optionKeys(selected);

			if (requiredGroups > 0 && selectedKeys.length < requiredGroups) {
				return null;
			}

			if (requiredGroups === 0) {
				return null;
			}

			return items.find(function (item) {
				var opts = item.options || {};
				var keys = optionKeys(opts);

				if (keys.length !== selectedKeys.length) {
					return false;
				}

				for (var i = 0; i < keys.length; i++) {
					if (opts[keys[i]] !== selected[keys[i]]) {
						return false;
					}
				}

				return true;
			}) || null;
		}

		function isOptionAvailable(group, value) {
			var trial = Object.assign({}, selected);
			trial[group] = value;

			return items.some(function (item) {
				var opts = item.options || {};

				for (var key in trial) {
					if (trial[key] === '') {
						continue;
					}

					if (opts[key] !== trial[key]) {
						return false;
					}
				}

				return item.in_stock;
			});
		}

		function clearInvalidSelections() {
			var changed = false;

			groups.forEach(function (groupEl) {
				var btn = groupEl.querySelector('.product-variation-option');
				var group = btn ? btn.getAttribute('data-group') : '';
				var value = selected[group];

				if (!group || !value) {
					return;
				}

				if (!isOptionAvailable(group, value)) {
					delete selected[group];
					changed = true;
				}
			});

			return changed;
		}

		function refreshOptionStatesForVariations() {
			if (!variationsRoot) {
				return;
			}

			variationsRoot.querySelectorAll('.product-variation-option').forEach(function (btn) {
				var group = btn.getAttribute('data-group');
				var value = btn.getAttribute('data-value');
				var available = isOptionAvailable(group, value);
				var isSelected = selected[group] === value;
				var label = variationsRoot.querySelector('.product-variation-selected[data-group-label="' + group + '"]');

				btn.disabled = !available;
				btn.classList.toggle('active', isSelected);
				btn.setAttribute('aria-checked', isSelected ? 'true' : 'false');

				if (label) {
					label.textContent = isSelected ? ': ' + value : '';
				}
			});
		}

		function updateSummary(match) {
			if (!summaryEl) {
				return;
			}

			var parts = groups.map(function (groupEl) {
				var btn = groupEl.querySelector('.product-variation-option.active');

				return btn ? btn.getAttribute('data-value') : '';
			}).filter(Boolean);

			if (parts.length === 0) {
				summaryEl.classList.add('d-none');
				summaryEl.textContent = '';
				return;
			}

			summaryEl.classList.remove('d-none');
			summaryEl.textContent = parts.join(' / ');
		}

		function applyAddButtonState(match) {
			if (!addBtn) {
				return;
			}

			var variationOk = requiredGroups === 0 || (match && match.in_stock);
			var optionsOk = optionsComplete();
			var canAdd = variationOk && optionsOk;

			addBtn.disabled = !canAdd;

			if (requiredGroups === 0) {
				addBtn.dataset.variation = '0';
			} else if (match) {
				addBtn.dataset.variation = String(match.id_variation);
			} else {
				addBtn.dataset.variation = '0';
			}
		}

		function updateUI() {
			while (clearInvalidSelections()) {
				// invalid seçimleri temizle
			}

			var match = findMatch();

			refreshOptionStatesForVariations();
			refreshOptionStates();

			if (requiredGroups === 0) {
				if (qtyPicker && optionGroups.length === 0) {
					qtyPicker.classList.remove('d-none');
				}

				if (optionHint) {
					optionHint.textContent = optionsComplete() ? '' : selectHint;
				}

				applyAddButtonState(null);
				updateTotal(null);
				return;
			}

			if (match) {
				if (hidden) {
					hidden.value = String(match.id_variation);
				}

				if (priceEl && match.price_formatted) {
					priceEl.innerHTML = match.price_formatted;
				}

				if (qtyInput) {
					qtyInput.dataset.max = String(match.stock);
					var current = getQty();
					qtyInput.value = String(Math.min(Math.max(1, current), match.stock));
				}

				if (qtyPicker) {
					qtyPicker.classList.remove('d-none');
				}

				if (hint) {
					hint.textContent = match.in_stock ? '' : outHint;
				}

				if (optionHint) {
					optionHint.textContent = optionsComplete() ? '' : selectHint;
				}

				updateSummary(match);
				updateTotal(match);
				applyAddButtonState(match);
			} else {
				if (hidden) {
					hidden.value = '0';
				}

				if (priceEl) {
					priceEl.innerHTML = basePriceHtml;
				}

				if (qtyPicker) {
					qtyPicker.classList.add('d-none');
				}

				if (hint) {
					hint.textContent = selectHint;
				}

				if (optionHint) {
					optionHint.textContent = optionsComplete() ? '' : selectHint;
				}

				updateSummary(null);
				updateTotal(null);
				applyAddButtonState(null);
			}
		}

		if (variationsRoot) {
			variationsRoot.addEventListener('click', function (event) {
				var btn = event.target.closest('.product-variation-option');

				if (!btn || btn.disabled) {
					return;
				}

				var group = btn.getAttribute('data-group');
				var value = btn.getAttribute('data-value');

				if (!group) {
					return;
				}

				selected[group] = value;
				updateUI();
			});
		}

		if (optionsRoot) {
			optionsRoot.addEventListener('click', function (event) {
				var btn = event.target.closest('.product-option-btn');

				if (!btn) {
					return;
				}

				var group = btn.getAttribute('data-group');
				var value = btn.getAttribute('data-value');

				if (!group) {
					return;
				}

				optionSelected[group] = value;
				updateUI();
			});
		}

		root.querySelectorAll('[data-qty-action]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				if (!qtyInput) {
					return;
				}

				var max = parseInt(qtyInput.dataset.max, 10) || 99;
				var current = getQty();
				var action = btn.getAttribute('data-qty-action');
				var next = action === 'increase' ? current + 1 : current - 1;

				if (next >= 1 && next <= max) {
					qtyInput.value = String(next);
					updateUI();
				}
			});
		});

		updateUI();
	}

	return { init: init };
})();
