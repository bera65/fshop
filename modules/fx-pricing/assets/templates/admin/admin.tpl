<div class="admin-panel">
	<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
		<div>
			<h1 class="h3 mb-1">Canlı Kur & Döviz Fiyat</h1>
			<p class="text-muted mb-0">Mağaza ziyaretçileri para birimini değiştirdiğinde fiyatlar canlı kurla gösterilir. Döviz fiyatlı ürünlerin TL karşılığı cron ile güncellenir.</p>
		</div>
	</div>

	{if $flash}
	<div class="alert alert-{$flashType|default:'info'|escape}">{$flash|escape}</div>
	{/if}

	<div class="row g-4">
		<div class="col-lg-7">
			<form method="post" class="admin-panel p-4">
				<input type="hidden" name="saveFxPricingSettings" value="1">
				<input type="hidden" name="token" value="{$adminToken|escape}">

				<div class="form-check form-switch mb-3">
					<input class="form-check-input" type="checkbox" name="enabled" id="fxEnabled" value="1"{if $fxSettings.enabled} checked{/if}>
					<label class="form-check-label" for="fxEnabled">Modül aktif</label>
				</div>

				<div class="mb-3">
					<label class="form-label" for="fxAdminCurrencies">Admin ürün formunda kullanılacak dövizler</label>
					<input type="text" class="form-control" id="fxAdminCurrencies" name="admin_currencies" value="{$fxSettings.admin_currencies|escape}" placeholder="usd,eur">
					<div class="form-text">Virgülle ayırın. Mağaza birimi ({$shopCurrency|escape}) hariç tutulur.</div>
				</div>

				<button type="submit" class="btn btn-primary">Kaydet</button>
			</form>
		</div>

		<div class="col-lg-5">
			<div class="admin-panel p-4 mb-4">
				<h2 class="h6">Güncel kurlar (1 birim → {$shopCurrency|upper|escape})</h2>
				<p class="text-muted small">Kurlar BigPara (Hürriyet) API üzerinden otomatik çekilir; elle girilmez. Tablo <strong>1 USD / 1 EUR</strong> kaç TL eder gösterir (alış kuru).</p>
				{if $fxRates|@count == 0}
				<p class="text-muted small mb-0">Kur henüz çekilmedi. Aşağıdaki butonla yenileyin.</p>
				{else}
				<ul class="list-unstyled small mb-0">
					{foreach $fxRates as $code => $rate}
					<li class="d-flex justify-content-between py-1 border-bottom"><span>{if $currencyList}{foreach $currencyList as $c}{if $c.code == $code}{$c.label|escape}{/if}{/foreach}{else}{$code|upper|escape}{/if}</span><strong>{$rate|string_format:'%.4f'}</strong></li>
					{/foreach}
				</ul>
				{/if}
			</div>

			<div class="admin-panel p-4">
				<p class="small text-muted mb-2">Döviz fiyatlı ürün sayısı: <strong>{$fxProductCount|escape}</strong></p>
				<form method="post" class="mb-3">
					<input type="hidden" name="refreshFxRates" value="1">
					<input type="hidden" name="token" value="{$adminToken|escape}">
					<button type="submit" class="btn btn-outline-primary btn-sm">Kurları yenile & ürün fiyatlarını güncelle</button>
				</form>
				<label class="form-label small">Cron URL — genel döviz (api/cron)</label>
				<input type="text" class="form-control form-control-sm mb-2" readonly value="{$cronUrl|escape}" onclick="this.select();">
				<label class="form-label small">Cron URL — fx-pricing refresh (SHOP_TOKEN zorunlu)</label>
				<input type="text" class="form-control form-control-sm" readonly value="{$fxRefreshCronUrl|escape}" onclick="this.select();">
				<div class="form-text">Token olmadan çağrı 403 döner. Tercihen <code>X-Cron-Token</code> header kullanın.</div>
			</div>
		</div>
	</div>
</div>
