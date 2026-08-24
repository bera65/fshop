<div class="admin-toolbar d-flex flex-wrap gap-2 mb-3">
	<ul class="nav nav-pills">
		<li class="nav-item">
			<a class="nav-link active" href="#couponCodes" data-bs-toggle="tab">{'Coupon codes'|adminT}</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="#cartPromotions" data-bs-toggle="tab">{'Cart promotions'|adminT}</a>
		</li>
	</ul>
</div>

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

<div class="tab-content">
	<div class="tab-pane fade show active" id="couponCodes">
		<div class="d-flex justify-content-end mb-3">
			<a href="{$adminUrl}coupon" class="btn btn-sm btn-primary">{'+ New coupon'|adminT}</a>
		</div>

		<div class="admin-panel p-0">
			<div class="table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead>
						<tr>
							<th>{'Code'|adminT}</th>
							<th>{'Discount'|adminT}</th>
							<th>{'Customer'|adminT}</th>
							<th>{'Min. cart'|adminT}</th>
							<th>{'Usage'|adminT}</th>
							<th>{'Validity'|adminT}</th>
							<th>{'Status'|adminT}</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{if $coupons|@count}
						{foreach $coupons as $row}
						<tr>
							<td><strong>{$row.code|escape}</strong></td>
							<td>{$row.discount_label}</td>
							<td class="small">{if $row.customer_label}{$row.customer_label|escape}{else}<span class="text-muted">{'All'|adminT}</span>{/if}</td>
							<td>{$row.min_cart_formatted}</td>
							<td>{$row.used_count}{if $row.max_uses > 0} / {$row.max_uses}{/if}</td>
							<td class="small">
								{if $row.date_from}{$row.date_from|escape}{else}—{/if}
								→
								{if $row.date_to}{$row.date_to|escape}{else}—{/if}
							</td>
							<td>{if $row.active}{'Active'|adminT}{else}<span class="text-danger">{'Inactive'|adminT}</span>{/if}</td>
							<td class="text-end">
								<a href="{$adminUrl}coupon?id={$row.id_coupon}" class="btn btn-sm btn-outline-dark">{'Edit'|adminT}</a>
								<form method="post" class="d-inline" data-confirm-title="{'Delete'|adminT}" data-confirm-message="{'Delete this coupon?'|adminT}">
									<input type="hidden" name="deleteCoupon" value="1">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="hidden" name="id_coupon" value="{$row.id_coupon}">
									<button type="submit" class="btn btn-sm btn-outline-danger">{'Delete'|adminT}</button>
								</form>
							</td>
						</tr>
						{/foreach}
						{else}
						<tr><td colspan="8" class="text-muted">{'No coupons yet.'|adminT}</td></tr>
						{/if}
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="tab-pane fade" id="cartPromotions">
		<div class="alert alert-light border small mb-3">
			{'Cart promotions apply automatically; customers do not need to enter a code.'|adminT}
			{'<strong>Nth item discount:</strong> e.g. 10 off the 2nd item or 5%.'|adminT}
			{'<strong>Buy X pay Y:</strong> e.g. buy 3 pay for 2 (cheapest item free).'|adminT}
			{'All active promotions that match are applied together in the cart.'|adminT}
		</div>

		<div class="d-flex justify-content-end mb-3">
			<a href="{$adminUrl}cart-promotion" class="btn btn-sm btn-primary">{'+ New cart promotion'|adminT}</a>
		</div>

		<div class="admin-panel p-0">
			<div class="table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead>
						<tr>
							<th>{'Promotion'|adminT}</th>
							<th>{'Rule'|adminT}</th>
							<th>{'Min. cart'|adminT}</th>
							<th>{'Validity'|adminT}</th>
							<th>{'Status'|adminT}</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{if $promotions|@count}
						{foreach $promotions as $row}
						<tr>
							<td><strong>{$row.name|escape}</strong></td>
							<td>{$row.rule_label|escape}</td>
							<td>{$row.min_cart_formatted}</td>
							<td class="small">
								{if $row.date_from}{$row.date_from|escape}{else}—{/if}
								→
								{if $row.date_to}{$row.date_to|escape}{else}—{/if}
							</td>
							<td>{if $row.active}{'Active'|adminT}{else}<span class="text-danger">{'Inactive'|adminT}</span>{/if}</td>
							<td class="text-end">
								<a href="{$adminUrl}cart-promotion?id={$row.id_promotion}" class="btn btn-sm btn-outline-dark">{'Edit'|adminT}</a>
								<form method="post" class="d-inline" data-confirm-title="{'Delete'|adminT}" data-confirm-message="{'Delete this promotion?'|adminT}">
									<input type="hidden" name="deletePromotion" value="1">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="hidden" name="id_promotion" value="{$row.id_promotion}">
									<button type="submit" class="btn btn-sm btn-outline-danger">{'Delete'|adminT}</button>
								</form>
							</td>
						</tr>
						{/foreach}
						{else}
						<tr><td colspan="6" class="text-muted">{'No cart promotions yet.'|adminT}</td></tr>
						{/if}
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var hash = window.location.hash;
	if (hash === '#cartPromotions') {
		var tab = document.querySelector('[href="#cartPromotions"]');
		if (tab && window.bootstrap) {
			new bootstrap.Tab(tab).show();
		}
	}
});
</script>
