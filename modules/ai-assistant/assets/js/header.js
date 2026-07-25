(function () {
	'use strict';

	var cfg = window.AiAssistantHeader || {};
	var btn = document.getElementById('aiHeaderPrimaryBtn');
	var statusEl = document.getElementById('aiHeaderStatus');

	if (!btn || !cfg.mode) {
		return;
	}

	var translateModeSelect = document.getElementById('aiTranslateMode');

	if (translateModeSelect && cfg.mode === 'translate') {
		translateModeSelect.addEventListener('change', function () {
			btn.textContent = translateModeSelect.value === 'polish_en'
				? 'İngilizce kaynağı iyileştir'
				: 'Boşları AI ile çevir';
		});
	}

	function setStatus(msg, type) {
		if (!statusEl) {
			return;
		}

		statusEl.textContent = msg || '';
		statusEl.className = 'ai-assist-header__status small mb-0'
			+ (type === 'error' ? ' text-danger' : type === 'success' ? ' text-success' : ' text-muted');
	}

	function escapeHtml(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function markdownLite(text) {
		var html = escapeHtml(text || '');
		html = html.replace(/^### (.+)$/gm, '<h5>$1</h5>');
		html = html.replace(/^## (.+)$/gm, '<h4>$1</h4>');
		html = html.replace(/^# (.+)$/gm, '<h3>$1</h3>');
		html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
		html = html.replace(/^- (.+)$/gm, '<li>$1</li>');
		html = html.replace(/(?:<li>.*<\/li>\n?)+/g, function (block) {
			return '<ul>' + block + '</ul>';
		});
		html = html.replace(/\n{2,}/g, '</p><p>');
		html = html.replace(/\n/g, '<br>');
		return '<div class="ai-modal-analysis"><p>' + html + '</p></div>';
	}

	function scrapePageText() {
		var root = document.querySelector('.admin-content');

		if (!root) {
			return '';
		}

		var clone = root.cloneNode(true);
		clone.querySelectorAll('#aiAssistHeader, script, style, .ai-assist-header').forEach(function (el) {
			el.remove();
		});

		return (clone.innerText || '').replace(/\s+/g, ' ').trim().slice(0, 12000);
	}

	function postForm(url, fields) {
		var body = new FormData();
		body.append('token', cfg.token || '');

		Object.keys(fields || {}).forEach(function (key) {
			body.append(key, fields[key]);
		});

		return fetch(url, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (res) { return res.json(); });
	}

	function openAnalysis(title, analysis, message) {
		if (!window.AiAssistantModal) {
			return;
		}

		window.AiAssistantModal.open({
			title: title,
			body: markdownLite(analysis || '')
				+ (message ? '<p class="small text-success mb-0 mt-2">' + escapeHtml(message) + '</p>' : ''),
			footer: '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>'
		});
	}

	function formatField(label, value) {
		return '<div class="ai-modal-field">'
			+ '<div class="ai-modal-field__label">' + escapeHtml(label) + '</div>'
			+ '<div class="ai-modal-field__value">' + (value || '<span class="text-muted">—</span>') + '</div>'
			+ '</div>';
	}

	function setTinyOrInput(el, value) {
		if (!el) {
			return;
		}

		el.value = value;

		if (window.tinymce) {
			var ed = tinymce.get(el.id);

			if (ed) {
				ed.setContent(value || '');
			}
		}

		el.dispatchEvent(new Event('input', { bubbles: true }));
		el.dispatchEvent(new Event('change', { bubbles: true }));
	}

	function runSummary() {
		if (window.AiAssistantModal) {
			window.AiAssistantModal.loading(
				cfg.mode === 'dashboard' ? 'Panel analiz ediliyor' : 'Sayfa özetleniyor',
				'Lütfen bekleyin…'
			);
		}

		return postForm(cfg.apiSummary, {
			page_name: cfg.pageName || '',
			page_title: cfg.pageTitle || document.title || '',
			page_text: scrapePageText()
		}).then(function (data) {
			if (!data || !data.success) {
				throw new Error((data && data.message) || 'Özet alınamadı');
			}

			openAnalysis(
				cfg.mode === 'dashboard' ? 'Panel analizi' : 'Sayfa özeti',
				data.analysis || '',
				data.message || ''
			);
			setStatus(data.message || 'Hazır', 'success');
		});
	}

	function collectSeoPages() {
		var pages = [];

		document.querySelectorAll('.admin-panel .border.rounded.p-3.mb-3').forEach(function (block) {
			var titleInput = block.querySelector('input[name^="seo_"][name$="_title"]');
			var descInput = block.querySelector('input[name^="seo_"][name$="_description"]');

			if (!titleInput) {
				return;
			}

			var name = titleInput.getAttribute('name') || '';
			var match = name.match(/^seo_(.+)_title$/);

			if (!match) {
				return;
			}

			var labelEl = block.querySelector('h3');

			pages.push({
				id: match[1],
				label: labelEl ? labelEl.textContent.trim() : match[1],
				title: titleInput.value || '',
				description: descInput ? (descInput.value || '') : '',
				default_title: titleInput.getAttribute('placeholder') || '',
				default_desc: descInput ? (descInput.getAttribute('placeholder') || '') : ''
			});
		});

		return pages;
	}

	function applySeoSuggestions(suggestions) {
		Object.keys(suggestions || {}).forEach(function (pageId) {
			var row = suggestions[pageId] || {};
			var titleInput = document.querySelector('input[name="seo_' + pageId + '_title"]');
			var descInput = document.querySelector('input[name="seo_' + pageId + '_description"]');

			if (titleInput && row.title) {
				titleInput.value = row.title;
				titleInput.dispatchEvent(new Event('input', { bubbles: true }));
			}

			if (descInput && row.description) {
				descInput.value = row.description;
				descInput.dispatchEvent(new Event('input', { bubbles: true }));
			}
		});
	}

	function runSeo() {
		var pages = collectSeoPages();

		if (!pages.length) {
			throw new Error('SEO form alanları bulunamadı');
		}

		if (window.AiAssistantModal) {
			window.AiAssistantModal.loading('SEO yazılıyor', 'Meta başlık ve açıklamalar hazırlanıyor…');
		}

		return postForm(cfg.apiSeo, {
			pages_json: JSON.stringify(pages)
		}).then(function (data) {
			if (!data || !data.success) {
				throw new Error((data && data.message) || 'SEO yazılamadı');
			}

			var suggestions = data.suggestions || {};
			var html = '';

			Object.keys(suggestions).forEach(function (pageId) {
				var row = suggestions[pageId] || {};
				html += '<div class="border rounded p-2 mb-2">'
					+ '<strong class="small">' + escapeHtml(pageId) + '</strong>'
					+ formatField('Meta title', escapeHtml(row.title || ''))
					+ formatField('Meta description', escapeHtml(row.description || ''))
					+ '</div>';
			});

			if (data.notes) {
				html += '<div class="alert alert-light border small mb-0">' + escapeHtml(data.notes) + '</div>';
			}

			window.AiAssistantModal.open({
				title: 'AI SEO önerileri',
				body: html,
				footer: ''
					+ '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>'
					+ '<button type="button" class="btn btn-dark btn-sm" id="aiSeoApplyBtn">Forma yaz</button>'
			});

			var applyBtn = document.getElementById('aiSeoApplyBtn');

			if (applyBtn) {
				applyBtn.addEventListener('click', function () {
					applySeoSuggestions(suggestions);
					setStatus('SEO alanları güncellendi. Kaydetmeyi unutmayın.', 'success');
					window.AiAssistantModal.close();
				});
			}

			setStatus(data.message || 'Hazır', 'success');
		});
	}

	function getActiveCmsLang() {
		var activePane = document.querySelector('.tab-pane.show.active[id^="cms-pane-"]');

		if (activePane && activePane.id) {
			return activePane.id.replace('cms-pane-', '');
		}

		var first = document.querySelector('.tab-pane[id^="cms-pane-"]');

		return first && first.id ? first.id.replace('cms-pane-', '') : '';
	}

	function cmsField(name, lang) {
		return document.querySelector('[name="langs[' + lang + '][' + name + ']"]');
	}

	function readCmsFields() {
		var lang = getActiveCmsLang();

		function val(name) {
			var el = cmsField(name, lang);
			return el ? el.value : '';
		}

		return {
			lang: lang,
			title: val('title'),
			summary: val('summary'),
			content: val('content'),
			meta_title: val('meta_title'),
			meta_description: val('meta_description'),
			slug: val('slug')
		};
	}

	function applyCmsSuggestions(data) {
		var lang = getActiveCmsLang();

		if (!data) {
			return;
		}

		['title', 'summary', 'content', 'meta_title', 'meta_description'].forEach(function (key) {
			if (data[key]) {
				setTinyOrInput(cmsField(key, lang), data[key]);
			}
		});
	}

	function runCms() {
		var current = readCmsFields();

		if (!current.lang) {
			throw new Error('CMS dil sekmesi bulunamadı');
		}

		if (window.AiAssistantModal) {
			window.AiAssistantModal.loading('CMS yazılıyor', 'İçerik ve SEO alanları hazırlanıyor…');
		}

		return postForm(cfg.apiCms, {
			title: current.title,
			summary: current.summary,
			content: current.content,
			meta_title: current.meta_title,
			meta_description: current.meta_description,
			slug: current.slug,
			tone: cfg.tone || 'professional',
			lang: current.lang
		}).then(function (data) {
			if (!data || !data.success) {
				throw new Error((data && data.message) || 'CMS yazılamadı');
			}

			var suggestions = data.suggestions || {};
			var html = formatField('Başlık', escapeHtml(suggestions.title || ''));
			html += formatField('Kısa açıklama', escapeHtml(suggestions.summary || ''));
			html += formatField(
				'İçerik',
				'<div class="ai-modal-description">' + escapeHtml(suggestions.content || '').replace(/\n/g, '<br>') + '</div>'
			);
			html += formatField('Meta title', escapeHtml(suggestions.meta_title || ''));
			html += formatField('Meta description', escapeHtml(suggestions.meta_description || ''));

			if (data.notes) {
				html += '<div class="alert alert-light border small mb-0 mt-2">' + escapeHtml(data.notes) + '</div>';
			}

			window.AiAssistantModal.open({
				title: 'AI CMS önerileri',
				body: html,
				footer: ''
					+ '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>'
					+ '<button type="button" class="btn btn-dark btn-sm" id="aiCmsApplyBtn">Forma yaz</button>'
			});

			var applyBtn = document.getElementById('aiCmsApplyBtn');

			if (applyBtn) {
				applyBtn.addEventListener('click', function () {
					applyCmsSuggestions(suggestions);
					setStatus('CMS alanları güncellendi. Kaydetmeyi unutmayın.', 'success');
					window.AiAssistantModal.close();
				});
			}

			setStatus(data.message || 'Hazır', 'success');
		});
	}

	function blogField(name) {
		return document.querySelector('#blogPostForm [name="' + name + '"]');
	}

	function readBlogFields() {
		var ideaEl = document.getElementById('aiBlogIdea');

		function val(name) {
			var el = blogField(name);
			return el ? el.value : '';
		}

		return {
			idea: ideaEl ? ideaEl.value.trim() : '',
			title: val('title'),
			excerpt: val('excerpt'),
			content: val('content'),
			meta_title: val('meta_title'),
			meta_description: val('meta_description'),
			slug: val('slug'),
			editing: !!cfg.blogEditing || !!(blogField('id_blog_post') && parseInt(blogField('id_blog_post').value, 10) > 0)
		};
	}

	function applyBlogSuggestions(data) {
		if (!data) {
			return;
		}

		['title', 'excerpt', 'content', 'meta_title', 'meta_description', 'slug'].forEach(function (key) {
			if (data[key]) {
				setTinyOrInput(blogField(key), data[key]);
			}
		});
	}

	function runBlog() {
		if (!document.getElementById('blogPostForm')) {
			throw new Error('Blog yazı formu bulunamadı. Yazılar sekmesine geçin.');
		}

		var current = readBlogFields();

		if (!current.idea && !current.title && !current.content && !current.excerpt) {
			throw new Error('Önce bir konu/fikir girin veya formda başlık doldurun.');
		}

		if (window.AiAssistantModal) {
			window.AiAssistantModal.loading(
				current.editing ? 'Blog düzenleniyor' : 'Blog yazılıyor',
				'Başlık, özet, içerik ve SEO hazırlanıyor…'
			);
		}

		return postForm(cfg.apiBlog, {
			idea: current.idea,
			title: current.title,
			excerpt: current.excerpt,
			content: current.content,
			meta_title: current.meta_title,
			meta_description: current.meta_description,
			slug: current.slug,
			editing: current.editing ? '1' : '0',
			tone: cfg.tone || 'professional'
		}).then(function (data) {
			if (!data || !data.success) {
				throw new Error((data && data.message) || 'Blog yazılamadı');
			}

			var suggestions = data.suggestions || {};
			var html = formatField('Başlık', escapeHtml(suggestions.title || ''));
			html += formatField('Özet', escapeHtml(suggestions.excerpt || ''));
			html += formatField(
				'İçerik',
				'<div class="ai-modal-description">' + escapeHtml(suggestions.content || '').replace(/\n/g, '<br>') + '</div>'
			);
			html += formatField('Meta title', escapeHtml(suggestions.meta_title || ''));
			html += formatField('Meta description', escapeHtml(suggestions.meta_description || ''));
			html += formatField('Slug', escapeHtml(suggestions.slug || ''));

			if (data.notes) {
				html += '<div class="alert alert-light border small mb-0 mt-2">' + escapeHtml(data.notes) + '</div>';
			}

			window.AiAssistantModal.open({
				title: 'AI blog önerileri',
				body: html,
				footer: ''
					+ '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>'
					+ '<button type="button" class="btn btn-dark btn-sm" id="aiBlogApplyBtn">Forma yaz</button>'
			});

			var applyBtn = document.getElementById('aiBlogApplyBtn');

			if (applyBtn) {
				applyBtn.addEventListener('click', function () {
					applyBlogSuggestions(suggestions);
					setStatus('Blog alanları güncellendi. Kaydetmeyi unutmayın.', 'success');
					window.AiAssistantModal.close();
				});
			}

			setStatus(data.message || 'Hazır', 'success');
		});
	}

	function getActiveProductLang() {
		var activePane = document.querySelector('.tab-pane.show.active[id^="product-pane-"]');

		if (activePane && activePane.id) {
			return activePane.id.replace('product-pane-', '');
		}

		var first = document.querySelector('.tab-pane[id^="product-pane-"]');

		return first && first.id ? first.id.replace('product-pane-', '') : '';
	}

	function productField(name, lang) {
		if (lang) {
			return document.querySelector('[name="langs[' + lang + '][' + name + ']"]');
		}

		return document.querySelector('[name="' + name + '"]');
	}

	function readProductFields() {
		var lang = getActiveProductLang();

		function val(name) {
			var el = productField(name, lang);
			return el ? el.value : '';
		}

		return {
			lang: lang,
			product_name: val('product_name'),
			short_description: val('short_description'),
			description: val('description'),
			meta_title: val('meta_title'),
			meta_description: val('meta_description')
		};
	}

	function applyProductSuggestions(data) {
		var lang = getActiveProductLang();

		if (!data) {
			return;
		}

		['product_name', 'short_description', 'description', 'meta_title', 'meta_description'].forEach(function (key) {
			if (data[key]) {
				setTinyOrInput(productField(key, lang), data[key]);
			}
		});
	}

	function runProduct() {
		var current = readProductFields();
		var hasContent = !!(current.product_name || current.short_description || current.description);

		if (!hasContent) {
			throw new Error('İyileştirilecek en az bir alan doldurun (ürün adı veya açıklama).');
		}

		if (window.AiAssistantModal) {
			window.AiAssistantModal.loading('Ürün metinleri iyileştiriliyor', 'Başlık, açıklama ve SEO hazırlanıyor…');
		}

		return postForm(cfg.apiProduct, {
			tone: cfg.tone || 'professional',
			product_name: current.product_name,
			short_description: current.short_description,
			description: current.description,
			meta_title: current.meta_title,
			meta_description: current.meta_description
		}).then(function (data) {
			if (!data || !data.success) {
				throw new Error((data && data.message) || 'Ürün metni iyileştirilemedi');
			}

			var suggestions = data.suggestions || {};
			var html = formatField('Başlık', escapeHtml(suggestions.product_name || ''));
			html += formatField('Kısa açıklama', escapeHtml(suggestions.short_description || ''));
			html += formatField(
				'Uzun açıklama',
				'<div class="ai-modal-description">' + escapeHtml(suggestions.description || '').replace(/\n/g, '<br>') + '</div>'
			);
			html += formatField('Meta title', escapeHtml(suggestions.meta_title || ''));
			html += formatField('Meta description', escapeHtml(suggestions.meta_description || ''));

			if (data.notes) {
				html += '<div class="alert alert-light border small mb-0 mt-2">' + escapeHtml(data.notes) + '</div>';
			}

			window.AiAssistantModal.open({
				title: 'AI ürün metni önerileri',
				body: html,
				footer: ''
					+ '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>'
					+ '<button type="button" class="btn btn-dark btn-sm" id="aiProductApplyBtn">Forma yaz</button>'
			});

			var applyBtn = document.getElementById('aiProductApplyBtn');

			if (applyBtn) {
				applyBtn.addEventListener('click', function () {
					applyProductSuggestions(suggestions);
					setStatus('Ürün alanları güncellendi. Kaydetmeyi unutmayın.', 'success');
					window.AiAssistantModal.close();
				});
			}

			setStatus(data.message || 'Hazır', 'success');
		});
	}

	function collectTranslationItems(mode) {
		var items = [];
		var rows = document.querySelectorAll('#uiTranslationsTable tbody tr.ui-tr-row');

		rows.forEach(function (row) {
			var key = row.getAttribute('data-key') || '';
			var enInput = row.querySelector('.ui-en-input');
			var trInput = row.querySelector('.ui-tr-input');

			if (!key || !enInput) {
				return;
			}

			var en = (enInput.value || '').trim();

			if (!en) {
				return;
			}

			if (mode === 'translate') {
				var missing = trInput && trInput.getAttribute('data-missing') === '1';
				var empty = !trInput || !(trInput.value || '').trim();

				if (!missing && !empty) {
					return;
				}
			}

			items.push({ key: key, en: en });
		});

		return items;
	}

	function applyTranslationMap(map, field) {
		Object.keys(map || {}).forEach(function (key) {
			var selector = field === 'en' ? '.ui-en-input' : '.ui-tr-input';
			var inputs = document.querySelectorAll(selector);
			var input = null;

			for (var i = 0; i < inputs.length; i++) {
				if (inputs[i].getAttribute('data-key') === key) {
					input = inputs[i];
					break;
				}
			}

			if (!input || !map[key]) {
				return;
			}

			input.value = map[key];
			input.setAttribute('data-missing', '0');
			input.dispatchEvent(new Event('input', { bubbles: true }));

			var row = input.closest('tr');

			if (row && field === 'tr') {
				row.classList.remove('table-warning');
			}
		});
	}

	function runTranslateBatch(allItems, mode, targetLang, offset, filled) {
		offset = offset || 0;
		filled = filled || 0;

		if (offset >= allItems.length) {
			return Promise.resolve({ filled: filled, total: allItems.length });
		}

		var chunk = allItems.slice(offset, offset + 35);
		setStatus('AI çalışıyor… ' + Math.min(offset + chunk.length, allItems.length) + '/' + allItems.length);

		return postForm(cfg.apiTranslate, {
			mode: mode,
			target_lang: targetLang,
			items: JSON.stringify(chunk)
		}).then(function (data) {
			if (!data || !data.success) {
				throw new Error((data && data.message) || 'Çeviri alınamadı');
			}

			if (mode === 'polish_en') {
				applyTranslationMap(data.english || {}, 'en');
				filled += Object.keys(data.english || {}).length;
			} else {
				applyTranslationMap(data.translations || {}, 'tr');
				filled += Object.keys(data.translations || {}).length;
			}

			return runTranslateBatch(allItems, mode, targetLang, offset + chunk.length, filled);
		});
	}

	function runTranslate() {
		if (!cfg.apiTranslate) {
			throw new Error('Çeviri API adresi yok');
		}

		var modeSelect = document.getElementById('aiTranslateMode');
		var mode = modeSelect ? (modeSelect.value || 'translate') : 'translate';
		var pageCfg = window.UiTranslationsPage || {};
		var targetLang = cfg.targetLang || pageCfg.targetLang || 'tr';
		var items = collectTranslationItems(mode);

		if (!items.length) {
			throw new Error(mode === 'polish_en'
				? 'İyileştirilecek İngilizce satır yok'
				: 'Boş çeviri satırı yok (veya hepsi dolu)');
		}

		if (window.AiAssistantModal) {
			window.AiAssistantModal.loading(
				mode === 'polish_en' ? 'İngilizce iyileştiriliyor' : 'Çeviriler üretiliyor',
				items.length + ' satır işlenecek…'
			);
		}

		return runTranslateBatch(items, mode, targetLang, 0, 0).then(function (summary) {
			if (window.AiAssistantModal) {
				window.AiAssistantModal.open({
					title: mode === 'polish_en' ? 'İngilizce iyileştirildi' : 'Çeviriler hazır',
					body: '<p class="mb-0">' + escapeHtml(String(summary.filled || 0))
						+ ' satır forma yazıldı. Kaydetmeyi unutmayın.</p>',
					footer: '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>'
				});
			}

			setStatus((summary.filled || 0) + ' satır güncellendi — kaydedin', 'success');
		});
	}

	btn.addEventListener('click', function () {
		if (!cfg.configured) {
			window.location.href = cfg.settingsUrl || '#';
			return;
		}

		var action = btn.getAttribute('data-action') || cfg.mode || 'summary';
		btn.disabled = true;
		setStatus('Yapay zeka çalışıyor…');

		var runner;

		if (action === 'seo') {
			runner = runSeo();
		} else if (action === 'cms') {
			runner = runCms();
		} else if (action === 'blog') {
			runner = runBlog();
		} else if (action === 'product') {
			runner = runProduct();
		} else if (action === 'translate') {
			runner = runTranslate();
		} else {
			runner = runSummary();
		}

		Promise.resolve(runner)
			.catch(function (err) {
				var message = (err && err.message) ? err.message : 'İstek başarısız';
				setStatus(message, 'error');

				if (window.AiAssistantModal) {
					window.AiAssistantModal.open({
						title: 'Hata',
						body: '<div class="alert alert-danger mb-0">' + escapeHtml(message) + '</div>',
						footer: '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>'
					});
				}
			})
			.finally(function () {
				btn.disabled = false;
			});
	});
})();
