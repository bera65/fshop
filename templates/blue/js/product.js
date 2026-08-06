function updateQty(val) {
	const input = document.getElementById('qty-input');
	if (!input) return;

	const max = parseInt(input.dataset.max, 10) || 99;
	let current = parseInt(input.value, 10) || 1;
	const next = current + val;

	if (next >= 1 && next <= max) {
		input.value = next;
	}
}

(function () {
	var mainImg = document.getElementById('main-display');
	var modalImg = document.getElementById('modal-display');
	var thumbs = Array.prototype.slice.call(document.querySelectorAll('.product-gallery__thumb'));
	var prevBtn = document.querySelector('.product-gallery__nav--prev');
	var nextBtn = document.querySelector('.product-gallery__nav--next');
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
	}

	thumbs.forEach(function (thumb, index) {
		thumb.addEventListener('click', function () {
			setActiveIndex(index);
		});
	});

	if (prevBtn) {
		prevBtn.addEventListener('click', function (event) {
			event.preventDefault();
			event.stopPropagation();
			setActiveIndex(currentIndex - 1);
		});
	}

	if (nextBtn) {
		nextBtn.addEventListener('click', function (event) {
			event.preventDefault();
			event.stopPropagation();
			setActiveIndex(currentIndex + 1);
		});
	}

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
		});
	}
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
	var addBtn = document.querySelector('.addtocart.requires-variation');
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

			if (addBtn) {
				addBtn.disabled = !match.in_stock;
				addBtn.dataset.variation = String(match.id_variation);
			}

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

			if (addBtn) {
				addBtn.disabled = true;
				addBtn.dataset.variation = '0';
			}

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

		if (!btn || btn.disabled) {
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

	updateUI();
})();
