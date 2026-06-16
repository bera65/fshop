function showCart() {
  $('.cart').addClass('show');
  $('body').addClass('cartShow');
}

function hideCart() {
  $('.cart').removeClass('show');
  $('body').removeClass('cartShow');
}

function escapeHtml(text) {
  return $('<div>').text(text).html();
}

function renderCartItems(items) {
  if (!items || !items.length) {
    return '<p class="cart-empty text-muted text-center py-4 mb-0">Sepetiniz boş</p>';
  }

  return items.map(function (item) {
    return (
      '<div class="cart-item" data-id="' + item.id_product + '" data-max-qty="' + (item.max_qty || item.stock || 99) + '">' +
        '<a href="' + item.url + '" class="cart-item-image">' +
          '<img src="' + item.image_url + '" alt="' + escapeHtml(item.product_name) + '">' +
        '</a>' +
        '<div class="cart-item-info">' +
          '<a href="' + item.url + '" class="cart-item-name">' + escapeHtml(item.product_name) + '</a>' +
          '<div class="cart-item-price">' + item.price_formatted + '</div>' +
          '<div class="cart-item-actions">' +
            '<button type="button" class="cart-qty-btn" data-action="decrease" data-id="' + item.id_product + '">-</button>' +
            '<span class="cart-qty-value">' + item.qty + '</span>' +
            '<button type="button" class="cart-qty-btn" data-action="increase" data-id="' + item.id_product + '">+</button>' +
            '<button type="button" class="cart-remove-btn" data-id="' + item.id_product + '">Kaldır</button>' +
          '</div>' +
        '</div>' +
        '<div class="cart-item-total">' + item.line_total_formatted + '</div>' +
      '</div>'
    );
  }).join('');
}

function updateCartUI(data) {
  var count = data.count || 0;

  $('#cartBody, #cartPageList').each(function () {
    if ($(this).length) {
      $(this).html(renderCartItems(data.items));
    }
  });

  $('#cartTotal, #cartPageTotal').text(data.total_formatted || '₺0,00');
  $('#cartCountLabel').text(count);

  $('#items, #mobileCartBadge').text(count);
  if (count > 0) {
    $('#items, #mobileCartBadge').removeClass('d-none');
  } else {
    $('#items, #mobileCartBadge').addClass('d-none');
  }

  if (data.empty) {
    $('#cartClearBtn, #cartPageClearBtn').hide();
    if ($('#cartPageList').length && !$('#cartPageList').children().length) {
      location.reload();
    }
  } else {
    $('#cartClearBtn, #cartPageClearBtn').show();
  }
}

function cartRequest(action, idProduct, qty) {
  return $.ajax({
    url: cartApiUrl,
    method: 'POST',
    dataType: 'json',
    data: {
      action: action,
      id_product: idProduct || 0,
      qty: qty || 1,
      token: csrfToken
    }
  }).done(function (data) {
    if (data.success) {
      updateCartUI(data);
      if (action === 'add') {
        showToast(data.message || 'Sepete eklendi', 'success');
        showCart();
      }
    } else {
      showToast(data.message || 'Bir hata oluştu', 'danger');
    }
  }).fail(function () {
    showToast('Sunucuya bağlanılamadı', 'danger');
  });
}

$(document).on('click', '.addtocart', function () {
  var idProduct = $(this).data('id');
  var qty = 1;
  var qtyInput = document.getElementById('qty-input');

  if (qtyInput) {
    qty = parseInt(qtyInput.value, 10) || 1;
  }

  cartRequest('add', idProduct, qty);
});

$(document).on('click', '.cart-qty-btn', function () {
  var idProduct = $(this).data('id');
  var action = $(this).data('action');
  var $item = $(this).closest('.cart-item');
  var currentQty = parseInt($item.find('.cart-qty-value').text(), 10) || 1;
  var maxQty = parseInt($item.data('max-qty'), 10) || 99;
  var newQty = action === 'increase' ? currentQty + 1 : currentQty - 1;

  if (action === 'increase' && newQty > maxQty) {
    showToast('Stok sınırına ulaşıldı (' + maxQty + ' adet)', 'warning');
    return;
  }

  cartRequest('update', idProduct, newQty);
});

$(document).on('click', '.cart-remove-btn', function () {
  cartRequest('remove', $(this).data('id'));
});

$(document).on('click', '#cartClearBtn, #cartPageClearBtn', function () {
  $.ajax({
    url: cartApiUrl,
    method: 'POST',
    dataType: 'json',
    data: { action: 'clear', token: csrfToken }
  }).done(function (data) {
    updateCartUI(data);
    showToast(data.message || 'Sepet temizlendi', 'success');
    if ($('#cartPageList').length) {
      location.reload();
    }
  });
});

$(document).on('click', '.cartHide', function () {
  hideCart();
});

function favoriteRequest(action, idProduct) {
  return $.ajax({
    url: favoriteApiUrl,
    method: 'POST',
    dataType: 'json',
    data: {
      action: action,
      id_product: idProduct,
      token: csrfToken
    }
  });
}

function openLoginModal() {
  var loginModalEl = document.getElementById('loginModal');
  if (loginModalEl) {
    bootstrap.Modal.getOrCreateInstance(loginModalEl).show();
  }
}

$(document).on('click', '.toggle-favorite', function (e) {
  e.preventDefault();
  var btn = $(this);
  var idProduct = btn.data('id');

  favoriteRequest('toggle', idProduct).done(function (data) {
    if (data.login_required) {
      showToast(data.message, 'danger');
      openLoginModal();
      return;
    }

    if (data.success) {
      btn.toggleClass('active', data.is_favorite);
      $('.toggle-favorite[data-id="' + idProduct + '"]').toggleClass('active', data.is_favorite);
      showToast(data.message, 'success');

      if ($('.remove-favorite[data-id="' + idProduct + '"]').length && !data.is_favorite) {
        btn.closest('.col-6, .col-md-4, .col-lg-3').fadeOut(function () {
          $(this).remove();
        });
      }
    } else {
      showToast(data.message || 'İşlem başarısız', 'danger');
    }
  }).fail(function () {
    showToast('Sunucuya bağlanılamadı', 'danger');
  });
});

$(document).on('click', '.remove-favorite', function (e) {
  e.preventDefault();
  var btn = $(this);
  var idProduct = btn.data('id');

  favoriteRequest('remove', idProduct).done(function (data) {
    if (data.success) {
      showToast(data.message, 'success');
      btn.closest('.col-6, .col-md-4, .col-lg-3').fadeOut(function () {
        $(this).remove();
      });
    }
  }).fail(function () {
    showToast('Sunucuya bağlanılamadı', 'danger');
  });
});

$(document).on('input', '.phone-input', function () {

  let value = $(this).val().replace(/\D/g, '');

  if (!value.startsWith('0')) value = '0' + value;
  if (!value.startsWith('05')) value = '05' + value.slice(2);

  value = value.substring(0, 11);

  let formatted = value
    .replace(/^(\d{4})(\d{3})(\d{2})(\d{2})$/, '$1 $2 $3 $4')
    .replace(/^(\d{4})(\d{3})(\d{2})$/, '$1 $2 $3')
    .replace(/^(\d{4})(\d{3})$/, '$1 $2');

  $(this).val(formatted);
});
$(document).on('click', '#togglePassword, .auth-password-toggle', function () {
  var target = $(this).data('target');
  var input = target ? $(target) : $('#passwordInput');

  if (input.attr('type') === 'password') {
    input.attr('type', 'text');
  } else {
    input.attr('type', 'password');
  }
});

function authRequest(data) {
  data.token = csrfToken;

  return $.ajax({
    url: authApiUrl,
    method: 'POST',
    dataType: 'json',
    data: data
  });
}

function authFailMessage(xhr) {
  if (xhr.responseJSON && xhr.responseJSON.message) {
    return xhr.responseJSON.message;
  }

  if (xhr.responseText) {
    try {
      var data = JSON.parse(xhr.responseText);
      if (data.message) {
        return data.message;
      }
    } catch (e) {}
  }

  return 'Sunucuya bağlanılamadı';
}

function switchAuthModal(showRegister) {
  var loginModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('loginModal'));
  var registerModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('registerModal'));

  if (showRegister) {
    loginModal.hide();
    registerModal.show();
  } else {
    registerModal.hide();
    loginModal.show();
  }
}

$(document).on('click', '#showRegisterModal', function () {
  switchAuthModal(true);
});

$(document).on('click', '#showLoginModal', function () {
  switchAuthModal(false);
});

$(document).on('submit', '#loginForm', function (e) {
  e.preventDefault();

  authRequest({
    action: 'login',
    phone: $('#phoneInput').val(),
    password: $('#passwordInput').val(),
    remember: $('#rememberMe').is(':checked') ? 1 : 0
  }).done(function (data) {
    if (data.success) {
      showToast(data.message, 'success');
      window.location.href = data.redirect || domain;
    } else {
      showToast(data.message || 'Giriş başarısız', 'danger');
    }
  }).fail(function (xhr) {
    showToast(authFailMessage(xhr), 'danger');
  });
});

$(document).on('submit', '#registerForm', function (e) {
  e.preventDefault();

  var password = $('#registerPasswordInput').val();
  var password2 = $('#registerPassword2Input').val();

  if (password !== password2) {
    showToast('Şifreler eşleşmiyor', 'danger');
    return;
  }

  authRequest({
    action: 'register',
    full_name: $('#registerNameInput').val(),
    phone: $('#registerPhoneInput').val(),
    email: $('#registerEmailInput').val(),
    password: password
  }).done(function (data) {
    if (data.success) {
      showToast(data.message, 'success');
      window.location.href = data.redirect || domain;
    } else {
      showToast(data.message || 'Kayıt başarısız', 'danger');
    }
  }).fail(function (xhr) {
    showToast(authFailMessage(xhr), 'danger');
  });
});

$(document).on('submit', '#loginPageForm', function (e) {
  e.preventDefault();

  var $btn = $('#loginPageSubmit').prop('disabled', true);

  authRequest({
    action: 'login',
    phone: $('#loginPagePhone').val(),
    password: $('#loginPagePassword').val(),
    remember: $('#loginPageRemember').is(':checked') ? 1 : 0
  }).done(function (data) {
    if (data.success) {
      showToast(data.message, 'success');
      window.location.href = data.redirect || domain;
      return;
    }
    showToast(data.message || 'Giriş başarısız', 'danger');
    $btn.prop('disabled', false);
  }).fail(function (xhr) {
    showToast(authFailMessage(xhr), 'danger');
    $btn.prop('disabled', false);
  });
});

$(document).on('submit', '#registerPageForm', function (e) {
  e.preventDefault();

  var password = $('#registerPagePassword').val();
  var password2 = $('#registerPagePassword2').val();

  if (password !== password2) {
    showToast('Şifreler eşleşmiyor', 'danger');
    return;
  }

  var $btn = $('#registerPageSubmit').prop('disabled', true);

  authRequest({
    action: 'register',
    full_name: $('#registerPageName').val(),
    phone: $('#registerPagePhone').val(),
    email: $('#registerPageEmail').val(),
    password: password
  }).done(function (data) {
    if (data.success) {
      showToast(data.message, 'success');
      window.location.href = data.redirect || domain;
      return;
    }
    showToast(data.message || 'Kayıt başarısız', 'danger');
    $btn.prop('disabled', false);
  }).fail(function (xhr) {
    showToast(authFailMessage(xhr), 'danger');
    $btn.prop('disabled', false);
  });
});

$(document).on('click', '#logoutBtn', function () {
  authRequest({ action: 'logout' }).done(function (data) {
    showToast(data.message || 'Çıkış yapıldı', 'success');
    window.location.href = domain;
  });
});

$(document).on('click', '#logoutBtnHeader', function () {
  authRequest({ action: 'logout' }).done(function (data) {
    showToast(data.message || 'Çıkış yapıldı', 'success');
    window.location.href = domain;
  });
});

$(document).on('click', '#openRegisterFromMenu', function (e) {
  e.preventDefault();
  $('.header-tool-dropdown').removeClass('is-open');
  switchAuthModal(true);
});

(function () {
  var accountBtn = document.getElementById('accountMenuBtn');
  var accountDropdown = accountBtn ? accountBtn.closest('.header-tool-dropdown') : null;

  if (!accountBtn || !accountDropdown) {
    return;
  }

  accountBtn.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    accountDropdown.classList.toggle('is-open');
    accountBtn.setAttribute('aria-expanded', accountDropdown.classList.contains('is-open') ? 'true' : 'false');
  });

  document.addEventListener('click', function (e) {
    if (!accountDropdown.contains(e.target)) {
      accountDropdown.classList.remove('is-open');
      accountBtn.setAttribute('aria-expanded', 'false');
    }
  });

  accountDropdown.addEventListener('mouseenter', function () {
    if (window.innerWidth >= 992) {
      accountDropdown.classList.add('is-open');
      accountBtn.setAttribute('aria-expanded', 'true');
    }
  });

  accountDropdown.addEventListener('mouseleave', function () {
    if (window.innerWidth >= 992) {
      accountDropdown.classList.remove('is-open');
      accountBtn.setAttribute('aria-expanded', 'false');
    }
  });
})();

function showToast(message, cl) {
	$('#tostAlert').removeClass('danger');
	$('#tostAlert').removeClass('success');
    var toastEl = document.getElementById('tostAlert');
    $('#tostAlert').addClass(cl);
    $(toastEl).find('.toast-body').html(message);
    var toast = new bootstrap.Toast(toastEl);
    toast.show();
}
let deferredPrompt;
const pwaWrapper = document.getElementById('pwaWrapper');

if (pwaWrapper) {
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    pwaWrapper.setAttribute('style', 'display: block !important');
  });
}

const installBtn = document.getElementById('installBtn');
if (installBtn) {
  installBtn.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    if (outcome === 'accepted' && pwaWrapper) {
      pwaWrapper.setAttribute('style', 'display: none !important');
    }
    deferredPrompt = null;
  });
}

$('a').on('click', function() {

    var href = $(this).attr('href');

    if (
        href &&
        href !== '#' &&
        !href.startsWith('javascript:') &&
        !$(this).attr('data-bs-toggle')
    ) {
        $('#attrLoading').show();
    }

});

window.addEventListener('load', function () {
  var params = new URLSearchParams(window.location.search);
  if (params.get('login') === '1' && !isLoggedIn) {
    window.location.replace(domain + 'login');
  }

  initProductListCarousels();
});

function initProductListCarousels() {
  $('.product-list-scroll-wrap').each(function () {
    var $wrap = $(this);

    if ($wrap.data('plist-init')) {
      return;
    }

    $wrap.data('plist-init', true);

    var $scroll = $wrap.find('.product-list-scroll');
    var $prev = $wrap.find('.product-list-nav--prev');
    var $next = $wrap.find('.product-list-nav--next');
    var scrollEl = $scroll[0];

    if (!scrollEl || !$prev.length || !$next.length) {
      return;
    }

    function hasOverflow() {
      return scrollEl.scrollWidth > scrollEl.clientWidth + 2;
    }

    function updateNav() {
      if (!hasOverflow()) {
        $prev.add($next).addClass('is-hidden').prop('disabled', true);
        return;
      }

      $prev.add($next).removeClass('is-hidden');

      var maxScroll = scrollEl.scrollWidth - scrollEl.clientWidth;
      $prev.prop('disabled', scrollEl.scrollLeft <= 2);
      $next.prop('disabled', scrollEl.scrollLeft >= maxScroll - 2);
    }

    function scrollPage(direction) {
      scrollEl.scrollBy({
        left: direction * scrollEl.clientWidth,
        behavior: 'smooth'
      });
    }

    $prev.on('click', function () {
      scrollPage(-1);
    });

    $next.on('click', function () {
      scrollPage(1);
    });

    $scroll.on('scroll', updateNav);
    $(window).on('resize', updateNav);
    updateNav();
  });
}

