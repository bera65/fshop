document.addEventListener('DOMContentLoaded', function () {
	if (window.history.replaceState) {
		window.history.replaceState(null, null, window.location.href);
	}

	var mobileMenuBtn = document.getElementById('mobileMenuBtn');
	var sidebar = document.getElementById('adminSidebar');
	var backdrop = document.getElementById('sidebarBackdrop');
	var collapseBtn = document.getElementById('sidebarCollapseBtn');
	var collapseStorageKey = 'fshop.admin.sidebarCollapsed';

	function openSidebar() {
		if (!sidebar) {
			return;
		}
		sidebar.classList.add('active');
		if (backdrop) {
			backdrop.hidden = false;
		}
		document.body.classList.add('admin-sidebar-open');
	}

	function closeSidebar() {
		if (!sidebar) {
			return;
		}
		sidebar.classList.remove('active');
		if (backdrop) {
			backdrop.hidden = true;
		}
		document.body.classList.remove('admin-sidebar-open');
		if (mobileMenuBtn) {
			mobileMenuBtn.classList.remove('open');
		}
	}

	function toggleSidebar() {
		if (sidebar && sidebar.classList.contains('active')) {
			closeSidebar();
		} else {
			openSidebar();
		}
	}

	function setSidebarCollapsed(collapsed) {
		document.body.classList.toggle('admin-sidebar-collapsed', !!collapsed);
		if (collapseBtn) {
			collapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
		}
		try {
			localStorage.setItem(collapseStorageKey, collapsed ? '1' : '0');
		} catch (e) {}
	}

	function initSidebarCollapse() {
		var saved = false;
		try {
			saved = localStorage.getItem(collapseStorageKey) === '1';
		} catch (e) {}
		if (window.innerWidth >= 992 && saved) {
			setSidebarCollapsed(true);
		}
		if (collapseBtn) {
			collapseBtn.addEventListener('click', function () {
				if (window.innerWidth < 992) {
					return;
				}
				setSidebarCollapsed(!document.body.classList.contains('admin-sidebar-collapsed'));
			});
		}
	}

	if (mobileMenuBtn) {
		mobileMenuBtn.addEventListener('click', function () {
			this.classList.toggle('open');
			toggleSidebar();
		});
	}

	if (backdrop) {
		backdrop.addEventListener('click', closeSidebar);
	}

	window.addEventListener('resize', function () {
		if (window.innerWidth >= 992) {
			closeSidebar();
			try {
				if (localStorage.getItem(collapseStorageKey) === '1') {
					document.body.classList.add('admin-sidebar-collapsed');
				}
			} catch (e) {}
		} else {
			document.body.classList.remove('admin-sidebar-collapsed');
		}
	});

	initSidebarCollapse();
	initSidebarAccordion();
	initModuleListFilters();
	initAdminConfirmBindings();
	initAutoHideAlerts();
	initLivePoll();
});

function initSidebarAccordion() {
	var menu = document.getElementById('adminSidebarMenu');
	if (!menu) {
		return;
	}

	var groups = Array.prototype.slice.call(menu.querySelectorAll('.nav-accordion'));
	if (!groups.length) {
		return;
	}

	function setOpen(group, open) {
		group.classList.toggle('is-open', !!open);
		var btn = group.querySelector('.nav-accordion__toggle');
		if (btn) {
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
	}

	function openOnly(target) {
		groups.forEach(function (group) {
			setOpen(group, group === target);
		});
	}

	groups.forEach(function (group) {
		var btn = group.querySelector('.nav-accordion__toggle');
		if (!btn) {
			return;
		}
		btn.addEventListener('click', function () {
			if (document.body.classList.contains('admin-sidebar-collapsed') && window.innerWidth >= 992) {
				return;
			}
			if (group.classList.contains('is-open')) {
				setOpen(group, false);
				return;
			}
			openOnly(group);
		});
	});

	var activeItem = menu.querySelector('.menu-item.active');
	var activeGroup = activeItem ? activeItem.closest('.nav-accordion') : null;
	if (activeGroup) {
		openOnly(activeGroup);
		try {
			activeItem.scrollIntoView({ block: 'nearest' });
		} catch (e) {}
	} else {
		openOnly(groups[0]);
	}
}
function initAutoHideAlerts() {
	var alerts = document.querySelectorAll('.admin-content > .alert, .admin-login-alert');
	if (!alerts.length) {
		return;
	}

	setTimeout(function () {
		alerts.forEach(function (el) {
			if (el.classList.contains('alert-light')) {
				return;
			}
			el.style.display = 'none';
		});
	}, 7571);
}

function ensureLiveToastContainer() {
	var el = document.getElementById('adminLiveToastContainer');
	if (el) {
		return el;
	}
	el = document.createElement('div');
	el.id = 'adminLiveToastContainer';
	el.className = 'toast-container position-fixed bottom-0 end-0 p-4';
	el.style.zIndex = '9999';
	document.body.appendChild(el);
	return el;
}

function showAdminLiveToast(opts) {
	opts = opts || {};
	var type = opts.type || 'info';
	var title = opts.title || '';
	var message = opts.message || '';
	var href = opts.href || '';
	var container = ensureLiveToastContainer();
	var toastEl = document.createElement('div');
	toastEl.className = 'toast frisay-toast ' + type;
	toastEl.setAttribute('role', 'alert');
	toastEl.innerHTML =
		'<div class="toast-icon">' + (opts.iconHtml || '') + '</div>' +
		'<div class="toast-body p-0">' +
			(title ? '<div class="toast-title"></div>' : '') +
			'<div class="toast-message"></div>' +
		'</div>' +
		'<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>';

	var titleEl = toastEl.querySelector('.toast-title');
	var msgEl = toastEl.querySelector('.toast-message');
	if (titleEl) {
		titleEl.textContent = title;
	}
	if (msgEl) {
		msgEl.textContent = message;
	}

	if (href) {
		toastEl.style.cursor = 'pointer';
		toastEl.addEventListener('click', function (e) {
			if (e.target.closest('.btn-close')) {
				return;
			}
			window.location.href = href;
		});
	}

	container.appendChild(toastEl);

	if (window.bootstrap && bootstrap.Toast) {
		var toast = new bootstrap.Toast(toastEl, { delay: 6000, autohide: true });
		toastEl.addEventListener('hidden.bs.toast', function () {
			toastEl.remove();
		});
		toast.show();
	} else {
		toastEl.classList.add('show');
		setTimeout(function () {
			toastEl.remove();
		}, 6000);
	}
}

function updateNavBadge(key, count) {
	count = parseInt(count, 10) || 0;
	var selectors = [
		'.menu-item[href$="/' + key + '"] .nav-badge',
		'.menu-item[href$="' + key + '"] .nav-badge',
		'.header-icon-btn[href$="/' + key + '"] .header-icon-btn__badge',
		'.header-icon-btn[href$="' + key + '"] .header-icon-btn__badge'
	];
	document.querySelectorAll(selectors.join(',')).forEach(function (badge) {
		if (count > 0) {
			badge.textContent = String(count);
			badge.hidden = false;
			badge.style.display = '';
		} else {
			badge.hidden = true;
			badge.style.display = 'none';
		}
	});
}

function playNewOrderSound() {
	try {
		var Ctx = window.AudioContext || window.webkitAudioContext;
		if (!Ctx) {
			return;
		}
		if (!window.__adminAudioCtx) {
			window.__adminAudioCtx = new Ctx();
		}
		var ctx = window.__adminAudioCtx;
		if (ctx.state === 'suspended') {
			ctx.resume();
		}

		var now = ctx.currentTime;
		var notes = [880, 1174.7]; // A5 + D6 — kısa “ding-ding”

		notes.forEach(function (freq, i) {
			var osc = ctx.createOscillator();
			var gain = ctx.createGain();
			osc.type = 'sine';
			osc.frequency.value = freq;
			gain.gain.setValueAtTime(0.0001, now);
			gain.gain.exponentialRampToValueAtTime(0.18, now + 0.02 + i * 0.12);
			gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.28 + i * 0.12);
			osc.connect(gain);
			gain.connect(ctx.destination);
			osc.start(now + i * 0.12);
			osc.stop(now + 0.35 + i * 0.12);
		});
	} catch (e) {
		/* ses engellenirse sessizce geç */
	}
}

function initLivePoll() {
	var cfg = window.__adminLivePoll;
	if (!cfg || !cfg.url) {
		return;
	}

	var storageKey = 'fshop_admin_live_poll_v1';
	var state = null;

	try {
		state = JSON.parse(sessionStorage.getItem(storageKey) || 'null');
	} catch (e) {
		state = null;
	}

	var i18n = window.__adminI18n || {};
	var icons = {
		order: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
		message: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
		bell: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>'
	};

	function poll() {
		if (document.hidden) {
			return;
		}

		fetch(cfg.url, {
			method: 'GET',
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
		})
			.then(function (res) {
				if (!res.ok) {
					throw new Error('poll failed');
				}
				return res.json();
			})
			.then(function (data) {
				if (!data || !data.success) {
					return;
				}

				var latest = data.latest || {};
				var badges = data.badges || {};

				if (badges.orders !== undefined) {
					updateNavBadge('orders', badges.orders);
				}
				if (badges.messages !== undefined) {
					updateNavBadge('messages', badges.messages);
				}
				if (badges.notifications !== undefined) {
					updateNavBadge('notifications', badges.notifications);
				}
				if (badges.returns !== undefined) {
					updateNavBadge('returns', badges.returns);
				}
				if (badges.cancellations !== undefined) {
					updateNavBadge('cancellations', badges.cancellations);
				}

				if (!state) {
					state = {
						order_id: latest.order_id || 0,
						message_id: latest.message_id || 0,
						notification_id: latest.notification_id || 0
					};
					sessionStorage.setItem(storageKey, JSON.stringify(state));
					return;
				}

				if ((latest.order_id || 0) > (state.order_id || 0)) {
					var ref = latest.order_ref ? (' #' + latest.order_ref) : '';
					playNewOrderSound();
					showAdminLiveToast({
						type: 'success',
						title: i18n.newOrderTitle || 'Yeni sipariş',
						message: (i18n.newOrderMessage || 'Mağazanıza yeni bir sipariş geldi') + ref,
						href: (cfg.adminUrl || '') + 'orders',
						iconHtml: icons.order
					});
					state.order_id = latest.order_id;
				}

				if ((latest.message_id || 0) > (state.message_id || 0)) {
					showAdminLiveToast({
						type: 'info',
						title: i18n.newMessageTitle || 'Yeni mesaj',
						message: i18n.newMessageMessage || 'Okunmamış yeni bir müşteri mesajı var',
						href: (cfg.adminUrl || '') + 'messages',
						iconHtml: icons.message
					});
					state.message_id = latest.message_id;
				}

				if ((latest.notification_id || 0) > (state.notification_id || 0)) {
					showAdminLiveToast({
						type: 'warning',
						title: i18n.newNotificationTitle || 'Yeni bildirim',
						message: i18n.newNotificationMessage || 'Yönetim panelinde yeni bir bildirim var',
						href: (cfg.adminUrl || '') + 'notifications',
						iconHtml: icons.bell
					});
					state.notification_id = latest.notification_id;
				}

				sessionStorage.setItem(storageKey, JSON.stringify(state));
			})
			.catch(function () {
				/* sessizce yut — geçici ağ hataları */
			});
	}

	poll();
	setInterval(poll, parseInt(cfg.intervalMs, 10) || 30000);
}

window.AdminConfirm = {
	show: function (title, message, onConfirm) {
		var modalEl = document.getElementById('admin-confirm-modal');
		if (!modalEl) {
			if (window.confirm(message || title)) {
				if (typeof onConfirm === 'function') {
					onConfirm();
				}
			}
			return;
		}

		if (modalEl.parentNode !== document.body) {
			document.body.appendChild(modalEl);
		}

		var titleEl = document.getElementById('admin-confirm-title');
		var messageEl = document.getElementById('admin-confirm-message');
		var confirmBtn = document.getElementById('admin-confirm-btn');

		if (titleEl) {
			titleEl.textContent = title || (window.__adminI18n && window.__adminI18n.confirmTitle) || 'Confirm action';
		}
		if (messageEl) {
			messageEl.textContent = message || (window.__adminI18n && window.__adminI18n.confirmMessage) || 'Are you sure you want to perform this action?';
		}

		var newBtn = confirmBtn.cloneNode(true);
		confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

		newBtn.addEventListener('click', function () {
			var instance = window.bootstrap && bootstrap.Modal
				? bootstrap.Modal.getInstance(modalEl)
				: null;
			if (instance) {
				instance.hide();
			}
			if (typeof onConfirm === 'function') {
				onConfirm();
			}
		});

		if (window.bootstrap && bootstrap.Modal) {
			bootstrap.Modal.getOrCreateInstance(modalEl).show();
		} else {
			modalEl.classList.add('show');
			modalEl.style.display = 'block';
		}
	}
};

function initAdminConfirmBindings() {
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.js-admin-confirm');
		if (!btn) {
			return;
		}

		e.preventDefault();
		e.stopPropagation();

		var title = btn.getAttribute('data-confirm-title') || (window.__adminI18n && window.__adminI18n.confirmTitle) || 'Confirm action';
		var message = btn.getAttribute('data-confirm-message') || (window.__adminI18n && window.__adminI18n.confirmMessage) || 'Are you sure you want to perform this action?';
		var form = btn.form || btn.closest('form');

		AdminConfirm.show(title, message, function () {
			if (!form) {
				return;
			}

			if (typeof form.requestSubmit === 'function') {
				btn.classList.remove('js-admin-confirm');
				form.requestSubmit(btn);
				btn.classList.add('js-admin-confirm');
				return;
			}

			var name = btn.getAttribute('name');
			var value = btn.getAttribute('value') || '1';
			if (name) {
				var existing = form.querySelector('input[type="hidden"][data-admin-confirm-proxy="1"][name="' + name + '"]');
				if (!existing) {
					existing = document.createElement('input');
					existing.type = 'hidden';
					existing.name = name;
					existing.setAttribute('data-admin-confirm-proxy', '1');
					form.appendChild(existing);
				}
				existing.value = value;
			}
			form.submit();
		});
	}, true);
}

function initModuleListFilters() {
	var list = document.getElementById('moduleList');
	var search = document.getElementById('moduleSearch');
	var filter = document.getElementById('moduleStatusFilter');
	var emptyState = document.getElementById('moduleListEmpty');

	if (!list) {
		return;
	}

	function normalize(value) {
		return (value || '').toLocaleLowerCase('tr-TR').trim();
	}

	function matchesStatus(rowStatus, statusFilter) {
		if (statusFilter === 'all') {
			return true;
		}

		if (statusFilter === 'installed') {
			return rowStatus === 'installed' || rowStatus === 'active';
		}

		return rowStatus === statusFilter;
	}

	function applyFilters() {
		var query = normalize(search ? search.value : '');
		var status = filter ? filter.value : 'all';
		var rows = list.querySelectorAll('.module-row');
		var visibleCount = 0;

		rows.forEach(function (row) {
			var text = normalize(row.getAttribute('data-module-search'));
			var rowStatus = row.getAttribute('data-module-status') || '';
			var matchQuery = query === '' || text.indexOf(query) !== -1;
			var matchStatus = matchesStatus(rowStatus, status);
			var visible = matchQuery && matchStatus;

			row.classList.toggle('d-none', !visible);

			if (visible) {
				visibleCount++;
			}
		});

		if (emptyState) {
			emptyState.classList.toggle('d-none', visibleCount > 0 || rows.length === 0);
		}
	}

	if (search) {
		search.addEventListener('input', applyFilters);
		search.addEventListener('search', applyFilters);
	}

	if (filter) {
		filter.addEventListener('change', applyFilters);
	}

	var params = new URLSearchParams(window.location.search);
	var uploadedModule = (params.get('uploaded') || '').trim();
	var shouldFilterUploaded = uploadedModule !== '' && params.get('refreshed') === '1';

	if (shouldFilterUploaded) {
		if (search) {
			search.value = uploadedModule;
		}

		if (filter) {
			filter.value = 'not_installed';
		}

		applyFilters();

		var rowSelector = '.module-row[data-module-name="' + uploadedModule.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"]';
		var uploadedRow = list.querySelector(rowSelector);

		if (!uploadedRow) {
			if (filter) {
				filter.value = 'all';
			}

			applyFilters();
			uploadedRow = list.querySelector(rowSelector);
		}

		if (uploadedRow) {
			uploadedRow.classList.add('module-row--highlight');
			uploadedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}

		if (window.history.replaceState) {
			var cleanUrl = new URL(window.location.href);
			cleanUrl.searchParams.delete('uploaded');
			cleanUrl.searchParams.delete('refreshed');
			window.history.replaceState(null, '', cleanUrl.toString());
		}
	} else {
		applyFilters();
	}
}
document.querySelectorAll('.frisay-toast').forEach(function(el){

    const toast = new bootstrap.Toast(el,{
        delay:4571
    });

    toast.show();

});