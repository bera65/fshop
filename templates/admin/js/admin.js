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
	initAdminBusyBindings();
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
		if (group.querySelector('.menu-item.active')) {
			group.classList.add('has-active');
		}

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

function adminI18n(key, fallback) {
	var pack = window.__adminI18n || {};
	return pack[key] || fallback;
}

function ensureSubmitterField(form, submitter) {
	if (!form || !submitter || submitter.tagName === 'A') {
		return;
	}

	var name = submitter.getAttribute('name');

	if (!name) {
		return;
	}

	var value = submitter.getAttribute('value');

	if (value === null) {
		value = '1';
	}

	var safeName = name.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
	var existing = form.querySelector('input[type="hidden"][data-admin-confirm-proxy="1"][name="' + safeName + '"]');

	if (!existing) {
		existing = document.createElement('input');
		existing.type = 'hidden';
		existing.name = name;
		existing.setAttribute('data-admin-confirm-proxy', '1');
		form.appendChild(existing);
	}

	existing.value = value;
}

window.AdminBusy = {
	start: function (el) {
		if (!el || el.dataset.adminBusy === '1') {
			return;
		}

		var form = el.form || (el.closest ? el.closest('form') : null);

		if (form) {
			ensureSubmitterField(form, el);
		}

		el.dataset.adminBusy = '1';
		el.classList.add('is-busy');

		if ('disabled' in el) {
			el.disabled = true;
		}

		if (!el.querySelector('.adm-btn-spinner')) {
			var spin = document.createElement('span');
			spin.className = 'adm-btn-spinner';
			spin.setAttribute('aria-hidden', 'true');
			el.insertBefore(spin, el.firstChild);
		}
	},
	stop: function (el) {
		if (!el || el.dataset.adminBusy !== '1') {
			return;
		}

		delete el.dataset.adminBusy;
		el.classList.remove('is-busy');

		if ('disabled' in el) {
			el.disabled = false;
		}

		var spin = el.querySelector('.adm-btn-spinner');

		if (spin) {
			spin.remove();
		}
	}
};

window.AdminToast = {
	show: function (message, type) {
		showAdminLiveToast({
			message: message || '',
			type: type || 'info'
		});
	}
};

window.AdminConfirm = {
	_pending: null,
	_bound: false,
	ensureModal: function () {
		var modalEl = document.getElementById('admin-confirm-modal');

		if (modalEl) {
			if (modalEl.parentNode !== document.body) {
				document.body.appendChild(modalEl);
			}
		} else {
			modalEl = document.createElement('div');
			modalEl.className = 'modal fade';
			modalEl.id = 'admin-confirm-modal';
			modalEl.tabIndex = -1;
			modalEl.setAttribute('aria-hidden', 'true');
			modalEl.innerHTML =
				'<div class="modal-dialog modal-dialog-centered">' +
					'<div class="modal-content">' +
						'<div class="modal-header py-2 bg-light">' +
							'<h5 class="modal-title h6 mb-0 text-dark" id="admin-confirm-title"></h5>' +
							'<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
						'</div>' +
						'<div class="modal-body py-3" id="admin-confirm-message"></div>' +
						'<div class="modal-footer py-2">' +
							'<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" id="admin-confirm-cancel">' +
								adminI18n('confirmCancel', 'Cancel') +
							'</button>' +
							'<button type="button" class="btn btn-danger btn-sm" id="admin-confirm-btn">' +
								adminI18n('confirmYes', 'Yes, confirm') +
							'</button>' +
						'</div>' +
					'</div>' +
				'</div>';
			document.body.appendChild(modalEl);
		}

		if (!AdminConfirm._bound) {
			AdminConfirm._bound = true;
			modalEl.addEventListener('hidden.bs.modal', function () {
				var pending = AdminConfirm._pending;
				AdminConfirm._pending = null;

				if (pending && typeof pending.resolve === 'function') {
					pending.resolve(false);
				}
			});
		}

		return modalEl;
	},
	ask: function (opts) {
		if (typeof opts === 'string') {
			opts = { message: opts };
		}

		opts = opts || {};

		return new Promise(function (resolve) {
			var modalEl = AdminConfirm.ensureModal();
			var titleEl = document.getElementById('admin-confirm-title');
			var messageEl = document.getElementById('admin-confirm-message');
			var confirmBtn = document.getElementById('admin-confirm-btn');

			if (AdminConfirm._pending && typeof AdminConfirm._pending.resolve === 'function') {
				AdminConfirm._pending.resolve(false);
			}

			AdminConfirm._pending = { resolve: resolve };

			if (titleEl) {
				titleEl.textContent = opts.title || adminI18n('confirmTitle', 'Confirm action');
			}

			if (messageEl) {
				messageEl.textContent = opts.message || adminI18n('confirmMessage', 'Are you sure you want to perform this action?');
			}

			if (confirmBtn) {
				confirmBtn.textContent = opts.confirmLabel || adminI18n('confirmYes', 'Yes, confirm');
				confirmBtn.className = 'btn btn-sm ' + (opts.danger === false ? 'btn-dark' : 'btn-danger');
				var freshBtn = confirmBtn.cloneNode(true);
				confirmBtn.parentNode.replaceChild(freshBtn, confirmBtn);
				confirmBtn = freshBtn;
				confirmBtn.addEventListener('click', function () {
					var pending = AdminConfirm._pending;
					AdminConfirm._pending = null;

					if (window.bootstrap && bootstrap.Modal) {
						var instance = bootstrap.Modal.getInstance(modalEl);

						if (instance) {
							instance.hide();
						}
					} else {
						modalEl.classList.remove('show');
						modalEl.style.display = 'none';
					}

					if (pending && typeof pending.resolve === 'function') {
						pending.resolve(true);
					}
				});
			}

			if (window.bootstrap && bootstrap.Modal) {
				bootstrap.Modal.getOrCreateInstance(modalEl).show();
			} else {
				modalEl.classList.add('show');
				modalEl.style.display = 'block';
			}
		});
	},
	show: function (title, message, onConfirm) {
		return AdminConfirm.ask({ title: title, message: message }).then(function (ok) {
			if (ok && typeof onConfirm === 'function') {
				onConfirm();
			}

			return ok;
		});
	}
};

function submitAdminForm(form, submitter) {
	if (!form) {
		return;
	}

	form.dataset.adminConfirmed = '1';

	if (submitter) {
		ensureSubmitterField(form, submitter);
		AdminBusy.start(submitter);
	}

	form.submit();
}

function initAdminConfirmBindings() {
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.js-admin-confirm');

		if (!btn) {
			return;
		}

		e.preventDefault();
		e.stopPropagation();

		var requireChecked = btn.getAttribute('data-confirm-require-checked');

		if (requireChecked) {
			var scope = btn.form || document;
			if (!scope.querySelectorAll(requireChecked + ':checked').length) {
				if (window.AdminToast) {
					AdminToast.show(btn.getAttribute('data-confirm-empty-message') || adminI18n('confirmMessage', 'Select at least one item'), 'warning');
				}
				return;
			}
		}

		var title = btn.getAttribute('data-confirm-title') || adminI18n('confirmTitle', 'Confirm action');
		var message = btn.getAttribute('data-confirm-message') || adminI18n('confirmMessage', 'Are you sure you want to perform this action?');
		var form = btn.form || btn.closest('form');

		AdminConfirm.ask({ title: title, message: message }).then(function (ok) {
			if (!ok) {
				return;
			}

			if (btn.tagName === 'A' && btn.getAttribute('href')) {
				AdminBusy.start(btn);
				window.location.href = btn.getAttribute('href');
				return;
			}

			submitAdminForm(form, btn);
		});
	}, true);
}

function initAdminBusyBindings() {
	document.addEventListener('submit', function (e) {
		var form = e.target;

		if (!form || form.tagName !== 'FORM') {
			return;
		}

		var submitter = e.submitter || null;
		var confirmMessage = form.getAttribute('data-confirm-message');

		if (form.dataset.adminConfirmed === '1') {
			if (submitter && (submitter.classList.contains('js-admin-busy') || confirmMessage)) {
				AdminBusy.start(submitter);
			}

			return;
		}

		if (submitter && submitter.classList.contains('js-admin-confirm')) {
			return;
		}

		if (confirmMessage) {
			e.preventDefault();
			AdminConfirm.ask({
				title: form.getAttribute('data-confirm-title') || adminI18n('confirmTitle', 'Confirm action'),
				message: confirmMessage
			}).then(function (ok) {
				if (!ok) {
					return;
				}

				submitAdminForm(form, submitter && form.contains(submitter) ? submitter : null);
			});

			return;
		}

		if (submitter && submitter.classList.contains('js-admin-busy')) {
			AdminBusy.start(submitter);
		}
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