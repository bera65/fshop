function accountRequest(data) {
  data.token = csrfToken;

  return $.ajax({
    url: accountApiUrl,
    method: 'POST',
    dataType: 'json',
    data: data
  });
}

function resetAddressForm(defaultName, defaultPhone) {
  $('#addressForm')[0].reset();
  $('#addressIdInput').val('0');
  $('#addressFormTitle').text('Yeni Adres Ekle');
  $('#cancelAddressEdit').addClass('d-none');
  $('#addressForm [name="full_name"]').val(defaultName || '');
  $('#addressForm [name="phone"]').val(defaultPhone || '');
}

$(document).on('submit', '#profileForm', function (e) {
  e.preventDefault();

  accountRequest({
    action: 'update_profile',
    full_name: $('#profileForm [name="full_name"]').val(),
    phone: $('#profileForm [name="phone"]').val(),
    email: $('#profileForm [name="email"]').val()
  }).done(function (data) {
    if (data.success) {
      showToast(data.message, 'success');
      if (data.user) {
        $('#overviewFullName').text(data.user.user_full_name);
        $('#overviewPhone').text(data.user.phone);
        $('#overviewEmail').text(data.user.email || 'Belirtilmemiş');
      }
    } else {
      showToast(data.message || 'Güncelleme başarısız', 'danger');
    }
  }).fail(function () {
    showToast('Sunucuya bağlanılamadı', 'danger');
  });
});

$(document).on('submit', '#passwordForm', function (e) {
  e.preventDefault();

  var newPassword = $('#passwordForm [name="new_password"]').val();
  var newPassword2 = $('#passwordForm [name="new_password2"]').val();

  if (newPassword !== newPassword2) {
    showToast('Yeni şifreler eşleşmiyor', 'danger');
    return;
  }

  accountRequest({
    action: 'update_password',
    current_password: $('#passwordForm [name="current_password"]').val(),
    new_password: newPassword
  }).done(function (data) {
    if (data.success) {
      showToast(data.message, 'success');
      $('#passwordForm')[0].reset();
    } else {
      showToast(data.message || 'Şifre güncellenemedi', 'danger');
    }
  }).fail(function () {
    showToast('Sunucuya bağlanılamadı', 'danger');
  });
});

$(document).on('submit', '#addressForm', function (e) {
  e.preventDefault();

  accountRequest({
    action: 'save_address',
    id_address: $('#addressIdInput').val(),
    label: $('#addressForm [name="label"]').val(),
    full_name: $('#addressForm [name="full_name"]').val(),
    phone: $('#addressForm [name="phone"]').val(),
    city: $('#addressForm [name="city"]').val(),
    district: $('#addressForm [name="district"]').val(),
    address_text: $('#addressForm [name="address_text"]').val(),
    is_default: $('#addressDefaultCheck').is(':checked') ? 1 : 0
  }).done(function (data) {
    if (data.success) {
      showToast(data.message, 'success');
      window.location.hash = 'addresses';
      window.location.reload();
    } else {
      showToast(data.message || 'Adres kaydedilemedi', 'danger');
    }
  }).fail(function () {
    showToast('Sunucuya bağlanılamadı', 'danger');
  });
});

$(document).on('click', '.edit-address', function () {
  var $btn = $(this);

  $('#addressIdInput').val($btn.data('id'));
  $('#addressFormTitle').text('Adresi Düzenle');
  $('#addressForm [name="label"]').val($btn.data('label'));
  $('#addressForm [name="full_name"]').val($btn.data('fullName'));
  $('#addressForm [name="phone"]').val($btn.data('phone'));
  $('#addressForm [name="city"]').val($btn.data('city'));
  $('#addressForm [name="district"]').val($btn.data('district'));
  $('#addressForm [name="address_text"]').val($btn.data('addressText'));
  $('#addressDefaultCheck').prop('checked', String($btn.data('isDefault')) === '1');
  $('#cancelAddressEdit').removeClass('d-none');

  var tab = document.getElementById('addresses-tab');
  if (tab) {
    bootstrap.Tab.getOrCreateInstance(tab).show();
  }

  $('html, body').animate({ scrollTop: $('#addressForm').offset().top - 80 }, 200);
});

$(document).on('click', '#cancelAddressEdit', function () {
  resetAddressForm(
    $('#profileForm [name="full_name"]').val() || $('#overviewFullName').text(),
    $('#profileForm [name="phone"]').val() || $('#overviewPhone').text()
  );
});

$(document).on('click', '.delete-address', function () {
  if (!window.confirm('Bu adresi silmek istediğinize emin misiniz?')) {
    return;
  }

  accountRequest({
    action: 'delete_address',
    id_address: $(this).data('id')
  }).done(function (data) {
    if (data.success) {
      showToast(data.message, 'success');
      window.location.reload();
    } else {
      showToast(data.message || 'Adres silinemedi', 'danger');
    }
  }).fail(function () {
    showToast('Sunucuya bağlanılamadı', 'danger');
  });
});

$(document).on('click', '.set-default-address', function () {
  accountRequest({
    action: 'set_default_address',
    id_address: $(this).data('id')
  }).done(function (data) {
    if (data.success) {
      showToast(data.message, 'success');
      window.location.reload();
    } else {
      showToast(data.message || 'İşlem başarısız', 'danger');
    }
  }).fail(function () {
    showToast('Sunucuya bağlanılamadı', 'danger');
  });
});

function syncCheckoutAddressFields() {
  var $selected = $('.checkout-address-radio:checked');
  var useSaved = $selected.length && String($selected.val()) !== '0';
  var $fields = $('#checkoutAddressFields');
  var $inputs = $('.checkout-field');

  if (!$fields.length) {
    return;
  }

  if (useSaved) {
    $('#checkoutCustomerName').val($selected.data('fullName'));
    $('#checkoutCustomerPhone').val($selected.data('phone'));
    $('#checkoutCity').val($selected.data('city'));
    $('#checkoutDistrict').val($selected.data('district'));
    $('#checkoutAddressText').val($selected.data('addressText'));
    $fields.addClass('is-readonly');
    $inputs.prop('readonly', true);
    $('#saveAddressBlock').addClass('d-none');
  } else {
    $fields.removeClass('is-readonly');
    $inputs.prop('readonly', false);
    $('#saveAddressBlock').removeClass('d-none');
  }
}

$(document).on('change', '.checkout-address-radio', syncCheckoutAddressFields);

$(document).on('change', '#saveAddressCheck', function () {
  if ($(this).is(':checked')) {
    $('#saveAddressExtra').removeClass('d-none');
  } else {
    $('#saveAddressExtra').addClass('d-none');
  }
});

function updateCheckoutTotals(data) {
  if (!data) return;

  $('#checkoutSubtotal').text(data.subtotal_formatted || '');
  $('#checkoutShipping').text(data.shipping > 0 ? data.shipping_formatted : 'Ücretsiz');
  $('#checkoutTotal').text(data.total_formatted || '');

  if (data.discount > 0) {
    $('#checkoutDiscountRow').removeClass('d-none');
    $('#checkoutDiscount').text('-' + data.discount_formatted);
  } else {
    $('#checkoutDiscountRow').addClass('d-none');
  }

  if (data.coupon_code) {
    $('#couponCodeInput').val(data.coupon_code);
  }
}

function couponRequest(action, code) {
  return $.ajax({
    url: typeof couponApiUrl !== 'undefined' ? couponApiUrl : '',
    method: 'POST',
    dataType: 'json',
    data: {
      action: action,
      code: code || '',
      token: csrfToken
    }
  });
}

$(document).on('click', '#applyCouponBtn', function () {
  var code = $('#couponCodeInput').val();

  couponRequest('apply', code).done(function (data) {
    if (data.success) {
      showToast(data.message, 'success');
      updateCheckoutTotals(data);
      location.reload();
    } else {
      showToast(data.message || 'Kupon uygulanamadı', 'danger');
    }
  }).fail(function () {
    showToast('Sunucuya bağlanılamadı', 'danger');
  });
});

$(document).on('click', '#removeCouponBtn', function () {
  couponRequest('remove').done(function (data) {
    if (data.success) {
      showToast(data.message, 'success');
      location.reload();
    }
  });
});

$(document).on('click', '.mark-notification-read', function () {
  var $btn = $(this);
  var $item = $btn.closest('.notification-item');

  accountRequest({
    action: 'mark_notification_read',
    id_notification: $btn.data('id')
  }).done(function (data) {
    if (data.success) {
      $item.removeClass('is-unread');
      $btn.remove();
      if (data.unread_count === 0) {
        $('#notificationTabBadge').remove();
        $('#markAllNotificationsRead').remove();
      }
    }
  });
});

$(document).on('click', '#markAllNotificationsRead', function () {
  accountRequest({ action: 'mark_all_notifications_read' }).done(function (data) {
    if (data.success) {
      $('.notification-item').removeClass('is-unread');
      $('.mark-notification-read').remove();
      $('#notificationTabBadge').remove();
      $('#markAllNotificationsRead').remove();
      showToast(data.message, 'success');
    }
  });
});

$(function () {
  if ($('.checkout-address-radio').length) {
    syncCheckoutAddressFields();
  }

  if (window.location.hash === '#addresses') {
    var tab = document.getElementById('addresses-tab');
    if (tab) {
      bootstrap.Tab.getOrCreateInstance(tab).show();
    }
  }

  if (window.location.hash === '#notifications') {
    var notifTab = document.getElementById('notifications-tab');
    if (notifTab) {
      bootstrap.Tab.getOrCreateInstance(notifTab).show();
    }
  }
});
