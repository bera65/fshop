{if $flash}
<div class="alert alert-{$flashType|default:'success'|escape} mb-3">{$flash|escape}</div>
{/if}

<div class="row g-3">
	<div class="col-lg-7">
		<div class="admin-panel">
			<h2 class="h6 mb-3">Shipink API ayarları</h2>
			<form method="post">
				<input type="hidden" name="token" value="{$adminToken}">
				<div class="row g-3">
					<div class="col-md-6">
						<label class="form-label">Kullanıcı adı (e-posta)</label>
						<input type="email" name="shipink_username" class="form-control" value="{$shipinkUsername|escape}" required autocomplete="username">
					</div>
					<div class="col-md-6">
						<label class="form-label">Şifre{if $shipinkHasPassword} <span class="text-muted small">(boş bırakılırsa değişmez)</span>{/if}</label>
						<input type="password" name="shipink_password" class="form-control" value="" autocomplete="new-password" placeholder="{if $shipinkHasPassword}••••••••{else}Shipink şifresi{/if}">
					</div>
					<div class="col-md-4">
						<label class="form-label">Ortam</label>
						<select name="shipink_env" class="form-select">
							<option value="prod"{if $shipinkEnv == 'prod'} selected{/if}>Production (api.shipink.io)</option>
							<option value="dev"{if $shipinkEnv == 'dev'} selected{/if}>Test (api.dev.shipink.io)</option>
						</select>
					</div>
					<div class="col-md-8 d-flex align-items-end">
						<div class="form-check mb-2">
							<input class="form-check-input" type="checkbox" name="shipink_auto_send" id="shipinkAutoSend" value="1"{if $shipinkAutoSend} checked{/if}>
							<label class="form-check-label" for="shipinkAutoSend">Sipariş oluşunca / hazırlanınca otomatik gönder</label>
						</div>
					</div>

					<div class="col-md-6">
						<label class="form-label">Warehouse ID</label>
						{if $shipinkWarehouses|@count}
						<select name="shipink_warehouse_id" class="form-select">
							<option value="">— Seçin —</option>
							{foreach $shipinkWarehouses as $wh}
							{assign var=whId value=$wh.id|default:''}
							<option value="{$whId|escape}"{if $shipinkWarehouseId == $whId} selected{/if}>{$wh.name|default:$whId|escape}</option>
							{/foreach}
						</select>
						{else}
						<input type="text" name="shipink_warehouse_id" class="form-control" value="{$shipinkWarehouseId|escape}" placeholder="UUID">
						{/if}
						<div class="form-text">Shipink paneli veya API warehouses listesi</div>
					</div>
					<div class="col-md-6">
						<label class="form-label">Carrier Account ID</label>
						{if $shipinkCarrierAccounts|@count}
						<select name="shipink_carrier_account_id" class="form-select">
							<option value="">— Seçin —</option>
							{foreach $shipinkCarrierAccounts as $acc}
							{assign var=accId value=$acc.id|default:''}
							<option value="{$accId|escape}"{if $shipinkCarrierAccountId == $accId} selected{/if}>{$acc.name|default:$acc.carrier_id|default:$accId|escape}</option>
							{/foreach}
						</select>
						{else}
						<input type="text" name="shipink_carrier_account_id" class="form-control" value="{$shipinkCarrierAccountId|escape}" placeholder="UUID">
						{/if}
					</div>
					<div class="col-md-6">
						<label class="form-label">Carrier Service ID</label>
						{if $shipinkCarrierServices|@count}
						<select name="shipink_carrier_service_id" class="form-select">
							<option value="">— Seçin —</option>
							{foreach $shipinkCarrierServices as $svc}
							{assign var=svcId value=$svc.id|default:''}
							<option value="{$svcId|escape}"{if $shipinkCarrierServiceId == $svcId} selected{/if}>{$svc.name|default:$svcId|escape}</option>
							{/foreach}
						</select>
						{else}
						<input type="text" name="shipink_carrier_service_id" class="form-control" value="{$shipinkCarrierServiceId|escape}" placeholder="örn. aras_standart">
						{/if}
					</div>
					<div class="col-md-6">
						<label class="form-label">Card ID <span class="text-muted">(opsiyonel)</span></label>
						<input type="text" name="shipink_card_id" class="form-control" value="{$shipinkCardId|escape}" placeholder="Shipink anlaşmalı hesap için">
						<div class="form-text">Shipink taşıyıcı anlaşması kullanıyorsanız zorunlu olabilir</div>
					</div>

					<div class="col-12"><hr class="my-1"></div>
					<div class="col-md-3">
						<label class="form-label">Paket ağırlık (kg)</label>
						<input type="text" name="shipink_pkg_weight" class="form-control" value="{$shipinkPkgWeight|escape}">
					</div>
					<div class="col-md-3">
						<label class="form-label">Yükseklik (cm)</label>
						<input type="text" name="shipink_pkg_height" class="form-control" value="{$shipinkPkgHeight|escape}">
					</div>
					<div class="col-md-3">
						<label class="form-label">Genişlik (cm)</label>
						<input type="text" name="shipink_pkg_width" class="form-control" value="{$shipinkPkgWidth|escape}">
					</div>
					<div class="col-md-3">
						<label class="form-label">Uzunluk (cm)</label>
						<input type="text" name="shipink_pkg_length" class="form-control" value="{$shipinkPkgLength|escape}">
					</div>

					<div class="col-md-6">
						<label class="form-label">Webhook link token</label>
						<input type="text" name="shipink_link_token" class="form-control" value="{$shipinkLinkToken|escape}" placeholder="sadece harf ve rakam">
					</div>
					<div class="col-md-6">
						<label class="form-label">Webhook URL</label>
						<input type="text" class="form-control" readonly value="{$shipinkWebhookUrl|escape}">
						<div class="form-text">Shipink panelinde webhook varsa bu adresi kullanın</div>
					</div>
				</div>
				<div class="mt-3 d-flex flex-wrap gap-2">
					<button type="submit" name="saveShipink" value="{$adminToken}" class="btn btn-dark">Kaydet</button>
					<button type="submit" name="testConnection" value="{$adminToken}" class="btn btn-outline-dark">Bağlantıyı test et</button>
					<button type="submit" name="refreshLists" value="{$adminToken}" class="btn btn-outline-secondary">Depo / taşıyıcı listesini yenile</button>
				</div>
			</form>
		</div>
	</div>
	<div class="col-lg-5">
		<div class="alert alert-info">
			<h3 class="h6">Kurulum</h3>
			<ol class="small mb-0 ps-3">
				<li><a href="https://app.shipink.io/signup" target="_blank" rel="noopener">Shipink</a> hesabı oluşturun (test için app.dev.shipink.io).</li>
				<li>Kullanıcı / şifre ile token alınır; depo ve taşıyıcı hesap ID’lerini kaydedin.</li>
				<li>Akış: FShop siparişi → Shipink <code>orders</code> → <code>shipments</code> → takip no siparişe yazılır.</li>
				<li>Kapıda ödeme siparişlerinde gönderiye COD eklenir.</li>
			</ol>
		</div>
		{if $isConfigured}
		<div class="alert alert-success small">Yapılandırma tamam — gönderi oluşturulabilir.</div>
		{else}
		<div class="alert alert-warning small">Eksik alanlar var: kullanıcı, şifre, warehouse, carrier service ve carrier account zorunlu.</div>
		{/if}
		{if $sonuc}
		<div class="admin-panel">
			<h3 class="h6">API yanıtı</h3>
			<pre class="small mb-0" style="max-height:280px;overflow:auto;">{$sonuc|escape}</pre>
		</div>
		{/if}
	</div>
</div>
