(function () {
	'use strict';

	var tplRow = document.getElementById('t4TplRow');
	var tplCol = document.getElementById('t4TplCol');
	var tplWidget = document.getElementById('t4TplWidget');
	var catEl = document.getElementById('t4CategoriesJson');
	var widthsEl = document.getElementById('t4ColWidthsJson');
	var linkOptEl = document.getElementById('t4LinkOptionsJson');
	var i18nEl = document.getElementById('t4I18n');

	var categories = [];
	try {
		categories = JSON.parse((catEl && catEl.textContent) || '[]');
	} catch (e1) {
		categories = [];
	}

	var colWidthOptions = [];
	try {
		colWidthOptions = JSON.parse((widthsEl && widthsEl.textContent) || '[]');
	} catch (e2) {
		colWidthOptions = [];
	}
	if (!colWidthOptions.length) {
		for (var w = 1; w <= 12; w++) {
			colWidthOptions.push({ value: w, pct: Math.round((w / 12) * 100) });
		}
	}

	var linkOptions = { pages: [], cms: [], categories: [] };
	try {
		linkOptions = JSON.parse((linkOptEl && linkOptEl.textContent) || '{}') || linkOptions;
	} catch (e2b) {
		linkOptions = { pages: [], cms: [], categories: [] };
	}
	if (!linkOptions.pages) linkOptions.pages = [];
	if (!linkOptions.cms) linkOptions.cms = [];
	if (!linkOptions.categories) linkOptions.categories = [];

	var i18n = {};
	try {
		i18n = JSON.parse((i18nEl && i18nEl.textContent) || '{}');
	} catch (e3) {
		i18n = {};
	}

	function t(key, fallback) {
		return i18n[key] || fallback || key;
	}

	function uid(prefix) {
		return prefix + '_' + Math.random().toString(36).slice(2, 9);
	}

	function randomSuffix() {
		return Math.random().toString(36).slice(2, 9);
	}

	function sanitizeHtmlId(existing, fallbackPrefix) {
		var raw = String(existing || '').trim().replace(/[^a-zA-Z0-9_-]/g, '');
		if (!raw) {
			return (fallbackPrefix || 'block') + randomSuffix();
		}
		if (/^[0-9]/.test(raw)) {
			raw = 'id' + raw;
		}
		return raw.slice(0, 64);
	}

	function makeRowId(existing) {
		return sanitizeHtmlId(existing, 'row');
	}

	function makeColId(existing) {
		return sanitizeHtmlId(existing, 'column');
	}

	function sanitizeCssClass(raw) {
		var parts = String(raw || '').trim().split(/\s+/);
		var out = [];
		var seen = {};
		parts.forEach(function (p) {
			p = p.replace(/[^a-zA-Z0-9_-]/g, '');
			if (!p || /^[0-9]/.test(p) || seen[p]) return;
			seen[p] = true;
			out.push(p);
		});
		return out.slice(0, 12).join(' ');
	}

	function clampWidth(n, fallback) {
		n = parseInt(n, 10);
		if (!n || n < 1 || n > 12) {
			return fallback || 12;
		}
		return n;
	}

	function normalizeWidth(width) {
		if (width && typeof width === 'object' && !Array.isArray(width)) {
			return {
				mobile: clampWidth(width.mobile, 12),
				tablet: clampWidth(width.tablet, 6),
				desktop: clampWidth(width.desktop, 6)
			};
		}
		var n = clampWidth(width, 12);
		return { mobile: 12, tablet: n, desktop: n };
	}

	function defaultColWidth() {
		return { mobile: 12, tablet: 6, desktop: 6 };
	}

	function defaultHide() {
		return { mobile: 0, tablet: 0, desktop: 0 };
	}

	function normalizeHide(hide) {
		hide = hide && typeof hide === 'object' ? hide : {};
		return {
			mobile: hide.mobile ? 1 : 0,
			tablet: hide.tablet ? 1 : 0,
			desktop: hide.desktop ? 1 : 0
		};
	}

	function buildColClass(width, extraClass) {
		var w = normalizeWidth(width);
		var base = 'col-' + w.mobile + ' col-md-' + w.tablet + ' col-lg-' + w.desktop;
		extraClass = sanitizeCssClass(extraClass || '');
		return extraClass ? (base + ' ' + extraClass) : base;
	}

	function buildRowClass(extraClass) {
		var base = 't4-row card mb-3';
		extraClass = sanitizeCssClass(extraClass || '');
		return extraClass ? (base + ' ' + extraClass) : base;
	}

	function hasAnyHide(hide) {
		hide = normalizeHide(hide);
		return !!(hide.mobile || hide.tablet || hide.desktop);
	}

	function updateHideBadge(el, hide) {
		var badge = el.querySelector('[data-hide-badge]');
		if (!badge) return;
		if (hasAnyHide(hide)) {
			badge.classList.remove('d-none');
			var parts = [];
			hide = normalizeHide(hide);
			if (hide.mobile) parts.push(t('mobile', 'Mobile'));
			if (hide.tablet) parts.push(t('tablet', 'Tablet'));
			if (hide.desktop) parts.push(t('desktop', 'Desktop'));
			badge.title = parts.join(', ');
		} else {
			badge.classList.add('d-none');
			badge.removeAttribute('title');
		}
	}

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function widthOptionsHtml(selected, bsPrefix) {
		selected = String(selected || 6);
		return colWidthOptions.map(function (opt) {
			var val = String(opt.value);
			var cls = bsPrefix ? ('col-' + bsPrefix + '-' + val) : ('col-' + val);
			var label = (opt.pct != null ? opt.pct : Math.round((opt.value / 12) * 100)) + '% (' + cls + ')';
			return '<option value="' + esc(val) + '"' + (val === selected ? ' selected' : '') + '>'
				+ esc(label) + '</option>';
		}).join('');
	}

	var settingsCtx = null;
	var modalEl = document.getElementById('t4BlockSettingsModal');
	var bsModal = null;

	function getModal() {
		if (!modalEl) return null;
		if (!bsModal && window.bootstrap && bootstrap.Modal) {
			bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
		}
		return bsModal;
	}

	function fillWidthSelects(width) {
		width = normalizeWidth(width);
		var m = document.getElementById('t4SetWMobile');
		var tb = document.getElementById('t4SetWTablet');
		var d = document.getElementById('t4SetWDesktop');
		if (!m || !tb || !d) return;

		if (!colWidthOptions.length) {
			for (var wi = 1; wi <= 12; wi++) {
				colWidthOptions.push({ value: wi, pct: Math.round((wi / 12) * 100) });
			}
		}

		m.innerHTML = widthOptionsHtml(width.mobile, '');
		tb.innerHTML = widthOptionsHtml(width.tablet, 'md');
		d.innerHTML = widthOptionsHtml(width.desktop, 'lg');
		m.value = String(width.mobile);
		tb.value = String(width.tablet);
		d.value = String(width.desktop);
		if (!m.value) m.selectedIndex = Math.max(0, width.mobile - 1);
		if (!tb.value) tb.selectedIndex = Math.max(0, width.tablet - 1);
		if (!d.value) d.selectedIndex = Math.max(0, width.desktop - 1);
	}

	function syncSwitchLabels(input) {
		if (!input) return;
		var row = input.closest('.t4-switch-row');
		if (!row) return;
		var noEl = row.querySelector('[data-switch-no]');
		var yesEl = row.querySelector('[data-switch-yes]');
		if (noEl) noEl.classList.toggle('fw-semibold', !input.checked);
		if (noEl) noEl.classList.toggle('text-dark', !input.checked);
		if (yesEl) yesEl.classList.toggle('fw-semibold', !!input.checked);
		if (yesEl) yesEl.classList.toggle('text-dark', !!input.checked);
	}

	function openSettings(kind, node) {
		settingsCtx = { kind: kind, node: node };
		var data = kind === 'row' ? node._row : node._col;
		if (!data) return;

		var title = document.getElementById('t4SetTitle');
		var idInput = document.getElementById('t4SetId');
		var classInput = document.getElementById('t4SetClass');
		var widthsBox = document.getElementById('t4SetWidths');
		var hideM = document.getElementById('t4SetHideMobile');
		var hideT = document.getElementById('t4SetHideTablet');
		var hideD = document.getElementById('t4SetHideDesktop');

		if (title) {
			title.textContent = kind === 'row'
				? t('rowSettings', 'Row settings')
				: t('columnSettings', 'Column settings');
		}
		if (idInput) idInput.value = data.id || '';
		if (classInput) classInput.value = data.class || '';

		if (widthsBox) {
			if (kind === 'col') {
				widthsBox.classList.remove('d-none');
				fillWidthSelects(data.width);
			} else {
				widthsBox.classList.add('d-none');
			}
		}

		var hide = normalizeHide(data.hide);
		if (hideM) { hideM.checked = !!hide.mobile; syncSwitchLabels(hideM); }
		if (hideT) { hideT.checked = !!hide.tablet; syncSwitchLabels(hideT); }
		if (hideD) { hideD.checked = !!hide.desktop; syncSwitchLabels(hideD); }

		var modal = getModal();
		if (modal) {
			modal.show();
		} else if (modalEl) {
			modalEl.classList.add('show');
			modalEl.style.display = 'block';
		}
	}

	function applySettings() {
		if (!settingsCtx || !settingsCtx.node) return;
		var kind = settingsCtx.kind;
		var node = settingsCtx.node;
		var data = kind === 'row' ? node._row : node._col;
		if (!data) return;

		var idRaw = (document.getElementById('t4SetId') || {}).value || '';
		var classRaw = (document.getElementById('t4SetClass') || {}).value || '';
		var newId = sanitizeHtmlId(idRaw, kind === 'row' ? 'row' : 'column');
		var newClass = sanitizeCssClass(classRaw);

		data.id = newId;
		data.class = newClass;
		node.id = newId;
		node.setAttribute(kind === 'row' ? 'data-row-id' : 'data-col-id', newId);

		data.hide = {
			mobile: document.getElementById('t4SetHideMobile').checked ? 1 : 0,
			tablet: document.getElementById('t4SetHideTablet').checked ? 1 : 0,
			desktop: document.getElementById('t4SetHideDesktop').checked ? 1 : 0
		};
		updateHideBadge(node, data.hide);

		if (kind === 'col') {
			var mEl = document.getElementById('t4SetWMobile');
			var tEl = document.getElementById('t4SetWTablet');
			var dEl = document.getElementById('t4SetWDesktop');
			data.width = normalizeWidth({
				mobile: mEl ? mEl.value : 12,
				tablet: tEl ? tEl.value : 6,
				desktop: dEl ? dEl.value : 6
			});
			node.className = buildColClass(data.width, data.class);
		} else {
			node.className = buildRowClass(data.class);
		}

		var gear = node.querySelector(kind === 'row' ? '[data-row-settings]' : '[data-col-settings]');
		if (gear) {
			gear.setAttribute('title', t('settings', 'Settings') + ': #' + newId + (newClass ? ' .' + newClass.split(' ').join(' .') : ''));
		}

		var modal = getModal();
		if (modal) {
			modal.hide();
		} else if (modalEl) {
			modalEl.classList.remove('show');
			modalEl.style.display = 'none';
		}
		settingsCtx = null;
	}

	var applyBtn = document.getElementById('t4SetApply');
	if (applyBtn) {
		applyBtn.addEventListener('click', applySettings);
	}

	['t4SetHideMobile', 't4SetHideTablet', 't4SetHideDesktop'].forEach(function (hid) {
		var el = document.getElementById(hid);
		if (el) {
			el.addEventListener('change', function () { syncSwitchLabels(el); });
		}
	});

	var WIDGET_LABELS = {
		banner: function () { return t('widgetBanner', 'Banner'); },
		hook: function () { return t('widgetHook', 'Hook'); },
		category_products: function () { return t('widgetCategory', 'Category products'); },
		text: function () { return t('widgetText', 'Text / HTML'); },
		logo: function () { return t('widgetLogo', 'Logo'); },
		links: function () { return t('widgetLinks', 'Links'); },
		search: function () { return t('widgetSearch', 'Search bar'); },
		header_tools: function () { return t('widgetHeaderTools', 'Header tools'); }
	};

	function initBuilder(root) {
		if (!root || !tplRow || !tplCol || !tplWidget) return;

		var builderId = root.getAttribute('data-t4-builder') || 'builder';
		var rowsEl = root.querySelector('[data-t4-rows]');
		var form = root.querySelector('form');
		var jsonInput = root.querySelector('[data-layout-json]');
		var addRowBtn = root.querySelector('[data-t4-add-row]');
		var resetBtn = root.querySelector('[data-t4-reset]');
		var bootEl = root.querySelector('[data-layout-boot]');
		var defaultEl = root.querySelector('[data-layout-default]');

		var layout = { rows: [] };
		try {
			layout = JSON.parse((bootEl && bootEl.textContent) || '{"rows":[]}');
		} catch (e4) {
			layout = { rows: [] };
		}

		var defaultLayout = { rows: [] };
		try {
			defaultLayout = JSON.parse((defaultEl && defaultEl.textContent) || '{"rows":[]}');
		} catch (e5) {
			defaultLayout = { rows: [] };
		}

		var hooks = [];
		try {
			hooks = JSON.parse(root.getAttribute('data-hooks') || '[]');
		} catch (e6) {
			hooks = ['home_slider', 'home_promo_slider', 'home_bottom', 'main_menu', 'footer'];
		}

		var allowedWidgets = [];
		try {
			allowedWidgets = JSON.parse(root.getAttribute('data-widgets') || '[]');
		} catch (e7) {
			allowedWidgets = ['text'];
		}
		if (!allowedWidgets.length) {
			allowedWidgets = ['text'];
		}

		function defaultSettings(type) {
			if (type === 'banner') return { image: '', link: '', alt: '' };
			if (type === 'hook') return { hook: hooks[0] || 'home_slider' };
			if (type === 'category_products') return { id_category: 0, limit: 8, title: '', show_link: 1 };
			if (type === 'text') return { html: '' };
			if (type === 'logo') return { image: '', link: '', alt: '', caption: '' };
			if (type === 'links') return { title: '', items: [{ source: 'custom', ref: '', label: '', url: '' }] };
			if (type === 'search') return { placeholder: '' };
			if (type === 'header_tools') {
				return {
					show_account: 1,
					show_favorites: 1,
					show_cart: 1,
					show_notifications: 1,
					show_menu_btn: 1
				};
			}
			return {};
		}

		function widgetTypeOptions(selected) {
			return allowedWidgets.map(function (type) {
				var labelFn = WIDGET_LABELS[type];
				var label = labelFn ? labelFn() : type;
				return '<option value="' + esc(type) + '"' + (type === selected ? ' selected' : '') + '>'
					+ esc(label) + '</option>';
			}).join('');
		}

		function linkSourceOptions(selected) {
			var opts = [
				{ id: 'custom', label: t('linkSourceCustom', 'Custom URL') },
				{ id: 'page', label: t('linkSourcePage', 'Site page') },
				{ id: 'cms', label: t('linkSourceCms', 'CMS page') },
				{ id: 'category', label: t('linkSourceCategory', 'Category') }
			];
			return opts.map(function (o) {
				return '<option value="' + esc(o.id) + '"' + (o.id === selected ? ' selected' : '') + '>' + esc(o.label) + '</option>';
			}).join('');
		}

		function linkRefOptions(source, selected) {
			var list = [];
			if (source === 'page') list = linkOptions.pages || [];
			else if (source === 'cms') list = linkOptions.cms || [];
			else if (source === 'category') list = linkOptions.categories || [];
			var html = '<option value="">' + esc(t('selectLink', '— select —')) + '</option>';
			list.forEach(function (item) {
				html += '<option value="' + esc(item.id) + '"' + (String(item.id) === String(selected) ? ' selected' : '')
					+ ' data-url="' + esc(item.url || '') + '" data-label="' + esc(item.label || '') + '">'
					+ esc(item.label) + '</option>';
			});
			return html;
		}

		function linksEditorHtml(settings) {
			var items = Array.isArray(settings.items) && settings.items.length
				? settings.items
				: [{ source: 'custom', ref: '', label: '', url: '' }];
			var rows = items.map(function (item) {
				return linkRowHtml(item);
			}).join('');
			return ''
				+ '<label>' + esc(t('linksTitle', 'Section title')) + '</label>'
				+ '<input type="text" class="form-control form-control-sm mb-2" data-f="title" value="' + esc(settings.title || '') + '">'
				+ '<div data-links-list>' + rows + '</div>'
				+ '<button type="button" class="btn btn-sm btn-outline-dark" data-add-link>+ ' + esc(t('addLink', 'Add link')) + '</button>';
		}

		function linkRowHtml(item) {
			item = item || { source: 'custom', ref: '', label: '', url: '' };
			var source = item.source || 'custom';
			if (source !== 'custom' && source !== 'page' && source !== 'cms' && source !== 'category') {
				source = 'custom';
			}
			var isCustom = source === 'custom';
			return ''
				+ '<div class="border rounded p-2 mb-2" data-link-row>'
				+ '<div class="d-flex justify-content-between align-items-center mb-1">'
				+ '<span class="small text-muted">' + esc(t('widgetLinks', 'Links')) + '</span>'
				+ '<button type="button" class="btn btn-sm btn-outline-danger py-0" data-del-link>' + esc(t('Delete', 'Delete')) + '</button>'
				+ '</div>'
				+ '<label class="small">' + esc(t('linkSource', 'Link type')) + '</label>'
				+ '<select class="form-select form-select-sm mb-1" data-link-source>' + linkSourceOptions(source) + '</select>'
				+ '<div class="mb-1' + (isCustom ? ' d-none' : '') + '" data-link-ref-wrap>'
				+ '<label class="small">' + esc(t('selectLink', '— select —')) + '</label>'
				+ '<select class="form-select form-select-sm" data-link-ref>' + linkRefOptions(source, item.ref || '') + '</select>'
				+ '</div>'
				+ '<label class="small">' + esc(t('linkLabel', 'Label')) + '</label>'
				+ '<input type="text" class="form-control form-control-sm mb-1" data-link-label value="' + esc(item.label || '') + '">'
				+ '<div' + (isCustom ? '' : ' class="d-none"') + ' data-link-url-wrap>'
				+ '<label class="small">' + esc(t('linkUrl', 'URL')) + '</label>'
				+ '<input type="text" class="form-control form-control-sm" data-link-url value="' + esc(item.url || '') + '">'
				+ '</div>'
				+ '<input type="hidden" data-link-url-hidden value="' + esc(item.url || '') + '">'
				+ '</div>';
		}

		function fieldsHtml(type, settings) {
			settings = settings || defaultSettings(type);
			if (type === 'banner') {
				var img = settings.image || '';
				return ''
					+ '<label>' + esc(t('bannerImage', 'Banner image')) + '</label>'
					+ '<div class="d-flex gap-2 mb-2">'
					+ '<input type="text" class="form-control form-control-sm" data-f="image" value="' + esc(img) + '" placeholder="' + esc(t('selectMediaPlaceholder', 'Select from media')) + '" readonly>'
					+ '<button type="button" class="btn btn-sm btn-outline-dark text-nowrap" data-media-open>' + esc(t('chooseMedia', 'Choose from media')) + '</button>'
					+ '</div>'
					+ '<div class="mb-2">'
					+ (img
						? '<img src="' + esc(img) + '" alt="" class="t4-banner-preview" data-banner-preview>'
						: '<img src="" alt="" class="t4-banner-preview d-none" data-banner-preview>')
					+ '</div>'
					+ '<label>' + esc(t('linkOptional', 'Link (optional)')) + '</label><input type="url" class="form-control form-control-sm mb-2" data-f="link" value="' + esc(settings.link) + '">'
					+ '<label>' + esc(t('altText', 'Alt text')) + '</label><input type="text" class="form-control form-control-sm" data-f="alt" value="' + esc(settings.alt) + '">';
			}
			if (type === 'hook') {
				var opts = hooks.map(function (h) {
					return '<option value="' + esc(h) + '"' + (settings.hook === h ? ' selected' : '') + '>' + esc(h) + '</option>';
				}).join('');
				return '<label>Hook</label><select class="form-select form-select-sm" data-f="hook">' + opts + '</select>'
					+ '<div class="form-text">' + esc(t('hookHelp', 'Hook point used by modules such as sliders')) + '</div>';
			}
			if (type === 'category_products') {
				var cats = '<option value="0">' + esc(t('selectCategory', '— select category —')) + '</option>';
				categories.forEach(function (c) {
					cats += '<option value="' + c.id + '"' + (String(settings.id_category) === String(c.id) ? ' selected' : '') + '>' + esc(c.name) + '</option>';
				});
				return ''
					+ '<label>' + esc(t('category', 'Category')) + '</label><select class="form-select form-select-sm mb-2" data-f="id_category">' + cats + '</select>'
					+ '<label>' + esc(t('title', 'Title')) + '</label><input type="text" class="form-control form-control-sm mb-2" data-f="title" value="' + esc(settings.title) + '">'
					+ '<label>' + esc(t('productCount', 'Product count')) + '</label><input type="number" min="1" max="48" class="form-control form-control-sm mb-2" data-f="limit" value="' + esc(settings.limit || 8) + '">'
					+ '<div class="form-check"><input class="form-check-input" type="checkbox" data-f="show_link" id="sl_' + uid('x') + '"' + (settings.show_link ? ' checked' : '') + '>'
					+ '<label class="form-check-label">' + esc(t('showViewAll', 'Show “View all” link')) + '</label></div>';
			}
			if (type === 'text') {
				return '<label>' + esc(t('htmlText', 'HTML / text')) + '</label><textarea class="form-control form-control-sm" rows="4" data-f="html">' + esc(settings.html) + '</textarea>';
			}
			if (type === 'logo') {
				var limg = settings.image || '';
				return ''
					+ '<label>' + esc(t('logoImage', 'Logo image')) + '</label>'
					+ '<div class="d-flex gap-2 mb-2">'
					+ '<input type="text" class="form-control form-control-sm" data-f="image" value="' + esc(limg) + '" placeholder="' + esc(t('selectMediaPlaceholder', 'Select from media')) + '" readonly>'
					+ '<button type="button" class="btn btn-sm btn-outline-dark text-nowrap" data-media-open>' + esc(t('selectLogo', 'Select logo')) + '</button>'
					+ '</div>'
					+ '<div class="mb-2">'
					+ (limg
						? '<img src="' + esc(limg) + '" alt="" class="t4-banner-preview" data-banner-preview>'
						: '<img src="" alt="" class="t4-banner-preview d-none" data-banner-preview>')
					+ '</div>'
					+ '<label>' + esc(t('linkOptional', 'Link (optional)')) + '</label><input type="text" class="form-control form-control-sm mb-2" data-f="link" value="' + esc(settings.link || '') + '">'
					+ '<label>' + esc(t('altText', 'Alt text')) + '</label><input type="text" class="form-control form-control-sm mb-2" data-f="alt" value="' + esc(settings.alt || '') + '">'
					+ '<label>' + esc(t('captionOptional', 'Caption / text under logo')) + '</label><textarea class="form-control form-control-sm" rows="2" data-f="caption">' + esc(settings.caption || '') + '</textarea>';
			}
			if (type === 'links') {
				return linksEditorHtml(settings);
			}
			if (type === 'search') {
				return '<label>' + esc(t('searchPlaceholder', 'Search placeholder')) + '</label>'
					+ '<input type="text" class="form-control form-control-sm" data-f="placeholder" value="' + esc(settings.placeholder || '') + '">';
			}
			if (type === 'header_tools') {
				function chk(key, label) {
					return '<div class="form-check mb-1"><input class="form-check-input" type="checkbox" data-f="' + key + '" id="' + uid(key) + '"'
						+ (settings[key] ? ' checked' : '') + '>'
						+ '<label class="form-check-label">' + esc(label) + '</label></div>';
				}
				return ''
					+ chk('show_menu_btn', t('showMenuBtn', 'Mobile menu button'))
					+ chk('show_notifications', t('showNotifications', 'Notifications'))
					+ chk('show_account', t('showAccount', 'Account'))
					+ chk('show_favorites', t('showFavorites', 'Favorites'))
					+ chk('show_cart', t('showCart', 'Cart'));
			}
			return '';
		}

		function bindMediaPicker(fieldsEl) {
			var btn = fieldsEl.querySelector('[data-media-open]');
			if (!btn) return;

			btn.addEventListener('click', function (e) {
				e.preventDefault();
				if (!window.FShopMediaPicker || !FShopMediaPicker.available) {
					window.alert(t('mediaUnavailable', 'Media library could not be loaded. Refresh the page and try again.'));
					return;
				}

				FShopMediaPicker.open({
					multi: false,
					confirmLabel: t('selectAsBanner', 'Select as banner'),
					onSelect: function (items) {
						if (!items || !items.length) return;
						var item = items[0];
						var url = item.url || '';
						var input = fieldsEl.querySelector('[data-f="image"]');
						var preview = fieldsEl.querySelector('[data-banner-preview]');
						var altInput = fieldsEl.querySelector('[data-f="alt"]');

						if (input) {
							input.value = url;
							input.dispatchEvent(new Event('change', { bubbles: true }));
						}
						if (preview) {
							if (url) {
								preview.src = url;
								preview.classList.remove('d-none');
							} else {
								preview.removeAttribute('src');
								preview.classList.add('d-none');
							}
						}
						if (altInput && !altInput.value && item.name) {
							altInput.value = item.name;
						}
					}
				});
			});
		}

		function bindLinksEditor(fieldsEl) {
			var list = fieldsEl.querySelector('[data-links-list]');
			if (!list) return;

			var addBtn = fieldsEl.querySelector('[data-add-link]');
			if (addBtn) {
				addBtn.addEventListener('click', function () {
					var wrap = document.createElement('div');
					wrap.innerHTML = linkRowHtml({ source: 'custom', ref: '', label: '', url: '' });
					var row = wrap.querySelector('[data-link-row]');
					if (row) {
						list.appendChild(row);
						bindLinkRow(row);
					}
				});
			}

			list.querySelectorAll('[data-link-row]').forEach(bindLinkRow);
		}

		function bindLinkRow(row) {
			var del = row.querySelector('[data-del-link]');
			if (del) {
				del.addEventListener('click', function () {
					row.remove();
				});
			}

			var sourceSel = row.querySelector('[data-link-source]');
			var refSel = row.querySelector('[data-link-ref]');
			var refWrap = row.querySelector('[data-link-ref-wrap]');
			var urlWrap = row.querySelector('[data-link-url-wrap]');
			var urlInput = row.querySelector('[data-link-url]');
			var urlHidden = row.querySelector('[data-link-url-hidden]');
			var labelInput = row.querySelector('[data-link-label]');

			function syncFromRef() {
				if (!refSel) return;
				var opt = refSel.options[refSel.selectedIndex];
				if (!opt || !opt.value) return;
				var url = opt.getAttribute('data-url') || '';
				var label = opt.getAttribute('data-label') || '';
				if (urlHidden) urlHidden.value = url;
				if (urlInput) urlInput.value = url;
				if (labelInput && (!labelInput.value || labelInput.dataset.auto === '1')) {
					labelInput.value = label;
					labelInput.dataset.auto = '1';
				}
			}

			if (sourceSel) {
				sourceSel.addEventListener('change', function () {
					var source = sourceSel.value || 'custom';
					var isCustom = source === 'custom';
					if (refWrap) refWrap.classList.toggle('d-none', isCustom);
					if (urlWrap) urlWrap.classList.toggle('d-none', !isCustom);
					if (refSel) {
						refSel.innerHTML = linkRefOptions(source, '');
					}
					if (isCustom) {
						if (urlHidden) urlHidden.value = '';
					} else if (labelInput) {
						labelInput.dataset.auto = '1';
						labelInput.value = '';
					}
				});
			}

			if (refSel) {
				refSel.addEventListener('change', syncFromRef);
			}

			if (labelInput) {
				labelInput.addEventListener('input', function () {
					labelInput.dataset.auto = '0';
				});
			}
		}

		function makeSortable(el, opts) {
			if (!el || typeof Sortable === 'undefined') return null;
			if (el._sortable) {
				el._sortable.destroy();
			}
			el._sortable = Sortable.create(el, {
				animation: 150,
				handle: opts.handle,
				draggable: opts.draggable,
				group: opts.group || undefined,
				direction: opts.direction || undefined,
				ghostClass: 't4-sortable-ghost',
				chosenClass: 't4-sortable-chosen',
				filter: opts.filter || undefined,
				preventOnFilter: true,
				swapThreshold: opts.swapThreshold,
				invertSwap: opts.invertSwap,
				onMove: opts.onMove || undefined
			});
			return el._sortable;
		}

		function initRowSortable() {
			makeSortable(rowsEl, {
				handle: '[data-row-handle]',
				draggable: '[data-row]',
				direction: 'vertical',
				swapThreshold: 0.65,
				invertSwap: true,
				group: {
					name: 't4-rows-' + builderId,
					pull: true,
					put: function (to, from, dragEl) {
						return !!(dragEl && dragEl.hasAttribute('data-row'));
					}
				},
				onMove: function (evt) {
					var related = evt.related;
					if (!related) return true;
					if (related.parentNode === rowsEl && related.hasAttribute('data-row')) {
						return true;
					}
					return false;
				}
			});
		}

		function initColSortable(colsWrap) {
			makeSortable(colsWrap, {
				handle: '[data-col-handle]',
				draggable: '[data-col]',
				group: {
					name: 't4-cols-' + builderId,
					pull: true,
					put: function (to, from, dragEl) {
						return !!(dragEl && dragEl.hasAttribute('data-col'));
					}
				}
			});
		}

		function initWidgetSortable(widgetsWrap) {
			makeSortable(widgetsWrap, {
				handle: '[data-widget-handle]',
				draggable: '[data-widget]',
				group: {
					name: 't4-widgets-' + builderId,
					pull: true,
					put: function (to, from, dragEl) {
						return !!(dragEl && dragEl.hasAttribute('data-widget'));
					}
				}
			});
		}

		function bindWidget(card, widget) {
			var typeSel = card.querySelector('[data-widget-type]');
			var fields = card.querySelector('[data-widget-fields]');
			if (!allowedWidgets.includes(widget.type)) {
				widget.type = allowedWidgets[0];
				widget.settings = defaultSettings(widget.type);
			}
			typeSel.innerHTML = widgetTypeOptions(widget.type);
			typeSel.value = widget.type || allowedWidgets[0];
			fields.innerHTML = fieldsHtml(typeSel.value, widget.settings);
			bindMediaPicker(fields);
			bindLinksEditor(fields);

			typeSel.addEventListener('change', function () {
				widget.type = typeSel.value;
				widget.settings = defaultSettings(widget.type);
				fields.innerHTML = fieldsHtml(widget.type, widget.settings);
				bindMediaPicker(fields);
				bindLinksEditor(fields);
			});

			card.querySelector('[data-del-widget]').addEventListener('click', function () {
				card.remove();
			});
		}

		function createWidgetEl(widget) {
			var firstType = allowedWidgets[0] || 'text';
			widget = widget || { id: uid('w'), type: firstType, settings: defaultSettings(firstType) };
			if (!widget.id) widget.id = uid('w');
			if (!widget.settings) widget.settings = defaultSettings(widget.type);

			var node = tplWidget.content.firstElementChild.cloneNode(true);
			node._widget = widget;
			bindWidget(node, widget);
			return node;
		}

		function createColEl(col) {
			col = col || { id: makeColId(), class: '', width: defaultColWidth(), hide: defaultHide(), widgets: [] };
			col.id = makeColId(col.id);
			col.class = sanitizeCssClass(col.class || '');
			col.width = normalizeWidth(col.width != null ? col.width : defaultColWidth());
			col.hide = normalizeHide(col.hide);
			if (!col.widgets) col.widgets = [];

			var node = tplCol.content.firstElementChild.cloneNode(true);
			node._col = col;
			node.id = col.id;
			node.setAttribute('data-col-id', col.id);
			node.className = buildColClass(col.width, col.class);
			updateHideBadge(node, col.hide);

			var gear = node.querySelector('[data-col-settings]');
			if (gear) {
				gear.setAttribute('title', t('settings', 'Settings') + ': #' + col.id);
				gear.addEventListener('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					openSettings('col', node);
				});
			}

			var widgetsWrap = node.querySelector('[data-widgets]');

			(col.widgets || []).forEach(function (w) {
				widgetsWrap.appendChild(createWidgetEl(w));
			});

			node.querySelector('[data-add-widget]').addEventListener('click', function () {
				widgetsWrap.appendChild(createWidgetEl());
			});

			node.querySelector('[data-del-col]').addEventListener('click', function () {
				node.remove();
			});

			initWidgetSortable(widgetsWrap);
			return node;
		}

		function createRowEl(row) {
			row = row || { id: makeRowId(), class: '', hide: defaultHide(), cols: [] };
			row.id = makeRowId(row.id);
			row.class = sanitizeCssClass(row.class || '');
			row.hide = normalizeHide(row.hide);
			if (!row.cols) row.cols = [];

			var node = tplRow.content.firstElementChild.cloneNode(true);
			node._row = row;
			node.id = row.id;
			node.setAttribute('data-row-id', row.id);
			node.className = buildRowClass(row.class);
			updateHideBadge(node, row.hide);

			var gear = node.querySelector('[data-row-settings]');
			if (gear) {
				gear.setAttribute('title', t('settings', 'Settings') + ': #' + row.id);
				gear.addEventListener('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					openSettings('row', node);
				});
			}

			var colsWrap = node.querySelector('[data-cols]');

			(row.cols || []).forEach(function (c) {
				colsWrap.appendChild(createColEl(c));
			});

			node.querySelector('[data-add-col]').addEventListener('click', function () {
				colsWrap.appendChild(createColEl({
					id: makeColId(),
					class: '',
					width: defaultColWidth(),
					hide: defaultHide(),
					widgets: []
				}));
				initColSortable(colsWrap);
			});

			node.querySelector('[data-del-row]').addEventListener('click', function () {
				node.remove();
			});

			initColSortable(colsWrap);
			return node;
		}

		function render(layoutData) {
			rowsEl.innerHTML = '';
			(layoutData.rows || []).forEach(function (row) {
				rowsEl.appendChild(createRowEl(row));
			});
			if (!(layoutData.rows || []).length) {
				rowsEl.appendChild(createRowEl({
					id: makeRowId(),
					cols: [{ id: makeColId(), width: { mobile: 12, tablet: 12, desktop: 12 }, widgets: [] }]
				}));
			}
			initRowSortable();
		}

		function readSettings(fieldsEl, type) {
			var out = defaultSettings(type);
			fieldsEl.querySelectorAll('[data-f]').forEach(function (el) {
				var key = el.getAttribute('data-f');
				if (el.type === 'checkbox') {
					out[key] = el.checked ? 1 : 0;
				} else if (key === 'id_category' || key === 'limit') {
					out[key] = parseInt(el.value, 10) || 0;
				} else {
					out[key] = el.value;
				}
			});
			if (type === 'links') {
				var items = [];
				fieldsEl.querySelectorAll('[data-link-row]').forEach(function (row) {
					var source = ((row.querySelector('[data-link-source]') || {}).value || 'custom').trim();
					var ref = ((row.querySelector('[data-link-ref]') || {}).value || '').trim();
					var label = ((row.querySelector('[data-link-label]') || {}).value || '').trim();
					var urlInput = row.querySelector('[data-link-url]');
					var urlHidden = row.querySelector('[data-link-url-hidden]');
					var url = '';
					if (source === 'custom') {
						url = (urlInput ? urlInput.value : '').trim();
					} else {
						url = (urlHidden ? urlHidden.value : (urlInput ? urlInput.value : '')).trim();
						if (!url && row.querySelector('[data-link-ref]')) {
							var opt = row.querySelector('[data-link-ref]').options[row.querySelector('[data-link-ref]').selectedIndex];
							if (opt) url = (opt.getAttribute('data-url') || '').trim();
						}
					}
					if (source === 'custom') {
						if (label && url) {
							items.push({ source: 'custom', ref: '', label: label, url: url });
						}
					} else if (ref) {
						items.push({ source: source, ref: ref, label: label, url: url });
					}
				});
				out.items = items;
			}
			return out;
		}

		function serialize() {
			var rows = [];
			Array.prototype.forEach.call(rowsEl.children, function (rowEl) {
				if (!rowEl.hasAttribute || !rowEl.hasAttribute('data-row')) return;
				var cols = [];
				var colsWrap = rowEl.querySelector('[data-cols]');
				if (colsWrap) {
					Array.prototype.forEach.call(colsWrap.children, function (colEl) {
						if (!colEl.hasAttribute || !colEl.hasAttribute('data-col')) return;
						var colBase = colEl._col || {};
						var widgets = [];
						var widgetsWrap = colEl.querySelector('[data-widgets]');
						if (widgetsWrap) {
							Array.prototype.forEach.call(widgetsWrap.children, function (wEl) {
								if (!wEl.hasAttribute || !wEl.hasAttribute('data-widget')) return;
								var type = wEl.querySelector('[data-widget-type]').value;
								var fields = wEl.querySelector('[data-widget-fields]');
								var base = wEl._widget || { id: uid('w') };
								widgets.push({
									id: base.id || uid('w'),
									type: type,
									settings: readSettings(fields, type)
								});
							});
						}
						cols.push({
							id: makeColId(colBase.id || colEl.id),
							class: sanitizeCssClass(colBase.class || ''),
							width: normalizeWidth(colBase.width),
							hide: normalizeHide(colBase.hide),
							widgets: widgets
						});
					});
				}
				var rowBase = rowEl._row || {};
				rows.push({
					id: makeRowId(rowBase.id || rowEl.id),
					class: sanitizeCssClass(rowBase.class || ''),
					hide: normalizeHide(rowBase.hide),
					cols: cols
				});
			});
			return { rows: rows };
		}

		if (addRowBtn) {
			addRowBtn.addEventListener('click', function () {
				rowsEl.appendChild(createRowEl({
					id: makeRowId(),
					cols: [{ id: makeColId(), width: { mobile: 12, tablet: 12, desktop: 12 }, widgets: [] }]
				}));
				initRowSortable();
			});
		}

		if (resetBtn) {
			resetBtn.addEventListener('click', function () {
				if (!window.confirm(t('resetConfirm', 'Load the default layout? (Not applied until you save)'))) return;
				render(JSON.parse(JSON.stringify(defaultLayout)));
			});
		}

		if (form && jsonInput) {
			form.addEventListener('submit', function () {
				jsonInput.value = JSON.stringify(serialize());
			});
		}

		render(layout);
	}

	document.querySelectorAll('[data-t4-builder]').forEach(initBuilder);
})();
