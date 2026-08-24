{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">
					{$flash|escape}
				</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}

<div class="row g-4">
	<div class="col-lg-5">
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">{if $editCargo}{'Edit carrier'|adminT}{else}{'Add new carrier'|adminT}{/if}</h2>
			<form method="post" id="cargoForm">
				<input type="hidden" name="saveCargo" value="1">
				<input type="hidden" name="token" value="{$adminToken}">
				{if $editCargo}
				<input type="hidden" name="id_cargo" value="{$editCargo.id_cargo}">
				{/if}

				<div class="mb-3">
					<label class="form-label">{'Carrier company'|adminT}</label>
					<input type="text" name="name" class="form-control" required maxlength="128"
						value="{$editCargo.name|default:''|escape}" placeholder="{'e.g. PTT Cargo'|adminT}">
				</div>

				<div class="mb-3">
					<label class="form-label">{'Tracking link (prefix)'|adminT}</label>
					<input type="text" name="tracking_url" class="form-control"
						value="{$editCargo.tracking_url|default:''|escape}"
						placeholder="https://gonderitakip.ptt.gov.tr/track?code=">
					<div class="form-text">{'The tracking number is appended to the <strong>end</strong> of this URL. You may use <code>{ldelim}code{rdelim}</code>.'|adminT}</div>
				</div>

				<div class="row g-2 mb-3">
					<div class="col-6">
						<label class="form-label">{'Sort order'|adminT}</label>
						<input type="number" name="position" class="form-control" value="{$editCargo.position|default:0}">
					</div>
					<div class="col-6 d-flex align-items-end gap-3 pb-1">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="active" id="cargoActive" value="1"
								{if !$editCargo || $editCargo.active}checked{/if}>
							<label class="form-check-label" for="cargoActive">{'Active'|adminT}</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="is_default" id="cargoDefault" value="1"
								{if $editCargo && $editCargo.is_default}checked{/if}>
							<label class="form-check-label" for="cargoDefault">{'Default'|adminT}</label>
						</div>
					</div>
				</div>

				<label class="form-label">{'Price ranges (cart total → shipping fee)'|adminT}</label>
				<p class="small text-muted mb-2">{'E.g. 0–1500 → 80 · 1500–2000 → 100 · 2000+ → 0 (free). Empty max = no upper limit.'|adminT}</p>

				<div id="cargoRates" class="vstack gap-2 mb-2">
					{if $editCargo && $editCargo.rates}
						{foreach $editCargo.rates as $rate}
						<div class="row g-2 align-items-end cargo-rate-row">
							<div class="col-4">
								<label class="form-label small mb-0">Min (₺)</label>
								<input type="text" name="rate_min[]" class="form-control form-control-sm" value="{$rate.min_amount|escape}">
							</div>
							<div class="col-4">
								<label class="form-label small mb-0">Max (₺)</label>
								<input type="text" name="rate_max[]" class="form-control form-control-sm" value="{if $rate.max_amount > 0}{$rate.max_amount|escape}{/if}" placeholder="{'empty = +∞'|adminT}">
							</div>
							<div class="col-3">
								<label class="form-label small mb-0">{'Fee (₺)'|adminT}</label>
								<input type="text" name="rate_fee[]" class="form-control form-control-sm" value="{$rate.fee|escape}">
							</div>
							<div class="col-1">
								<button type="button" class="btn btn-sm btn-outline-danger cargo-rate-remove" title="{'Delete'|adminT}">&times;</button>
							</div>
						</div>
						{/foreach}
					{else}
						<div class="row g-2 align-items-end cargo-rate-row">
							<div class="col-4">
								<label class="form-label small mb-0">Min (₺)</label>
								<input type="text" name="rate_min[]" class="form-control form-control-sm" value="0">
							</div>
							<div class="col-4">
								<label class="form-label small mb-0">Max (₺)</label>
								<input type="text" name="rate_max[]" class="form-control form-control-sm" value="1500" placeholder="{'empty = +∞'|adminT}">
							</div>
							<div class="col-3">
								<label class="form-label small mb-0">{'Fee (₺)'|adminT}</label>
								<input type="text" name="rate_fee[]" class="form-control form-control-sm" value="80">
							</div>
							<div class="col-1">
								<button type="button" class="btn btn-sm btn-outline-danger cargo-rate-remove" title="{'Delete'|adminT}">&times;</button>
							</div>
						</div>
						<div class="row g-2 align-items-end cargo-rate-row">
							<div class="col-4">
								<label class="form-label small mb-0">Min (₺)</label>
								<input type="text" name="rate_min[]" class="form-control form-control-sm" value="1500">
							</div>
							<div class="col-4">
								<label class="form-label small mb-0">Max (₺)</label>
								<input type="text" name="rate_max[]" class="form-control form-control-sm" value="2000" placeholder="{'empty = +∞'|adminT}">
							</div>
							<div class="col-3">
								<label class="form-label small mb-0">{'Fee (₺)'|adminT}</label>
								<input type="text" name="rate_fee[]" class="form-control form-control-sm" value="100">
							</div>
							<div class="col-1">
								<button type="button" class="btn btn-sm btn-outline-danger cargo-rate-remove" title="{'Delete'|adminT}">&times;</button>
							</div>
						</div>
						<div class="row g-2 align-items-end cargo-rate-row">
							<div class="col-4">
								<label class="form-label small mb-0">Min (₺)</label>
								<input type="text" name="rate_min[]" class="form-control form-control-sm" value="2000">
							</div>
							<div class="col-4">
								<label class="form-label small mb-0">Max (₺)</label>
								<input type="text" name="rate_max[]" class="form-control form-control-sm" value="" placeholder="{'empty = +∞'|adminT}">
							</div>
							<div class="col-3">
								<label class="form-label small mb-0">{'Fee (₺)'|adminT}</label>
								<input type="text" name="rate_fee[]" class="form-control form-control-sm" value="0">
							</div>
							<div class="col-1">
								<button type="button" class="btn btn-sm btn-outline-danger cargo-rate-remove" title="{'Delete'|adminT}">&times;</button>
							</div>
						</div>
					{/if}
				</div>

				<button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="cargoRateAdd" data-empty-max-ph="{'empty = +∞'|adminT|escape}">{'+ Add range'|adminT}</button>

				<div class="d-flex flex-wrap gap-2">
					<button type="submit" class="btn btn-dark">{if $editCargo}{'Update'|adminT}{else}{'Save'|adminT}{/if}</button>
					{if $editCargo}
					<a href="{$adminUrl}cargos" class="btn btn-outline-secondary">{'Cancel'|adminT}</a>
					{/if}
				</div>
			</form>
		</div>

		<div class="alert alert-light border small mt-3 mb-0">
			{'Shipping fees are calculated only from carriers and ranges here. Customer selects carrier at checkout.'|adminT}
		</div>
	</div>

	<div class="col-lg-7">
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">{'Carrier companies'|adminT}</h2>
			{if !$cargos}
			<p class="text-muted small mb-0">{'No carriers yet. Add one on the left.'|adminT}</p>
			{else}
			<div class="vstack gap-3">
				{foreach $cargos as $cargo}
				<div class="border rounded p-3">
					<div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
						<div>
							<strong>{$cargo.name|escape}</strong>
							{if $cargo.is_default}<span class="badge text-bg-primary">{'Default'|adminT}</span>{/if}
							{if $cargo.active}
							<span class="badge text-bg-success">{'Active'|adminT}</span>
							{else}
							<span class="badge text-bg-secondary">{'Inactive'|adminT}</span>
							{/if}
						</div>
						<div class="d-flex gap-1">
							<a href="{$adminUrl}cargos?edit={$cargo.id_cargo}" class="btn btn-sm btn-outline-primary">{'Edit'|adminT}</a>
							<form method="post" class="d-inline" data-confirm-title="{'Delete'|adminT}" data-confirm-message="{'Delete this carrier?'|adminT}">
								<input type="hidden" name="deleteCargo" value="1">
								<input type="hidden" name="token" value="{$adminToken}">
								<input type="hidden" name="id_cargo" value="{$cargo.id_cargo}">
								<button type="submit" class="btn btn-sm btn-outline-danger">{'Delete'|adminT}</button>
							</form>
						</div>
					</div>
					{if $cargo.tracking_url}
					<div class="small text-muted mb-2">{'Tracking:'|adminT} <code>{$cargo.tracking_url|escape}</code>{' + tracking no'|adminT}</div>
					{/if}
					{if $cargo.rates}
					<ul class="small mb-0 ps-3">
						{foreach $cargo.rates as $rate}
						<li>{$rate.label|escape}</li>
						{/foreach}
					</ul>
					{else}
					<p class="small text-warning mb-0">{'No price range defined.'|adminT}</p>
					{/if}
				</div>
				{/foreach}
			</div>
			{/if}
		</div>
	</div>
</div>

{literal}
<script>
(function () {
	var wrap = document.getElementById('cargoRates');
	var addBtn = document.getElementById('cargoRateAdd');
	if (!wrap || !addBtn) return;

	var emptyMaxPh = addBtn.getAttribute('data-empty-max-ph') || '';

	function bindRemove(btn) {
		btn.addEventListener('click', function () {
			var rows = wrap.querySelectorAll('.cargo-rate-row');
			if (rows.length <= 1) {
				rows[0].querySelectorAll('input').forEach(function (i) { i.value = ''; });
				return;
			}
			btn.closest('.cargo-rate-row').remove();
		});
	}

	wrap.querySelectorAll('.cargo-rate-remove').forEach(bindRemove);

	addBtn.addEventListener('click', function () {
		var row = document.createElement('div');
		row.className = 'row g-2 align-items-end cargo-rate-row';
		row.innerHTML =
			'<div class="col-4"><label class="form-label small mb-0">Min (₺)</label>' +
			'<input type="text" name="rate_min[]" class="form-control form-control-sm" value=""></div>' +
			'<div class="col-4"><label class="form-label small mb-0">Max (₺)</label>' +
			'<input type="text" name="rate_max[]" class="form-control form-control-sm" value="" placeholder="' + emptyMaxPh.replace(/"/g, '&quot;') + '"></div>' +
			'<div class="col-3"><label class="form-label small mb-0">Fee</label>' +
			'<input type="text" name="rate_fee[]" class="form-control form-control-sm" value=""></div>' +
			'<div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger cargo-rate-remove" title="Delete">&times;</button></div>';
		wrap.appendChild(row);
		bindRemove(row.querySelector('.cargo-rate-remove'));
	});
})();
</script>
{/literal}
