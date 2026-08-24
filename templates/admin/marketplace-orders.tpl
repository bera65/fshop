{include file='admin/marketplace/_nav.tpl'}
<div class="mp-orders-page">
	<div class="admin-panel p-3 mb-3">
		<div class="mp-filter-card mb-0">
			<div class="mp-filter-header">
				<div class="mp-filter-title">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M22 3H2l8 9v7l4 2v-9l8-9z"/>
					</svg>
					Sipariş Filtreleri
				</div>
				<div class="mp-filter-sub">Tarih, müşteri, sipariş no ve ürün/stok koduna göre listele.</div>
			</div>

			<form id="mpFilterForm" method="get" action="{$ordersUrl|escape}">
				<div class="row">
					<div class="col-md-4 mb-3">
						<label>PAZARYERİ</label>
						<select name="marketplace_platform" class="form-select form-select-sm">
							<option value="all"{if $marketplaceOrderPlatform == 'all'} selected{/if}>Tümü</option>
							<option value="trendyol"{if $marketplaceOrderPlatform == 'trendyol'} selected{/if}>Trendyol</option>
							<option value="hepsiburada"{if $marketplaceOrderPlatform == 'hepsiburada'} selected{/if}>Hepsiburada</option>
							<option value="n11"{if $marketplaceOrderPlatform == 'n11'} selected{/if}>N11</option>
						</select>
					</div>

					<div class="col-md-4 mb-3">
						<label>BAŞLANGIÇ TARİHİ</label>
						<input type="date" name="start_date" class="form-control form-control-sm" value="{$marketplaceOrderStartDate|escape}">
					</div>

					<div class="col-md-4 mb-3">
						<label>BİTİŞ TARİHİ</label>
						<input type="date" name="end_date" class="form-control form-control-sm" value="{$marketplaceOrderEndDate|escape}">
					</div>

					<div class="col-md-3 mb-3">
						<label>SİPARİŞ NO</label>
						<input type="text" name="order_number" class="form-control form-control-sm" placeholder="Sipariş numarası" value="{$marketplaceOrderFilterOrderNumber|escape}">
					</div>

					<div class="col-md-3 mb-3">
						<label>MÜŞTERİ ADI</label>
						<input type="text" name="customer_name" class="form-control form-control-sm" placeholder="Müşteri adı" value="{$marketplaceOrderFilterCustomerName|escape}">
					</div>

					<div class="col-md-3 mb-3">
						<label>ÜRÜN / STOK KODU</label>
						<input type="text" name="product_query" class="form-control form-control-sm" placeholder="Ürün adı veya stok kodu" value="{$marketplaceOrderFilterProductQuery|escape}">
					</div>

					<div class="col-md-3 mb-3">
						<label>SİPARİŞ DURUMU</label>
						<select name="order_status" class="form-select form-select-sm">
							<option value="all"{if $marketplaceOrderFilterStatus == 'all'} selected{/if}>Tümü</option>
							<option value="pending"{if $marketplaceOrderFilterStatus == 'pending'} selected{/if}>Hazırlanıyor</option>
							<option value="navy"{if $marketplaceOrderFilterStatus == 'navy'} selected{/if}>Paketleniyor</option>
							<option value="success"{if $marketplaceOrderFilterStatus == 'success'} selected{/if}>Kargoda</option>
							<option value="done"{if $marketplaceOrderFilterStatus == 'done'} selected{/if}>Teslim Edildi</option>
							<option value="danger"{if $marketplaceOrderFilterStatus == 'danger'} selected{/if}>İptal / İade</option>
						</select>
					</div>
				</div>

				<div class="mp-filter-actions">
					<div style="display:flex;gap:10px;flex-wrap:wrap">
						<button type="submit" class="btn btn-warning">Ara</button>
						<button type="button" id="mpExportBtn" class="btn btn-success">Excel'e Aktar</button>
						<button type="button" id="mpImportOrderBtn" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mpImportModal">Sipariş Import</button>
					</div>
				</div>
			</form>
		</div>
	</div>

	{if $marketplaceOrders|@count}
	<div class="adm-orders-bulk admin-panel mb-3">
		<div class="adm-orders-bulk__bd">
			<div class="adm-orders-bulk__left">
				<label class="adm-orders-bulk__check">
					<input type="checkbox" class="form-check-input mp-order-check" id="mpSelectAll">
					<span>Tümünü Seç</span>
				</label>
				<button type="button" id="mpPrintSelected" class="btn btn-sm btn-outline-secondary">
					<i data-lucide="printer" style="width:14px;height:14px"></i>
					{'Print'|adminT}
				</button>
			</div>
		</div>
	</div>

	<div class="admin-panel adm-orders-table-panel mb-3">
		<div class="adm-orders-table-panel__hd">
			<div>
				<h2 class="h6 mb-0">Pazaryeri siparişleri</h2>
				<p class="text-muted small mb-0">{$marketplaceOrders|@count} kayıt</p>
			</div>
		</div>
		<div class="p-0">
			<div class="table-responsive adm-orders-shell">
				<table class="table adm-orders-table mb-0 align-middle">
					<thead>
						<tr>
							<th style="width:96px"><span class="visually-hidden">{'Select'|adminT}</span></th>
							<th>{'Channel'|adminT}</th>
							<th>{'Order date'|adminT}</th>
							<th class="text-center" style="width:148px">{'Actions'|adminT}</th>
							<th>{'Order no'|adminT}</th>
							<th>{'Status'|adminT}</th>
							<th>{'Customer'|adminT}</th>
							<th>{'Payment'|adminT}</th>
							<th>{'Cost'|adminT}</th>
							<th>{'Total'|adminT}</th>
						</tr>
					</thead>
					<tbody>
						{include file='admin/partials/marketplace-order-list-rows.tpl'}
					</tbody>
				</table>
			</div>
		</div>
	</div>
	{else}
	<div class="mp-order-empty mb-3">Henüz sipariş yok.</div>
	{/if}

	{if isset($pagination) && $pagination.total_pages > 1}
		{include file='admin/plugin/pagination.tpl'}
	{/if}
</div>

<div class="mp-drawer-overlay" id="mpDrawerOverlay"></div>
<aside class="mp-drawer" id="mpDrawer" aria-hidden="true">
	<div class="mp-drawer-head">
		<div class="mp-drawer-kicker">Sipariş Detayı</div>
		<div class="mp-drawer-top">
			<div class="mp-drawer-order" id="mpDrawerOrderNo">#—</div>
			<div style="display:flex;align-items:center;gap:8px;">
				<span class="mp-order-status-pill" id="mpDrawerStatusPill"><span id="mpDrawerStatus">—</span></span>
				<button type="button" class="mp-drawer-close" id="mpDrawerClose" aria-label="Kapat">&times;</button>
			</div>
		</div>
		<div class="mp-drawer-meta">
			<span class="mp-chip">
				<img id="mpDrawerPlatformIcon" src="" alt="" onerror="this.style.display='none'">
				<span id="mpDrawerPlatform">—</span>
			</span>
			<span class="mp-chip" id="mpDrawerDate">—</span>
			<span class="mp-chip">Pazaryeri Hesabı</span>
		</div>
	</div>

	<div class="mp-drawer-body">
		<div class="mp-card">
			<div class="mp-grid-2">
				<div>
					<div class="mp-card-label">Tahsilat</div>
					<div class="mp-soft-box blue" id="mpDrawerTotal">—</div>
				</div>
				<div>
					<div class="mp-card-label">Ödeme Yöntemi</div>
					<div class="mp-soft-box gray">Pazaryeri</div>
				</div>
			</div>
			<div class="mp-note">Pazaryeri siparişi — tahsilat hesap üzerinden takip edilir.</div>
		</div>

		<div class="mp-stepper">
			<div class="mp-stepper-top">
				<div class="mp-stepper-title">Sipariş Durumu</div>
				<div id="mpDrawerStatusRight" style="font-weight:700;color:#166534;">—</div>
			</div>
			<div class="mp-steps" id="mpDrawerSteps">
				<div class="mp-step" data-step="1"><div class="mp-step-dot"></div><div class="mp-step-label">Onay</div></div>
				<div class="mp-step" data-step="2"><div class="mp-step-dot"></div><div class="mp-step-label">Hazırlık</div></div>
				<div class="mp-step" data-step="3"><div class="mp-step-dot"></div><div class="mp-step-label">Kargo</div></div>
				<div class="mp-step" data-step="4"><div class="mp-step-dot"></div><div class="mp-step-label">Teslim</div></div>
			</div>
		</div>

		<div class="mp-card">
			<div class="mp-card-label">Müşteri Bilgileri</div>
			<div class="mp-customer">
				<div class="mp-avatar" id="mpDrawerInitials">?</div>
				<div>
					<div class="mp-customer-name" id="mpDrawerCustomer">—</div>
					<div class="mp-customer-sub" id="mpDrawerCustomerSub">—</div>
				</div>
			</div>
		</div>

		<div class="mp-card mp-card-muted">
			<div class="mp-card-label">Ürünler</div>
			<ul class="mp-items" id="mpDrawerItems"></ul>
		</div>

		<div class="mp-card">
			<div class="mp-card-label">Kargo</div>
			<div id="mpDrawerCargo" style="font-weight:700;">—</div>
			<div class="mp-customer-sub" id="mpDrawerTracking"></div>
		</div>
	</div>

	<div class="mp-drawer-foot">
		<a href="#" target="_blank" rel="noopener" class="mp-drawer-btn" id="mpDrawerPrintBtn">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
			Kargo Şablonu
		</a>
		<a href="#" target="_blank" rel="noopener" class="mp-drawer-btn primary" id="mpDrawerTrackBtn">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
			Kargo Takip
		</a>
	</div>
</aside>

<script>
(function () {
	var all = document.getElementById('mpSelectAll');
	if (!all) return;
	all.addEventListener('change', function () {
		document.querySelectorAll('.mp-order-row-check').forEach(function (el) {
			el.checked = all.checked;
		});
	});
})();

(function () {
	var exportBtn = document.getElementById('mpExportBtn');
	var form = document.getElementById('mpFilterForm');
	if (!exportBtn || !form) return;
	exportBtn.addEventListener('click', function (e) {
		e.preventDefault();
		var params = new URLSearchParams(new FormData(form));
		params.set('token', {$adminToken|js nofilter});
		window.location.href = {$exportOrdersUrl|js nofilter} + '&' + params.toString();
	});
})();

(function () {
	var printBtn = document.getElementById('mpPrintSelected');
	if (!printBtn) return;

	var adminBase = {$adminUrl|js nofilter};
	printBtn.addEventListener('click', function () {
		var selected = [];
		document.querySelectorAll('.mp-order-row-check:checked').forEach(function (el) {
			selected.push(el.value);
		});

		if (selected.length === 0) {
			if (window.AdminToast) {
				AdminToast.show('Lütfen yazdırmak için en az 1 sipariş seçin.', 'warning');
			}
			return;
		}

		var url = adminBase + 'marketplace-order-print?auto=1&keys='
			+ encodeURIComponent(selected.join('|'));
		window.open(url, '_blank', 'noopener');
	});
})();

(function () {
	var drawer = document.getElementById('mpDrawer');
	var overlay = document.getElementById('mpDrawerOverlay');
	var closeBtn = document.getElementById('mpDrawerClose');
	var domain = {$domain|js nofilter};
	var adminBase = {$adminUrl|js nofilter};

	if (!drawer || !overlay) return;

	function openDrawer(order) {
		if (!order) return;

		document.getElementById('mpDrawerOrderNo').textContent = '#' + (order.order_number || '—');
		document.getElementById('mpDrawerStatus').textContent = order.status || '—';
		document.getElementById('mpDrawerStatusRight').textContent = order.status || '—';

		var pill = document.getElementById('mpDrawerStatusPill');
		pill.className = 'mp-order-status-pill ' + (order.status_tone || 'muted');

		document.getElementById('mpDrawerPlatform').textContent = order.platform_label || order.platform || '—';
		var icon = document.getElementById('mpDrawerPlatformIcon');
		icon.style.display = '';
		icon.src = domain + 'templates/admin/img/icons/' + (order.platform_icon_file || '');

		var dateText = order.date_day || '—';
		if (order.date_time) dateText += ' • ' + order.date_time;
		document.getElementById('mpDrawerDate').textContent = dateText;

		document.getElementById('mpDrawerTotal').textContent = order.total_price || '—';
		document.getElementById('mpDrawerInitials').textContent = order.customer_initials || '?';
		document.getElementById('mpDrawerCustomer').textContent = order.customer_name || '—';
		document.getElementById('mpDrawerCustomerSub').textContent = order.customer_sub || '';

		document.getElementById('mpDrawerCargo').textContent = order.cargo_provider || '—';
		document.getElementById('mpDrawerTracking').textContent = order.cargo_tracking_number
			? ('Takip: ' + order.cargo_tracking_number)
			: '';

		var itemsEl = document.getElementById('mpDrawerItems');
		itemsEl.innerHTML = '';
		(order.items || []).forEach(function (it) {
			var li = document.createElement('li');
			li.innerHTML = '<span></span><strong></strong>';
			li.children[0].textContent = it.name || '—';
			li.children[1].textContent = (it.quantity || 1) + ' adet';
			itemsEl.appendChild(li);
		});
		if (!(order.items || []).length) {
			itemsEl.innerHTML = '<li><span>Ürün bilgisi yok</span><strong></strong></li>';
		}

		var step = parseInt(order.status_step || 1, 10);
		document.querySelectorAll('#mpDrawerSteps .mp-step').forEach(function (el) {
			var n = parseInt(el.getAttribute('data-step'), 10);
			el.classList.remove('active', 'done');
			if (step <= 0) return;
			if (n < step) el.classList.add('done');
			if (n === step) el.classList.add('active');
			if (step === 4 && n === 4) el.classList.add('done');
		});

		var printUrl = adminBase + 'marketplace-order-print?auto=1'
			+ '&platform=' + encodeURIComponent(order.platform || '')
			+ '&order_number=' + encodeURIComponent(order.order_number || '')
			+ '&package_id=' + encodeURIComponent(order.shipment_package_id || '');
		document.getElementById('mpDrawerPrintBtn').href = printUrl;

		var trackBtn = document.getElementById('mpDrawerTrackBtn');
		var trackLink = (order.cargo_tracking_link || '').trim();
		if (trackLink && /^https?:\/\//i.test(trackLink)) {
			trackBtn.href = trackLink;
			trackBtn.style.opacity = '1';
		} else if (order.cargo_tracking_number) {
			trackBtn.href = 'https://www.google.com/search?q=' + encodeURIComponent(order.cargo_tracking_number + ' ' + (order.cargo_provider || 'kargo'));
			trackBtn.style.opacity = '1';
		} else {
			trackBtn.href = '#';
			trackBtn.style.opacity = '.6';
		}

		drawer.classList.add('open');
		overlay.classList.add('open');
		drawer.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
	}

	function closeDrawer() {
		drawer.classList.remove('open');
		overlay.classList.remove('open');
		drawer.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
	}

	document.querySelectorAll('.js-mp-open-detail').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var row = btn.closest('.mp-order-row');
			if (!row) return;
			try {
				openDrawer(JSON.parse(row.getAttribute('data-order') || '{}'));
			} catch (err) {}
		});
	});

	function toggleMpDetail(id) {
		var row = document.querySelector('.mp-order-row[data-order-id="' + id + '"]');
		var detail = document.getElementById('adm-order-detail-' + id);
		if (!detail) return;
		var open = !detail.classList.contains('is-open');
		detail.classList.toggle('is-open', open);
		detail.hidden = !open;
		if (row) row.classList.toggle('is-open', open);
		if (window.lucide) window.lucide.createIcons();
	}

	document.querySelectorAll('.mp-order-row').forEach(function (row) {
		row.addEventListener('click', function (e) {
			if (e.target.closest('a, button, input, select, .dropdown-menu, .adm-order-select-cell, .adm-order-action-cell')) {
				return;
			}
			toggleMpDetail(row.getAttribute('data-order-id'));
		});
		row.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				toggleMpDetail(row.getAttribute('data-order-id'));
			}
		});
	});

	document.querySelectorAll('.mp-order-row [data-order-expand]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.stopPropagation();
			toggleMpDetail(btn.getAttribute('data-order-expand'));
		});
	});

	closeBtn && closeBtn.addEventListener('click', closeDrawer);
	overlay.addEventListener('click', closeDrawer);
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') closeDrawer();
	});

	var params = new URLSearchParams(window.location.search);
	if (params.get('open') === '1' && params.get('order_number')) {
		var wantPlatform = params.get('platform') || '';
		var wantOrder = params.get('order_number') || '';
		var wantPackage = params.get('package_id') || '';
		document.querySelectorAll('.mp-order-row').forEach(function (row) {
			try {
				var order = JSON.parse(row.getAttribute('data-order') || '{}');
				if (String(order.order_number) === String(wantOrder)
					&& (!wantPlatform || order.platform === wantPlatform)
					&& (!wantPackage || String(order.shipment_package_id) === String(wantPackage))) {
					openDrawer(order);
				}
			} catch (err) {}
		});
	}

	if (window.lucide) window.lucide.createIcons();
})();
</script>

<div class="modal fade" id="mpImportModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Sipariş Import</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label">Pazaryeri</label>
					<select id="mpImportPlatform" class="form-select form-select-sm">
						<option value="trendyol">Trendyol</option>
						<option value="hepsiburada">Hepsiburada</option>
						<option value="n11">N11</option>
					</select>
				</div>
				<div class="mb-2">
					<label class="form-label">Sipariş numarası</label>
					<input type="text" id="mpImportOrderNo" class="form-control form-control-sm" placeholder="Sipariş no">
				</div>
				<div class="small text-muted">Varsa güncellenir, yoksa eklenir. İptal sipariş {$siteName|escape}'ta yoksa aktarılmaz.</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
				<button type="button" class="btn btn-primary btn-sm" id="mpImportSubmit">Import et</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="mpCancelModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Siparişi iptal et</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
			</div>
			<div class="modal-body">
				<p class="mb-2"><strong>Bu işlem geri alınamaz.</strong> Sipariş pazaryerinde iptal edilecek ve {$siteName|escape}'ta iptal görünecek.</p>
				<p class="mb-2" id="mpCancelOrderLabel"></p>
				<label class="form-label">Stok ne yapılsın?</label>
				<div class="form-check">
					<input class="form-check-input" type="radio" name="mpCancelStock" id="mpStockRestore" value="restore" checked>
					<label class="form-check-label" for="mpStockRestore">Stoğa geri ekle</label>
				</div>
				<div class="form-check">
					<input class="form-check-input" type="radio" name="mpCancelStock" id="mpStockZero" value="zero">
					<label class="form-check-label" for="mpStockZero">Ürün stoğunu 0 yap (stok kalmadığı için iptal)</label>
				</div>
				<div class="form-check mb-0">
					<input class="form-check-input" type="radio" name="mpCancelStock" id="mpStockKeep" value="keep">
					<label class="form-check-label" for="mpStockKeep">Stoka dokunma</label>
				</div>
				<div class="small text-muted mt-2">Stok değişirse diğer pazaryerlerine de gönderilir.</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Hayır</button>
				<button type="button" class="btn btn-danger btn-sm" id="mpCancelSubmit">Evet, iptal et</button>
			</div>
		</div>
	</div>
</div>

<script>
(function () {
	var actionUrl = {$orderActionUrl|js nofilter};
	var csrfToken = {$adminToken|@json_encode nofilter};
	var shopName = {$siteName|@json_encode nofilter};
	var cancelCtx = null;

	function parseOrder(row) {
		try { return JSON.parse(row.getAttribute('data-order') || '{}'); }
		catch (e) { return null; }
	}

	function toast(message, type) {
		if (window.AdminToast) {
			AdminToast.show(message, type || 'info');
		}
	}

	function busyStart(btn) {
		if (window.AdminBusy) {
			AdminBusy.start(btn);
		} else if (btn) {
			btn.disabled = true;
		}
	}

	function busyStop(btn) {
		if (window.AdminBusy) {
			AdminBusy.stop(btn);
		} else if (btn) {
			btn.disabled = false;
		}
	}

	function postAction(payload) {
		var data = Object.assign({}, payload || {}, { token: csrfToken });
		var body = new URLSearchParams(data);
		return fetch(actionUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-CSRF-TOKEN': csrfToken || ''
			},
			body: body.toString()
		}).then(function (r) { return r.json(); });
	}

	var importSubmit = document.getElementById('mpImportSubmit');
	if (importSubmit) {
		importSubmit.addEventListener('click', function () {
			var platform = document.getElementById('mpImportPlatform').value;
			var orderNo = (document.getElementById('mpImportOrderNo').value || '').trim();
			if (!orderNo) { toast('Sipariş numarası girin', 'warning'); return; }
			busyStart(importSubmit);
			postAction({ op: 'import', platform: platform, order_number: orderNo })
				.then(function (res) {
					toast(res.message || (res.success ? 'Tamam' : 'Hata'), res.success ? 'success' : 'danger');
					if (res.success) window.location.reload();
				})
				.catch(function () { toast('İstek başarısız', 'danger'); })
				.finally(function () { busyStop(importSubmit); });
		});
	}

	document.querySelectorAll('.js-mp-refresh-order').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var row = btn.closest('.mp-order-row');
			var order = row ? parseOrder(row) : null;
			if (!order) return;
			busyStart(btn);
			postAction({
				op: 'refresh',
				platform: order.platform || '',
				order_number: order.order_number || '',
				package_id: order.shipment_package_id || ''
			}).then(function (res) {
				toast(res.message || (res.success ? 'Güncellendi' : 'Hata'), res.success ? 'success' : 'danger');
				if (res.success) window.location.reload();
			}).catch(function () { toast('İstek başarısız', 'danger'); })
			.finally(function () { busyStop(btn); });
		});
	});

	document.querySelectorAll('.js-mp-delete-order').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var row = btn.closest('.mp-order-row');
			var order = row ? parseOrder(row) : null;
			if (!order) return;
			var ask = window.AdminConfirm && AdminConfirm.ask
				? AdminConfirm.ask({
					title: 'Siparişi sil',
					message: 'Sipariş ' + shopName + ' listesinden silinecek. Pazaryerindeki sipariş durur. Devam?'
				})
				: Promise.resolve(false);
			ask.then(function (ok) {
				if (!ok) return;
				busyStart(btn);
				postAction({
					op: 'delete',
					platform: order.platform || '',
					order_number: order.order_number || '',
					package_id: order.shipment_package_id || ''
				}).then(function (res) {
					toast(res.message || (res.success ? 'Silindi' : 'Hata'), res.success ? 'success' : 'danger');
					if (res.success) window.location.reload();
				}).catch(function () { toast('İstek başarısız', 'danger'); })
				.finally(function () { busyStop(btn); });
			});
		});
	});

	document.querySelectorAll('.js-mp-cancel-order').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var row = btn.closest('.mp-order-row');
			var order = row ? parseOrder(row) : null;
			if (!order) return;
			cancelCtx = order;
			document.getElementById('mpCancelOrderLabel').textContent =
				'#' + (order.order_number || '') + ' · ' + (order.platform_label || order.platform || '');
			document.getElementById('mpStockRestore').checked = true;
			var cancelEl = document.getElementById('mpCancelModal');
			if (window.bootstrap && bootstrap.Modal && cancelEl) {
				bootstrap.Modal.getOrCreateInstance(cancelEl).show();
			} else if (cancelEl) {
				cancelEl.classList.add('show');
				cancelEl.style.display = 'block';
				cancelEl.removeAttribute('aria-hidden');
			}
		});
	});

	var cancelSubmit = document.getElementById('mpCancelSubmit');
	if (cancelSubmit) {
		cancelSubmit.addEventListener('click', function () {
			if (!cancelCtx) return;
			var mode = 'restore';
			var checked = document.querySelector('input[name="mpCancelStock"]:checked');
			if (checked) mode = checked.value;
			cancelSubmit.disabled = true;
			if (window.AdminBusy) {
				AdminBusy.start(cancelSubmit);
			}
			postAction({
				op: 'cancel',
				platform: cancelCtx.platform || '',
				order_number: cancelCtx.order_number || '',
				package_id: cancelCtx.shipment_package_id || '',
				stock_mode: mode
			}).then(function (res) {
				if (window.AdminToast) {
					AdminToast.show(res.message || (res.success ? 'İptal edildi' : 'Hata'), res.success ? 'success' : 'danger');
				}
				if (res.success) window.location.reload();
			}).catch(function () {
				if (window.AdminToast) {
					AdminToast.show('İstek başarısız', 'danger');
				}
			})
			.finally(function () {
				cancelSubmit.disabled = false;
				if (window.AdminBusy) {
					AdminBusy.stop(cancelSubmit);
				}
			});
		});
	}
})();
</script>

{include file='admin/marketplace/_close.tpl'}
