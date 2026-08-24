{if $flash}
<div class="alert alert-{$flashType|default:'success'}">{$flash|escape}</div>
{/if}

<ul class="nav nav-tabs ai-sc-tabs mb-3">
	<li class="nav-item">
		<a class="nav-link{if $tab == 'rules'} active{/if}" href="{$adminUrl}module-smart-campaign?tab=rules">Kurallar</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $tab == 'queue'} active{/if}" href="{$adminUrl}module-smart-campaign?tab=queue">Gönderim kuyruğu</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $tab == 'stats'} active{/if}" href="{$adminUrl}module-smart-campaign?tab=stats">İstatistik & Cron</a>
	</li>
</ul>

{if $tab == 'stats'}
<div class="ai-sc-stats mb-4">
	<div class="ai-sc-stat"><strong>{$stats.rules}</strong><span>Kural</span></div>
	<div class="ai-sc-stat"><strong>{$stats.pending}</strong><span>Bekleyen</span></div>
	<div class="ai-sc-stat"><strong>{$stats.sent}</strong><span>Gönderildi</span></div>
	<div class="ai-sc-stat"><strong>{$stats.clicked}</strong><span>Tıklanan</span></div>
	<div class="ai-sc-stat"><strong>{$stats.clicks}</strong><span>Toplam tıklama</span></div>
</div>

<div class="admin-panel p-3 mb-4">
	<h2 class="h6 mb-2">Cron URL</h2>
	<p class="small text-muted">Sunucu cron'unu saatte bir çalıştırın. Bekleyen e-postalar gecikme süresi dolunca gönderilir.</p>
	<code class="d-block p-2 bg-light rounded small mb-3">{$cronUrl|escape}</code>
	<form method="post" class="d-inline">
		<input type="hidden" name="token" value="{$adminToken}">
		<button type="submit" name="runSmartCampaignCron" value="1" class="btn btn-sm btn-outline-dark">Şimdi çalıştır (test)</button>
	</form>
</div>

<div class="admin-panel p-3">
	<h2 class="h6 mb-2">Takip nasıl çalışır?</h2>
	<ul class="small text-muted mb-0 ps-3">
		<li>E-postada <code>{ldelim}track_url{rdelim}</code> kullanın — tıklama kaydedilir ve hedef URL'ye yönlendirilir.</li>
		<li>Hedef URL'nin sonuna otomatik <code>?sc=KOD</code> eklenir (geri dönüş takibi).</li>
		<li>Müşteri doğrudan hedef URL'ye <code>sc=</code> ile gelirse tıklama yine kaydedilir.</li>
	</ul>
</div>

{elseif $tab == 'queue'}
<div class="admin-panel p-0">
	<div class="table-responsive">
		<table class="table table-sm mb-0 ai-sc-table">
			<thead>
				<tr>
					<th>Kural</th>
					<th>Müşteri</th>
					<th>Sipariş</th>
					<th>Gönderim zamanı</th>
					<th>Durum</th>
					<th>Tıklama</th>
					<th>Kod</th>
				</tr>
			</thead>
			<tbody>
				{foreach $queueRows as $row}
				<tr>
					<td>{$row.rule_name|escape}</td>
					<td>
						<div>{$row.customer_name|escape}</div>
						<div class="text-muted">{$row.customer_email|escape}</div>
					</td>
					<td>#{$row.order_reference|escape}</td>
					<td>
						<div>Gönder: {$row.send_after|escape}</div>
						{if $row.sent_at}<div class="text-muted">Gitti: {$row.sent_at|escape}</div>{/if}
					</td>
					<td>
						{if $row.status == 'sent'}<span class="badge text-bg-success">Gönderildi</span>
						{elseif $row.status == 'pending'}<span class="badge text-bg-secondary">Bekliyor</span>
						{elseif $row.status == 'failed'}<span class="badge text-bg-danger" title="{$row.error_message|escape}">Hata</span>
						{else}<span class="badge text-bg-light text-dark">{$row.status|escape}</span>{/if}
					</td>
					<td>
						{if $row.click_count > 0}
						<strong>{$row.click_count}</strong>
						{if $row.first_click_at}<div class="text-muted">{$row.first_click_at|escape}</div>{/if}
						{else}—{/if}
					</td>
					<td><code class="ai-sc-code">{$row.tracking_code|escape}</code></td>
				</tr>
				{foreachelse}
				<tr><td colspan="7" class="text-muted p-4">Henüz kuyruk kaydı yok.</td></tr>
				{/foreach}
			</tbody>
		</table>
	</div>
</div>

{else}
<div class="row g-4">
	<div class="col-lg-5">
		<form method="post" class="admin-panel p-3">
			<input type="hidden" name="saveSmartCampaignRule" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<input type="hidden" name="id_rule" value="{$editRule.id_rule|default:0}">

			<h2 class="h6 mb-3">{if $editRule}Kuralı düzenle{else}Yeni kural{/if}</h2>

			<div class="mb-3">
				<label class="form-label">Kural adı</label>
				<input type="text" name="name" class="form-control form-control-sm" required value="{$editRule.name|default:''|escape}" placeholder="Örn: X ürünü sonrası teşekkür maili">
			</div>

			<div class="mb-3">
				<label class="form-label">Tetikleyici ürün (X)</label>
				<select name="id_product" class="form-select form-select-sm" required>
					<option value="">— Ürün seçin —</option>
					{foreach $products as $p}
					<option value="{$p.id_product}"{if $editRule && $editRule.id_product == $p.id_product} selected{/if}>{$p.product_name|escape}</option>
					{/foreach}
				</select>
			</div>

			<div class="mb-3">
				<label class="form-label">Tetikleyici durum</label>
				<select name="trigger_status" class="form-select form-select-sm">
					{foreach $triggerStatusOptions as $statusId => $statusLabel}
					<option value="{$statusId}"{if ($editRule && $editRule.trigger_status == $statusId) || (!$editRule && $statusId == 0)} selected{/if}>{$statusLabel|escape}</option>
					{/foreach}
				</select>
				<div class="form-text">Gecikme süresi, seçilen duruma geçildiği andan itibaren sayılır.</div>
			</div>

			<div class="row g-2 mb-3">
				<div class="col-6">
					<label class="form-label">Gecikme (Y)</label>
					<input type="number" name="delay_amount" class="form-control form-control-sm" min="1" value="{$editRule.delay_amount|default:7}">
				</div>
				<div class="col-6">
					<label class="form-label">&nbsp;</label>
					<select name="delay_unit" class="form-select form-select-sm">
						<option value="days"{if !$editRule || $editRule.delay_unit == 'days'} selected{/if}>Gün</option>
						<option value="hours"{if $editRule && $editRule.delay_unit == 'hours'} selected{/if}>Saat</option>
						<option value="minutes"{if $editRule && $editRule.delay_unit == 'minutes'} selected{/if}>Dakika (test)</option>
					</select>
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label">E-posta konusu</label>
				<input type="text" name="email_subject" class="form-control form-control-sm" required value="{$editRule.email_subject|default:''|escape}" placeholder="{ldelim}product_name{rdelim} için size özel">
			</div>

			<div class="mb-3">
				<label class="form-label" for="smartCampaignEmailBody">E-posta içeriği</label>
				<textarea
					id="smartCampaignEmailBody"
					name="email_body"
					class="form-control wysiwyg-editor"
					rows="14"
				>{if $editRule && $editRule.email_body != ''}{$editRule.email_body|escape}{else}{$defaultBody|escape}{/if}</textarea>
				<div class="form-text">Metin editöründen biçimlendirin. Buton linki için {ldelim}track_url{rdelim} değişkenini kullanın.</div>
			</div>

			<div class="mb-3 ai-sc-placeholder">
				<label class="form-label">Kişiselleştirme değişkenleri</label>
				<div>{foreach $placeholders as $ph}<code>{$ph|escape}</code>{/foreach}</div>
			</div>

			<div class="mb-3">
				<label class="form-label">Hedef URL (geri dönüş adresi)</label>
				<input type="url" name="target_url" class="form-control form-control-sm" required value="{$editRule.target_url|default:''|escape}" placeholder="https://site.com/urun/...">
				<div class="form-text">Sonuna otomatik <code>?sc=KOD</code> eklenir.</div>
			</div>

			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="active" value="1" id="scActive"{if !$editRule || $editRule.active} checked{/if}>
				<label class="form-check-label" for="scActive">Kural aktif</label>
			</div>

			<div class="d-flex flex-wrap gap-2">
				<button type="submit" class="btn btn-dark btn-sm">Kaydet</button>
				{if $editRule}
				<a href="{$adminUrl}module-smart-campaign?tab=rules" class="btn btn-outline-secondary btn-sm">Yeni kural</a>
				{/if}
			</div>
		</form>
	</div>

	<div class="col-lg-7">
		<div class="alert alert-info mb-3">
			<h3 class="h6 alert-heading mb-2">Akıllı Kampanya nedir?</h3>
			<p class="small mb-2">Belirli bir ürünü satın alan müşterilere, sipariş oluşturulduğunda veya sipariş belirli bir duruma geçtiğinde (kargoya verildi, teslim edildi vb.) belirlediğiniz süre sonra otomatik e-posta gönderir.</p>
			<ol class="small mb-2 ps-3">
				<li><strong>Ürün seçin</strong> — hangi ürün alındığında tetiklensin</li>
				<li><strong>Durum seçin</strong> — sipariş anında mı, kargoya verilince mi, teslim edilince mi</li>
				<li><strong>Gecikme belirleyin</strong> — örn. teslimden 7 gün sonra</li>
				<li><strong>E-postayı tasarlayın</strong> — editörden metin ve buton ekleyin</li>
				<li><strong>Cron çalışsın</strong> — İstatistik sekmesindeki URL ile zamanlanmış görev kurun</li>
			</ol>
			<p class="small mb-0">E-postadaki butona <code>{ldelim}track_url{rdelim}</code> linki verin; tıklamalar ve hedef sayfaya dönüşler <strong>Gönderim kuyruğu</strong> sekmesinden izlenir. Hedef URL'nin sonuna otomatik <code>?sc=KOD</code> eklenir.</p>
		</div>

		<div class="admin-panel p-0">
			<div class="table-responsive">
				<table class="table table-sm mb-0 ai-sc-table">
					<thead>
						<tr>
							<th>Kural</th>
							<th>Ürün</th>
							<th>Tetikleyici</th>
							<th>Gecikme</th>
							<th>Gönderim / Tıklama</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{foreach $rules as $rule}
						<tr>
							<td>
								<strong>{$rule.name|escape}</strong>
								{if !$rule.active}<span class="badge text-bg-light text-dark ms-1">Pasif</span>{/if}
							</td>
							<td>{$rule.product_name|default:'—'|escape}</td>
							<td class="small">{$rule.trigger_status_label|default:'Sipariş oluşturulduğunda'|escape}</td>
							<td>{$rule.delay_amount} {if $rule.delay_unit == 'hours'}saat{elseif $rule.delay_unit == 'minutes'}dk{else}gün{/if}</td>
							<td>
								<div>{$rule.sent_total|default:0} / {$rule.queue_total|default:0} gönderim</div>
								<div class="text-muted">{$rule.click_total|default:0} tıklama</div>
							</td>
							<td class="text-end text-nowrap">
								<a href="{$adminUrl}module-smart-campaign?edit={$rule.id_rule}" class="btn btn-sm btn-outline-dark">Düzenle</a>
								<form method="post" class="d-inline">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="hidden" name="id_rule" value="{$rule.id_rule}">
									<button type="submit" name="toggleSmartCampaignRule" value="1" class="btn btn-sm btn-outline-secondary">{if $rule.active}Durdur{else}Aktif et{/if}</button>
								</form>
								<form method="post" class="d-inline" data-confirm-message="Kural silinsin mi?">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="hidden" name="id_rule" value="{$rule.id_rule}">
									<button type="submit" name="deleteSmartCampaignRule" value="1" class="btn btn-sm btn-outline-danger">Sil</button>
								</form>
							</td>
						</tr>
						{foreachelse}
						<tr><td colspan="6" class="text-muted p-4">Henüz kural yok. Soldan ilk kuralınızı ekleyin.</td></tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
{/if}
