(function ($) {
	'use strict';

	function postAccount(action, data) {
		return $.ajax({
			url: typeof accountApiUrl !== 'undefined' ? accountApiUrl : (domain + 'api/account.php'),
			method: 'POST',
			dataType: 'json',
			data: $.extend({ action: action, token: csrfToken }, data || {})
		});
	}

	function showToast(message, type) {
		var $toast = $('#tostAlert');
		if (!$toast.length) {
			alert(message);
			return;
		}
		$toast.removeClass('text-bg-success text-bg-danger text-bg-info');
		$toast.addClass(type === 'error' ? 'text-bg-danger' : 'text-bg-success');
		$toast.find('.toast-body').text(message);
		var toast = bootstrap.Toast.getOrCreateInstance($toast[0]);
		toast.show();
	}

	// Password visibility
	$(document).on('click', '.auth-password-toggle', function () {
		var target = $(this).data('target');
		var $input = $(target);
		if (!$input.length) return;
		var isPassword = $input.attr('type') === 'password';
		$input.attr('type', isPassword ? 'text' : 'password');
	});

	// Account tabs
	$(document).on('click', '[data-account-tab]', function () {
		var tab = $(this).data('account-tab');
		$('[data-account-tab]').removeClass('is-active');
		$(this).addClass('is-active');
		$('[data-account-panel]').removeClass('is-active');
		$('[data-account-panel="' + tab + '"]').addClass('is-active');
	});

	$(function () {
		var params = new URLSearchParams(window.location.search);
		if (params.get('tab')) {
			$('[data-account-tab="' + params.get('tab') + '"]').trigger('click');
		} else if (window.location.hash === '#notifications') {
			$('[data-account-tab="notifications"]').trigger('click');
		}
	});

	// Profile
	$('#profileForm').on('submit', function (e) {
		e.preventDefault();
		var $form = $(this);
		postAccount('update_profile', {
			full_name: $form.find('[name="full_name"]').val(),
			phone: $form.find('[name="phone"]').val(),
			email: $form.find('[name="email"]').val()
		}).done(function (res) {
			if (res.success) {
				showToast(res.message || 'OK');
				if (res.user) {
					$('#sidebarFullName, #welcomeFullName, #overviewFullName').text(res.user.user_full_name || '');
					$('#sidebarEmail, #overviewEmail').text(res.user.email || '');
					$('#profileFullName').val(res.user.user_full_name || '');
					$('#profilePhone').val(res.user.phone || '');
					$('#profileEmail').val(res.user.email || '');
					if (res.user.user_full_name) {
						$('#accountAvatar').text(res.user.user_full_name.charAt(0).toUpperCase());
					}
				}
			} else {
				showToast(res.message || 'Error', 'error');
			}
		}).fail(function () {
			showToast('Connection error', 'error');
		});
	});

	// Password
	$('#passwordForm').on('submit', function (e) {
		e.preventDefault();
		var $form = $(this);
		var p1 = $form.find('[name="new_password"]').val();
		var p2 = $form.find('[name="new_password2"]').val();
		if (p1 !== p2) {
			showToast('Passwords do not match', 'error');
			return;
		}
		postAccount('update_password', {
			current_password: $form.find('[name="current_password"]').val(),
			new_password: p1
		}).done(function (res) {
			showToast(res.message || (res.success ? 'OK' : 'Error'), res.success ? 'success' : 'error');
			if (res.success) $form[0].reset();
		});
	});

	// Address form
	$('#addressForm').on('submit', function (e) {
		e.preventDefault();
		var $form = $(this);
		postAccount('save_address', $form.serialize()).done(function (res) {
			showToast(res.message || (res.success ? 'OK' : 'Error'), res.success ? 'success' : 'error');
			if (res.success) window.location.reload();
		});
	});

	$(document).on('click', '.delete-address', function () {
		if (!confirm('Delete this address?')) return;
		postAccount('delete_address', { id_address: $(this).data('id') }).done(function (res) {
			if (res.success) window.location.reload();
			else showToast(res.message, 'error');
		});
	});

	$(document).on('click', '.set-default-address', function () {
		postAccount('set_default_address', { id_address: $(this).data('id') }).done(function (res) {
			if (res.success) window.location.reload();
		});
	});

	$(document).on('click', '.edit-address', function () {
		var $btn = $(this);
		$('#addressIdInput').val($btn.data('id'));
		$('#addressFormTitle').text('Edit Address');
		$('#cancelAddressEdit').removeClass('d-none');
		var $form = $('#addressForm');
		$form.find('[name="label"]').val($btn.data('label'));
		$form.find('[name="full_name"]').val($btn.data('fullName'));
		$form.find('[name="phone"]').val($btn.data('phone'));
		$form.find('[name="city"]').val($btn.data('city'));
		$form.find('[name="district"]').val($btn.data('district'));
		$form.find('[name="address_text"]').val($btn.data('addressText'));
		$form.find('[name="company_name"]').val($btn.data('companyName'));
		$form.find('[name="tax_office"]').val($btn.data('taxOffice'));
		$form.find('[name="tax_number"]').val($btn.data('taxNumber'));
		$('#addressDefaultCheck').prop('checked', parseInt($btn.data('isDefault'), 10) === 1);
		$('html, body').animate({ scrollTop: $('#addressForm').offset().top - 80 }, 300);
	});

	$('#cancelAddressEdit').on('click', function () {
		$('#addressIdInput').val(0);
		$('#addressForm')[0].reset();
		$('#addressFormTitle').text('Add New Address');
		$(this).addClass('d-none');
	});

	// Notifications
	$(document).on('click', '.mark-notification-read', function () {
		var id = $(this).data('id');
		postAccount('mark_notification_read', { id_notification: id }).done(function (res) {
			if (res.success) window.location.reload();
		});
	});

	$('#markAllNotificationsRead').on('click', function () {
		postAccount('mark_all_notifications_read').done(function () {
			window.location.reload();
		});
	});

	if (window.location.hash === '#notifications') {
		$('[data-account-tab="notifications"]').trigger('click');
	}

	// Logout
	$('#logoutBtn').on('click', function () {
		$.post(typeof authApiUrl !== 'undefined' ? authApiUrl : (domain + 'api/auth.php'), {
			action: 'logout',
			token: csrfToken
		}).always(function () {
			window.location.href = domain;
		});
	});

	// Checkout address toggle
	function syncCheckoutAddressFields() {
		var $checked = $('.checkout-address-radio:checked');
		var useNew = !$checked.length || parseInt($checked.val(), 10) === 0;
		var $fields = $('#checkoutAddressFields');
		if (!$fields.length) return;

		if (useNew) {
			$fields.show();
			$fields.find('.checkout-field').prop('disabled', false);
			$('#saveAddressBlock').show();
		} else {
			var $radio = $checked;
			$('#checkoutCustomerName').val($radio.data('fullName') || '');
			$('#checkoutCustomerPhone').val($radio.data('phone') || '');
			$('#checkoutCity').val($radio.data('city') || '');
			$('#checkoutDistrict').val($radio.data('district') || '');
			$('#checkoutAddressText').val($radio.data('addressText') || '');
			$fields.show();
			$fields.find('.checkout-field').prop('disabled', false);
			$('#saveAddressBlock').hide();
		}
	}

	$(document).on('change', '.checkout-address-radio', syncCheckoutAddressFields);
	$('#saveAddressCheck').on('change', function () {
		$('#saveAddressExtra').toggleClass('d-none', !this.checked);
	});
	syncCheckoutAddressFields();

	// Coupon
	function refreshCheckoutTotals(data) {
		if (!data) return;
		if (data.subtotal_formatted) $('#checkoutSubtotal').text(data.subtotal_formatted);
		var $promoLines = $('#checkoutPromotionLines');
		if ($promoLines.length) {
			if (data.promotion_lines && data.promotion_lines.length) {
				$promoLines.html(data.promotion_lines.map(function (line) {
					return '<div class="checkout-summary__row checkout-summary__row--discount"><span>' +
						$('<div>').text(line.name || '').html() +
						'</span><span>-' + (line.discount_formatted || '') + '</span></div>';
				}).join(''));
			} else if ((data.promotion_discount || 0) > 0) {
				$promoLines.html(
					'<div class="checkout-summary__row checkout-summary__row--discount"><span>' +
					$('<div>').text(data.promotion_name || '').html() +
					'</span><span>-' + (data.promotion_discount_formatted || '') + '</span></div>'
				);
			} else {
				$promoLines.empty();
			}
		} else if (data.promotion_discount > 0) {
			$('#checkoutPromotionRow').removeClass('d-none');
			$('#checkoutPromotionName').text(data.promotion_name || '');
			$('#checkoutPromotion').text('-' + data.promotion_discount_formatted);
		} else {
			$('#checkoutPromotionRow').addClass('d-none');
		}
		if (data.coupon_discount > 0) {
			$('#checkoutCouponDiscountRow').removeClass('d-none');
			$('#checkoutCouponLabel').text('Kupon: ' + (data.coupon_code || ''));
			$('#checkoutCouponDiscount').text('-' + data.coupon_discount_formatted);
		} else {
			$('#checkoutCouponDiscountRow').addClass('d-none');
		}
		if (data.discount > 0) {
			$('#checkoutDiscountRow').removeClass('d-none');
			$('#checkoutDiscount').text('-' + data.discount_formatted);
		} else {
			$('#checkoutDiscountRow').addClass('d-none');
		}
		if (data.payment_discount > 0) {
			$('#checkoutPaymentDiscountRow').removeClass('d-none');
			$('#checkoutPaymentDiscountLabel').text(data.payment_discount_label || 'Ödeme indirimi');
			$('#checkoutPaymentDiscount').text('-' + (data.payment_discount_formatted || ''));
		} else {
			$('#checkoutPaymentDiscountRow').addClass('d-none');
		}
		if (data.shipping_formatted) $('#checkoutShipping').text(data.shipping_formatted);
		if (data.total_formatted) {
			$('#checkoutTotal').text(data.total_formatted);
			$('#checkoutSubmitTotal').text(data.total_formatted);
		}
		if (data.cargo_options && data.cargo_options.length) {
			data.cargo_options.forEach(function (opt) {
				$('.checkout-cargo-fee[data-cargo-id="' + opt.id_cargo + '"]').text(opt.fee_formatted || '');
			});
		}
	}

	$(document).on('change', 'input[name="id_cargo"]', function () {
		var idCargo = $(this).val();
		$.post(typeof couponApiUrl !== 'undefined' ? couponApiUrl : (domain + 'api/coupon.php'), {
			action: 'set_cargo',
			id_cargo: idCargo,
			token: csrfToken
		}).done(function (res) {
			if (res.success) refreshCheckoutTotals(res);
		});
	});

	$(document).on('change', 'input[name="payment_method"]', function () {
		var method = $(this).val();
		$.post(typeof couponApiUrl !== 'undefined' ? couponApiUrl : (domain + 'api/coupon.php'), {
			action: 'set_payment',
			payment_method: method,
			token: csrfToken
		}).done(function (res) {
			if (res.success) refreshCheckoutTotals(res);
		});
	});

	$('#applyCouponBtn').on('click', function () {
		var code = $('#couponCodeInput').val();
		$.post(typeof couponApiUrl !== 'undefined' ? couponApiUrl : (domain + 'api/coupon.php'), {
			action: 'apply',
			code: code,
			token: csrfToken
		}).done(function (res) {
			showToast(res.message || (res.success ? 'OK' : 'Error'), res.success ? 'success' : 'error');
			if (res.success) refreshCheckoutTotals(res);
		});
	});

	$('#removeCouponBtn').on('click', function () {
		$.post(typeof couponApiUrl !== 'undefined' ? couponApiUrl : (domain + 'api/coupon.php'), {
			action: 'remove',
			token: csrfToken
		}).done(function (res) {
			if (res.success) {
				$('#couponCodeInput').val('');
				refreshCheckoutTotals(res);
				window.location.reload();
			}
		});
	});

})(jQuery);
