(function () {
	'use strict';

	var cfg = window.customerNotifyAdminConfig || {};
	var form = document.getElementById('customerNotifyForm');
	var scopeAll = document.getElementById('cnScopeAll');
	var scopeSelected = document.getElementById('cnScopeSelected');
	var selectedWrap = document.querySelector('.cn-selected-wrap');
	var queryInput = document.getElementById('cnCustomerQuery');
	var resultsBox = document.querySelector('.cn-customer-results');
	var selectedBox = document.querySelector('.cn-selected-list');
	var selected = {};

	function toggleScope() {
		if (!selectedWrap) {
			return;
		}

		if (scopeSelected && scopeSelected.checked) {
			selectedWrap.classList.remove('d-none');
		} else {
			selectedWrap.classList.add('d-none');
		}
	}

	function renderSelected() {
		if (!selectedBox || !form) {
			return;
		}

		selectedBox.innerHTML = '';
		Object.keys(selected).forEach(function (id) {
			var item = selected[id];
			var wrap = document.createElement('span');
			wrap.className = 'badge text-bg-light border me-1 mb-1 d-inline-flex align-items-center gap-1';
			wrap.innerHTML = '<span>' + escapeHtml(item.label) + '</span>'
				+ '<button type="button" class="btn-close" aria-label="Kaldır" data-id="' + id + '"></button>';
			selectedBox.appendChild(wrap);

			var input = document.createElement('input');
			input.type = 'hidden';
			input.name = 'user_ids[]';
			input.value = id;
			form.appendChild(input);
		});
	}

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.textContent = text || '';
		return div.innerHTML;
	}

	function addCustomer(item) {
		if (!item || !item.id_user) {
			return;
		}

		selected[String(item.id_user)] = item;
		renderSelected();
	}

	function searchCustomers(q) {
		if (!resultsBox || !cfg.searchUrl) {
			return;
		}

		if (q.length < 2) {
			resultsBox.innerHTML = '';
			return;
		}

		var body = new URLSearchParams();
		body.append('token', cfg.token || '');
		body.append('q', q);

		fetch(cfg.searchUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: body.toString(),
			credentials: 'same-origin',
		})
			.then(function (res) { return res.json(); })
			.then(function (data) {
				resultsBox.innerHTML = '';

				if (!data || !data.success || !data.items || !data.items.length) {
					resultsBox.innerHTML = '<div class="list-group-item small text-muted">Sonuç yok</div>';
					return;
				}

				data.items.forEach(function (item) {
					var btn = document.createElement('button');
					btn.type = 'button';
					btn.className = 'list-group-item list-group-item-action';
					btn.textContent = item.label || item.full_name || ('#' + item.id_user);
					btn.addEventListener('click', function () {
						addCustomer(item);
						resultsBox.innerHTML = '';
						if (queryInput) {
							queryInput.value = '';
						}
					});
					resultsBox.appendChild(btn);
				});
			})
			.catch(function () {
				resultsBox.innerHTML = '<div class="list-group-item small text-danger">Arama başarısız</div>';
			});
	}

	if (scopeAll) {
		scopeAll.addEventListener('change', toggleScope);
	}

	if (scopeSelected) {
		scopeSelected.addEventListener('change', toggleScope);
	}

	if (queryInput) {
		var timer = null;
		queryInput.addEventListener('input', function () {
			clearTimeout(timer);
			timer = setTimeout(function () {
				searchCustomers(queryInput.value.trim());
			}, 250);
		});
	}

	if (selectedBox) {
		selectedBox.addEventListener('click', function (e) {
			var btn = e.target.closest('.btn-close');

			if (!btn) {
				return;
			}

			delete selected[String(btn.getAttribute('data-id'))];
			renderSelected();
		});
	}

	if (form) {
		form.addEventListener('submit', function () {
			form.querySelectorAll('input[name="user_ids[]"]').forEach(function (el) {
				el.remove();
			});
			renderSelected();
		});
	}

	toggleScope();
})();
