{if $flash}
<div class="alert alert-{$flashType|default:'info'}">{$flash|escape}</div>
{/if}

<div class="admin-panel p-3 mb-4">
	<h2 class="h6 mb-3">Otomatik hatırlatma</h2>
	<form method="post" class="row g-3">
		<input type="hidden" name="saveAbandonedCartSettings" value="1">
		<input type="hidden" name="token" value="{$adminToken}">
		<div class="col-md-3">
			<label class="form-label">Terk süresi (saat)</label>
			<input type="number" min="1" name="idle_hours" class="form-control form-control-sm" value="{$settings.idle_hours}">
		</div>
		<div class="col-md-3">
			<label class="form-label">Hatırlatma aralığı (saat)</label>
			<input type="number" min="1" name="remind_hours" class="form-control form-control-sm" value="{$settings.remind_hours}">
		</div>
		<div class="col-md-3 d-flex align-items-end">
			<div class="form-check">
				<input class="form-check-input" type="checkbox" name="auto_remind" value="1" id="autoRemind"{if $settings.auto_remind} checked{/if}>
				<label class="form-check-label" for="autoRemind">Cron ile otomatik gönder</label>
			</div>
		</div>
		<div class="col-12">
			<label class="form-label">Otomatik e-posta metni</label>
			<textarea name="auto_message" class="form-control form-control-sm" rows="2">{$settings.auto_message|escape}</textarea>
		</div>
		<div class="col-12">
			<button type="submit" class="btn btn-sm btn-dark">Kaydet</button>
			<code class="small ms-2">{$cronUrl|escape}</code>
		</div>
	</form>
</div>

<form method="get" action="{$moduleConfigUrl}" class="row g-2 mb-3">
	<div class="col-md-3">
		<select name="status" class="form-select form-select-sm">
			<option value="">Tüm durumlar</option>
			<option value="active"{if $statusFilter == 'active'} selected{/if}>Aktif</option>
			<option value="abandoned"{if $statusFilter == 'abandoned'} selected{/if}>Terk edildi</option>
			<option value="converted"{if $statusFilter == 'converted'} selected{/if}>Sipariş oluşturdu</option>
		</select>
	</div>
	<div class="col-md-2">
		<button type="submit" class="btn btn-sm btn-dark">Filtrele</button>
	</div>
</form>

<div class="admin-panel p-0">
	{if $carts|@count}
	<div class="table-responsive">
		<table class="table table-sm align-middle mb-0 abandoned-cart-table">
			<thead>
				<tr>
					<th>Müşteri</th>
					<th>Ürün</th>
					<th>Tutar</th>
					<th>Durum</th>
					<th>Son güncelleme</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{foreach $carts as $cart}
				<tr class="abandoned-cart-row abandoned-cart-row--{$cart.status_class|escape}">
					<td>
						{if $cart.customer_name}{$cart.customer_name|escape}{else}<span class="text-muted">Misafir</span>{/if}
						{if $cart.customer_email}<br><small class="text-muted">{$cart.customer_email|escape}</small>{/if}
					</td>
					<td>
						{$cart.item_count} ürün
						{if $cart.items|@count}
						<ul class="small text-muted mb-0 ps-3">
							{foreach $cart.items as $item}
							<li>{$item.product_name|escape} × {$item.qty}</li>
							{/foreach}
						</ul>
						{/if}
					</td>
					<td>{$cart.subtotal_formatted|escape}</td>
					<td>
						<span class="badge bg-{$cart.status_class|escape}">{$cart.status_label|escape}</span>
						{if $cart.order_reference}<br><small>#{$cart.order_reference|escape}</small>{/if}
					</td>
					<td><small>{$cart.date_upd|escape}</small></td>
					<td class="text-end">
						{if $cart.can_remind}
						<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#remindModal{$cart.id_cart}">Hatırlatma gönder</button>
						{/if}
					</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	{else}
	<p class="text-muted p-4 mb-0">Kayıt bulunamadı.</p>
	{/if}
</div>

{foreach $carts as $cart}
{if $cart.can_remind}
<div class="modal fade" id="remindModal{$cart.id_cart}" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<form method="post" class="modal-content">
			<input type="hidden" name="sendCartReminder" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<input type="hidden" name="id_cart" value="{$cart.id_cart}">
			<div class="modal-header">
				<h5 class="modal-title">Sepet hatırlatması</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label">Mesaj</label>
					<textarea name="reminder_message" class="form-control" rows="4" placeholder="Sepetinizde ürünler bekliyor…"></textarea>
				</div>
				<div class="mb-3">
					<label class="form-label">Mevcut kupon kodu (opsiyonel)</label>
					<input type="text" name="coupon_code" class="form-control" placeholder="INDIRIM10">
				</div>
				<hr>
				<div class="form-check mb-2">
					<input class="form-check-input" type="checkbox" name="create_coupon" value="1" id="createCoupon{$cart.id_cart}">
					<label class="form-check-label" for="createCoupon{$cart.id_cart}">Yeni kişisel kupon oluştur</label>
				</div>
				<div class="row g-2">
					<div class="col-6">
						<select name="coupon_type" class="form-select form-select-sm">
							<option value="percent">Yüzde</option>
							<option value="fixed">Sabit tutar</option>
						</select>
					</div>
					<div class="col-6">
						<input type="text" name="coupon_value" class="form-control form-control-sm" placeholder="10" value="10">
					</div>
					<div class="col-12">
						<input type="text" name="coupon_min_cart" class="form-control form-control-sm" placeholder="Min. sepet tutarı (opsiyonel)">
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
				<button type="submit" class="btn btn-primary">Gönder</button>
			</div>
		</form>
	</div>
</div>
{/if}
{/foreach}

{if $pagination.total_pages > 1}
<div class="mt-3">{include file='admin/plugin/pagination.tpl'}</div>
{/if}
