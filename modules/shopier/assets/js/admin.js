(function () {
	function postAction(url, idProduct, msgEl, onSuccess) {
		var body = new URLSearchParams({
			id_product: idProduct,
			token: window.shopierAdminToken || window.adminToken || ''
		});
		return fetch(url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			credentials: 'same-origin',
			body: body.toString()
		})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (msgEl) {
					msgEl.textContent = res.message || '';
					msgEl.classList.toggle('text-danger', !res.success);
					msgEl.classList.toggle('text-success', !!res.success);
				}
				if (res.success && typeof onSuccess === 'function') {
					onSuccess(res);
				}
				return res;
			})
			.catch(function () {
				if (msgEl) {
					msgEl.textContent = 'İstek başarısız';
					msgEl.classList.add('text-danger');
				}
			});
	}

	document.querySelectorAll('.shopier-sync-btn').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var wrap = btn.closest('.shopier-product-actions');
			var msgEl = wrap ? wrap.querySelector('.shopier-action-msg') : null;
			btn.disabled = true;
			if (msgEl) msgEl.textContent = 'Gönderiliyor…';
			postAction(btn.getAttribute('data-url'), btn.getAttribute('data-id'), msgEl, function () {
				setTimeout(function () { location.reload(); }, 700);
			}).finally(function () {
				btn.disabled = false;
			});
		});
	});

	document.querySelectorAll('.shopier-delete-btn').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (!window.confirm('Bu ürün Shopier mağazanızdan silinecek. Devam edilsin mi?')) {
				return;
			}
			var wrap = btn.closest('.shopier-product-actions');
			var msgEl = wrap ? wrap.querySelector('.shopier-action-msg') : null;
			btn.disabled = true;
			if (msgEl) msgEl.textContent = 'Siliniyor…';
			postAction(btn.getAttribute('data-url'), btn.getAttribute('data-id'), msgEl, function () {
				setTimeout(function () { location.reload(); }, 700);
			}).finally(function () {
				btn.disabled = false;
			});
		});
	});

	function renderCategoryList(container, categories) {
		if (!container) return;
		container.innerHTML = '';
		categories.forEach(function (cat) {
			if (!cat || !cat.id) return;
			var pill = document.createElement('button');
			pill.type = 'button';
			pill.className = 'shopier-category-pill';
			pill.textContent = (cat.title || cat.id) + ' (' + cat.id + ')';
			pill.addEventListener('click', function () {
				var target = container.getAttribute('data-target');
				if (target) {
					var input = document.querySelector(target);
					if (input) input.value = cat.id;
				}
			});
			container.appendChild(pill);
		});
	}

	document.querySelectorAll('.shopier-load-categories').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var apiUrl = window.shopierCategoriesApiUrl;
			if (!apiUrl) return;
			var token = window.shopierAdminToken || window.adminToken || '';
			if (token) {
				apiUrl += (apiUrl.indexOf('?') >= 0 ? '&' : '?') + 'token=' + encodeURIComponent(token);
			}
			btn.disabled = true;
			var list = btn.getAttribute('data-list')
				? document.querySelector('.shopier-category-list[data-list="1"]')
				: document.querySelector('.shopier-category-list[data-target="' + btn.getAttribute('data-target') + '"]');
			if (list) list.textContent = 'Yükleniyor…';
			fetch(apiUrl, { credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res.success) {
						if (list) list.textContent = res.message || 'Kategori alınamadı';
						return;
					}
					renderCategoryList(list, res.categories || []);
				})
				.catch(function () {
					if (list) list.textContent = 'Kategori alınamadı';
				})
				.finally(function () {
					btn.disabled = false;
				});
		});
	});
})();
