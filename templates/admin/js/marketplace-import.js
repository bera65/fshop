(function () {
	'use strict';

	var cfg = window.trendyolProductImportConfig || {};
	var submitBtn = document.getElementById('trendyolImportSubmit');
	var errorBox = document.getElementById('trendyolImportError');
	var successBox = document.getElementById('trendyolImportSuccess');

	function setAlert(el, message) {
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

	function readSelectMode(selectEl) {
		if (!selectEl) {
			return { mode: 'create', id: 0 };
		}

		var value = String(selectEl.value || 'create');

		if (value === 'create') {
			return { mode: 'create', id: 0 };
		}

		return { mode: 'select', id: parseInt(value, 10) || 0 };
	}

	if (!submitBtn) {
		return;
	}

	submitBtn.addEventListener('click', function () {
		setAlert(errorBox, '');
		setAlert(successBox, '');

		var barcodeEl = document.getElementById('trendyolImportBarcode');
		var sourceEl = document.getElementById('trendyolImportSource');
		var categoryEl = document.getElementById('trendyolImportCategory');
		var brandEl = document.getElementById('trendyolImportBrand');
		var barcode = barcodeEl ? barcodeEl.value.trim() : '';
		var category = readSelectMode(categoryEl);
		var brand = readSelectMode(brandEl);

		if (barcode === '') {
			setAlert(errorBox, 'Barkod girin');
			return;
		}

		if (category.mode === 'select' && category.id <= 0) {
			setAlert(errorBox, 'Kategori seçin');
			return;
		}

		if (brand.mode === 'select' && brand.id <= 0) {
			setAlert(errorBox, 'Marka seçin');
			return;
		}

		submitBtn.disabled = true;

		var body = new URLSearchParams();
		body.append('token', cfg.token || '');
		body.append('source', sourceEl ? sourceEl.value : 'trendyol');
		body.append('barcode', barcode);
		body.append('category_mode', category.mode);
		body.append('brand_mode', brand.mode);
		body.append('id_category', String(category.id));
		body.append('id_brand', String(brand.id));

		fetch(cfg.importUrl || '', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: body.toString(),
			credentials: 'same-origin',
		})
			.then(function (res) {
				return res.text().then(function (text) {
					var data = null;

					if (text) {
						try {
							data = JSON.parse(text);
						} catch (e) {
							throw new Error(text.slice(0, 200) || ('HTTP ' + res.status));
						}
					}

					if (!res.ok && data && data.message) {
						throw new Error(data.message);
					}

					if (!res.ok) {
						throw new Error('HTTP ' + res.status);
					}

					return data;
				});
			})
			.then(function (data) {
				if (!data || !data.success) {
					setAlert(errorBox, (data && data.message) || 'İçe aktarma başarısız');
					return;
				}

				var msg = data.message || 'Ürün oluşturuldu';

				if (data.edit_url) {
					msg += ' — ';
					var link = document.createElement('a');
					link.href = data.edit_url;
					link.textContent = 'Ürünü düzenle';
					successBox.innerHTML = '';
					successBox.appendChild(document.createTextNode(msg.split(' — ')[0] + ' — '));
					successBox.appendChild(link);
					successBox.classList.remove('d-none');
				} else {
					setAlert(successBox, msg);
				}

				if (barcodeEl) {
					barcodeEl.value = '';
				}
			})
			.catch(function (err) {
				setAlert(errorBox, (err && err.message) ? err.message : 'Bağlantı hatası');
			})
			.finally(function () {
				submitBtn.disabled = false;
			});
	});
})();
