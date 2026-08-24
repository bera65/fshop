document.addEventListener('DOMContentLoaded', function () {
	var cfg = window.__adminOrderStatus || {};
	var apiUrl = cfg.apiUrl || '';
	var token = cfg.token || '';

	if (!apiUrl || !token) {
		return;
	}

	var statusClassPrefix = 'ps-status-select--';

	function applySelectClass(select, statusClass) {
		Array.prototype.slice.call(select.classList).forEach(function (className) {
			if (className.indexOf(statusClassPrefix) === 0) {
				select.classList.remove(className);
			}
		});

		select.classList.add(statusClassPrefix + (statusClass || 'default'));
	}

	document.querySelectorAll('.ps-order-status-select').forEach(function (select) {
		select.addEventListener('change', function () {
			var idOrder = parseInt(select.getAttribute('data-order-id') || '0', 10);
			var previousStatus = parseInt(select.getAttribute('data-current') || '0', 10);
			var newStatus = parseInt(select.value || '0', 10);

			if (!idOrder || !newStatus || newStatus === previousStatus) {
				select.value = String(previousStatus);
				return;
			}

			select.disabled = true;
			select.classList.add('is-loading');

			var body = new URLSearchParams();
			body.set('token', token);
			body.set('id_order', String(idOrder));
			body.set('status', String(newStatus));

			fetch(apiUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
					'X-Requested-With': 'XMLHttpRequest'
				},
				body: body.toString(),
				credentials: 'same-origin'
			})
				.then(function (res) {
					return res.json().catch(function () {
						throw new Error('Sunucu yanıtı okunamadı');
					}).then(function (data) {
						if (!res.ok) {
							throw new Error((data && data.message) || 'Durum güncellenemedi');
						}

						return data;
					});
				})
				.then(function (data) {
					if (!data || !data.success) {
						throw new Error((data && data.message) || 'Durum güncellenemedi');
					}

					select.setAttribute('data-current', String(newStatus));
					applySelectClass(select, data.status_class || 'default');
				})
				.catch(function (err) {
					select.value = String(previousStatus);
					if (window.AdminToast) {
						AdminToast.show(err.message || 'Durum güncellenemedi', 'danger');
					}
				})
				.finally(function () {
					select.disabled = false;
					select.classList.remove('is-loading');
				});
		});
	});
});
