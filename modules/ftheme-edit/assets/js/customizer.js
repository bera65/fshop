(function () {
	'use strict';

	var boot = window.FthemeCustomizerBoot || {};
	var stateEl = document.getElementById('fthemeClientState');
	var frame = document.getElementById('fthemePreviewFrame');
	var saveForm = document.getElementById('fthemeSaveForm');
	var payloadInput = document.getElementById('fthemePayloadInput');

	if (!stateEl || !frame || !saveForm || !payloadInput) {
		return;
	}

	var state = JSON.parse(stateEl.textContent || '{}');
	state.customCss = state.customCss || '';
	state.customJs = state.customJs || '';
	var selectedRegion = null;
	var selectedBlockId = null;
	var editingBlock = null;
	var dirty = false;

	function qs(id) {
		return document.getElementById(id);
	}

	function showToast(message) {
		var toastEl = qs('fthemeToast');
		var bodyEl = qs('fthemeToastBody');

		if (!toastEl || !bodyEl || !window.bootstrap) {
			return;
		}

		bodyEl.textContent = message;
		var toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 2500 });
		toast.show();
	}

	function postPreview(message) {
		if (!frame.contentWindow) {
			return;
		}

		frame.contentWindow.postMessage(Object.assign({ source: 'ftheme-customizer' }, message), '*');
	}

	function markDirty() {
		dirty = true;
	}

	function settingKey(regionId) {
		var region = state.regions[regionId];
		return region ? region.setting : null;
	}

	function getSetting(key) {
		return state.settings[key] || '';
	}

	function setSetting(key, value) {
		state.settings[key] = value;
		markDirty();
		postPreview({ type: 'updateSetting', region: findRegionBySetting(key), value: value });
	}

	function findRegionBySetting(key) {
		var found = null;
		Object.keys(state.regions).forEach(function (regionId) {
			if (state.regions[regionId].setting === key) {
				found = regionId;
			}
		});
		return found;
	}

	function switchPanel(name) {
		document.querySelectorAll('.ftheme-nav-btn').forEach(function (btn) {
			btn.classList.toggle('active', btn.getAttribute('data-panel') === name);
		});
		document.querySelectorAll('.ftheme-panel').forEach(function (panel) {
			panel.classList.toggle('active', panel.getAttribute('data-panel') === name);
		});
	}

	function renderBlockList() {
		var list = qs('fthemeBlockList');
		if (!list) {
			return;
		}

		list.innerHTML = '';

		state.blocks.forEach(function (block, index) {
			var li = document.createElement('li');
			li.className = 'ftheme-block-item' + (block.enabled ? '' : ' is-disabled');
			if (selectedBlockId === block.id) {
				li.classList.add('is-editing');
			}
			li.setAttribute('data-block-id', block.id);

			var info = document.createElement('div');
			info.innerHTML = '<div class="ftheme-block-item__title">' + escapeHtml(block.label || block.id) + '</div>' +
				'<div class="ftheme-block-item__meta">' + escapeHtml(block.type) + '</div>';

			var actions = document.createElement('div');
			actions.className = 'ftheme-block-item__actions';

			actions.appendChild(actionBtn('↑', index > 0, function () {
				moveBlock(index, index - 1);
			}));
			actions.appendChild(actionBtn('↓', index < state.blocks.length - 1, function () {
				moveBlock(index, index + 1);
			}));
			actions.appendChild(actionBtn(block.enabled ? '👁' : '🚫', true, function () {
				block.enabled = !block.enabled;
				renderBlockList();
				pushLivePreview();
				markDirty();
			}));

			if (block.type === 'html' || block.type === 'banner') {
				actions.appendChild(actionBtn('Düzenle', true, function () {
					openBlockEditor(block);
				}, 'ftheme-btn-edit'));
			}

			if (String(block.id).indexOf('custom_') === 0) {
				actions.appendChild(actionBtn('×', true, function () {
					var ask = window.AdminConfirm && AdminConfirm.ask
						? AdminConfirm.ask({ message: 'Bu bloğu silmek istiyor musunuz?' })
						: Promise.resolve(false);
					ask.then(function (ok) {
						if (!ok) {
							return;
						}
						state.blocks.splice(index, 1);
						closeBlockEditor();
						renderBlockList();
						pushLivePreview();
						markDirty();
					});
				}));
			}

			li.appendChild(info);
			li.appendChild(actions);
			list.appendChild(li);
		});
	}

	function actionBtn(label, enabled, handler, extraClass) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.textContent = label;
		btn.disabled = !enabled;
		if (extraClass) {
			btn.className = extraClass;
		}
		btn.addEventListener('click', handler);
		return btn;
	}

	function moveBlock(from, to) {
		var item = state.blocks.splice(from, 1)[0];
		state.blocks.splice(to, 0, item);
		renderBlockList();
		pushLivePreview();
		markDirty();
	}

	function pushLivePreview() {
		postPreview({ type: 'syncBlocks', blocks: state.blocks, domain: boot.domain || '' });
	}

	var liveTimer = null;
	function pushLivePreviewDebounced() {
		clearTimeout(liveTimer);
		liveTimer = setTimeout(pushLivePreview, 180);
	}

	function openBlockEditor(block) {
		if (!block || (block.type !== 'html' && block.type !== 'banner')) {
			return;
		}

		editingBlock = block;
		selectedBlockId = block.id;
		switchPanel('blocks');
		renderBlockList();

		var editor = qs('fthemeBlockEditor');
		var badge = qs('fthemeBlockEditorBadge');
		var titleLabel = qs('fthemeBlockEditorTitle');
		var htmlPanel = qs('fthemeEditorHtml');
		var bannerPanel = qs('fthemeEditorBanner');
		var sidebar = qs('fthemeSidebar');

		if (!editor) {
			return;
		}

		editor.hidden = false;
		editor.classList.remove('is-pulse');
		void editor.offsetWidth;
		editor.classList.add('is-pulse');

		if (badge) {
			badge.textContent = block.type === 'banner' ? 'Banner' : 'Özel bölüm';
		}

		if (titleLabel) {
			titleLabel.textContent = block.label || (block.type === 'banner' ? 'Banner' : 'Özel bölüm');
		}

		if (htmlPanel) {
			htmlPanel.hidden = block.type !== 'html';
		}

		if (bannerPanel) {
			bannerPanel.hidden = block.type !== 'banner';
		}

		if (block.type === 'html') {
			var titleInput = qs('fthemeHtmlTitle');
			var contentInput = qs('fthemeHtmlContent');
			if (titleInput) {
				titleInput.value = block.title || '';
			}
			if (contentInput) {
				contentInput.value = block.content || '';
			}
			setTimeout(function () {
				if (titleInput) {
					titleInput.focus();
				}
			}, 120);
		}

		if (block.type === 'banner') {
			var imageInput = qs('fthemeBannerImage');
			var linkInput = qs('fthemeBannerLink');
			var widthInput = qs('fthemeBannerWidth');
			if (imageInput) {
				imageInput.value = block.image || '';
			}
			if (linkInput) {
				linkInput.value = block.link || '';
			}
			if (widthInput) {
				widthInput.value = String(block.width || 100);
			}
			updateBannerPreview();
			setTimeout(function () {
				if (imageInput) {
					imageInput.focus();
				}
			}, 120);
		}

		if (sidebar) {
			sidebar.scrollTop = 0;
		}

		postPreview({ type: 'highlightBlock', blockId: block.id });
		showToast('Editör açıldı — üstten düzenleyin');
	}

	function resolveImageUrl(path) {
		if (!path) {
			return '';
		}

		if (/^https?:\/\//i.test(path)) {
			return path;
		}

		var base = (boot.domain || '/').replace(/\/?$/, '/');

		if (path.indexOf('img/') === 0) {
			return base + path;
		}

		return base + 'img/' + path.replace(/^\/+/, '');
	}

	function updateBannerPreview() {
		var previewWrap = qs('fthemeBannerPreview');
		var previewImg = qs('fthemeBannerPreviewImg');
		var imageInput = qs('fthemeBannerImage');

		if (!previewWrap || !imageInput) {
			return;
		}

		var url = resolveImageUrl((imageInput.value || '').trim());

		if (!url) {
			if (previewImg) {
				previewImg.hidden = true;
				previewImg.removeAttribute('src');
			}
			previewWrap.innerHTML = '<div class="text-muted small p-3">Medyadan görsel seçin</div>';
			return;
		}

		if (!previewImg) {
			previewWrap.innerHTML = '<img src="' + escapeAttr(url) + '" alt="Banner önizleme">';
			return;
		}

		previewWrap.innerHTML = '';
		previewWrap.appendChild(previewImg);
		previewImg.src = url;
		previewImg.hidden = false;
	}

	function escapeAttr(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}

	function closeBlockEditor() {
		editingBlock = null;
		selectedBlockId = null;

		var editor = qs('fthemeBlockEditor');
		if (editor) {
			editor.hidden = true;
		}

		renderBlockList();
		postPreview({ type: 'highlightBlock', blockId: '' });
	}

	function bindBlockEditor() {
		var titleInput = qs('fthemeHtmlTitle');
		var contentInput = qs('fthemeHtmlContent');
		var imageInput = qs('fthemeBannerImage');
		var linkInput = qs('fthemeBannerLink');
		var widthInput = qs('fthemeBannerWidth');
		var closeBtn = qs('fthemeBlockEditorClose');

		if (titleInput) {
			titleInput.addEventListener('input', function () {
				if (!editingBlock || editingBlock.type !== 'html') {
					return;
				}
				editingBlock.title = titleInput.value;
				editingBlock.label = titleInput.value || 'Özel bölüm';
				var titleLabel = qs('fthemeBlockEditorTitle');
				if (titleLabel) {
					titleLabel.textContent = editingBlock.label;
				}
				renderBlockList();
				markDirty();
				pushLivePreviewDebounced();
			});
		}

		if (contentInput) {
			contentInput.addEventListener('input', function () {
				if (!editingBlock || editingBlock.type !== 'html') {
					return;
				}
				editingBlock.content = contentInput.value;
				markDirty();
				pushLivePreviewDebounced();
			});
		}

		function applyBannerField() {
			if (!editingBlock || editingBlock.type !== 'banner') {
				return;
			}
			editingBlock.image = imageInput ? imageInput.value.trim() : '';
			editingBlock.link = linkInput ? linkInput.value.trim() : '';
			editingBlock.width = widthInput ? parseInt(widthInput.value, 10) || 100 : 100;
			editingBlock.label = 'Banner (%' + editingBlock.width + ')';
			var titleLabel = qs('fthemeBlockEditorTitle');
			if (titleLabel) {
				titleLabel.textContent = editingBlock.label;
			}
			updateBannerPreview();
			renderBlockList();
			markDirty();
			pushLivePreviewDebounced();
		}

		if (imageInput) {
			imageInput.addEventListener('input', applyBannerField);
			imageInput.addEventListener('change', applyBannerField);
		}
		if (linkInput) {
			linkInput.addEventListener('input', applyBannerField);
		}
		if (widthInput) {
			widthInput.addEventListener('change', applyBannerField);
		}

		if (closeBtn) {
			closeBtn.addEventListener('click', closeBlockEditor);
		}
	}

	var reloadTimer = null;
	function reloadPreviewDebounced() {
		clearTimeout(reloadTimer);
		reloadTimer = setTimeout(reloadPreview, 500);
	}

	function reloadPreview() {
		if (!frame) {
			return;
		}
		var sep = boot.previewUrl.indexOf('?') >= 0 ? '&' : '?';
		frame.src = boot.previewUrl + sep + '_=' + Date.now();
	}

	function renderColorField(container, key, label) {
		var row = document.createElement('div');
		row.className = 'ftheme-color-row';
		row.innerHTML =
			'<label>' + escapeHtml(label) + '</label>' +
			'<input type="color" data-color-key="' + escapeHtml(key) + '">';

		var text = document.createElement('input');
		text.type = 'text';
		text.className = 'form-control form-control-sm';
		text.value = state.colors[key] || '';
		text.setAttribute('data-color-key', key);

		row.insertBefore(text, row.lastElementChild);

		var picker = row.querySelector('input[type="color"]');
		if (picker && /^#([0-9a-f]{6})$/i.test(text.value)) {
			picker.value = text.value;
		}

		text.addEventListener('input', function () {
			state.colors[key] = text.value;
			if (picker && /^#([0-9a-f]{6})$/i.test(text.value)) {
				picker.value = text.value;
			}
			postPreview({ type: 'updateColors', colors: state.colors, colorAliases: state.colorAliases || {} });
			markDirty();
		});

		picker.addEventListener('input', function () {
			text.value = picker.value;
			state.colors[key] = picker.value;
			postPreview({ type: 'updateColors', colors: state.colors, colorAliases: state.colorAliases || {} });
			markDirty();
		});

		container.appendChild(row);
	}

	function renderColors() {
		var quick = qs('fthemeQuickColors');
		var all = qs('fthemeAllColors');
		if (!quick || !all) {
			return;
		}

		quick.innerHTML = '';
		all.innerHTML = '';

		Object.keys(state.quickColors || {}).forEach(function (key) {
			renderColorField(quick, key, state.quickColors[key]);
		});

		Object.keys(state.colorGroups || {}).forEach(function (groupLabel) {
			var fields = state.colorGroups[groupLabel] || {};
			var extraKeys = Object.keys(fields).filter(function (key) {
				return !state.quickColors || !state.quickColors[key];
			});

			if (!extraKeys.length) {
				return;
			}

			var title = document.createElement('div');
			title.className = 'small fw-semibold mt-3 mb-2 text-uppercase';
			title.textContent = groupLabel;
			all.appendChild(title);

			extraKeys.forEach(function (key) {
				renderColorField(all, key, fields[key]);
			});
		});
	}

	function renderAddBlockMenu() {
		var menu = qs('fthemeAddBlockMenu');
		if (!menu) {
			return;
		}

		menu.innerHTML = '';
		(state.blockTypes || []).forEach(function (item) {
			var li = document.createElement('li');
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'dropdown-item';
			btn.textContent = item.label;
			btn.addEventListener('click', function () {
				addBlock(item.type);
			});
			li.appendChild(btn);
			menu.appendChild(li);
		});
	}

	function addBlock(type) {
		var id = (type === 'html' || type === 'banner') ? ('custom_' + Date.now().toString(36)) : type;
		var exists = state.blocks.some(function (block) { return block.id === id; });

		if (exists && type !== 'html' && type !== 'banner') {
			showToast('Bu blok zaten listede.');
			return;
		}

		var block = {
			id: id,
			type: type,
			enabled: true,
			label: labelForType(type)
		};

		if (type === 'categories') {
			block.limit = 2;
		}

		if (type === 'html') {
			block.title = 'Yeni bölüm';
			block.content = '<p>Yeni içerik buraya yazılır.</p>';
		}

		if (type === 'banner') {
			block.image = '';
			block.link = '';
			block.width = 50;
			block.label = 'Banner (%50)';
		}

		state.blocks.push(block);
		renderBlockList();
		pushLivePreview();
		markDirty();

		if (type === 'html' || type === 'banner') {
			openBlockEditor(block);
		}
	}

	function labelForType(type) {
		var map = {
			slider: 'Ana slider',
			featured: 'Öne çıkan modüller',
			promo: 'Promo slider',
			categories: 'Kategori blokları',
			home_text: 'Ana sayfa metni',
			html: 'Özel bölüm',
			banner: 'Banner (%50)'
		};
		return map[type] || 'Blok';
	}

	function bindLayoutControls() {
		var headerSelect = qs('fthemeHeaderSelect');
		var footerSelect = qs('fthemeFooterSelect');
		var fontInput = qs('fthemeFontInput');

		if (headerSelect) {
			Object.keys(boot.headerVariants || {}).forEach(function (key) {
				var opt = document.createElement('option');
				opt.value = key;
				opt.textContent = boot.headerVariants[key];
				headerSelect.appendChild(opt);
			});
			headerSelect.value = getSetting('HEADER') || '1';
			headerSelect.addEventListener('change', function () {
				setSetting('HEADER', headerSelect.value);
				reloadPreview();
			});
		}

		if (footerSelect) {
			Object.keys(boot.footerVariants || {}).forEach(function (key) {
				var opt = document.createElement('option');
				opt.value = key;
				opt.textContent = boot.footerVariants[key];
				footerSelect.appendChild(opt);
			});
			footerSelect.value = getSetting('FOOTER') || '1';
			footerSelect.addEventListener('change', function () {
				setSetting('FOOTER', footerSelect.value);
				reloadPreview();
			});
		}

		if (fontInput) {
			fontInput.value = getSetting('THEME-FONT') || 'Poppins';
			fontInput.addEventListener('input', function () {
				setSetting('THEME-FONT', fontInput.value);
				postPreview({ type: 'updateColors', colors: state.colors, font: fontInput.value });
			});
		}

		bindToggle('fthemeLoadingToggle', 'LOADING');
		bindToggle('fthemeTopBarToggle', 'SHOW-TOP-BAR');
		bindToggle('fthemeGotoTopToggle', 'GOTO-TOP');
		bindToggle('fthemeCookieToggle', 'SHOW-COOKIE', true);
	}

	function bindToggle(id, key, reload) {
		var el = qs(id);
		if (!el) {
			return;
		}

		el.checked = getSetting(key) === '1';
		el.addEventListener('change', function () {
			setSetting(key, el.checked ? '1' : '0');
			if (reload) {
				reloadPreview();
			}
		});
	}

	function showRegionEditor(regionId) {
		var region = state.regions[regionId];
		if (!region) {
			return;
		}

		selectedRegion = regionId;
		switchPanel('edit');

		var hint = qs('fthemeEditHint');
		var form = qs('fthemeEditForm');
		var label = qs('fthemeEditLabel');
		var input = qs('fthemeEditInput');
		var textarea = qs('fthemeEditTextarea');

		if (!region || !form || !label || !input || !textarea) {
			return;
		}

		hint.classList.add('d-none');
		form.classList.remove('d-none');
		label.textContent = region.label;

		var value = getSetting(region.setting);
		input.classList.add('d-none');
		textarea.classList.add('d-none');

		if (region.type === 'textarea') {
			textarea.classList.remove('d-none');
			textarea.value = value;
		} else {
			input.classList.remove('d-none');
			input.value = value;
		}

		postPreview({ type: 'highlight', region: regionId });
	}

	function bindCodeEditors() {
		var cssInput = qs('fthemeCustomCss');
		var jsInput = qs('fthemeCustomJs');

		if (cssInput) {
			cssInput.value = state.customCss || '';
			cssInput.addEventListener('input', function () {
				state.customCss = cssInput.value;
				markDirty();
				postPreview({ type: 'updateCustomCss', css: state.customCss });
			});
		}

		if (jsInput) {
			jsInput.value = state.customJs || '';
			jsInput.addEventListener('input', function () {
				state.customJs = jsInput.value;
				markDirty();
				reloadPreviewDebounced();
			});
		}
	}

	function bindEditInputs() {
		var input = qs('fthemeEditInput');
		var textarea = qs('fthemeEditTextarea');

		function apply(value) {
			if (!selectedRegion) {
				return;
			}

			var key = settingKey(selectedRegion);
			if (!key) {
				return;
			}

			setSetting(key, value);
			postPreview({ type: 'updateSetting', region: selectedRegion, value: value });
		}

		if (input) {
			input.addEventListener('input', function () {
				apply(input.value);
			});
		}

		if (textarea) {
			textarea.addEventListener('input', function () {
				apply(textarea.value);
			});
		}
	}

	function escapeHtml(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	document.querySelectorAll('.ftheme-nav-btn').forEach(function (btn) {
		btn.addEventListener('click', function () {
			switchPanel(btn.getAttribute('data-panel'));
		});
	});

	var reloadBtn = qs('fthemeReloadPreview');
	if (reloadBtn) {
		reloadBtn.addEventListener('click', reloadPreview);
	}

	var publishBtn = qs('fthemePublishBtn');
	if (publishBtn) {
		publishBtn.addEventListener('click', function () {
			publishBtn.disabled = true;
			publishBtn.textContent = 'Kaydediliyor…';

			fetch(boot.configUrl + '?customize=1', {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'Accept': 'application/json'
				},
				body: JSON.stringify({
					saveCustomizer: 1,
					token: boot.adminToken,
					payload: {
						settings: state.settings,
						colors: state.colors,
						blocks: state.blocks,
						customCss: state.customCss,
						customJs: state.customJs
					}
				})
			})
				.then(function (res) { return res.json(); })
				.then(function (result) {
					showToast(result.message || (result.success ? 'Kaydedildi' : 'Hata'));
					if (result.success) {
						dirty = false;
					}
				})
				.catch(function () {
					showToast('Kayıt sırasında hata oluştu');
				})
				.finally(function () {
					publishBtn.disabled = false;
					publishBtn.textContent = 'Yayınla';
				});
		});
	}

	window.addEventListener('message', function (event) {
		var data = event.data || {};
		if (data.source !== 'ftheme-preview') {
			return;
		}

		if (data.type === 'selectRegion') {
			showRegionEditor(data.region);
		}

		if (data.type === 'addBlockHere') {
			switchPanel('blocks');
			addBlock('banner');
		}

		if (data.type === 'selectHtmlBlock' || data.type === 'selectCustomBlock') {
			var block = state.blocks.find(function (item) {
				return item.id === data.blockId;
			});
			if (block) {
				openBlockEditor(block);
			}
		}
	});

	frame.addEventListener('load', function () {
		postPreview({
			type: 'init',
			colors: state.colors,
			colorAliases: state.colorAliases || {},
			regions: Object.keys(state.regions),
			customCss: state.customCss,
			customJs: state.customJs,
			blocks: state.blocks,
			domain: boot.domain || ''
		});
	});

	renderBlockList();
	renderColors();
	renderAddBlockMenu();
	bindLayoutControls();
	bindBlockEditor();
	bindCodeEditors();
	bindEditInputs();
})();
