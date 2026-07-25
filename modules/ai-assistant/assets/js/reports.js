(function () {

	'use strict';



	var cfg = window.AiAssistantReports || {};



	function escapeHtml(value) {

		return String(value || '')

			.replace(/&/g, '&amp;')

			.replace(/</g, '&lt;')

			.replace(/>/g, '&gt;');

	}



	function simpleMarkdown(md) {

		var text = escapeHtml(md || '');

		text = text.replace(/^### (.+)$/gm, '<h5 class="h6 mt-3">$1</h5>');

		text = text.replace(/^## (.+)$/gm, '<h4 class="h6 mt-3">$1</h4>');

		text = text.replace(/^# (.+)$/gm, '<h3 class="h6 mt-3">$1</h3>');

		text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

		text = text.replace(/^\s*[-*] (.+)$/gm, '<li>$1</li>');

		text = text.replace(/(?:<li>.*?<\/li>\s*)+/gs, function (block) {

			return '<ul class="mb-2 ps-3">' + block + '</ul>';

		});

		text = text.replace(/\n{2,}/g, '</p><p class="mb-2">');



		return '<div class="ai-modal-analysis">' + text + '</div>';

	}



	function setStatus(el, msg, type) {

		if (!el) {

			return;

		}



		el.textContent = msg || '';

		el.className = 'small mt-2 mb-0' + (type === 'error' ? ' text-danger' : type === 'success' ? ' text-success' : ' text-muted');

	}



	function showError(title, message) {

		if (window.AiAssistantModal) {

			window.AiAssistantModal.open({

				title: title,

				body: '<div class="alert alert-danger mb-0">' + escapeHtml(message) + '</div>',

				footer: '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>'

			});

		}

	}



	function showResult(title, analysis, meta) {

		if (!window.AiAssistantModal) {

			return;

		}



		window.AiAssistantModal.open({

			title: title,

			body: '<p class="small text-muted mb-3">' + escapeHtml(meta || '') + '</p>' + simpleMarkdown(analysis || ''),

			footer: '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>'

		});

	}



	function postReport(url, fields, opts) {

		opts = opts || {};



		if (!cfg.configured) {

			window.location.href = cfg.settingsUrl || '#';

			return Promise.reject(new Error('not configured'));

		}



		var body = new FormData();

		body.append('token', cfg.token || '');



		Object.keys(fields || {}).forEach(function (key) {

			body.append(key, fields[key]);

		});



		if (window.AiAssistantModal) {

			window.AiAssistantModal.loading(opts.loadingTitle || 'Rapor hazırlanıyor', opts.loadingMessage || 'Veriler toplanıyor…');

		}



		return fetch(url, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (res) {
			return res.text().then(function (text) {
				try {
					return JSON.parse(text);
				} catch (e) {
					throw new Error(text ? text.slice(0, 200) : ('HTTP ' + res.status));
				}
			});
		});

	}



	var salesForm = document.getElementById('aiReportSalesForm');

	var salesStatus = document.getElementById('aiSalesStatus');



	if (salesForm) {

		salesForm.addEventListener('submit', function (e) {

			e.preventDefault();



			var btn = salesForm.querySelector('[type="submit"]');

			if (btn) {

				btn.disabled = true;

			}



			setStatus(salesStatus, 'Satış raporu oluşturuluyor…', 'info');



			postReport(cfg.apiSales, {

				date_from: document.getElementById('aiSalesDateFrom').value,

				date_to: document.getElementById('aiSalesDateTo').value,

				channel: document.getElementById('aiSalesChannel').value

			}, {

				loadingTitle: 'Satış raporu',

				loadingMessage: 'Satış ve ciro verileri analiz ediliyor…'

			})

				.then(function (data) {

					if (!data || !data.success) {

						var err = (data && data.message) || 'Rapor oluşturulamadı';

						setStatus(salesStatus, err, 'error');

						showError('Satış raporu başarısız', err);

						return;

					}



					var summary = data.summary || {};

					var meta = (data.message || '') + (data.model ? ' · ' + data.model : '')

						+ (summary.order_count ? ' · ' + summary.order_count + ' sipariş' : '');

					setStatus(salesStatus, 'Rapor hazır.', 'success');

					showResult('Satış raporu', data.analysis, meta);

				})

				.catch(function (err) {
					var msg = (err && err.message) ? err.message : 'Sunucuya ulaşılamadı.';
					setStatus(salesStatus, 'Bağlantı hatası', 'error');
					showError('Bağlantı hatası', msg);
				})

				.finally(function () {

					if (btn) {

						btn.disabled = false;

					}

				});

		});

	}



	var seoForm = document.getElementById('aiReportSeoForm');

	var seoStatus = document.getElementById('aiSeoStatus');



	if (seoForm) {

		seoForm.addEventListener('submit', function (e) {

			e.preventDefault();



			var btn = seoForm.querySelector('[type="submit"]');

			if (btn) {

				btn.disabled = true;

			}



			setStatus(seoStatus, 'SEO analizi başlatıldı…', 'info');



			postReport(cfg.apiProductsSeo, {

				limit: document.getElementById('aiSeoLimit').value

			}, {

				loadingTitle: 'Ürün SEO analizi',

				loadingMessage: 'Ürün meta alanları taranıyor…'

			})

				.then(function (data) {

					if (!data || !data.success) {

						var err = (data && data.message) || 'Analiz başarısız';

						setStatus(seoStatus, err, 'error');

						showError('SEO raporu başarısız', err);

						return;

					}



					var overview = data.summary || {};

					var meta = (data.message || '') + (data.model ? ' · ' + data.model : '')

						+ (overview.product_count ? ' · ' + overview.product_count + ' ürün' : '');

					setStatus(seoStatus, 'SEO raporu hazır.', 'success');

					showResult('Ürün SEO raporu', data.analysis, meta);

				})

				.catch(function (err) {
					var msg = (err && err.message) ? err.message : 'Sunucuya ulaşılamadı.';
					setStatus(seoStatus, 'Bağlantı hatası', 'error');
					showError('Bağlantı hatası', msg);
				})

				.finally(function () {

					if (btn) {

						btn.disabled = false;

					}

				});

		});

	}



	var cancelForm = document.getElementById('aiReportCancelForm');

	var cancelStatus = document.getElementById('aiCancelStatus');



	if (cancelForm) {

		cancelForm.addEventListener('submit', function (e) {

			e.preventDefault();



			var btn = cancelForm.querySelector('[type="submit"]');

			if (btn) {

				btn.disabled = true;

			}



			setStatus(cancelStatus, 'İptal / iade raporu oluşturuluyor…', 'info');



			postReport(cfg.apiCancelReturns, {

				date_from: document.getElementById('aiCancelDateFrom').value,

				date_to: document.getElementById('aiCancelDateTo').value,

				channel: document.getElementById('aiCancelChannel').value

			}, {

				loadingTitle: 'İptal / iade raporu',

				loadingMessage: 'İptal ve iade verileri analiz ediliyor…'

			})

				.then(function (data) {

					if (!data || !data.success) {

						var err = (data && data.message) || 'Rapor oluşturulamadı';

						setStatus(cancelStatus, err, 'error');

						showError('İptal / iade raporu başarısız', err);

						return;

					}



					var summary = data.summary || {};

					var meta = (data.message || '') + (data.model ? ' · ' + data.model : '');

					if (summary.cancelled_count || summary.returned_count) {

						meta += ' · ' + (summary.cancelled_count || 0) + ' iptal, ' + (summary.returned_count || 0) + ' iade';

					}

					setStatus(cancelStatus, 'Rapor hazır.', 'success');

					showResult('İptal / iade raporu', data.analysis, meta);

				})

				.catch(function (err) {
					var msg = (err && err.message) ? err.message : 'Sunucuya ulaşılamadı.';
					setStatus(cancelStatus, 'Bağlantı hatası', 'error');
					showError('Bağlantı hatası', msg);
				})

				.finally(function () {

					if (btn) {

						btn.disabled = false;

					}

				});

		});

	}

})();

