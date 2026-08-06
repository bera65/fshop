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

<ul class="nav nav-tabs mb-3">
	<li class="nav-item">
		<a class="nav-link{if $settingsTab == 'general'} active{/if}" href="{$adminUrl}settings?tab=general">{'General'|adminT}</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $settingsTab == 'contact'} active{/if}" href="{$adminUrl}settings?tab=contact">{'Contact'|adminT}</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $settingsTab == 'email'} active{/if}" href="{$adminUrl}settings?tab=email">{'Email'|adminT}</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $settingsTab == 'orders'} active{/if}" href="{$adminUrl}settings?tab=orders">{'Order number'|adminT}</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $settingsTab == 'returns'} active{/if}" href="{$adminUrl}settings?tab=returns">{'Returns'|adminT}</a>
	</li>
</ul>

<div class="row g-4">
	<div class="col-lg-8">
		<form method="post">
			<input type="hidden" name="saveSettings" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<input type="hidden" name="settings_tab" value="{$settingsTab|escape}">

			<div class="settings-tab-panel{if $settingsTab != 'general'} d-none{/if}">
				<div class="admin-panel mb-4">
					<h2 class="h6 mb-3">{'General'|adminT}</h2>
					<div class="mb-3">
						<label class="form-label">{$settingsKeys.SITE_NAME.label|adminT|escape}</label>
						<input type="text" name="SITE_NAME" class="form-control" value="{$settingsValues.SITE_NAME|escape}">
					</div>
					<div class="mb-3">
						<label class="form-label">{'Site visibility'|adminT}</label>
						<select name="SITE_VISIBILITY" class="form-select" id="siteVisibilitySelect">
							<option value="public"{if $settingsValues.SITE_VISIBILITY != 'members_only'} selected{/if}>{'Open to everyone'|adminT}</option>
							<option value="members_only"{if $settingsValues.SITE_VISIBILITY == 'members_only'} selected{/if}>{'Members only'|adminT}</option>
						</select>
					</div>
					<div id="membersOnlySettings"{if $settingsValues.SITE_VISIBILITY != 'members_only'} style="display:none"{/if}>
						<div class="mb-3">
							<label class="form-label">{'Member approval'|adminT}</label>
							<select name="MEMBER_APPROVAL" class="form-select">
								<option value="auto"{if $settingsValues.MEMBER_APPROVAL != 'manual'} selected{/if}>{'Auto approve (when registered)'|adminT}</option>
								<option value="manual"{if $settingsValues.MEMBER_APPROVAL == 'manual'} selected{/if}>{'Admin approve (manual)'|adminT}</option>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label">{'Gate title'|adminT}</label>
							<input type="text" name="GATE_TITLE" class="form-control" value="{$settingsValues.GATE_TITLE|escape}" placeholder="{'Members only'|adminT}">
							<div class="form-text">{'Shown as the main headline on the members-only gate screen.'|adminT}</div>
						</div>
						<div class="mb-3">
							<label class="form-label">{'Gate features'|adminT}</label>
							<textarea name="GATE_FEATURES" class="form-control" rows="3" placeholder="Antalya içi aynı gün ücretsiz teslimat&#10;₺1.500+ kargo bedava (Türkiye geneli)&#10;Eczacı ve tedarikçilere özel çözümler">{$settingsValues.GATE_FEATURES|escape}</textarea>
							<div class="form-text">{'Each line will be shown as a feature with a check icon.'|adminT}</div>
						</div>
					</div>
					<div class="mb-3">
						<label class="form-label">{'Store currency'|adminT}</label>
						<p class="mb-2"><strong>{$shopCurrencyLabel|escape}</strong> <code>{$shopCurrencyCode|escape}</code></p>
						<a href="{$adminUrl}currencies" class="btn btn-sm btn-outline-secondary">{'Manage currencies'|adminT}</a>
						<div class="form-text mt-2">{'Use the currencies page to add units or change the active currency.'|adminT}</div>
					</div>
					<div class="mb-0">
						<label class="form-label">{'Tax rates (VAT)'|adminT}</label>
						<a href="{$adminUrl}taxes" class="btn btn-sm btn-outline-secondary">{'Manage tax rates'|adminT}</a>
						<div class="form-text mt-2">{'Add or edit VAT rates used on products (e.g. 1%, 10%, 20%).'|adminT}</div>
					</div>
				</div>

				<div class="admin-panel mb-4">
					<h2 class="h6 mb-3">{'Store status'|adminT}</h2>
					<input type="hidden" name="SHOP_ACTIVE" value="0">
					<div class="form-check form-switch mb-3">
						<input class="form-check-input" type="checkbox" name="SHOP_ACTIVE" value="1" id="shopActiveSwitch"{if $settingsValues.SHOP_ACTIVE != '0'} checked{/if}>
						<label class="form-check-label" for="shopActiveSwitch">{'Store is active (open to visitors)'|adminT}</label>
					</div>
					<p class="small text-muted mb-3">{'When the store is closed, visitors see the maintenance message below. Admin panel always stays accessible.'|adminT}</p>

					<div class="mb-3">
						<label class="form-label">{$settingsKeys.SHOP_MAINTENANCE_MESSAGE.label|adminT|escape}</label>
						<textarea name="SHOP_MAINTENANCE_MESSAGE" class="form-control wysiwyg-editor" rows="8">{$settingsValues.SHOP_MAINTENANCE_MESSAGE|escape}</textarea>
						<div class="form-text">{'Shown on the storefront when the store is closed.'|adminT}</div>
					</div>

					<div class="mb-0">
						<label class="form-label">{$settingsKeys.SHOP_MAINTENANCE_IPS.label|adminT|escape}</label>
						<textarea name="SHOP_MAINTENANCE_IPS" class="form-control" rows="4" placeholder="127.0.0.1&#10;192.168.1.10">{$settingsValues.SHOP_MAINTENANCE_IPS|escape}</textarea>
						<div class="form-text">{'One IP per line. These addresses can browse the store even when it is closed.'|adminT} {'Your current IP:'|adminT} <code>{$currentClientIp|escape}</code></div>
					</div>
				</div>
			</div>

			<div class="settings-tab-panel{if $settingsTab != 'contact'} d-none{/if}">
				<div class="admin-panel mb-4">
					<h2 class="h6 mb-3">{'Contact details'|adminT}</h2>
					<div class="mb-3">
						<label class="form-label">{$settingsKeys.CONTACT_EMAIL.label|adminT|escape}</label>
						<input type="email" name="CONTACT_EMAIL" class="form-control" value="{$settingsValues.CONTACT_EMAIL|escape}">
						<div class="form-text">{'If you use PHP mail(), this email is used as the sender address.'|adminT}</div>
					</div>
					<div class="row g-3 mb-0">
						<div class="col-md-6">
							<label class="form-label">{$settingsKeys.CONTACT_PHONE.label|adminT|escape}</label>
							<input type="text" name="CONTACT_PHONE" class="form-control" value="{$settingsValues.CONTACT_PHONE|escape}">
						</div>
						<div class="col-md-6">
							<label class="form-label">{$settingsKeys.CONTACT_PHONE_TEL.label|adminT|escape}</label>
							<input type="text" name="CONTACT_PHONE_TEL" class="form-control" value="{$settingsValues.CONTACT_PHONE_TEL|escape}" placeholder="+905551234567">
						</div>
					</div>
				</div>

				<div class="admin-panel mb-4">
					<h2 class="h6 mb-3">{'Store address'|adminT}</h2>
					<div class="mb-3">
						<label class="form-label">{$settingsKeys.CONTACT_ADDRESS.label|adminT|escape}</label>
						<textarea name="CONTACT_ADDRESS" class="form-control" rows="3" placeholder="{'Street, building, district...'|adminT}">{$settingsValues.CONTACT_ADDRESS|escape}</textarea>
					</div>
					<div class="row g-3 mb-3">
						<div class="col-md-4">
							<label class="form-label">{$settingsKeys.CONTACT_CITY.label|adminT|escape}</label>
							<input type="text" name="CONTACT_CITY" class="form-control" value="{$settingsValues.CONTACT_CITY|escape}">
						</div>
						<div class="col-md-4">
							<label class="form-label">{$settingsKeys.POSTAL_CODE.label|adminT|escape}</label>
							<input type="text" name="POSTAL_CODE" class="form-control" value="{$settingsValues.POSTAL_CODE|escape}">
						</div>
						<div class="col-md-4">
							<label class="form-label">{$settingsKeys.CONTACT_COUNTRY.label|adminT|escape}</label>
							<input type="text" name="CONTACT_COUNTRY" class="form-control" value="{$settingsValues.CONTACT_COUNTRY|escape}">
						</div>
					</div>
					<div class="row g-3 mb-0">
						<div class="col-md-6">
							<label class="form-label">{$settingsKeys.OPEN_HOUR.label|adminT|escape}</label>
							<input type="time" name="OPEN_HOUR" class="form-control" value="{$settingsValues.OPEN_HOUR|default:'09:00'|escape}">
						</div>
						<div class="col-md-6">
							<label class="form-label">{$settingsKeys.CLOSE_HOUR.label|adminT|escape}</label>
							<input type="time" name="CLOSE_HOUR" class="form-control" value="{$settingsValues.CLOSE_HOUR|default:'18:00'|escape}">
						</div>
					</div>
				</div>

				<div class="admin-panel mb-4">
					<h2 class="h6 mb-3">{'Social media links'|adminT}</h2>
					<p class="small text-muted mb-3">{'Used in the site footer and schema markup. Leave blank to hide.'|adminT}</p>
					<div class="row g-3">
						<div class="col-md-6">
							<label class="form-label">{$settingsKeys.FACEBOOK_LINK.label|adminT|escape}</label>
							<input type="url" name="FACEBOOK_LINK" class="form-control" value="{$settingsValues.FACEBOOK_LINK|escape}" placeholder="https://facebook.com/...">
						</div>
						<div class="col-md-6">
							<label class="form-label">{$settingsKeys.INSTAGRAM_LINK.label|adminT|escape}</label>
							<input type="url" name="INSTAGRAM_LINK" class="form-control" value="{$settingsValues.INSTAGRAM_LINK|escape}" placeholder="https://instagram.com/...">
						</div>
						<div class="col-md-6">
							<label class="form-label">{$settingsKeys.X_LINK.label|adminT|escape}</label>
							<input type="url" name="X_LINK" class="form-control" value="{$settingsValues.X_LINK|escape}" placeholder="https://x.com/...">
						</div>
						<div class="col-md-6">
							<label class="form-label">{$settingsKeys.YOUTUBE_LINK.label|adminT|escape}</label>
							<input type="url" name="YOUTUBE_LINK" class="form-control" value="{$settingsValues.YOUTUBE_LINK|escape}" placeholder="https://youtube.com/...">
						</div>
						<div class="col-md-6">
							<label class="form-label">{$settingsKeys.LINKEDIN_LINK.label|adminT|escape}</label>
							<input type="url" name="LINKEDIN_LINK" class="form-control" value="{$settingsValues.LINKEDIN_LINK|escape}" placeholder="https://linkedin.com/...">
						</div>
						<div class="col-md-6">
							<label class="form-label">{$settingsKeys.PINTEREST_LINK.label|adminT|escape}</label>
							<input type="url" name="PINTEREST_LINK" class="form-control" value="{$settingsValues.PINTEREST_LINK|escape}" placeholder="https://pinterest.com/...">
						</div>
						<div class="col-md-6 mb-0">
							<label class="form-label">{$settingsKeys.TIKTOK_LINK.label|adminT|escape}</label>
							<input type="url" name="TIKTOK_LINK" class="form-control" value="{$settingsValues.TIKTOK_LINK|escape}" placeholder="https://tiktok.com/...">
						</div>
					</div>
				</div>
			</div>

			<div class="settings-tab-panel{if $settingsTab != 'email'} d-none{/if}">
				<div class="admin-panel mb-4">
					<h2 class="h6 mb-3">{'Email delivery'|adminT}</h2>
					<div class="mb-3">
						<label class="form-label">{$settingsKeys.MAIL_DRIVER.label|adminT|escape}</label>
						<select name="MAIL_DRIVER" class="form-select" id="mailDriverSelect">
							<option value="mail" {if $settingsValues.MAIL_DRIVER != 'smtp'}selected{/if}>{'PHP mail() - Server mail system'|adminT}</option>
							<option value="smtp" {if $settingsValues.MAIL_DRIVER == 'smtp'}selected{/if}>{'SMTP - External mail server'|adminT}</option>
						</select>
					</div>

					{if $mailConfigured}
					<div class="alert alert-success py-2 small">
						{if $usesSmtp}{'SMTP configuration is ready.'|adminT}{else}{'Sender email is set for PHP mail().'|adminT}{/if}
					</div>
					{else}
					<div class="alert alert-warning py-2 small">
						{if $usesSmtp}{'Complete SMTP settings (host, user, password).'|adminT}{else}{'Enter a contact email for PHP mail().'|adminT}{/if}
					</div>
					{/if}

					<div id="smtpFields" {if $settingsValues.MAIL_DRIVER != 'smtp'}style="display:none"{/if}>
						<div class="row g-3">
							<div class="col-md-8">
								<label class="form-label">{$settingsKeys.SMTP_HOST.label|adminT|escape}</label>
								<input type="text" name="SMTP_HOST" class="form-control" value="{$settingsValues.SMTP_HOST|escape}" placeholder="mail.example.com">
							</div>
							<div class="col-md-4">
								<label class="form-label">{$settingsKeys.SMTP_PORT.label|adminT|escape}</label>
								<input type="text" name="SMTP_PORT" class="form-control" value="{$settingsValues.SMTP_PORT|escape}" placeholder="465">
							</div>
							<div class="col-md-6">
								<label class="form-label">{$settingsKeys.SMTP_USER.label|adminT|escape}</label>
								<input type="email" name="SMTP_USER" class="form-control" value="{$settingsValues.SMTP_USER|escape}" placeholder="sales@example.com">
							</div>
							<div class="col-md-6">
								<label class="form-label">{$settingsKeys.SMTP_PASS.label|adminT|escape}</label>
								<input type="password" name="SMTP_PASS" class="form-control" value="" placeholder="{if $settingsValues.SMTP_PASS}********{else}{'SMTP password'|adminT}{/if}" autocomplete="new-password">
								<div class="form-text">{'Leave blank to keep the current password.'|adminT}</div>
							</div>
							<div class="col-md-4">
								<label class="form-label">{$settingsKeys.SMTP_ENCRYPTION.label|adminT|escape}</label>
								<select name="SMTP_ENCRYPTION" class="form-select">
									<option value="ssl" {if $settingsValues.SMTP_ENCRYPTION == 'ssl'}selected{/if}>SSL (465)</option>
									<option value="tls" {if $settingsValues.SMTP_ENCRYPTION == 'tls'}selected{/if}>TLS (587)</option>
									<option value="none" {if $settingsValues.SMTP_ENCRYPTION == 'none'}selected{/if}>{'None'|adminT}</option>
								</select>
							</div>
							<div class="col-md-4">
								<label class="form-label">{$settingsKeys.SMTP_FROM_EMAIL.label|adminT|escape}</label>
								<input type="email" name="SMTP_FROM_EMAIL" class="form-control" value="{$settingsValues.SMTP_FROM_EMAIL|escape}" placeholder="sales@example.com">
							</div>
							<div class="col-md-4">
								<label class="form-label">{$settingsKeys.SMTP_FROM_NAME.label|adminT|escape}</label>
								<input type="text" name="SMTP_FROM_NAME" class="form-control" value="{$settingsValues.SMTP_FROM_NAME|escape}" placeholder="{'Site name'|adminT}">
							</div>
						</div>
					</div>

					<div id="phpMailHint" class="text-muted small mt-2" {if $settingsValues.MAIL_DRIVER == 'smtp'}style="display:none"{/if}>
						{'On local stacks like WAMP/XAMPP, PHP mail() usually fails. Use production hosting or SMTP.'|adminT}
					</div>
				</div>

				<div class="admin-panel mb-4">
					<h2 class="h6 mb-2">{'Email template'|adminT}</h2>
					<p class="small text-muted mb-3">{'All outgoing emails use the same layout: fixed header and footer, with dynamic content in the middle. Order notifications, welcome emails, campaigns and test mails all share this structure.'|adminT}</p>

					<div class="mb-3">
						<label class="form-label">{$settingsKeys.MAIL_HEADER.label|adminT|escape}</label>
						<textarea name="MAIL_HEADER" class="form-control wysiwyg-editor" rows="8">{$settingsValues.MAIL_HEADER|escape}</textarea>
						<div class="form-text">{'Shown at the top of every email. Default includes the store logo.'|adminT}</div>
					</div>

					<div class="alert alert-light border small mb-3">
						<strong>{'Middle content'|adminT}</strong> — {'Filled automatically per email (order text, password reset, campaign body, etc.). You do not edit it here.'|adminT}
					</div>

					<div class="mb-3">
						<label class="form-label">{$settingsKeys.MAIL_FOOTER.label|adminT|escape}</label>
						<textarea name="MAIL_FOOTER" class="form-control wysiwyg-editor" rows="8">{$settingsValues.MAIL_FOOTER|escape}</textarea>
						<div class="form-text">{'Shown at the bottom of every email. Default includes contact details.'|adminT}</div>
					</div>

					<div class="mb-0">
						<label class="form-label">{'Layout placeholders'|adminT}</label>
						<div class="small">{foreach $mailLayoutPlaceholders as $ph}<code class="me-2">{$ph|escape}</code>{/foreach}</div>
					</div>
				</div>
			</div>

			<div class="settings-tab-panel{if $settingsTab != 'orders'} d-none{/if}">
				<div class="admin-panel mb-4">
					<h2 class="h6 mb-3">{'Order number'|adminT}</h2>
					<p class="small text-muted">{'Store, POS and online orders use the same numbering rule. Set a prefix (ECZ, EST, etc.) to distinguish sites.'|adminT}</p>
					<div class="row g-3">
						<div class="col-md-3">
							<label class="form-label">{$settingsKeys.ORDER_REF_PREFIX.label|adminT|escape}</label>
							<input type="text" name="ORDER_REF_PREFIX" class="form-control" maxlength="12"
								value="{$settingsValues.ORDER_REF_PREFIX|escape}" placeholder="{'e.g. ECZ, EST'|adminT}">
							<div class="form-text">{'Optional. Letters and digits only.'|adminT}</div>
						</div>
						<div class="col-md-5">
							<label class="form-label">{$settingsKeys.ORDER_REF_SUFFIX_MODE.label|adminT|escape}</label>
							<select name="ORDER_REF_SUFFIX_MODE" class="form-select" id="orderRefModeSelect">
								{foreach $orderRefModes as $modeKey => $modeLabel}
								<option value="{$modeKey|escape}"{if $orderRefActiveMode == $modeKey} selected{/if}>{$modeLabel|adminT|escape}</option>
								{/foreach}
							</select>
						</div>
						<div class="col-md-4" id="orderRefPadWrap">
							<label class="form-label">{$settingsKeys.ORDER_REF_PAD.label|adminT|escape}</label>
							<input type="number" min="3" max="10" name="ORDER_REF_PAD" class="form-control"
								value="{$settingsValues.ORDER_REF_PAD|default:5|escape}">
						</div>
					</div>
					<div class="alert alert-light border small mt-3 mb-0">
						<strong>{'Example:'|adminT}</strong> <code id="orderRefPreviewCode">{$orderRefPreview|escape}</code>
						<span class="text-muted ms-2">{'The next order number will look like this.'|adminT}</span>
					</div>
				</div>
				<div class="admin-panel mb-4">
					<h2 class="h6 mb-3">{'Gift wrap'|adminT}</h2>
					<div class="form-check form-switch mb-3">
						<input type="checkbox" class="form-check-input" id="giftWrapEnabled" name="GIFT_WRAP_ENABLED" value="1"{if $settingsValues.GIFT_WRAP_ENABLED|default:'0' == '1'} checked{/if}>
						<label class="form-check-label" for="giftWrapEnabled">{$settingsKeys.GIFT_WRAP_ENABLED.label|adminT|escape}</label>
					</div>
					<div class="mb-0" style="max-width:220px">
						<label class="form-label">{$settingsKeys.GIFT_WRAP_FEE.label|adminT|escape}</label>
						<input type="text" name="GIFT_WRAP_FEE" class="form-control" value="{$settingsValues.GIFT_WRAP_FEE|default:'0'|escape}" inputmode="decimal">
						<div class="form-text">{'0 = free gift wrap. Shown at checkout when enabled.'|adminT}</div>
					</div>
				</div>
			</div>

			<div class="settings-tab-panel{if $settingsTab != 'returns'} d-none{/if}">
				<div class="admin-panel mb-4">
					<h2 class="h6 mb-3">{'Returns'|adminT}</h2>
					<div class="mb-0">
						<label class="form-label">{$settingsKeys.RETURN_REQUEST_DAYS.label|adminT|escape}</label>
						<input type="number" min="0" max="365" name="RETURN_REQUEST_DAYS" class="form-control" value="{$settingsValues.RETURN_REQUEST_DAYS|escape}">
						<div class="form-text">{'Customers can open return requests only for <strong>delivered</strong> orders within this many days after delivery. 0 = disabled.'|adminT nofilter}</div>
					</div>
				</div>
			</div>

			<button type="submit" class="btn btn-dark">{'Save'|adminT}</button>
		</form>

		{if $settingsTab == 'email'}
		<form method="post" class="mt-3 d-flex flex-wrap gap-2 align-items-end">
			<input type="hidden" name="testMail" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<div class="flex-grow-1" style="min-width:220px;">
				<label class="form-label small mb-1">{'Test email address'|adminT}</label>
				<input type="email" name="test_email" class="form-control form-control-sm" placeholder="{$settingsValues.CONTACT_EMAIL|escape}">
			</div>
			<button type="submit" class="btn btn-outline-secondary btn-sm">{'Send test email'|adminT}</button>
		</form>
		{/if}
	</div>

	<div class="col-lg-4">
		{if $settingsTab == 'general'}
		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Read only'|adminT}</h2>
			<p class="small mb-2"><strong>{'Domain:'|adminT}</strong> {$readOnlySettings.DOMAIN|escape}</p>
			<p class="small mb-0"><strong>{'Folder:'|adminT}</strong> {$readOnlySettings.FOLDER|escape}</p>
			<p class="text-muted small mt-3 mb-0">{'Domain and folder must be changed directly in the database.'|adminT}</p>
		</div>
		<div class="admin-panel mt-4">
			<h2 class="h6 mb-3">{'Web API'|adminT}</h2>
			<p class="small text-muted mb-2">{'Partner API keys and permissions are managed on a separate page.'|adminT}</p>
			<a href="{$adminUrl}/api" class="btn btn-dark btn-sm">{'Go to API settings'|adminT}</a>
		</div>
		<div class="admin-panel mt-4">
			<h2 class="h6 mb-3">{'Store status'|adminT}</h2>
			{if $shopIsActive}
			<p class="small mb-0"><span class="badge text-bg-success">{'Active'|adminT}</span> {'Storefront is open to all visitors.'|adminT}</p>
			{else}
			<p class="small mb-0"><span class="badge text-bg-warning text-dark">{'Closed'|adminT}</span> {'Only allowed IPs can browse the store.'|adminT}</p>
			{/if}
		</div>
		{elseif $settingsTab == 'email'}
		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Email template preview'|adminT}</h2>
			<p class="small text-muted mb-2">{'Sample email with current header and footer settings.'|adminT}</p>
			<div class="border rounded overflow-hidden bg-white" style="max-height:420px;overflow-y:auto;">
				<iframe title="{'Email template preview'|adminT|escape}" srcdoc="{$mailTemplatePreview|escape:'html'}" style="width:100%;height:380px;border:0;background:#fff;"></iframe>
			</div>
		</div>
		<div class="admin-panel mt-4">
			<h2 class="h6 mb-3">{'Email info'|adminT}</h2>
			<ul class="small text-muted mb-0 ps-3">
				<li><strong>PHP mail():</strong> {'Hosting server mail() function'|adminT}</li>
				<li><strong>SMTP:</strong> {'External server such as frisay.com'|adminT}</li>
				<li>{'Test errors are now shown in detail'|adminT}</li>
				<li>{'SSL usually uses port 465'|adminT}</li>
			</ul>
		</div>
		{/if}
	</div>
</div>

<script>
(function () {
	var select = document.getElementById('mailDriverSelect');
	var smtpFields = document.getElementById('smtpFields');
	var phpHint = document.getElementById('phpMailHint');
	if (select) {
		select.addEventListener('change', function () {
			var isSmtp = select.value === 'smtp';
			smtpFields.style.display = isSmtp ? '' : 'none';
			phpHint.style.display = isSmtp ? 'none' : '';
		});
	}

	var siteVisSelect = document.getElementById('siteVisibilitySelect');
	var membersOnlyWrap = document.getElementById('membersOnlySettings');
	if (siteVisSelect && membersOnlyWrap) {
		siteVisSelect.addEventListener('change', function () {
			membersOnlyWrap.style.display = this.value === 'members_only' ? '' : 'none';
		});
	}

	var refMode = document.getElementById('orderRefModeSelect');
	var refPadWrap = document.getElementById('orderRefPadWrap');

	function syncRefPadVisibility() {
		if (!refMode || !refPadWrap) {
			return;
		}

		refPadWrap.style.display = refMode.value === 'sequential' ? '' : 'none';
	}

	if (refMode) {
		refMode.addEventListener('change', syncRefPadVisibility);
		syncRefPadVisibility();
	}
})();
</script>
