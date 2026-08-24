window.__fshopAlertPriceBound = true;

$(document).on('submit', '#alertPrice, .alert-price-form', function (e) {
	e.preventDefault();

	var $form = $(this);
	var url = $form.data('api-url') || $form.attr('data-api-url');
	var productId = $form.data('product-id') || $form.find('[name="selectedProductId"]').val();
	var $messageBox = $form.find('[id^="alertPriceMessage"]').first();
	if (!$messageBox.length) {
		$messageBox = $('#alertPriceMessage');
	}
	var $submitBtn = $form.find('button[type="submit"]');
	var modalEl = $form.closest('.modal').get(0) || document.getElementById('priceModal');
	var email = $.trim($form.find('[name="userEmail"], [name="email"]').val());
	var price = $form.find('[name="price"], [name="target_price"]').val();
	var token = (typeof window.csrfToken !== 'undefined' && window.csrfToken)
		? window.csrfToken
		: (typeof csrfToken !== 'undefined' ? csrfToken : ($form.find('[name="token"]').val() || ''));

	if (!url || !token) {
		alertPriceNotify('Sayfayı yenileyip tekrar deneyin.', false, $messageBox);
		return;
	}

	$submitBtn.prop('disabled', true);

	$.ajax({
		url: url,
		method: 'POST',
		dataType: 'json',
		headers: {
			'X-CSRF-TOKEN': token,
			'X-Requested-With': 'XMLHttpRequest'
		},
		data: {
			token: token,
			idProduct: productId,
			email: email,
			price: price
		}
	}).done(function (data) {
		var ok = !!(data && data.success);
		var message = (data && data.message) ? data.message : (ok ? 'Talebiniz alındı.' : 'İşlem başarısız.');
		alertPriceNotify(message, ok, $messageBox);

		if (ok) {
			$form[0].reset();
			if (modalEl && typeof bootstrap !== 'undefined') {
				setTimeout(function () {
					var modal = bootstrap.Modal.getInstance(modalEl);
					if (modal) {
						modal.hide();
					}
				}, 1600);
			}
		}
	}).fail(function (xhr) {
		var message = 'Bir hata oluştu. Lütfen tekrar deneyin.';
		if (xhr.responseJSON && xhr.responseJSON.message) {
			message = xhr.responseJSON.message;
		} else if (xhr.status === 403) {
			message = 'Geçersiz istek. Sayfayı yenileyip tekrar deneyin.';
		}
		alertPriceNotify(message, false, $messageBox);
	}).always(function () {
		$submitBtn.prop('disabled', false);
	});
});

function alertPriceNotify(message, success, $messageBox) {
	var type = success ? 'success' : 'danger';

	if (typeof window.showToast === 'function') {
		window.showToast(message, type);
	} else if (typeof showToast === 'function') {
		showToast(message, type);
	} else if (message) {
		window.alert(message);
	}

	if ($messageBox && $messageBox.length) {
		$messageBox
			.removeClass('d-none alert-success alert-danger')
			.addClass(success ? 'alert alert-success' : 'alert alert-danger')
			.text(message)
			.show();
	}
}
