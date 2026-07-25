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
<div class="admin-panel p-3">
	<form method="post">
		<input type="hidden" name="saveCoupon" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="row g-3">
			<div class="col-md-4">
				<label class="form-label">{'Coupon code'|adminT}</label>
				<input type="text" name="code" class="form-control text-uppercase" value="{$coupon.code|escape}" required{if !$isNew} readonly{/if}>
			</div>
			<div class="col-md-4">
				<label class="form-label">{'Discount type'|adminT}</label>
				<select name="discount_type" class="form-select">
					<option value="percent"{if $coupon.discount_type == 'percent'} selected{/if}>{'Percent (%)'|adminT}</option>
					<option value="fixed"{if $coupon.discount_type == 'fixed'} selected{/if}>{'Fixed amount'|adminT}</option>
				</select>
			</div>
			<div class="col-md-4">
				<label class="form-label">{'Discount value'|adminT}</label>
				<input type="number" name="discount_value" class="form-control" value="{$coupon.discount_value}" min="0.01" step="0.01" required>
			</div>
			<div class="col-md-4">
				<label class="form-label">{'Minimum cart (₺)'|adminT}</label>
				<input type="number" name="min_cart" class="form-control" value="{$coupon.min_cart}" min="0" step="0.01">
			</div>
			<div class="col-md-4">
				<label class="form-label">{'Max uses (0 = unlimited)'|adminT}</label>
				<input type="number" name="max_uses" class="form-control" value="{$coupon.max_uses}" min="0">
			</div>
			<div class="col-md-4">
				<label class="form-label">{'Status'|adminT}</label>
				<select name="active" class="form-select">
					<option value="1"{if $coupon.active} selected{/if}>{'Active'|adminT}</option>
					<option value="0"{if !$coupon.active} selected{/if}>{'Inactive'|adminT}</option>
				</select>
			</div>
			<div class="col-md-8">
				<label class="form-label">{'Customer (optional)'|adminT}</label>
				<select name="id_user" class="form-select">
					<option value="0">{'All customers'|adminT}</option>
					{foreach $customerOptions as $customer}
					<option value="{$customer.id_user}"{if $coupon.id_user == $customer.id_user} selected{/if}>
						{$customer.user_full_name|escape}{if $customer.email} ({$customer.email|escape}){/if}
					</option>
					{/foreach}
				</select>
				<div class="form-text">{'Leave empty to allow any customer. Bound coupons require login.'|adminT}</div>
			</div>
			<div class="col-md-6">
				<label class="form-label">{'Start (optional)'|adminT}</label>
				<input type="datetime-local" name="date_from" class="form-control" value="{$coupon.date_from_input|escape}">
			</div>
			<div class="col-md-6">
				<label class="form-label">{'End (optional)'|adminT}</label>
				<input type="datetime-local" name="date_to" class="form-control" value="{$coupon.date_to_input|escape}">
			</div>
		</div>

		<div class="d-flex gap-2 mt-4">
			<button type="submit" class="btn btn-dark">{'Save'|adminT}</button>
			<a href="{$adminUrl}coupons" class="btn btn-outline-secondary">{'Back'|adminT}</a>
		</div>
	</form>
</div>
