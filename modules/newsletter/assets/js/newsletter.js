$(document).on('submit', '#footerNewsletterForm, .newsletter-tab-form, .fl-newsletter__form', function (e) {

	e.preventDefault();



	var $form = $(this);

	var url = $form.data('api-url');

	var email = $.trim($form.find('[name="email"]').val());

	var token = (typeof window.csrfToken !== 'undefined' && window.csrfToken)

		? window.csrfToken

		: (typeof csrfToken !== 'undefined' ? csrfToken : ($form.find('[name="token"]').val() || ''));



	if (!url) {

		return;

	}



	if (!token) {

		newsletterToast('Sayfayı yenileyip tekrar deneyin.', 'danger');

		return;

	}



	$.ajax({

		url: url,

		method: 'POST',

		dataType: 'json',

		headers: {

			'X-CSRF-TOKEN': token,

			'X-Requested-With': 'XMLHttpRequest'

		},

		data: {

			email: email,

			token: token

		}

	}).done(function (data) {

		newsletterToast(data.message || 'İşlem tamamlandı', data.success ? 'success' : 'danger');



		if (data.success) {

			$form[0].reset();

		}

	}).fail(function (xhr) {

		var message = 'Sunucuya bağlanılamadı';



		if (xhr.responseJSON && xhr.responseJSON.message) {

			message = xhr.responseJSON.message;

		} else if (xhr.status === 403) {

			message = 'Geçersiz istek. Sayfayı yenileyip tekrar deneyin.';

		}



		newsletterToast(message, 'danger');

	});

});



function newsletterToast(message, type) {

	type = (type === 'danger' || type === 'error') ? 'danger' : 'success';



	if (typeof window.showToast === 'function') {

		window.showToast(message, type);

		return;

	}



	if (typeof showToast === 'function') {

		showToast(message, type);

		return;

	}



	if (message) {

		window.alert(message);

	}

}


