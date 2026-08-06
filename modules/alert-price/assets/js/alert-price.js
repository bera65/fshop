$(document).on('submit', '#alertPrice, .alert-price-form', function (e) {
	e.preventDefault();

	var $form 		= $(this);
	var url 		= $form.data('api-url');
	var productId 	= $form.data('product-id') || $form.find('[name="selectedProductId"]').val();
	var $messageBox = $form.closest('.modal-body, .modal-content, form').find('[id^="alertPriceMessage"]').first();
	if (!$messageBox.length) {
		$messageBox = $('#alertPriceMessage');
	}
	var $submitBtn 	= $form.find('button[type="submit"]');
	var modalEl 	= $form.closest('.modal').get(0) || document.getElementById('priceModal');
	var email 		= $.trim($form.find('[name="userEmail"], [name="email"]').val());
	var price 		= $form.find('[name="price"], [name="target_price"]').val();

	if (!url || typeof csrfToken === 'undefined') {
		if ($messageBox.length) {
			$messageBox.removeClass('d-none alert-success').addClass('alert alert-danger');
			$messageBox.text('İstek gönderilemedi. Sayfayı yenileyip tekrar deneyin.');
		}
		return;
	}

	$submitBtn.prop('disabled', true);

	$.ajax({
		url: url,
		method: 'POST',
		dataType: 'json',
		data: {
			token: csrfToken,
			idProduct: productId,
			email: email,
			price: price
		}
	}).done(function (data) {
		if (!$messageBox.length) {
			return;
		}

		$messageBox.removeClass('d-none alert-success alert-danger');

		if (data.success) {
			$messageBox.addClass('alert alert-success').text(data.message || 'Talebiniz alındı.');
			$form[0].reset();

			if (modalEl && typeof bootstrap !== 'undefined') {
				setTimeout(function () {
					var modal = bootstrap.Modal.getInstance(modalEl);
					if (modal) {
						modal.hide();
					}
				}, 2000);
			}
		} else {
			$messageBox.addClass('alert alert-danger').text(data.message || 'İşlem başarısız.');
		}
	}).fail(function (xhr) {
		if (!$messageBox.length) {
			return;
		}

		var message = 'Bir hata oluştu. Lütfen tekrar deneyin.';

		if (xhr.responseJSON && xhr.responseJSON.message) {
			message = xhr.responseJSON.message;
		} else if (xhr.status === 403) {
			message = 'Geçersiz istek. Sayfayı yenileyip tekrar deneyin.';
		}

		$messageBox.removeClass('d-none alert-success').addClass('alert alert-danger').text(message);
	}).always(function () {
		$submitBtn.prop('disabled', false);
	});
});
