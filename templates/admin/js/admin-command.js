(function () {
	var overlay = document.getElementById('adminCmdk');
	var input = document.getElementById('adminCmdkInput');
	var list = document.getElementById('adminCmdkList');
	var openBtn = document.getElementById('adminCmdkOpen');
	if (!overlay || !input || !list) {
		return;
	}

	var adminUrl = (window.__adminCmdk && window.__adminCmdk.adminUrl) || '/admin/';
	var storeUrl = (window.__adminCmdk && window.__adminCmdk.storeUrl) || '/';
	var recentKey = 'fshop_admin_cmdk_recent';

	var catalog = [
		{ group: 'Products', label: 'Products', keywords: 'urun product catalog', url: adminUrl + 'products', icon: 'package' },
		{ group: 'Products', label: 'New Product', keywords: 'yeni urun add create', url: adminUrl + 'product', icon: 'plus' },
		{ group: 'Products', label: 'Categories', keywords: 'kategori category', url: adminUrl + 'categories', icon: 'folder-tree' },
		{ group: 'Products', label: 'Brands', keywords: 'marka brand', url: adminUrl + 'brands', icon: 'tag' },
		{ group: 'Products', label: 'Taxes', keywords: 'vergi kdv tax vat', url: adminUrl + 'taxes', icon: 'percent' },
		{ group: 'Sales', label: 'Orders', keywords: 'siparis order', url: adminUrl + 'orders', icon: 'shopping-bag' },
		{ group: 'Sales', label: 'Returns', keywords: 'iade return', url: adminUrl + 'returns', icon: 'undo-2' },
		{ group: 'Sales', label: 'Cancellations', keywords: 'iptal cancel', url: adminUrl + 'cancellations', icon: 'x-circle' },
		{ group: 'Sales', label: 'Coupons', keywords: 'kupon coupon', url: adminUrl + 'coupons', icon: 'ticket-percent' },
		{ group: 'Sales', label: 'Shipping', keywords: 'kargo cargo shipping', url: adminUrl + 'cargos', icon: 'truck' },
		{ group: 'Customers', label: 'Customers', keywords: 'musteri customer', url: adminUrl + 'customers', icon: 'users' },
		{ group: 'Customers', label: 'Messages', keywords: 'mesaj message', url: adminUrl + 'messages', icon: 'message-square' },
		{ group: 'Settings', label: 'My Account', keywords: 'hesabim account profil sifre admin', url: adminUrl + 'account', icon: 'user-cog' },
		{ group: 'Settings', label: 'Settings', keywords: 'ayar settings', url: adminUrl + 'settings', icon: 'settings' },
		{ group: 'Settings', label: 'Modules', keywords: 'modul module', url: adminUrl + 'modules', icon: 'blocks' },
		{ group: 'Settings', label: 'Themes', keywords: 'tema theme', url: adminUrl + 'templates', icon: 'palette' },
		{ group: 'Settings', label: 'SEO', keywords: 'seo', url: adminUrl + 'seo', icon: 'search' },
		{ group: 'Settings', label: 'Performance', keywords: 'cache performance', url: adminUrl + 'performance', icon: 'zap' },
		{ group: 'Settings', label: 'API', keywords: 'api', url: adminUrl + 'api', icon: 'link-2' },
		{ group: 'Store', label: 'View Store', keywords: 'magaza store site', url: storeUrl, icon: 'external-link' },
		{ group: 'General', label: 'Dashboard', keywords: 'dashboard ana', url: adminUrl + 'dashboard', icon: 'layout-dashboard' }
	];

	var activeIndex = 0;
	var visible = [];

	function getRecent() {
		try {
			return JSON.parse(localStorage.getItem(recentKey) || '[]');
		} catch (e) {
			return [];
		}
	}

	function pushRecent(item) {
		var recent = getRecent().filter(function (r) { return r.url !== item.url; });
		recent.unshift({ label: item.label, url: item.url, group: item.group });
		localStorage.setItem(recentKey, JSON.stringify(recent.slice(0, 6)));
	}

	function open() {
		overlay.hidden = false;
		overlay.classList.add('is-open');
		document.body.classList.add('cmdk-open');
		input.value = '';
		render('');
		setTimeout(function () { input.focus(); }, 10);
	}

	function close() {
		overlay.classList.remove('is-open');
		overlay.hidden = true;
		document.body.classList.remove('cmdk-open');
	}

	function filterItems(q) {
		q = (q || '').trim().toLowerCase();
		if (!q) {
			var recent = getRecent();
			if (recent.length) {
				return recent.map(function (r) {
					return {
						group: 'Recent',
						label: r.label,
						url: r.url,
						icon: 'history',
						keywords: ''
					};
				}).concat(catalog.slice(0, 8));
			}
			return catalog.slice(0, 12);
		}
		return catalog.filter(function (item) {
			var hay = (item.label + ' ' + item.group + ' ' + item.keywords).toLowerCase();
			return hay.indexOf(q) !== -1;
		});
	}

	function render(q) {
		visible = filterItems(q);
		activeIndex = 0;
		if (!visible.length) {
			list.innerHTML = '<div class="cmdk-empty">No results</div>';
			return;
		}
		var html = '';
		var lastGroup = '';
		visible.forEach(function (item, idx) {
			if (item.group !== lastGroup) {
				lastGroup = item.group;
				html += '<div class="cmdk-group">' + lastGroup + '</div>';
			}
			html += '<button type="button" class="cmdk-item' + (idx === 0 ? ' is-active' : '') + '" data-idx="' + idx + '" data-url="' + item.url.replace(/"/g, '&quot;') + '">' +
				'<span class="cmdk-item__icon"><i data-lucide="' + (item.icon || 'corner-down-right') + '"></i></span>' +
				'<span class="cmdk-item__text"><strong>' + item.label + '</strong><small>' + item.group + '</small></span>' +
				'</button>';
		});
		list.innerHTML = html;
		if (window.lucide) {
			window.lucide.createIcons();
		}
	}

	function go(idx) {
		var item = visible[idx];
		if (!item) {
			return;
		}
		pushRecent(item);
		close();
		window.location.href = item.url;
	}

	function setActive(idx) {
		var items = list.querySelectorAll('.cmdk-item');
		if (!items.length) {
			return;
		}
		activeIndex = (idx + items.length) % items.length;
		items.forEach(function (el, i) {
			el.classList.toggle('is-active', i === activeIndex);
		});
		items[activeIndex].scrollIntoView({ block: 'nearest' });
	}

	if (openBtn) {
		openBtn.addEventListener('click', open);
	}

	overlay.addEventListener('click', function (e) {
		if (e.target === overlay) {
			close();
		}
	});

	input.addEventListener('input', function () {
		render(input.value);
	});

	list.addEventListener('click', function (e) {
		var btn = e.target.closest('.cmdk-item');
		if (!btn) {
			return;
		}
		go(parseInt(btn.getAttribute('data-idx'), 10));
	});

	document.addEventListener('keydown', function (e) {
		var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
		if ((isMac ? e.metaKey : e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
			e.preventDefault();
			if (overlay.classList.contains('is-open')) {
				close();
			} else {
				open();
			}
			return;
		}
		if (!overlay.classList.contains('is-open')) {
			return;
		}
		if (e.key === 'Escape') {
			e.preventDefault();
			close();
		} else if (e.key === 'ArrowDown') {
			e.preventDefault();
			setActive(activeIndex + 1);
		} else if (e.key === 'ArrowUp') {
			e.preventDefault();
			setActive(activeIndex - 1);
		} else if (e.key === 'Enter') {
			e.preventDefault();
			go(activeIndex);
		}
	});
})();
