(function ($) {
	'use strict';

	var authModalInstance = null;

	function getAuthModal() {
		var el = document.getElementById('authModal');
		if (!el) return null;
		if (!authModalInstance) {
			authModalInstance = bootstrap.Modal.getOrCreateInstance(el);
		}
		return authModalInstance;
	}

	function showAuthAlert($el, message, type) {
		if (!$el.length) return;
		$el.removeClass('d-none alert-danger alert-success alert-info')
			.addClass(type === 'success' ? 'alert-success' : 'alert-danger')
			.text(message);
	}

	function clearAuthAlerts() {
		$('#authLoginAlert, #authRegisterAlert').addClass('d-none').text('');
	}

	function openAuthModal(mode) {
		var modal = getAuthModal();
		if (!modal) {
			window.location.href = domain + (mode === 'register' ? 'register' : 'login');
			return;
		}
		clearAuthAlerts();
		if (mode === 'register') {
			var registerTab = document.getElementById('auth-register-tab');
			if (registerTab) bootstrap.Tab.getOrCreateInstance(registerTab).show();
		} else {
			var loginTab = document.getElementById('auth-login-tab');
			if (loginTab) bootstrap.Tab.getOrCreateInstance(loginTab).show();
		}
		modal.show();
	}

	$(document).on('click', '[data-auth-modal]', function (e) {
		e.preventDefault();
		openAuthModal($(this).data('auth-modal') || 'login');
	});

	$('#authModalLoginForm').on('submit', function (e) {
		e.preventDefault();
		var $alert = $('#authLoginAlert');
		$.post(typeof authApiUrl !== 'undefined' ? authApiUrl : (domain + 'api/auth.php'), {
			action: 'login',
			login: $('#authModalLoginPhone').val(),
			phone: $('#authModalLoginPhone').val(),
			password: $('#authModalLoginPassword').val(),
			remember: $('#authModalRemember').is(':checked') ? '1' : '0',
			token: csrfToken
		}).done(function (res) {
			if (res.success) {
				window.location.reload();
				return;
			}
			showAuthAlert($alert, res.message || 'Error', 'error');
		}).fail(function () {
			showAuthAlert($alert, 'Request failed', 'error');
		});
	});

	$('#authModalRegisterForm').on('submit', function (e) {
		e.preventDefault();
		var $alert = $('#authRegisterAlert');
		$.post(typeof authApiUrl !== 'undefined' ? authApiUrl : (domain + 'api/auth.php'), {
			action: 'register',
			full_name: $('#authModalRegisterName').val(),
			phone: $('#authModalRegisterPhone').val(),
			email: $('#authModalRegisterEmail').val(),
			password: $('#authModalRegisterPassword').val(),
			token: csrfToken
		}).done(function (res) {
			if (res.success) {
				window.location.reload();
				return;
			}
			showAuthAlert($alert, res.message || 'Error', 'error');
		}).fail(function () {
			showAuthAlert($alert, 'Request failed', 'error');
		});
	});
})(jQuery);
