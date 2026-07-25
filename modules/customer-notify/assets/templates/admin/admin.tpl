{if $flash}
<div class="alert alert-{$flashType|default:'info'} py-2">{$flash|escape}</div>
{/if}

<ul class="nav nav-tabs mb-3">
	<li class="nav-item">
		<a class="nav-link{if $tab == 'send' || $tab == ''} active{/if}" href="{$adminUrl}module-customer-notify?tab=send">Bildirim Gönder</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $tab == 'history'} active{/if}" href="{$adminUrl}module-customer-notify?tab=history">Gönderim Geçmişi</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $tab == 'browser'} active{/if}" href="{$adminUrl}module-customer-notify?tab=browser">Tarayıcı Bildirimi</a>
	</li>
</ul>

{if $tab == 'browser'}
<div class="admin-panel p-3" style="max-width: 760px;">
	<h2 class="h6 mb-3">Tarayıcı bildirimi (OneSignal)</h2>
	<p class="small text-muted mb-3">
		OneSignal ile tarayıcı <strong>kapalıyken</strong> ve sitede değilken de push gider.
		Önce OneSignal panelinden <strong>REST API Key</strong> alın (Settings → Keys &amp; IDs) ve kaydedin.
	</p>

	{if $cnOneSignalReady}
	<div class="alert alert-success py-2 small">OneSignal yapılandırması hazır — sunucu push gönderebilir.</div>
	{else}
	<div class="alert alert-warning py-2 small">
		<strong>REST API Key eksik.</strong> SDK yüklenir ama sunucu OneSignal’a push gönderemez.
		Key’i kaydedene kadar sitedeyken yerel yedek (poll) kullanılır; tarayıcı kapalıyken bildirim gitmez.
	</div>
	{/if}

	{if $cnOneSignalLastError}
	<div class="alert alert-danger py-2 small">Son OneSignal hatası: {$cnOneSignalLastError|escape}</div>
	{/if}
	{if $cnOneSignalLastOk}
	<div class="alert alert-info py-2 small">Son başarılı gönderim: {$cnOneSignalLastOk|escape}</div>
	{/if}

	<p class="small mb-3">
		Worker URL (erişilebilir olmalı):
		<code class="user-select-all">{$cnOneSignalWorkerUrl|escape}</code>
	</p>

	<form method="post">
		<input type="hidden" name="token" value="{$adminToken}">
		<input type="hidden" name="saveCustomerNotifyPush" value="1">

		<div class="form-check form-switch mb-3">
			<input class="form-check-input" type="checkbox" name="browser_enabled" value="1" id="cnBrowserEnabled"{if $cnBrowserEnabled} checked{/if}>
			<label class="form-check-label" for="cnBrowserEnabled">Tarayıcı bildirimlerini aç</label>
		</div>

		<div class="mb-3">
			<label class="form-label" for="cnBrowserMode">Ne zaman gönderilsin?</label>
			<select name="browser_mode" id="cnBrowserMode" class="form-select">
				<option value="shipped"{if $cnBrowserMode == 'shipped'} selected{/if}>Sipariş kargoya verilince + yayınlar</option>
				<option value="all_status"{if $cnBrowserMode == 'all_status'} selected{/if}>Tüm sipariş durumu değişiklikleri + yayınlar</option>
				<option value="broadcast_only"{if $cnBrowserMode == 'broadcast_only'} selected{/if}>Sadece bu modülden gönderilen yayınlar</option>
			</select>
		</div>

		<div class="mb-3">
			<label class="form-label" for="cnOsAppId">OneSignal App ID</label>
			<input type="text" class="form-control font-monospace" id="cnOsAppId" name="onesignal_app_id" value="{$cnOneSignalAppId|escape}" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
		</div>

		<div class="mb-3">
			<label class="form-label" for="cnOsSafari">Safari Web ID</label>
			<input type="text" class="form-control font-monospace" id="cnOsSafari" name="onesignal_safari_web_id" value="{$cnOneSignalSafari|escape}" placeholder="web.onesignal.auto....">
		</div>

		<div class="mb-3">
			<label class="form-label" for="cnOsRest">REST API Key</label>
			<input type="password" class="form-control font-monospace" id="cnOsRest" name="onesignal_rest_api_key" value="" placeholder="{if $cnOneSignalRestConfigured}•••••••• (değiştirmek için yeni key yazın){else}OneSignal → Settings → Keys &amp; IDs{/if}" autocomplete="new-password">
			<div class="form-text">Boş bırakırsanız mevcut key korunur. Bu key sunucudan push göndermek için zorunlu.</div>
		</div>

		<div class="mb-3">
			<label class="form-label" for="cnBrowserWebhook">Webhook URL (isteğe bağlı)</label>
			<input type="url" class="form-control" id="cnBrowserWebhook" name="browser_webhook" value="{$cnBrowserWebhook|escape}" placeholder="https://hooks.example.com/push">
		</div>

		<button type="submit" class="btn btn-dark btn-sm">Ayarları kaydet</button>
	</form>

	<hr class="my-4">

	<h3 class="h6 mb-2">OneSignal test gönderimi</h3>
	<p class="small text-muted mb-3">
		Müşteri önce mağazada giriş yapıp bildirim iznini “Evet” demiş olmalı.
		OneSignal panelinde bu kullanıcının <code>external_id</code> olarak müşteri ID’si görünmeli.
	</p>
	<form method="post" class="row g-2 align-items-end" style="max-width: 420px;">
		<input type="hidden" name="token" value="{$adminToken}">
		<input type="hidden" name="testCustomerNotifyOneSignal" value="1">
		<div class="col-auto flex-grow-1">
			<label class="form-label" for="cnTestUserId">Müşteri ID</label>
			<input type="number" min="1" class="form-control" id="cnTestUserId" name="test_user_id" required placeholder="örn. 12">
		</div>
		<div class="col-auto">
			<button type="submit" class="btn btn-outline-primary btn-sm"{if !$cnOneSignalReady} disabled{/if}>Test push gönder</button>
		</div>
	</form>
</div>
{elseif $tab == 'send' || $tab == ''}
<div class="admin-panel p-3" style="max-width: 820px;">
	<h2 class="h6 mb-3">Müşterilere bildirim gönder</h2>
	<p class="small text-muted">Bildirimler mağaza zil ikonunda görünür. Tarayıcı bildirimi açıksa uygun cihazlara da düşer. Link boş bırakılırsa bildirim detay sayfası açılır.</p>

	<form method="post" id="customerNotifyForm">
		<input type="hidden" name="sendCustomerNotify" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="mb-3">
			<label class="form-label" for="cnTitle">Başlık</label>
			<input type="text" class="form-control" id="cnTitle" name="title" maxlength="255" required>
		</div>

		<div class="mb-3">
			<label class="form-label" for="cnMessage">Mesaj</label>
			<textarea class="form-control" id="cnMessage" name="message" rows="5" required></textarea>
		</div>

		<div class="mb-3">
			<label class="form-label" for="cnLink">Yönlendirme linki (isteğe bağlı)</label>
			<input type="text" class="form-control" id="cnLink" name="link" placeholder="kampanya, my-account, https://...">
			<div class="form-text">Boş bırakılırsa <code>/customer-notification?id=…</code> detay sayfası kullanılır.</div>
		</div>

		<div class="mb-3">
			<label class="form-label d-block">Alıcılar</label>
			<div class="form-check">
				<input class="form-check-input" type="radio" name="scope" id="cnScopeAll" value="all" checked>
				<label class="form-check-label" for="cnScopeAll">Tüm müşteriler ({$customerCount} aktif)</label>
			</div>
			<div class="form-check">
				<input class="form-check-input" type="radio" name="scope" id="cnScopeSelected" value="selected">
				<label class="form-check-label" for="cnScopeSelected">Seçili müşteriler</label>
			</div>
		</div>

		<div class="mb-3 cn-selected-wrap d-none">
			<label class="form-label" for="cnCustomerQuery">Müşteri ara</label>
			<input type="text" class="form-control" id="cnCustomerQuery" placeholder="Ad, e-posta veya telefon…" autocomplete="off">
			<div class="list-group mt-2 cn-customer-results"></div>
			<div class="mt-2 cn-selected-list"></div>
		</div>

		<div class="form-check mb-3">
			<input class="form-check-input" type="checkbox" name="send_email" value="1" id="cnSendEmail">
			<label class="form-check-label" for="cnSendEmail">E-posta ile de gönder (bildirim + e-posta)</label>
		</div>

		<button type="submit" class="btn btn-dark">Bildirimi gönder</button>
	</form>
</div>
{else}
<div class="admin-panel p-3">
	<h2 class="h6 mb-3">Gönderim geçmişi</h2>
	<div class="table-responsive">
		<table class="table table-sm align-middle">
			<thead>
				<tr>
					<th>#</th>
					<th>Başlık</th>
					<th>Kapsam</th>
					<th>Gönderilen</th>
					<th>E-posta</th>
					<th>Tarih</th>
				</tr>
			</thead>
			<tbody>
				{if $broadcasts|@count}
				{foreach $broadcasts as $row}
				<tr>
					<td>{$row.id_broadcast}</td>
					<td>
						<strong>{$row.title|escape}</strong>
						<div class="small text-muted text-truncate" style="max-width:320px">{$row.message|escape|truncate:120}</div>
						{if $row.link != ''}<div class="small"><code>{$row.link|escape}</code></div>{/if}
					</td>
					<td>{if $row.scope == 'selected'}Seçili{else}Tümü{/if}</td>
					<td>{$row.recipient_count}</td>
					<td>{if $row.send_email}Evet{else}Hayır{/if}</td>
					<td class="small text-nowrap">{$row.date_add|escape}</td>
				</tr>
				{/foreach}
				{else}
				<tr><td colspan="6" class="text-muted">Henüz gönderim yok.</td></tr>
				{/if}
			</tbody>
		</table>
	</div>
</div>
{/if}

<script>
window.customerNotifyAdminConfig = {
	searchUrl: {$searchCustomersUrl|@json_encode nofilter},
	token: {$adminToken|@json_encode nofilter}
};
</script>
