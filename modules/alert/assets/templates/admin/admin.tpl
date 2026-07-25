{if $flash}
<div class="alert alert-{$flashType|default:'info'}">{$flash|escape}</div>
{/if}

<ul class="nav nav-tabs mb-3">
	<li class="nav-item">
		<a class="nav-link{if $tab == 'settings'} active{/if}" href="{$moduleConfigUrl}?tab=settings">Ayarlar</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $tab == 'subscriptions'} active{/if}" href="{$moduleConfigUrl}?tab=subscriptions">Stok bildirim talepleri</a>
	</li>
</ul>

{if $tab == 'settings'}
<div class="admin-panel p-4">
	<h2 class="h6 mb-3">E-posta uyarıları</h2>
	<form method="post" class="row g-3">
		<input type="hidden" name="saveAlertSettings" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="col-12">
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" name="order_email" value="1" id="alertOrderEmail"{if $settings.order_email} checked{/if}>
				<label class="form-check-label" for="alertOrderEmail">Yeni sipariş gelince admin e-postası gönder</label>
			</div>
		</div>

		<div class="col-12">
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" name="critical_stock" value="1" id="alertCriticalStock"{if $settings.critical_stock} checked{/if}>
				<label class="form-check-label" for="alertCriticalStock">Stok kritik eşiğin altına inince admin e-postası gönder</label>
			</div>
		</div>

		<div class="col-md-4">
			<label class="form-label" for="alertThreshold">Kritik stok eşiği</label>
			<input type="number" min="0" class="form-control form-control-sm" id="alertThreshold" name="critical_threshold" value="{$settings.critical_threshold|escape}">
			<div class="form-text">Bu değer ve altı kritik kabul edilir (ör. 5).</div>
		</div>

		<div class="col-12">
			<div class="form-check form-switch">
				<input class="form-check-input" type="checkbox" name="back_in_stock" value="1" id="alertBackInStock"{if $settings.back_in_stock} checked{/if}>
				<label class="form-check-label" for="alertBackInStock">Mağazada “stoğa girince haber ver” formunu göster</label>
			</div>
		</div>

		<div class="col-12">
			<label class="form-label" for="alertAdminEmails">Admin bildirim e-postaları</label>
			<textarea class="form-control form-control-sm" id="alertAdminEmails" name="admin_emails" rows="2" placeholder="admin@site.com, depo@site.com">{$settings.admin_emails|escape}</textarea>
			<div class="form-text">Boş bırakılırsa İletişim e-postası kullanılır. Virgül veya satır ile ayırın.</div>
		</div>

		<div class="col-12">
			<button type="submit" class="btn btn-sm btn-dark">Kaydet</button>
		</div>
	</form>
</div>
{else}
<form method="get" action="{$moduleConfigUrl}" class="row g-2 mb-3">
	<input type="hidden" name="tab" value="subscriptions">
	<div class="col-md-3">
		<select name="filter" class="form-select form-select-sm">
			<option value="pending"{if $subscriptionFilter == 'pending'} selected{/if}>Bekleyen</option>
			<option value="sent"{if $subscriptionFilter == 'sent'} selected{/if}>Gönderildi</option>
			<option value="all"{if $subscriptionFilter == 'all'} selected{/if}>Tümü</option>
		</select>
	</div>
	<div class="col-md-2">
		<button type="submit" class="btn btn-sm btn-dark">Filtrele</button>
	</div>
</form>

<div class="admin-panel p-0">
	{if $subscriptions|@count}
	<div class="table-responsive">
		<table class="table table-sm align-middle mb-0">
			<thead>
				<tr>
					<th>Ürün</th>
					<th>E-posta</th>
					<th>Durum</th>
					<th>Tarih</th>
				</tr>
			</thead>
			<tbody>
				{foreach $subscriptions as $sub}
				<tr>
					<td>{$sub.product_name|escape}</td>
					<td>{$sub.email|escape}</td>
					<td>{if $sub.is_sent}<span class="text-success">Gönderildi</span>{else}<span class="text-warning">Bekliyor</span>{/if}</td>
					<td>{$sub.date_formatted|escape}{if $sub.is_sent}<br><small class="text-muted">{$sub.sent_at_formatted|escape}</small>{/if}</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	{else}
	<p class="text-muted p-4 mb-0">Kayıt bulunamadı.</p>
	{/if}
</div>

{if $pagination.total_pages > 1}
<div class="mt-3">{include file='admin/plugin/pagination.tpl'}</div>
{/if}
{/if}
