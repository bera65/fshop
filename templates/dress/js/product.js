function updateQty(val) {
	const input = document.getElementById('qty-input');
	if (!input) return;
	if ((input.dataset.saleUnit || 'piece') === 'm2') return;

	const max = parseFloat(input.dataset.max) || 99;
	let current = parseInt(input.value, 10) || 1;
	const next = current + val;

	if (next >= 1 && next <= max) {
		input.value = next;
	}
}

(function () {
	var measure = document.getElementById('m2Measure');
	if (!measure) return;

	var widthEl = document.getElementById('m2-width');
	var lengthEl = document.getElementById('m2-length');
	var qtyInput = document.getElementById('qty-input');
	var summary = document.getElementById('m2Summary');
	var minQty = parseFloat(measure.dataset.min) || 0.01;
	var step = parseFloat(measure.dataset.step) || 0.01;
	var unitPrice = parseFloat(measure.dataset.price) || 0;
	var stock = parseFloat(measure.dataset.stock) || 0;

	function formatNum(n) {
		var s = (Math.round(n * 1000) / 1000).toFixed(3);
		s = s.replace(/\.?0+$/, '');
		return s.replace('.', ',');
	}

	function normalizeArea(area) {
		if (!(area > 0)) return 0;
		var steps = Math.round(area / step);
		if (steps < 1) steps = 1;
		area = Math.round(steps * step * 1000) / 1000;
		if (area < minQty) area = minQty;
		return area;
	}

	function refresh() {
		var w = parseFloat(String(widthEl.value || '').replace(',', '.')) || 0;
		var l = parseFloat(String(lengthEl.value || '').replace(',', '.')) || 0;
		var area = normalizeArea(w * l);

		if (w <= 0 || l <= 0) {
			qtyInput.value = '0';
			summary.textContent = summary.getAttribute('data-empty') || summary.textContent;
			if (!summary.getAttribute('data-empty')) {
				summary.setAttribute('data-empty', summary.textContent);
			}
			return;
		}

		if (area > stock) {
			summary.textContent = (summary.getAttribute('data-stock') || 'Stock limit') + ': ' + formatNum(stock) + ' m²';
			qtyInput.value = String(area);
			return;
		}

		var total = unitPrice * area;
		summary.textContent = formatNum(w) + ' × ' + formatNum(l) + ' m = ' + formatNum(area) + ' m² · ' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
		qtyInput.value = String(area);
	}

	if (summary && !summary.getAttribute('data-empty')) {
		summary.setAttribute('data-empty', summary.textContent);
	}

	['input', 'change'].forEach(function (ev) {
		widthEl.addEventListener(ev, refresh);
		lengthEl.addEventListener(ev, refresh);
	});
})();

(function () {
	var mainImg = document.getElementById('main-display');
	var modalImg = document.getElementById('modal-display');
	var thumbs = Array.prototype.slice.call(document.querySelectorAll('.product-gallery__thumb'));
	var prevBtn = document.querySelector('.product-gallery__nav--prev');
	var nextBtn = document.querySelector('.product-gallery__nav--next');
	var modalPrev = document.getElementById('imageModalPrev');
	var modalNext = document.getElementById('imageModalNext');
	var modalCounter = document.getElementById('imageModalCounter');
	var currentIndex = 0;

	function getImageUrls() {
		if (thumbs.length) {
			return thumbs.map(function (thumb) {
				return thumb.getAttribute('data-image') || '';
			}).filter(Boolean);
		}

		return mainImg && mainImg.src ? [mainImg.src] : [];
	}

	function setActiveIndex(index) {
		var urls = getImageUrls();
		if (!urls.length) {
			return;
		}

		if (index < 0) {
			index = urls.length - 1;
		}
		if (index >= urls.length) {
			index = 0;
		}

		currentIndex = index;

		if (mainImg && urls[index]) {
			mainImg.src = urls[index];
		}
		if (modalImg && urls[index]) {
			modalImg.src = urls[index];
		}

		thumbs.forEach(function (thumb, i) {
			thumb.classList.toggle('active', i === index);
		});

		if (modalCounter) {
			modalCounter.textContent = String(index + 1);
		}
	}

	thumbs.forEach(function (thumb, index) {
		thumb.addEventListener('click', function () {
			setActiveIndex(index);
		});
	});

	function bindNav(btn, delta) {
		if (!btn) {
			return;
		}
		btn.addEventListener('click', function (event) {
			event.preventDefault();
			event.stopPropagation();
			setActiveIndex(currentIndex + delta);
		});
	}

	bindNav(prevBtn, -1);
	bindNav(nextBtn, 1);
	bindNav(modalPrev, -1);
	bindNav(modalNext, 1);

	if (mainImg) {
		mainImg.addEventListener('click', function () {
			if (modalImg && mainImg.src) {
				modalImg.src = mainImg.src;
			}
		});
	}

	var imageModal = document.getElementById('imageModal');
	if (imageModal) {
		imageModal.addEventListener('show.bs.modal', function () {
			if (modalImg && mainImg && mainImg.src) {
				modalImg.src = mainImg.src;
			}
			if (modalCounter) {
				modalCounter.textContent = String(currentIndex + 1);
			}
		});

		imageModal.addEventListener('keydown', function (event) {
			if (getImageUrls().length < 2) {
				return;
			}
			if (event.key === 'ArrowLeft') {
				event.preventDefault();
				setActiveIndex(currentIndex - 1);
			} else if (event.key === 'ArrowRight') {
				event.preventDefault();
				setActiveIndex(currentIndex + 1);
			}
		});
	}

	document.addEventListener('keydown', function (event) {
		if (!imageModal || !imageModal.classList.contains('show') || getImageUrls().length < 2) {
			return;
		}
		if (event.key === 'ArrowLeft') {
			event.preventDefault();
			setActiveIndex(currentIndex - 1);
		} else if (event.key === 'ArrowRight') {
			event.preventDefault();
			setActiveIndex(currentIndex + 1);
		}
	});
})();

(function () {
	var root = document.getElementById('productVariations');
	var dataEl = document.getElementById('variationItemsData');

	if (!root || !dataEl) {
		return;
	}

	var items = [];
	try {
		items = JSON.parse(dataEl.textContent || '[]');
	} catch (e) {
		items = [];
	}

	var groups = Array.prototype.slice.call(root.querySelectorAll('.product-variation-group'));
	var requiredGroups = groups.length;
	var selected = {};
	var hidden = document.getElementById('selectedVariationId');
	var priceEl = document.getElementById('productCurrentPrice');
	var qtyInput = document.getElementById('qty-input');
	var qtyPicker = document.getElementById('qtyPicker');
	var buyBtns = Array.prototype.slice.call(document.querySelectorAll('.addtocart.requires-variation, .buynow.requires-variation, .product-buy-block .requires-variation, .product-buy-bar .requires-variation'));
	var hint = document.getElementById('variationHint');
	var summaryEl = document.getElementById('variationSummary');
	var basePriceHtml = priceEl ? priceEl.innerHTML : '';
	var selectHint = root.getAttribute('data-select-hint') || 'Lütfen seçenekleri belirleyin';
	var outHint = root.getAttribute('data-out-hint') || 'Tükendi';

	function t(key, fallback) {
		if (key === 'selectOptions') {
			return selectHint;
		}

		if (key === 'outOfStock') {
			return outHint;
		}

		return fallback || key;
	}

	function optionKeys(obj) {
		return Object.keys(obj || {});
	}

	function getGroupOrder() {
		return groups.map(function (groupEl) {
			var btn = groupEl.querySelector('.product-variation-option');

			return btn ? btn.getAttribute('data-group') : '';
		}).filter(Boolean);
	}

	function findMatch() {
		var selectedKeys = optionKeys(selected);

		if (selectedKeys.length < requiredGroups) {
			return null;
		}

		return items.find(function (item) {
			var opts = item.options || {};
			var keys = optionKeys(opts);

			if (keys.length !== selectedKeys.length) {
				return false;
			}

			for (var i = 0; i < keys.length; i++) {
				var key = keys[i];

				if (opts[key] !== selected[key]) {
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

	function updateGroupLabel(group, value) {
		var label = root.querySelector('.product-variation-selected[data-group-label="' + group + '"]');

		if (label) {
			label.textContent = value ? ': ' + value : '';
		}
	}

	function clearInvalidSelections() {
		var order = getGroupOrder();
		var changed = false;

		order.forEach(function (group) {
			var value = selected[group];

			if (!value) {
				return;
			}

			if (!isOptionAvailable(group, value)) {
				delete selected[group];
				changed = true;
			}
		});

		return changed;
	}

	function refreshOptionStates() {
		root.querySelectorAll('.product-variation-option').forEach(function (btn) {
			var group = btn.getAttribute('data-group');
			var value = btn.getAttribute('data-value');
			var available = isOptionAvailable(group, value);
			var isSelected = selected[group] === value;

			btn.disabled = !available;
			btn.classList.toggle('active', isSelected);
			btn.setAttribute('aria-checked', isSelected ? 'true' : 'false');
		});

		getGroupOrder().forEach(function (group) {
			updateGroupLabel(group, selected[group] || '');
		});
	}

	function updateSummary(match) {
		if (!summaryEl) {
			return;
		}

		var parts = getGroupOrder().map(function (group) {
			return selected[group] || '';
		}).filter(Boolean);

		if (parts.length === 0) {
			summaryEl.classList.add('d-none');
			summaryEl.textContent = '';
			return;
		}

		summaryEl.classList.remove('d-none');
		summaryEl.textContent = parts.join(' / ');

		if (match && match.sku) {
			summaryEl.textContent += ' (' + match.sku + ')';
		}
	}

	function updateUI() {
		while (clearInvalidSelections()) {
			// Boşaltılan seçimler sonrası diğer grupları yeniden kontrol et
		}

		var match = findMatch();

		refreshOptionStates();

		if (match) {
			if (hidden) {
				hidden.value = String(match.id_variation);
			}

			if (priceEl && match.price_formatted) {
				priceEl.innerHTML = match.price_formatted;
			}

			if (qtyInput) {
				qtyInput.dataset.max = String(match.stock);
				var current = parseInt(qtyInput.value, 10) || 1;
				qtyInput.value = String(Math.min(Math.max(1, current), match.stock));
			}
			if (qtyPicker) {
				qtyPicker.classList.remove('d-none');
			}

			var optionsValid = true;
			var optRoot = document.getElementById('productOptions');
			if (optRoot) {
				var reqs = optRoot.querySelectorAll('.product-option-group[data-required="1"]');
				for (var i = 0; i < reqs.length; i++) {
					var inp = reqs[i].querySelector('.product-option-input');
					if (!inp || !inp.value) { optionsValid = false; break; }
				}
			}

			buyBtns.forEach(function (btn) {
				btn.disabled = !match.in_stock || (btn.classList.contains('requires-options') && !optionsValid);
				btn.dataset.variation = String(match.id_variation);
			});

			if (hint) {
				hint.textContent = match.in_stock ? '' : t('outOfStock', 'Tükendi');
			}

			updateSummary(match);
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

			buyBtns.forEach(function (btn) {
				btn.disabled = true;
				btn.dataset.variation = '0';
			});

			if (hint) {
				hint.textContent = optionKeys(selected).length > 0 && optionKeys(selected).length < requiredGroups
					? t('selectOptions', 'Lütfen tüm seçenekleri belirleyin')
					: t('selectOptions', 'Lütfen seçenekleri belirleyin');
			}

			updateSummary(null);
		}
	}

	root.addEventListener('click', function (event) {
		var btn = event.target.closest('.product-variation-option');

		if (!btn || btn.disabled || btn.classList.contains('product-option-btn')) {
			return;
		}

		var group = btn.getAttribute('data-group');
		var value = btn.getAttribute('data-value');

		if (!group || !value) {
			return;
		}

		selected[group] = value;
		updateUI();
	});

	window.fshopUpdateUI = updateUI;
	updateUI();
})();
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var root = document.querySelector('.product-configurator');

		if (root && window.ProductConfigurator) {
			ProductConfigurator.init(root);
		}
	});
})();

(function () {
	'use strict';

	var optRoot = document.getElementById('productOptions');
	if (!optRoot) return;

	function refreshOptionsBuyState() {
		var optionsValid = true;
		var reqs = optRoot.querySelectorAll('.product-option-group[data-required="1"]');
		for (var i = 0; i < reqs.length; i++) {
			var inp = reqs[i].querySelector('.product-option-input');
			if (!inp || !String(inp.value || '').trim()) {
				optionsValid = false;
				break;
			}
		}

		var buyBtns = document.querySelectorAll('.addtocart.requires-options, .buynow.requires-options');
		buyBtns.forEach(function (buyBtn) {
			if (buyBtn.classList.contains('requires-variation')) {
				if (typeof window.fshopUpdateUI === 'function') {
					window.fshopUpdateUI();
				}
				return;
			}
			buyBtn.disabled = !optionsValid;
		});

		var hint = document.getElementById('optionHint');
		if (hint) {
			hint.textContent = optionsValid ? '' : ((optRoot.closest('.product-configurator') && optRoot.closest('.product-configurator').getAttribute('data-select-hint')) || '');
		}
	}

	optRoot.addEventListener('click', function (event) {
		var btn = event.target.closest('.product-option-btn');
		if (!btn || btn.disabled) return;

		var group = btn.getAttribute('data-group');
		var value = btn.getAttribute('data-value');
		var groupEl = btn.closest('.product-option-group');
		if (!groupEl || !group || !value) return;

		var input = groupEl.querySelector('.product-option-input');
		if (!input) {
			input = document.createElement('input');
			input.type = 'hidden';
			input.className = 'product-option-input';
			input.name = 'options[' + group + ']';
			groupEl.appendChild(input);
		}
		input.value = value;

		var label = groupEl.querySelector('.product-option-selected');
		if (label) label.textContent = value ? ': ' + value : '';

		var btns = groupEl.querySelectorAll('.product-option-btn');
		btns.forEach(function (b) {
			var on = b === btn;
			b.setAttribute('aria-checked', on ? 'true' : 'false');
			b.classList.toggle('active', on);
		});

		refreshOptionsBuyState();

		if (typeof window.fshopUpdateUI === 'function') {
			window.fshopUpdateUI();
		}
	});

	refreshOptionsBuyState();
})();
