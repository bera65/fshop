/**
 * Trendyol admin: marka / kategori arama seçicileri + özellik formu
 */
(function (window, document) {
	'use strict';

	var timers = {};

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function apiUrl(key) {
		if (key === 'brands') return window.trendyolBrandsApiUrl || '';
		if (key === 'categories') return window.trendyolCategoriesApiUrl || '';
		if (key === 'attributes') return window.trendyolAttributesApiUrl || '';
		return '';
	}

	function getJson(url) {
		return fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); });
	}

	function debounce(key, fn, ms) {
		if (timers[key]) clearTimeout(timers[key]);
		timers[key] = setTimeout(fn, ms || 400);
	}

	function setBusy(el, on) {
		if (!el) {
			return;
		}

		if (window.AdminBusy) {
			if (on) {
				AdminBusy.start(el);
			} else {
				AdminBusy.stop(el);
			}
			return;
		}

		el.disabled = !!on;
	}

	function findPicker(el) {
		return el.closest('.ty-picker');
	}

	function setSelected(picker, id, label) {
		var idInput = picker.querySelector('.ty-picker-id');
		var nameInput = picker.querySelector('.ty-picker-name');
		var labelEl = picker.querySelector('.ty-picker-selected');
		var query = picker.querySelector('.ty-picker-query');
		var results = picker.querySelector('.ty-picker-results');

		if (idInput) idInput.value = id || '';
		if (nameInput) nameInput.value = label || '';
		if (labelEl) {
			if (id) {
				labelEl.innerHTML = '<span class="badge text-bg-success">' + esc(label || ('#' + id)) + '</span>' +
					' <span class="text-muted small">#' + esc(id) + '</span>';
			} else {
				labelEl.innerHTML = '<span class="text-muted small">Seçilmedi</span>';
			}
		}
		if (query) query.value = '';
		if (results) results.innerHTML = '';

		picker.dispatchEvent(new CustomEvent('ty:selected', {
			bubbles: true,
			detail: { id: id, label: label, type: picker.getAttribute('data-type') }
		}));
	}

	function renderResults(picker, items, type) {
		var results = picker.querySelector('.ty-picker-results');
		if (!results) return;

		if (!items.length) {
			results.innerHTML = '<div class="text-muted small p-2">Sonuç yok</div>';
			return;
		}

		var html = '<div class="list-group list-group-flush border rounded ty-picker-list">';
		items.forEach(function (item) {
			html += '<button type="button" class="list-group-item list-group-item-action ty-picker-item"' +
				' data-id="' + esc(item.id) + '" data-label="' + esc(item.label) + '">' +
				'<div class="fw-semibold small">' + esc(item.label) + '</div>' +
				'<div class="text-muted" style="font-size:11px">#' + esc(item.id) + '</div>' +
				'</button>';
		});
		html += '</div>';
		results.innerHTML = html;
	}

	function searchPicker(picker) {
		var type = picker.getAttribute('data-type');
		var queryEl = picker.querySelector('.ty-picker-query');
		var results = picker.querySelector('.ty-picker-results');
		var q = queryEl ? queryEl.value.trim() : '';

		if (!results) return;

		if (q.length < 2) {
			results.innerHTML = '<div class="text-muted small p-1">En az 2 karakter yazın…</div>';
			return;
		}

		var base = apiUrl(type === 'brand' ? 'brands' : 'categories');
		if (!base) {
			results.innerHTML = '<div class="text-danger small p-1">API adresi yok</div>';
			return;
		}

		results.innerHTML = '<div class="text-muted small p-1">Aranıyor…</div>';
		var sep = base.indexOf('?') >= 0 ? '&' : '?';

		getJson(base + sep + 'name=' + encodeURIComponent(q))
			.then(function (res) {
				if (!res.success) {
					results.innerHTML = '<div class="text-danger small p-1">' + esc(res.message || 'Hata') + '</div>';
					return;
				}

				var items = [];
				if (type === 'brand') {
					(res.brands || []).slice(0, 20).forEach(function (b) {
						var id = b.id || b.brandId || '';
						var name = b.name || b.brandName || '';
						if (id) items.push({ id: id, label: name || ('#' + id) });
					});
				} else {
					(res.categories || []).forEach(function (c) {
						items.push({
							id: c.id,
							label: c.path || c.name || ('#' + c.id)
						});
					});
				}

				renderResults(picker, items, type);
			})
			.catch(function () {
				results.innerHTML = '<div class="text-danger small p-1">İstek başarısız</div>';
			});
	}

	function collectAttributes(panel) {
		var hidden = panel.querySelector('.ty-attributes');
		var map = {};
		panel.querySelectorAll('.ty-attr-input').forEach(function (el) {
			var aid = el.getAttribute('data-attr-id');
			var val = (el.value || '').trim();
			if (aid && val !== '') map[aid] = val;
		});
		if (hidden) hidden.value = JSON.stringify(map);
		return map;
	}

	function renderAttributeForm(panel, categoryAttributes, selected) {
		var box = panel.querySelector('.ty-attr-form');
		var hidden = panel.querySelector('.ty-attributes');
		if (!box) return;

		selected = selected || {};
		if (hidden && hidden.value) {
			try {
				var parsed = JSON.parse(hidden.value);
				if (parsed && typeof parsed === 'object') {
					Object.keys(parsed).forEach(function (k) {
						if (selected[k] == null) selected[k] = parsed[k];
					});
				}
			} catch (e) { /* ignore */ }
		}

		if (!categoryAttributes || !categoryAttributes.length) {
			box.innerHTML = '<div class="text-muted small">Bu kategori için özellik listesi boş.</div>';
			return;
		}

		var html = '<div class="row g-2">';
		categoryAttributes.forEach(function (row) {
			var attr = row.attribute || {};
			var aid = attr.id;
			if (!aid) return;
			var aname = attr.name || ('#' + aid);
			var required = !!row.required;
			var allowCustom = !!row.allowCustom;
			var values = row.attributeValues || [];
			var cur = selected[String(aid)] != null ? selected[String(aid)] : '';

			html += '<div class="col-md-6"><label class="form-label small mb-0">' + esc(aname);
			if (required) html += ' <span class="text-danger">*</span>';
			html += '</label>';

			if (!allowCustom && values.length) {
				html += '<select class="form-select form-select-sm ty-attr-input" data-attr-id="' + esc(aid) + '">';
				html += '<option value="">Seçin…</option>';
				values.forEach(function (v) {
					var vid = v.id != null ? v.id : (v.attributeValueId != null ? v.attributeValueId : '');
					var vname = v.name || v.attributeValueName || ('#' + vid);
					var sel = String(cur) === String(vid) ? ' selected' : '';
					html += '<option value="' + esc(vid) + '"' + sel + '>' + esc(vname) + '</option>';
				});
				html += '</select>';
			} else {
				html += '<input type="text" class="form-control form-control-sm ty-attr-input" data-attr-id="' +
					esc(aid) + '" value="' + esc(cur) + '" placeholder="' + (allowCustom ? 'Serbest metin' : '') + '">';
			}
			html += '</div>';
		});
		html += '</div>';
		box.innerHTML = html;
		collectAttributes(panel);
	}

	function loadAttributesForPanel(panel, categoryId) {
		var box = panel.querySelector('.ty-attr-form');
		var url = apiUrl('attributes');
		if (!box || !url || !categoryId) return;

		box.innerHTML = '<div class="text-muted small">Özellikler yükleniyor…</div>';
		var sep = url.indexOf('?') >= 0 ? '&' : '?';

		getJson(url + sep + 'category_id=' + encodeURIComponent(categoryId))
			.then(function (res) {
				if (!res.success) {
					box.innerHTML = '<div class="text-danger small">' + esc(res.message || 'Özellikler alınamadı') + '</div>';
					return;
				}
				renderAttributeForm(panel, res.categoryAttributes || [], {});
			})
			.catch(function () {
				box.innerHTML = '<div class="text-danger small">Özellik isteği başarısız</div>';
			});
	}

	document.addEventListener('input', function (e) {
		var query = e.target.closest('.ty-picker-query');
		if (!query) return;
		var picker = findPicker(query);
		if (!picker) return;
		var key = picker.getAttribute('data-type') + ':' + (picker.getAttribute('data-key') || 'x');
		debounce(key, function () { searchPicker(picker); }, 350);
	});

	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Enter') return;
		var query = e.target.closest('.ty-picker-query');
		if (!query) return;
		e.preventDefault();
		var picker = findPicker(query);
		if (picker) searchPicker(picker);
	});

	document.addEventListener('click', function (e) {
		var item = e.target.closest('.ty-picker-item');
		if (item) {
			var picker = findPicker(item);
			if (picker) {
				setSelected(picker, item.getAttribute('data-id'), item.getAttribute('data-label'));
			}
			return;
		}

		var clearBtn = e.target.closest('.ty-picker-clear');
		if (clearBtn) {
			var picker = findPicker(clearBtn);
			if (picker) setSelected(picker, '', '');
			return;
		}

		if (!e.target.closest('.ty-picker')) {
			document.querySelectorAll('.ty-picker-results').forEach(function (el) {
				if (el.innerHTML && !el.querySelector('.ty-picker-list') === false) {
					/* keep open if has list until outside click of results area handled below */
				}
			});
		}
	});

	document.addEventListener('ty:selected', function (e) {
		var picker = e.target.closest ? e.target.closest('.ty-picker') : null;
		if (!picker || e.detail.type !== 'category') return;
		var panel = picker.closest('.trendyol-product-panel, .ty-category-map-row, .admin-panel');
		if (!panel || !panel.querySelector('.ty-attr-form')) return;
		if (e.detail.id) loadAttributesForPanel(panel, e.detail.id);
	});

	document.addEventListener('change', function (e) {
		if (!e.target.classList.contains('ty-attr-input')) return;
		var panel = e.target.closest('.trendyol-product-panel, .ty-category-map-row, .admin-panel');
		if (panel) collectAttributes(panel);
	});

	document.addEventListener('input', function (e) {
		if (!e.target.classList.contains('ty-attr-input')) return;
		var panel = e.target.closest('.trendyol-product-panel, .ty-category-map-row, .admin-panel');
		if (panel) collectAttributes(panel);
	});

	function adminCsrfToken() {
		if (window.__adminOrderStatus && window.__adminOrderStatus.token) {
			return String(window.__adminOrderStatus.token);
		}
		if (window.__adminCsrfToken) {
			return String(window.__adminCsrfToken);
		}
		return '';
	}

	function post(url, body) {
		body = body || {};
		if (!body.token) {
			body.token = adminCsrfToken();
		}
		return fetch(url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			credentials: 'same-origin',
			body: new URLSearchParams(body).toString()
		}).then(function (r) { return r.json(); });
	}

	function panelMsg(panel, text, ok) {
		var el = panel.querySelector('.ty-action-msg');
		if (!el) return;
		el.textContent = text || '';
		el.className = 'small d-block mt-2 ty-action-msg ' + (ok ? 'text-success' : 'text-danger');
	}

	function bindProductActions() {
		document.querySelectorAll('.ty-sync-btn').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var panel = btn.closest('.trendyol-product-panel');
				if (panel) collectAttributes(panel);
				var body = { id_product: btn.getAttribute('data-id') };
				var brand = panel && panel.querySelector('.ty-brand-id');
				var cat = panel && panel.querySelector('.ty-category-id');
				var attrs = panel && panel.querySelector('.ty-attributes');
				var sale = panel && panel.querySelector('.ty-sale-price-input');
				var list = panel && panel.querySelector('.ty-list-price-input');
				if (brand && brand.value) body.brand_id = brand.value;
				if (cat && cat.value) body.category_id = cat.value;
				if (attrs && attrs.value) body.attributes = attrs.value;
				if (sale && sale.value !== '') body.sale_price = sale.value;
				if (list && list.value !== '') body.list_price = list.value;
				setBusy(btn, true);
				panelMsg(panel, 'Gönderiliyor…', true);
				post(btn.getAttribute('data-url'), body)
					.then(function (res) {
						panelMsg(panel, res.message || '', !!res.success);
						if (res.success) setTimeout(function () { location.reload(); }, 800);
					})
					.catch(function () { panelMsg(panel, 'İstek başarısız', false); })
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.ty-price-btn').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var panel = btn.closest('.trendyol-product-panel');
				var body = { id_product: btn.getAttribute('data-id') };
				var sale = panel && panel.querySelector('.ty-sale-price-input');
				var list = panel && panel.querySelector('.ty-list-price-input');
				if (sale && sale.value !== '') body.sale_price = sale.value;
				if (list && list.value !== '') body.list_price = list.value;
				setBusy(btn, true);
				panelMsg(panel, 'Fiyat güncelleniyor…', true);
				post(btn.getAttribute('data-url'), body)
					.then(function (res) {
						panelMsg(panel, res.message || '', !!res.success);
						if (res.success && res.mapping) {
							var q = panel.querySelector('.ty-qty');
							if (q) q.textContent = res.mapping.quantity;
							if (sale) sale.value = res.mapping.sale_price;
							if (list) list.value = res.mapping.list_price;
							var card = document.querySelector('.marketplace-connection--trendyol[data-product-id="' + btn.getAttribute('data-id') + '"]');
							var status = card && card.querySelector('.marketplace-connection-status');
							var cardPrice = Number(res.mapping.sale_price || 0);

							if (status) {
								status.textContent = 'Bağlı' + (cardPrice > 0
									? ' · ' + cardPrice.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TL'
									: '');
							}
						}
					})
					.catch(function () { panelMsg(panel, 'İstek başarısız', false); })
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.ty-refresh-btn').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var panel = btn.closest('.trendyol-product-panel');
				setBusy(btn, true);
				panelMsg(panel, 'Yenileniyor…', true);
				post(btn.getAttribute('data-url'), { id_product: btn.getAttribute('data-id') })
					.then(function (res) {
						panelMsg(panel, res.message || '', !!res.success);
						if (res.success) setTimeout(function () { location.reload(); }, 700);
					})
					.catch(function () { panelMsg(panel, 'İstek başarısız', false); })
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.ty-unlink-btn').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var panel = btn.closest('.trendyol-product-panel');
				var message = 'Trendyol bağlantısı silinecek. Ürün Trendyol mağazasında kalır; yalnızca FShop eşlemesi kaldırılır. Devam edilsin mi?';
				var ask = window.AdminConfirm && AdminConfirm.ask
					? AdminConfirm.ask({ message: message })
					: Promise.resolve(false);

				ask.then(function (ok) {
				if (!ok) {
					return;
				}

				setBusy(btn, true);
				panelMsg(panel, 'Bağlantı siliniyor…', true);
				post(btn.getAttribute('data-url'), { id_product: btn.getAttribute('data-id') })
					.then(function (res) {
						if (panel) {
							panelMsg(panel, res.message || '', !!res.success);
						}

				if (res.success) {
					if (document.querySelector('.ty-marketplace-catalog')) {
						setTimeout(function () { location.reload(); }, 600);
						return;
					}

					var row = btn.closest('tr');
					if (row) {
						row.remove();
						return;
					}

					setTimeout(function () { location.reload(); }, 700);
				}
					})
					.catch(function () {
						if (panel) {
							panelMsg(panel, 'İstek başarısız', false);
						}
					})
					.finally(function () {
						setBusy(btn, false);
					});
				});
			});
		});

		document.querySelectorAll('.ty-link-existing-btn').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var panel = btn.closest('.trendyol-product-panel');
				var input = panel && panel.querySelector('.ty-existing-barcode-input');
				var barcode = input ? input.value.trim() : '';

				if (!barcode) {
					panelMsg(panel, 'Trendyol barkodu gerekli', false);
					return;
				}

				setBusy(btn, true);
				panelMsg(panel, 'Trendyol ürünü doğrulanıyor…', true);
				post(btn.getAttribute('data-url'), { id_product: btn.getAttribute('data-id'), barcode: barcode })
					.then(function (res) {
						panelMsg(panel, res.message || '', !!res.success);
						if (res.success) setTimeout(function () { location.reload(); }, 700);
					})
					.catch(function () { panelMsg(panel, 'İstek başarısız', false); })
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.quick-stock-btn').forEach(function (btn) {
			if (btn.dataset.qsBound) return;
			btn.dataset.qsBound = '1';
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var id = btn.getAttribute('data-id');
				var name = btn.getAttribute('data-name');
				var stock = btn.getAttribute('data-stock');

				var modalEl = document.getElementById('quickStockModal');
				if (!modalEl) return;

				var nameEl = modalEl.querySelector('.quick-stock-product-name');
				var idInput = modalEl.querySelector('#qs_id_product');
				var stockInput = modalEl.querySelector('#qs_stock');
				var msgEl = modalEl.querySelector('.qs-msg');

				if (nameEl) nameEl.textContent = name || ('#' + id);
				if (idInput) idInput.value = id || '0';
				if (stockInput) stockInput.value = stock != null ? stock : '0';
				if (msgEl) {
					msgEl.textContent = '';
					msgEl.className = 'qs-msg small mt-2';
				}

				if (window.bootstrap && window.bootstrap.Modal) {
					var bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
					bsModal.show();
				}
			});
		});

		var qsForm = document.getElementById('quickStockForm');
		if (qsForm && !qsForm.dataset.qsBound) {
			qsForm.dataset.qsBound = '1';
			qsForm.addEventListener('submit', function (e) {
				e.preventDefault();
				var modalEl = document.getElementById('quickStockModal');
				var submitBtn = qsForm.querySelector('.id-qs-submit-btn');
				var spinner = qsForm.querySelector('.qs-spinner');
				var msgEl = qsForm.querySelector('.qs-msg');
				var updateStockUrl = window.trendyolUpdateStockApiUrl || '';

				if (!updateStockUrl) {
					if (msgEl) {
						msgEl.textContent = 'API adresi bulunamadı.';
						msgEl.className = 'qs-msg small mt-2 text-danger';
					}
					return;
				}

				var formData = new FormData(qsForm);
				var body = {
					id_product: formData.get('id_product'),
					stock: formData.get('stock'),
					sync_marketplaces: formData.get('sync_marketplaces')
				};

				if (submitBtn) submitBtn.disabled = true;
				if (spinner) spinner.classList.remove('d-none');
				if (msgEl) {
					msgEl.textContent = 'Stok güncelleniyor…';
					msgEl.className = 'qs-msg small mt-2 text-info';
				}

				post(updateStockUrl, body)
					.then(function (res) {
						if (res.success) {
							if (msgEl) {
								msgEl.textContent = res.message || 'Stok güncellendi.';
								msgEl.className = 'qs-msg small mt-2 text-success';
							}
							var idProd = body.id_product;
							var newStock = res.stock;

							var item = document.getElementById('mp-item-' + idProd);
							if (item) {
								var stockValEl = item.querySelector('.stock-display-val');
								if (stockValEl) stockValEl.textContent = newStock;
								var btn = item.querySelector('.quick-stock-btn');
								if (btn) btn.setAttribute('data-stock', newStock);

								var tyQty = item.querySelector('.ty-qty');
								if (tyQty) tyQty.textContent = newStock;
							}

							setTimeout(function () {
								if (window.bootstrap && window.bootstrap.Modal) {
									var bsModal = window.bootstrap.Modal.getInstance(modalEl);
									if (bsModal) bsModal.hide();
								}
							}, 800);
						} else {
							if (msgEl) {
								msgEl.textContent = res.message || 'Güncelleme başarısız.';
								msgEl.className = 'qs-msg small mt-2 text-danger';
							}
						}
					})
					.catch(function () {
						if (msgEl) {
							msgEl.textContent = 'İstek başarısız oldu.';
							msgEl.className = 'qs-msg small mt-2 text-danger';
						}
					})
					.finally(function () {
						if (submitBtn) submitBtn.disabled = false;
						if (spinner) spinner.classList.add('d-none');
					});
			});
		}

		document.querySelectorAll('.trendyol-product-panel').forEach(function (panel) {
			var catId = panel.querySelector('.ty-category-id');
			var form = panel.querySelector('.ty-attr-form');
			if (catId && catId.value && form && !form.dataset.loaded) {
				form.dataset.loaded = '1';
				loadAttributesForPanel(panel, catId.value);
			}
		});

		bindHbN11PanelActions();
	}

	function bindHbN11PanelActions() {
		document.querySelectorAll('.hb-price-btn, .n11-price-btn').forEach(function (btn) {
			if (btn.dataset.mpBound) return;
			btn.dataset.mpBound = '1';
			btn.addEventListener('click', function () {
				var platform = btn.getAttribute('data-platform') || 'hepsiburada';
				var panel = btn.closest(platform === 'n11' ? '.n11-product-panel' : '.hb-product-panel');
				var saleSel = platform === 'n11' ? '.n11-sale-price-input' : '.hb-sale-price-input';
				var listSel = platform === 'n11' ? '.n11-list-price-input' : '.hb-list-price-input';
				var msgSel = platform === 'n11' ? '.n11-action-msg' : '.hb-action-msg';
				var body = {
					id_product: btn.getAttribute('data-id'),
					platform: platform
				};
				var sale = panel && panel.querySelector(saleSel);
				var list = panel && panel.querySelector(listSel);
				if (sale && sale.value !== '') body.sale_price = sale.value;
				if (list && list.value !== '') body.list_price = list.value;
				setBusy(btn, true);
				var msg = panel && panel.querySelector(msgSel);
				if (msg) { msg.textContent = 'Fiyat güncelleniyor…'; msg.className = msgSel.replace('.', '') + ' small d-block mt-3 text-muted'; }
				post(btn.getAttribute('data-url'), body)
					.then(function (res) {
						if (msg) {
							msg.textContent = res.message || '';
							msg.className = (msgSel.replace('.', '') + ' small d-block mt-3 ') + (res.success ? 'text-success' : 'text-danger');
						}
						if (res.success) setTimeout(function () { location.reload(); }, 700);
					})
					.catch(function () {
						if (msg) { msg.textContent = 'İstek başarısız'; msg.className = msgSel.replace('.', '') + ' small d-block mt-3 text-danger'; }
					})
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.hb-unlink-btn, .n11-unlink-btn').forEach(function (btn) {
			if (btn.dataset.mpBound) return;
			btn.dataset.mpBound = '1';
			btn.addEventListener('click', function () {
				var platform = btn.getAttribute('data-platform') || 'hepsiburada';
				var label = platform === 'n11' ? 'N11' : 'Hepsiburada';
				var ask = window.AdminConfirm && AdminConfirm.ask
					? AdminConfirm.ask({ message: label + ' bağlantısı silinecek. Devam edilsin mi?' })
					: Promise.resolve(false);
				ask.then(function (ok) {
					if (!ok) return;
				var panel = btn.closest(platform === 'n11' ? '.n11-product-panel' : '.hb-product-panel');
				var msgSel = platform === 'n11' ? '.n11-action-msg' : '.hb-action-msg';
				var msg = panel && panel.querySelector(msgSel);
				setBusy(btn, true);
				post(btn.getAttribute('data-url'), {
					id_product: btn.getAttribute('data-id'),
					platform: platform
				})
					.then(function (res) {
						if (msg) {
							msg.textContent = res.message || '';
							msg.className = (msgSel.replace('.', '') + ' small d-block mt-3 ') + (res.success ? 'text-success' : 'text-danger');
						}
						if (res.success) setTimeout(function () { location.reload(); }, 700);
					})
					.catch(function () {
						if (msg) { msg.textContent = 'İstek başarısız'; msg.className = msgSel.replace('.', '') + ' small d-block mt-3 text-danger'; }
					})
					.finally(function () {
						setBusy(btn, false);
					});
				});
			});
		});

		document.querySelectorAll('.hb-link-existing-btn, .n11-link-existing-btn').forEach(function (btn) {
			if (btn.dataset.mpBound) return;
			btn.dataset.mpBound = '1';
			btn.addEventListener('click', function () {
				var platform = btn.getAttribute('data-platform') || 'hepsiburada';
				var panel = btn.closest(platform === 'n11' ? '.n11-product-panel' : '.hb-product-panel');
				var inputSel = platform === 'n11' ? '.n11-stock-code-input' : '.hb-merchant-sku-input';
				var msgSel = platform === 'n11' ? '.n11-action-msg' : '.hb-action-msg';
				var input = panel && panel.querySelector(inputSel);
				var code = input ? input.value.trim() : '';
				var msg = panel && panel.querySelector(msgSel);
				var body = {
					id_product: btn.getAttribute('data-id'),
					platform: platform,
					barcode: code,
					merchant_sku: code,
					stock_code: code
				};
				setBusy(btn, true);
				if (msg) { msg.textContent = 'Eşleştiriliyor…'; msg.className = msgSel.replace('.', '') + ' small d-block mt-3 text-muted'; }
				post(btn.getAttribute('data-url'), body)
					.then(function (res) {
						if (msg) {
							msg.textContent = res.message || '';
							msg.className = (msgSel.replace('.', '') + ' small d-block mt-3 ') + (res.success ? 'text-success' : 'text-danger');
						}
						if (res.success) setTimeout(function () { location.reload(); }, 700);
					})
					.catch(function () {
						if (msg) { msg.textContent = 'İstek başarısız'; msg.className = msgSel.replace('.', '') + ' small d-block mt-3 text-danger'; }
					})
					.finally(function () { setBusy(btn, false); });
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bindProductActions);
	} else {
		bindProductActions();
	}

	window.TrendyolAdmin = {
		setSelected: setSelected,
		loadAttributesForPanel: loadAttributesForPanel,
		bindProductActions: bindProductActions
	};
})(window, document);
