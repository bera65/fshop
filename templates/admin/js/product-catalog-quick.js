(function () {
	'use strict';

	var cfg = window.productCatalogQuickConfig || {};
	var adminUrl = cfg.adminUrl || '';
	var token = cfg.token || '';

	function rebuildSelect(select, options, idKey, labelKey, selectedId) {
		if (!select) {
			return;
		}

		var current = selectedId != null ? String(selectedId) : String(select.value || '');
		select.innerHTML = '';

		(options || []).forEach(function (opt) {
			var el = document.createElement('option');
			el.value = String(opt[idKey]);
			el.textContent = opt[labelKey] || '';
			if (String(opt[idKey]) === current) {
				el.selected = true;
			}
			select.appendChild(el);
		});
	}

	function rebuildCategoryParentSelect(select, options, selectedId) {
		if (!select) {
			return;
		}

		var current = selectedId != null ? String(selectedId) : String(select.value || '');
		select.innerHTML = '';

		var root = document.createElement('option');
		root.value = '0';
		root.textContent = cfg.parentRootLabel || 'Yok (kök)';
		if (current === '0') {
			root.selected = true;
		}
		select.appendChild(root);

		(options || []).forEach(function (opt) {
			var el = document.createElement('option');
			el.value = String(opt.id_category);
			el.textContent = opt.category_name || '';
			if (String(opt.id_category) === current) {
				el.selected = true;
			}
			select.appendChild(el);
		});
	}

	function showModal(modalId) {
		var el = document.getElementById(modalId);
		if (!el || typeof bootstrap === 'undefined') {
			return null;
		}

		return bootstrap.Modal.getOrCreateInstance(el);
	}

	function setError(el, message) {
		if (!el) {
			return;
		}

		if (message) {
			el.textContent = message;
			el.classList.remove('d-none');
		} else {
			el.textContent = '';
			el.classList.add('d-none');
		}
	}

	function postQuickAdd(payload) {
		var body = new URLSearchParams();
		Object.keys(payload).forEach(function (key) {
			body.append(key, payload[key]);
		});
		body.append('ajax', '1');
		body.append('token', token);

		return fetch(adminUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: body.toString(),
			credentials: 'same-origin',
		}).then(function (res) {
			return res.json();
		});
	}

	var categoryBtn = document.getElementById('quickAddCategoryBtn');
	var brandBtn = document.getElementById('quickAddBrandBtn');
	var categorySelect = document.getElementById('productCategorySelect');
	var brandSelect = document.getElementById('productBrandSelect');
	var categoryModalEl = document.getElementById('quickAddCategoryModal');
	var brandModalEl = document.getElementById('quickAddBrandModal');
	var categoryNameInput = document.getElementById('quickCategoryName');
	var categoryParentSelect = document.getElementById('quickCategoryParent');
	var brandNameInput = document.getElementById('quickBrandName');
	var categoryError = document.getElementById('quickCategoryError');
	var brandError = document.getElementById('quickBrandError');
	var categorySaveBtn = document.getElementById('quickCategorySaveBtn');
	var brandSaveBtn = document.getElementById('quickBrandSaveBtn');

	if (categoryBtn && categoryModalEl) {
		categoryBtn.addEventListener('click', function () {
			setError(categoryError, '');
			if (categoryNameInput) {
				categoryNameInput.value = '';
			}
			if (categoryParentSelect && categorySelect) {
				categoryParentSelect.value = categorySelect.value || '0';
			}
			showModal('quickAddCategoryModal').show();
			if (categoryNameInput) {
				categoryNameInput.focus();
			}
		});
	}

	if (brandBtn && brandModalEl) {
		brandBtn.addEventListener('click', function () {
			setError(brandError, '');
			if (brandNameInput) {
				brandNameInput.value = '';
			}
			showModal('quickAddBrandModal').show();
			if (brandNameInput) {
				brandNameInput.focus();
			}
		});
	}

	if (categorySaveBtn) {
		categorySaveBtn.addEventListener('click', function () {
			var name = categoryNameInput ? categoryNameInput.value.trim() : '';
			var parentId = categoryParentSelect ? categoryParentSelect.value : '0';

			setError(categoryError, '');

			if (name === '') {
				setError(categoryError, 'Kategori adı zorunludur');
				return;
			}

			categorySaveBtn.disabled = true;

			postQuickAdd({
				quickAddCategory: '1',
				category_name: name,
				id_parent: parentId,
			})
				.then(function (data) {
					if (!data || !data.success) {
						setError(categoryError, (data && data.message) || 'Kategori eklenemedi');
						return;
					}

					rebuildSelect(categorySelect, data.categoryOptions || [], 'id_category', 'category_name', data.id);
					rebuildCategoryParentSelect(categoryParentSelect, data.categoryOptions || [], parentId);

					var modal = bootstrap.Modal.getInstance(categoryModalEl);
					if (modal) {
						modal.hide();
					}
				})
				.catch(function () {
					setError(categoryError, 'Bağlantı hatası');
				})
				.finally(function () {
					categorySaveBtn.disabled = false;
				});
		});
	}

	if (brandSaveBtn) {
		brandSaveBtn.addEventListener('click', function () {
			var name = brandNameInput ? brandNameInput.value.trim() : '';

			setError(brandError, '');

			if (name === '') {
				setError(brandError, 'Marka adı zorunludur');
				return;
			}

			brandSaveBtn.disabled = true;

			postQuickAdd({
				quickAddBrand: '1',
				brand_name: name,
			})
				.then(function (data) {
					if (!data || !data.success) {
						setError(brandError, (data && data.message) || 'Marka eklenemedi');
						return;
					}

					rebuildSelect(brandSelect, data.brandOptions || [], 'id_brand', 'brand_name', data.id);

					var modal = bootstrap.Modal.getInstance(brandModalEl);
					if (modal) {
						modal.hide();
					}
				})
				.catch(function () {
					setError(brandError, 'Bağlantı hatası');
				})
				.finally(function () {
					brandSaveBtn.disabled = false;
				});
		});
	}
})();
