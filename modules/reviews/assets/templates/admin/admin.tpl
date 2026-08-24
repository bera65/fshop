{if $flash}
<div class="alert alert-{$flashType|default:'info'} py-2">{$flash|escape}</div>
{/if}

<ul class="nav nav-tabs mb-3">
	<li class="nav-item">
		<a class="nav-link{if $tab == 'reviews'} active{/if}" href="{$adminUrl}module-reviews?tab=reviews">Yorumlar</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $tab == 'invite'} active{/if}" href="{$adminUrl}module-reviews?tab=invite">Yorum daveti</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $tab == 'queue'} active{/if}" href="{$adminUrl}module-reviews?tab=queue">Gönderim kuyruğu</a>
	</li>
</ul>

{if $tab == 'reviews'}
<div class="admin-toolbar d-flex flex-wrap gap-2 mb-3">
	<a href="{$adminUrl}module-reviews?tab=reviews&amp;filter=pending" class="btn btn-sm {if $filter == 'pending'}btn-dark{else}btn-outline-dark{/if}">
		Onay Bekleyen ({$pendingCount})
	</a>
	<a href="{$adminUrl}module-reviews?tab=reviews&amp;filter=approved" class="btn btn-sm {if $filter == 'approved'}btn-dark{else}btn-outline-dark{/if}">Onaylı</a>
	<a href="{$adminUrl}module-reviews?tab=reviews&amp;filter=all" class="btn btn-sm {if $filter == 'all'}btn-dark{else}btn-outline-dark{/if}">Tümü</a>
</div>

<div class="admin-panel">
	<div class="table-responsive">
		<table class="table table-sm align-middle mb-0">
			<thead>
				<tr>
					<th>Ürün</th>
					<th>Yazar</th>
					<th>Puan</th>
					<th>Yorum</th>
					<th>Durum</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{if $reviews|@count}
				{foreach $reviews as $row}
				<tr>
					<td>{$row.product_name|escape}</td>
					<td>{$row.author_name|escape}</td>
					<td>{$row.rating}/5</td>
					<td class="small" style="max-width:280px">
						{if $row.title}<strong>{$row.title|escape}</strong><br>{/if}
						{$row.comment|escape|truncate:120}
					</td>
					<td>{if $row.active}<span class="badge bg-success">Onaylı</span>{else}<span class="badge bg-warning text-dark">Bekliyor</span>{/if}</td>
					<td class="text-end text-nowrap">
						{if !$row.active}
						<form method="post" class="d-inline">
							<input type="hidden" name="reviewAction" value="1">
							<input type="hidden" name="token" value="{$adminToken}">
							<input type="hidden" name="id_review" value="{$row.id_review}">
							<button type="submit" name="action" value="approve" class="btn btn-sm btn-dark">Onayla</button>
						</form>
						{/if}
						<form method="post" class="d-inline" data-confirm-message="Silinsin mi?">
							<input type="hidden" name="reviewAction" value="1">
							<input type="hidden" name="token" value="{$adminToken}">
							<input type="hidden" name="id_review" value="{$row.id_review}">
							<button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">Sil</button>
						</form>
					</td>
				</tr>
				{/foreach}
				{else}
				<tr><td colspan="6" class="text-muted">Kayıt yok.</td></tr>
				{/if}
			</tbody>
		</table>
	</div>
</div>

{include file='admin/plugin/pagination.tpl'}

{elseif $tab == 'invite'}
<div class="admin-panel p-3">
	<p class="text-muted small mb-3">
		Sipariş <strong>teslim edildi</strong> durumuna geçtikten sonra belirlediğiniz gün sayısı kadar beklenir; ardından müşteriye satın aldığı ürünler için yorum daveti e-postası gönderilir.
		İsteğe bağlı olarak kişiye özel kupon da eklenebilir.
	</p>

	<form method="post">
		<input type="hidden" name="saveReviewInviteSettings" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="row g-3">
			<div class="col-md-4">
				<label class="form-label">Durum</label>
				<select name="invite_enabled" class="form-select">
					<option value="1"{if $inviteSettings.enabled} selected{/if}>Aktif</option>
					<option value="0"{if !$inviteSettings.enabled} selected{/if}>Pasif</option>
				</select>
			</div>
			<div class="col-md-4">
				<label class="form-label">Teslimden kaç gün sonra</label>
				<input type="number" name="delay_days" class="form-control" min="0" max="365" value="{$inviteSettings.delay_days}">
			</div>
			<div class="col-md-12">
				<label class="form-label">E-posta konusu</label>
				<input type="text" name="email_subject" class="form-control" value="{$inviteSettings.subject|escape}" required>
			</div>
			<div class="col-md-12">
				<label class="form-label">E-posta metni</label>
				<textarea name="email_body" class="form-control wysiwyg-editor" rows="10">{$inviteSettings.body|escape}</textarea>
				<div class="form-text mt-1">
					Yer tutucular:
					{foreach $placeholders as $ph}
						<code>{$ph|escape}</code>{if !$ph@last}, {/if}
					{/foreach}
				</div>
			</div>

			<div class="col-12"><hr class="my-2"></div>
			<div class="col-12"><h6 class="mb-0">Kişisel kupon (isteğe bağlı)</h6></div>

			<div class="col-md-4">
				<label class="form-label">Kupon oluştur</label>
				<select name="coupon_enabled" class="form-select">
					<option value="1"{if $inviteSettings.coupon_enabled} selected{/if}>Evet</option>
					<option value="0"{if !$inviteSettings.coupon_enabled} selected{/if}>Hayır</option>
				</select>
			</div>
			<div class="col-md-4">
				<label class="form-label">İndirim tipi</label>
				<select name="coupon_type" class="form-select">
					<option value="percent"{if $inviteSettings.coupon_type == 'percent'} selected{/if}>Yüzde (%)</option>
					<option value="fixed"{if $inviteSettings.coupon_type == 'fixed'} selected{/if}>Sabit tutar</option>
				</select>
			</div>
			<div class="col-md-4">
				<label class="form-label">İndirim değeri</label>
				<input type="number" name="coupon_value" class="form-control" min="0.01" step="0.01" value="{$inviteSettings.coupon_value}">
			</div>
			<div class="col-md-4">
				<label class="form-label">Min. sepet (₺)</label>
				<input type="number" name="coupon_min_cart" class="form-control" min="0" step="0.01" value="{$inviteSettings.coupon_min_cart}">
			</div>
			<div class="col-md-4">
				<label class="form-label">Geçerlilik (gün, 0 = sınırsız)</label>
				<input type="number" name="coupon_valid_days" class="form-control" min="0" max="365" value="{$inviteSettings.coupon_valid_days}">
			</div>
			<div class="col-md-4">
				<label class="form-label">Kod öneki</label>
				<input type="text" name="coupon_prefix" class="form-control text-uppercase" maxlength="8" value="{$inviteSettings.coupon_prefix|escape}">
			</div>
		</div>

		<div class="mt-4">
			<button type="submit" class="btn btn-dark">Kaydet</button>
		</div>
	</form>
</div>

{else}
<div class="admin-panel p-3 mb-3">
	<p class="small text-muted mb-2">Sunucu cron’unu günde bir veya saatte bir çalıştırın. Bekleyen davetler süre dolunca gönderilir.</p>
	<code class="d-block p-2 bg-light rounded small mb-3">{$cronUrl|escape}</code>
	<form method="post" class="d-inline">
		<input type="hidden" name="runReviewInviteCron" value="1">
		<input type="hidden" name="token" value="{$adminToken}">
		<button type="submit" class="btn btn-sm btn-outline-dark">Şimdi çalıştır</button>
	</form>
	<span class="small text-muted ms-2">
		Bekleyen: {$queueStats.pending} · Gönderilen: {$queueStats.sent} · Hata: {$queueStats.failed} · Atlanan: {$queueStats.skipped}
	</span>
</div>

<div class="admin-panel">
	<div class="table-responsive">
		<table class="table table-sm align-middle mb-0">
			<thead>
				<tr>
					<th>Sipariş</th>
					<th>Müşteri</th>
					<th>Planlanan</th>
					<th>Durum</th>
					<th>Kupon</th>
					<th>Not</th>
				</tr>
			</thead>
			<tbody>
				{if $queueRows|@count}
				{foreach $queueRows as $row}
				<tr>
					<td><code>{$row.order_reference|escape}</code></td>
					<td class="small">{$row.customer_name|escape}<br>{$row.customer_email|escape}</td>
					<td class="small">{$row.scheduled_formatted|escape}</td>
					<td>
						{if $row.status == 'sent'}<span class="badge bg-success">Gönderildi</span>
						{elseif $row.status == 'pending'}<span class="badge bg-warning text-dark">Bekliyor</span>
						{elseif $row.status == 'failed'}<span class="badge bg-danger">Hata</span>
						{else}<span class="badge bg-secondary">{$row.status|escape}</span>{/if}
					</td>
					<td class="small">{if $row.coupon_code}<code>{$row.coupon_code|escape}</code>{else}—{/if}</td>
					<td class="small text-muted">{$row.error_message|escape}</td>
				</tr>
				{/foreach}
				{else}
				<tr><td colspan="6" class="text-muted">Kuyruk boş.</td></tr>
				{/if}
			</tbody>
		</table>
	</div>
</div>
{/if}
