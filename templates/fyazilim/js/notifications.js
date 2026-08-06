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
		var $badge = $('#headerNotificationBadge');
		if (count > 0) {
			$badge.text(count).removeClass('d-none');
		} else {
			$badge.addClass('d-none');
		}
		$('#headerMarkAllRead').toggleClass('d-none', count <= 0);
		$('#notificationTabBadge').text(count).toggle(count > 0);
	}

	function renderList(notifications) {
		var $list = $('#notificationDropdownList');
		if (!$list.length) {
			return;
		}

		if (!notifications || !notifications.length) {
			var emptyText = $('#notificationDropdownWrap').data('empty') || 'No notifications yet';
			$list.html('<div class="notification-dropdown__empty" id="notificationDropdownEmpty">' + escapeHtml(emptyText) + '</div>');
			return;
		}

		var html = '';
		notifications.forEach(function (n) {
			var href = (typeof domain !== 'undefined' ? domain : '/') + (n.link || '').replace(/^\//, '');
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

	function refreshNotifications() {
		if (typeof isLoggedIn === 'undefined' || !isLoggedIn) {
			return;
		}

		postAccount('get_notifications').done(function (res) {
			if (!res || !res.success) {
				return;
			}
			updateBadges(parseInt(res.unread_count, 10) || 0);
			renderList(res.notifications || []);
		});
	}

	function closeDropdown() {
		$('#notificationDropdown').attr('hidden', true);
		$('#notificationBellBtn').attr('aria-expanded', 'false');
	}

	function openDropdown() {
		$('#notificationDropdown').removeAttr('hidden');
		$('#notificationBellBtn').attr('aria-expanded', 'true');
		refreshNotifications();
	}

	$(function () {
		var $wrap = $('#notificationDropdownWrap');
		if (!$wrap.length) {
			return;
		}

		$('#notificationBellBtn').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var isOpen = $('#notificationDropdown').is(':visible');
			if (isOpen) {
				closeDropdown();
			} else {
				openDropdown();
			}
		});

		$(document).on('click', function (e) {
			if (!$wrap.is(e.target) && $wrap.has(e.target).length === 0) {
				closeDropdown();
			}
		});

		$('#headerMarkAllRead').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			postAccount('mark_all_notifications_read').done(function (res) {
				if (res && res.success) {
					updateBadges(0);
					$('#notificationDropdownList .notification-dropdown__item').removeClass('is-unread');
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
