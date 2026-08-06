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

	function escapeHtml(text) {
		return $('<div>').text(text || '').html();
	}

	function updateBadges(count) {
		$('.notification-bell-badge').each(function () {
			if (count > 0) {
				$(this).text(count).removeClass('d-none');
			} else {
				$(this).addClass('d-none');
			}
		});
		$('.notification-mark-all-read').toggleClass('d-none', count <= 0);
		$('#notificationTabBadge').text(count).toggle(count > 0);
	}

	function renderList($list, notifications, emptyText) {
		if (!$list.length) {
			return;
		}

		if (!notifications || !notifications.length) {
			$list.html('<div class="notification-dropdown__empty">' + escapeHtml(emptyText) + '</div>');
			return;
		}

		var html = '';
		notifications.forEach(function (n) {
			var href = n.url || ((typeof domain !== 'undefined' ? domain : '/') + (n.link || '').replace(/^\//, ''));
			var unreadClass = parseInt(n.is_read, 10) === 0 ? ' is-unread' : '';
			var message = (n.message || '').replace(/\s+/g, ' ').trim();
			if (message.length > 90) {
				message = message.substring(0, 87) + '...';
			}
			html += '<a href="' + escapeHtml(href) + '" class="notification-dropdown__item' + unreadClass + '" data-id="' + parseInt(n.id_notification, 10) + '">'
				+ '<strong class="notification-dropdown__title">' + escapeHtml(n.title) + '</strong>'
				+ '<span class="notification-dropdown__message">' + escapeHtml(message) + '</span>'
				+ '<span class="notification-dropdown__time">' + escapeHtml(n.date_formatted || '') + '</span>'
				+ '</a>';
		});
		$list.html(html);
	}

	function renderAllLists(notifications) {
		var emptyText = $('.notification-dropdown-wrap').first().data('empty') || 'No notifications yet';
		$('.notification-dropdown__list').each(function () {
			renderList($(this), notifications, emptyText);
		});
	}

	function refreshNotifications() {
		if (typeof isLoggedIn === 'undefined' || !isLoggedIn) {
			return;
		}

		postAccount('get_notifications').done(function (res) {
			if (!res || !res.success) {
				return;
			}
			updateBadges(parseInt(res.unread_count, 10) || 0);
			renderAllLists(res.notifications || []);
		});
	}

	function closeAllDropdowns() {
		$('.notification-dropdown').attr('hidden', true);
		$('.notification-bell-btn').attr('aria-expanded', 'false');
	}

	function openDropdown($wrap) {
		closeAllDropdowns();
		$wrap.find('.notification-dropdown').removeAttr('hidden');
		$wrap.find('.notification-bell-btn').attr('aria-expanded', 'true');
		refreshNotifications();
	}

	$(function () {
		if (!$('.notification-dropdown-wrap').length) {
			return;
		}

		$(document).on('click', '.notification-bell-btn', function (e) {
			e.preventDefault();
			e.stopPropagation();

			var $wrap = $(this).closest('.notification-dropdown-wrap');
			var $dropdown = $wrap.find('.notification-dropdown');
			var isOpen = !$dropdown.is('[hidden]');

			if (isOpen) {
				$dropdown.attr('hidden', true);
				$(this).attr('aria-expanded', 'false');
			} else {
				openDropdown($wrap);
			}
		});

		$(document).on('click', function (e) {
			if (!$(e.target).closest('.notification-dropdown-wrap').length) {
				closeAllDropdowns();
			}
		});

		$(document).on('click', '.notification-mark-all-read', function (e) {
			e.preventDefault();
			e.stopPropagation();
			postAccount('mark_all_notifications_read').done(function (res) {
				if (res && res.success) {
					updateBadges(0);
					$('.notification-dropdown__item').removeClass('is-unread');
					refreshNotifications();
				}
			});
		});

		$(document).on('click', '.notification-dropdown__item', function () {
			var id = $(this).data('id');
			if (!id) {
				return;
			}
			postAccount('mark_notification_read', { id_notification: id });
		});

		if (window.location.hash === '#notifications') {
			$('[data-account-tab="notifications"]').trigger('click');
		}
	});
})(jQuery);
