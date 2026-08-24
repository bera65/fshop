(function () {
	'use strict';

	var root = document.getElementById('productImageUploader');
	if (!root) {
		return;
	}

	var productUploadUrl = root.getAttribute('data-upload-url') || '';
	var mediaApiUrl = root.getAttribute('data-media-api') || '';
	var token = root.getAttribute('data-token') || '';
	var productId = Number(root.getAttribute('data-product-id') || 0);
	var enabled = root.getAttribute('data-enabled') === '1';
	var dropzone = root.querySelector('[data-dropzone]');
	var openBtn = root.querySelector('[data-open-media]');
	var gallery = root.querySelector('[data-gallery]');
	var statusEl = root.querySelector('[data-status]');
	var busy = false;

	var modalEl = document.getElementById('adminMediaLibraryModal');
	var gridEl = modalEl ? modalEl.querySelector('[data-ml-grid]') : null;
	var crumbEl = modalEl ? modalEl.querySelector('[data-ml-crumbs]') : null;
	var filterEl = modalEl ? modalEl.querySelector('[data-ml-filter]') : null;
	var metaEl = modalEl ? modalEl.querySelector('[data-ml-meta]') : null;
	var uploadInput = modalEl ? modalEl.querySelector('[data-ml-upload]') : null;
	var uploadBtn = modalEl ? modalEl.querySelector('[data-ml-upload-btn]') : null;
	var mkdirBtn = modalEl ? modalEl.querySelector('[data-ml-mkdir]') : null;
	var refreshBtn = modalEl ? modalEl.querySelector('[data-ml-refresh]') : null;
	var attachBtn = modalEl ? modalEl.querySelector('[data-ml-attach]') : null;
	var statusModalEl = modalEl ? modalEl.querySelector('[data-ml-status]') : null;

	var currentPath = '';
	var currentItems = [];
	var selected = {};
	var canUpload = false;
	var canMkdir = false;

	function setStatus(message, type) {
		if (!statusEl) {
			return;
		}
		statusEl.textContent = message || '';
		statusEl.className = 'pe-media-status' + (type ? ' is-' + type : '');
	}

	function setModalStatus(message, type) {
		if (!statusModalEl) {
			return;
		}
		statusModalEl.textContent = message || '';
		statusModalEl.className = 'small mb-0' + (type === 'error' ? ' text-danger' : type === 'success' ? ' text-success' : ' text-muted');
	}

	function escapeHtml(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function renderProductImages(images) {
		if (!gallery) {
			return;
		}

		if (!images || !images.length) {
			gallery.innerHTML = '<p class="pe-media-empty">Henüz görsel yok. Medya kütüphanesinden seçin.</p>';
			return;
		}

		var html = '';
		images.forEach(function (img) {
			var id = img.id_image || img.id || 0;
			var cover = Number(img.cover) === 1;
			html += ''
				+ '<div class="pe-media-card' + (cover ? ' is-cover' : '') + '" data-image-id="' + escapeHtml(id) + '">'
				+ '<div class="pe-media-thumb"><img src="' + escapeHtml(img.url) + '" alt=""></div>'
				+ (cover ? '<span class="pe-media-badge">Kapak</span>' : '')
				+ '<div class="pe-media-actions">'
				+ (cover ? '' : '<button type="button" class="btn btn-sm btn-outline-dark" data-action="cover">Kapak</button>')
				+ '<button type="button" class="btn btn-sm btn-outline-danger" data-action="delete">Sil</button>'
				+ '</div></div>';
		});
		gallery.innerHTML = html;
	}

	function selectedCount() {
		return Object.keys(selected).length;
	}

	function updateMeta() {
		if (metaEl) {
			metaEl.textContent = selectedCount()
				? selectedCount() + ' görsel seçildi'
				: 'Görsel seçin veya yeni yükleyin';
		}
		if (attachBtn) {
			attachBtn.disabled = !enabled || selectedCount() === 0 || busy;
		}
		if (uploadBtn) {
			uploadBtn.disabled = !canUpload || busy;
		}
		if (mkdirBtn) {
			mkdirBtn.disabled = !canMkdir || busy;
		}
	}

	function renderCrumbs(crumbs) {
		if (!crumbEl) {
			return;
		}
		var html = '';
		(crumbs || []).forEach(function (crumb, idx) {
			if (idx > 0) {
				html += '<span>/</span>';
			}
			html += '<button type="button" data-ml-path="' + escapeHtml(crumb.path || '') + '">' + escapeHtml(crumb.label) + '</button>';
		});
		crumbEl.innerHTML = html;
	}

	function filteredItems() {
		var q = filterEl ? String(filterEl.value || '').toLocaleLowerCase('tr-TR').trim() : '';
		if (!q) {
			return currentItems;
		}
		return currentItems.filter(function (item) {
			return String(item.name || '').toLocaleLowerCase('tr-TR').indexOf(q) !== -1;
		});
	}

	function renderGrid() {
		if (!gridEl) {
			return;
		}

		var items = filteredItems();
		if (!items.length) {
			gridEl.innerHTML = '<div class="ml-empty">Bu klasörde görsel yok.</div>';
			updateMeta();
			return;
		}

		var html = '';
		items.forEach(function (item) {
			if (item.type === 'dir') {
				html += ''
					+ '<div class="ml-item is-dir" data-type="dir" data-path="' + escapeHtml(item.path) + '">'
					+ '<div class="ml-thumb"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9l-.81-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg></div>'
					+ '<span class="ml-name" title="' + escapeHtml(item.name) + '">' + escapeHtml(item.name) + '</span>'
					+ '</div>';
				return;
			}

			var isSel = !!selected[item.path];
			html += ''
				+ '<div class="ml-item' + (isSel ? ' is-selected' : '') + '" data-type="file" data-path="' + escapeHtml(item.path) + '">'
				+ '<span class="ml-check"></span>'
				+ '<div class="ml-thumb"><img src="' + escapeHtml(item.url) + '" alt="" loading="lazy"></div>'
				+ '<span class="ml-name" title="' + escapeHtml(item.name) + '">' + escapeHtml(item.name) + '</span>'
				+ '</div>';
		});
		gridEl.innerHTML = html;
		updateMeta();
	}

	function api(action, extra) {
		extra = extra || {};
		var isForm = extra.formData instanceof FormData;
		var url = mediaApiUrl + (mediaApiUrl.indexOf('?') >= 0 ? '&' : '?') + 'action=' + encodeURIComponent(action);

		var options = {
			method: isForm || extra.method === 'POST' ? 'POST' : 'GET',
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		};

		if (isForm) {
			extra.formData.append('token', token);
			if (!extra.formData.has('action')) {
				extra.formData.append('action', action);
			}
			options.body = extra.formData;
			url = mediaApiUrl;
		} else if (options.method === 'POST') {
			var body = new FormData();
			body.append('token', token);
			body.append('action', action);
			Object.keys(extra.fields || {}).forEach(function (key) {
				var val = extra.fields[key];
				if (Array.isArray(val)) {
					val.forEach(function (v) {
						body.append(key + '[]', v);
					});
				} else {
					body.append(key, val);
				}
			});
			options.body = body;
			url = mediaApiUrl;
		} else {
			url += '&token=' + encodeURIComponent(token);
			if (extra.path !== undefined) {
				url += '&path=' + encodeURIComponent(extra.path);
			}
		}

		return fetch(url, options).then(function (res) {
			return res.text().then(function (text) {
				var data = null;
				try {
					data = text ? JSON.parse(text) : null;
				} catch (err) {
					throw new Error('Sunucu geçersiz yanıt döndürdü');
				}
				if (!res.ok && (!data || data.message === undefined)) {
					throw new Error('İstek başarısız (' + res.status + ')');
				}
				return data || { success: false, message: 'Boş yanıt' };
			});
		});
	}

	function loadPath(path) {
		if (!enabled || busy) {
			return;
		}

		busy = true;
		currentPath = path || '';
		if (gridEl) {
			gridEl.innerHTML = '<div class="ml-loading">Yükleniyor…</div>';
		}
		setModalStatus('Klasör okunuyor…', 'info');

		api('list', { path: currentPath })
			.then(function (data) {
				if (!data || !data.success) {
					setModalStatus((data && data.message) || 'Liste alınamadı', 'error');
					currentItems = [];
					renderGrid();
					return;
				}

				currentPath = data.path || '';
				currentItems = data.items || [];
				canUpload = !!data.can_upload;
				canMkdir = !!data.can_mkdir;
				renderCrumbs(data.breadcrumbs || []);
				renderGrid();
				setModalStatus(canUpload ? 'Bu klasöre yükleme yapılabilir.' : 'Görüntüleme modu — yükleme için media klasörüne gidin.', 'info');
			})
			.catch(function (err) {
				setModalStatus((err && err.message) || 'Bağlantı hatası', 'error');
			})
			.finally(function () {
				busy = false;
				updateMeta();
			});
	}

	function openModal() {
		if (!enabled || !modalEl) {
			return;
		}
		selected = {};
		if (filterEl) {
			filterEl.value = '';
		}
		if (window.bootstrap && bootstrap.Modal) {
			bootstrap.Modal.getOrCreateInstance(modalEl).show();
		} else {
			modalEl.classList.add('show');
			modalEl.style.display = 'block';
		}
		loadPath(currentPath || '');
	}

	function attachSelected() {
		var paths = Object.keys(selected);
		if (!paths.length || !enabled || busy) {
			return;
		}

		busy = true;
		updateMeta();
		setModalStatus('Ürüne ekleniyor…', 'info');
		setStatus('Görseller ekleniyor…', 'info');

		api('attach', {
			method: 'POST',
			fields: {
				id_product: String(productId),
				paths: paths
			}
		})
			.then(function (data) {
				if (!data || !data.success) {
					setModalStatus((data && data.message) || 'Eklenemedi', 'error');
					setStatus((data && data.message) || 'Eklenemedi', 'error');
					return;
				}

				renderProductImages(data.images || []);
				setStatus(data.message || 'Görseller eklendi', 'success');
				selected = {};
				renderGrid();
				setModalStatus(data.message || 'Eklendi', 'success');

				if (window.bootstrap && bootstrap.Modal) {
					var instance = bootstrap.Modal.getInstance(modalEl);
					if (instance) {
						instance.hide();
					}
				}
			})
			.catch(function () {
				setModalStatus('Bağlantı hatası', 'error');
				setStatus('Bağlantı hatası', 'error');
			})
			.finally(function () {
				busy = false;
				updateMeta();
			});
	}

	function uploadFiles(fileList) {
		if (!canUpload || busy || !fileList || !fileList.length) {
			if (!canUpload) {
				setModalStatus('Yükleme yalnızca media klasöründe yapılabilir.', 'error');
			}
			return;
		}

		var files = Array.prototype.slice.call(fileList).filter(function (file) {
			return file && /^image\/(jpeg|png|webp|gif)$/i.test(file.type);
		});

		if (!files.length) {
			setModalStatus('Sadece JPG, PNG, WEBP veya GIF yükleyin.', 'error');
			return;
		}

		busy = true;
		updateMeta();
		setModalStatus(files.length + ' dosya yükleniyor…', 'info');

		var formData = new FormData();
		formData.append('action', 'upload');
		formData.append('path', currentPath || 'media');
		files.forEach(function (file) {
			formData.append('files[]', file, file.name);
		});

		api('upload', { formData: formData })
			.then(function (data) {
				if (!data || !data.success) {
					setModalStatus((data && data.message) || 'Yükleme başarısız', 'error');
					return;
				}
				currentItems = data.items || [];
				canUpload = !!data.can_upload;
				canMkdir = !!data.can_mkdir;
				currentPath = data.path || currentPath;
				renderCrumbs(data.breadcrumbs || []);
				(data.uploaded || []).forEach(function (item) {
					if (item && item.path) {
						selected[item.path] = true;
					}
				});
				renderGrid();
				setModalStatus(data.message || 'Yüklendi', 'success');
			})
			.catch(function () {
				setModalStatus('Yükleme hatası', 'error');
			})
			.finally(function () {
				busy = false;
				updateMeta();
				if (uploadInput) {
					uploadInput.value = '';
				}
			});
	}

	function productAction(action, idImage, triggerBtn) {
		if (!enabled || busy || !idImage) {
			return;
		}

		busy = true;
		if (window.AdminBusy && triggerBtn) {
			AdminBusy.start(triggerBtn);
		}
		var formData = new FormData();
		formData.append(action, '1');
		formData.append('ajax', '1');
		formData.append('token', token);
		formData.append('id_image', String(idImage));
		setStatus(action === 'deleteImage' ? 'Görsel siliniyor…' : 'Kapak güncelleniyor…', 'info');

		fetch(productUploadUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
			.then(function (res) { return res.json(); })
			.then(function (data) {
				if (!data || !data.success) {
					setStatus((data && data.message) || 'İşlem başarısız', 'error');
					return;
				}
				if (data.images) {
					renderProductImages(data.images);
				}
				setStatus(data.message || 'Tamam', 'success');
			})
			.catch(function () {
				setStatus('İşlem sırasında bir hata oluştu.', 'error');
			})
			.finally(function () {
				busy = false;
				if (window.AdminBusy && triggerBtn) {
					AdminBusy.stop(triggerBtn);
				}
			});
	}

	if (openBtn) {
		openBtn.addEventListener('click', function (e) {
			e.preventDefault();
			openModal();
		});
	}

	if (dropzone && enabled) {
		dropzone.addEventListener('click', function (e) {
			if (e.target.closest('[data-action]')) {
				return;
			}
			openModal();
		});
	}

	if (gallery) {
		gallery.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-action]');
			if (!btn) {
				return;
			}
			var card = btn.closest('.pe-media-card');
			var idImage = card ? Number(card.getAttribute('data-image-id') || 0) : 0;
			var action = btn.getAttribute('data-action');
			if (action === 'cover') {
				productAction('setCover', idImage, btn);
			} else if (action === 'delete') {
				var message = root.getAttribute('data-confirm-delete-image') || 'Delete this image?';
				var ask = window.AdminConfirm && typeof AdminConfirm.ask === 'function'
					? AdminConfirm.ask({ message: message })
					: Promise.resolve(false);
				ask.then(function (ok) {
					if (ok) {
						productAction('deleteImage', idImage, btn);
					}
				});
			}
		});
	}

	if (!modalEl) {
		return;
	}

	if (crumbEl) {
		crumbEl.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-ml-path]');
			if (!btn) {
				return;
			}
			loadPath(btn.getAttribute('data-ml-path') || '');
		});
	}

	if (gridEl) {
		gridEl.addEventListener('click', function (e) {
			var item = e.target.closest('.ml-item');
			if (!item) {
				return;
			}
			var type = item.getAttribute('data-type');
			var path = item.getAttribute('data-path') || '';
			if (type === 'dir') {
				loadPath(path);
				return;
			}
			if (selected[path]) {
				delete selected[path];
			} else {
				selected[path] = true;
			}
			renderGrid();
		});
	}

	if (filterEl) {
		filterEl.addEventListener('input', renderGrid);
	}

	if (uploadBtn && uploadInput) {
		uploadBtn.addEventListener('click', function () {
			if (!canUpload) {
				setModalStatus('Önce media klasörüne gidin veya oluşturun.', 'error');
				loadPath('media');
				return;
			}
			uploadInput.click();
		});
		uploadInput.addEventListener('change', function () {
			uploadFiles(uploadInput.files);
		});
	}

	if (mkdirBtn) {
		mkdirBtn.addEventListener('click', function () {
			if (!canMkdir) {
				setModalStatus('Klasör yalnızca media altında oluşturulabilir.', 'error');
				return;
			}
			var name = window.prompt('Yeni klasör adı');
			if (!name) {
				return;
			}
			busy = true;
			api('mkdir', {
				method: 'POST',
				fields: { path: currentPath || 'media', name: name }
			})
				.then(function (data) {
					busy = false;
					if (!data || !data.success) {
						setModalStatus((data && data.message) || 'Oluşturulamadı', 'error');
						updateMeta();
						return;
					}
					setModalStatus(data.message || 'Klasör oluşturuldu', 'success');
					loadPath(currentPath || 'media');
				})
				.catch(function () {
					busy = false;
					setModalStatus('Bağlantı hatası', 'error');
					updateMeta();
				});
		});
	}

	if (refreshBtn) {
		refreshBtn.addEventListener('click', function () {
			loadPath(currentPath);
		});
	}

	if (attachBtn) {
		attachBtn.addEventListener('click', attachSelected);
	}

	var mediaHomeBtn = modalEl.querySelector('[data-ml-home-media]');
	if (mediaHomeBtn) {
		mediaHomeBtn.addEventListener('click', function () {
			loadPath('media');
		});
	}
})();
