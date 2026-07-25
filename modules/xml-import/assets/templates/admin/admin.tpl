{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">{$flash|escape}</div>
			</div>
			<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}

{if $xmlDemoMode}
<div class="alert alert-warning mb-4" role="alert">
	<strong>{'Demo mode'|adminT}</strong>
	{'Demo mode: some edits are not allowed'|adminT}
</div>
{/if}

<div class="row g-4">
	<div class="col-lg-7">
		<form method="post" enctype="multipart/form-data" class="admin-panel p-3 mb-4"{if $xmlDemoMode} onsubmit="return false;"{/if}>
			<input type="hidden" name="token" value="{$adminToken}">

			<h2 class="h6 mb-3">{'XML product import'|adminT}</h2>
			<p class="text-muted small mb-3">
				{'Upload an XML file or paste a feed URL. Matching products are updated; others are created. Missing categories and brands are added automatically.'|adminT}
			</p>

			<div class="mb-3">
				<label class="form-label">{'Match products by'|adminT}</label>
				<select name="match_key" class="form-select"{if $xmlDemoMode} disabled{/if}>
					<option value="stock_code"{if $xmlMatchKey == 'stock_code'} selected{/if}>{'Stock code (productCode)'|adminT}</option>
					<option value="barcode"{if $xmlMatchKey == 'barcode'} selected{/if}>{'Barcode'|adminT}</option>
					<option value="name"{if $xmlMatchKey == 'name'} selected{/if}>{'Product name'|adminT}</option>
				</select>
				<p class="form-text mb-0">{'If a product with the same value exists it will be updated; otherwise a new product is created.'|adminT}</p>
			</div>

			<div class="mb-3">
				<label class="form-label d-block">{'Import source'|adminT}</label>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="import_source" id="xmlSourceFile" value="file" checked{if $xmlDemoMode} disabled{/if}>
					<label class="form-check-label" for="xmlSourceFile">{'XML file'|adminT}</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="import_source" id="xmlSourceUrl" value="url"{if $xmlDemoMode} disabled{/if}>
					<label class="form-check-label" for="xmlSourceUrl">{'Feed URL'|adminT}</label>
				</div>
			</div>

			<div class="mb-3" id="xmlFileWrap">
				<label class="form-label" for="xmlFile">{'XML file'|adminT}</label>
				<input type="file" name="xml_file" id="xmlFile" class="form-control" accept=".xml,text/xml,application/xml"{if $xmlDemoMode} disabled{/if}>
			</div>

			<div class="mb-3 d-none" id="xmlUrlWrap">
				<label class="form-label" for="xmlFeedUrl">{'XML feed URL'|adminT}</label>
				<input type="url" name="feed_url" id="xmlFeedUrl" class="form-control" value="{$xmlFeedUrl|escape}" placeholder="https://example.com/products.xml"{if $xmlDemoMode} disabled{/if}>
			</div>

			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="update_images" id="xmlUpdateImages" value="1"{if $xmlUpdateImages} checked{/if}{if $xmlDemoMode} disabled{/if}>
				<label class="form-check-label" for="xmlUpdateImages">
					{'Also refresh images when updating existing products'|adminT}
				</label>
			</div>

			<div class="mb-3">
				<label class="form-label" for="xmlImportLimit">{'How many products?'|adminT}</label>
				<select name="import_limit" id="xmlImportLimit" class="form-select"{if $xmlDemoMode} disabled{/if}>
					<option value="0">{'All products'|adminT}</option>
					<option value="5">{'Test: first 5 products'|adminT}</option>
				</select>
			</div>

			<button type="submit" name="runXmlImport" value="1" class="btn btn-primary btn-sm"{if $xmlDemoMode} disabled{/if}>
				{'Start import'|adminT}
			</button>
			<button type="submit" name="saveXmlImportSettings" value="1" class="btn btn-outline-dark btn-sm ms-1"{if $xmlDemoMode} disabled{/if}>
				{'Save settings'|adminT}
			</button>
		</form>

		{if $xmlImportStats}
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">{'Last import summary'|adminT}</h2>
			<div class="row g-2 mb-3">
				<div class="col-6 col-md-3">
					<div class="border rounded-3 p-2 text-center">
						<div class="small text-muted">{'Total'|adminT}</div>
						<strong>{$xmlImportStats.total|default:0}</strong>
					</div>
				</div>
				<div class="col-6 col-md-3">
					<div class="border rounded-3 p-2 text-center">
						<div class="small text-muted">{'Created'|adminT}</div>
						<strong class="text-success">{$xmlImportStats.created|default:0}</strong>
					</div>
				</div>
				<div class="col-6 col-md-3">
					<div class="border rounded-3 p-2 text-center">
						<div class="small text-muted">{'Updated'|adminT}</div>
						<strong class="text-primary">{$xmlImportStats.updated|default:0}</strong>
					</div>
				</div>
				<div class="col-6 col-md-3">
					<div class="border rounded-3 p-2 text-center">
						<div class="small text-muted">{'Errors'|adminT}</div>
						<strong class="text-danger">{$xmlImportStats.errors|default:0}</strong>
					</div>
				</div>
			</div>
			{if $xmlImportStats.error_messages|@count}
			<ul class="small text-danger mb-0 ps-3">
				{foreach $xmlImportStats.error_messages as $err}
				<li>{$err|escape}</li>
				{/foreach}
			</ul>
			{/if}
		</div>
		{/if}
	</div>

	<div class="col-lg-5">
		<div class="admin-panel p-3 mb-4">
			<h2 class="h6 mb-3">{'Cron / automatic refresh'|adminT}</h2>
			<p class="text-muted small mb-2">
				{'Save the XML feed URL and match settings first. Then schedule this URL on your server (e.g. every hour).'|adminT}
			</p>
			{if $xmlFeedUrl == ''}
			<div class="alert alert-warning py-2 small mb-2">
				{'No feed URL saved yet — cron cannot run without it.'|adminT}
			</div>
			{/if}
			<label class="form-label small mb-1">{'Cron URL'|adminT}</label>
			<input type="text" class="form-control form-control-sm font-monospace" readonly value="{$xmlCronUrl|escape}" onclick="this.select()">
			<p class="form-text mb-2">
				<code class="small">curl -s "…"</code>
				{'or wget in crontab. Uses SHOP_TOKEN.'|adminT}
			</p>
			{if $xmlLastCron != ''}
			<p class="small text-muted mb-0">{'Last cron run'|adminT}: <strong>{$xmlLastCron|escape}</strong></p>
			{else}
			<p class="small text-muted mb-0">{'Last cron run'|adminT}: —</p>
			{/if}
		</div>

		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">{'Supported XML fields'|adminT}</h2>
			<ul class="small text-muted mb-0 ps-3">
				<li><code>productCode</code> → {'stock code'|adminT}</li>
				<li><code>barcode</code>, <code>name</code>, <code>brand</code></li>
				<li><code>main_category</code> / <code>top_category</code> / <code>sub_category</code> or <code>category</code> (<code>>>></code>)</li>
				<li><code>price</code>, <code>listPrice</code>, <code>tax</code> (0.1 = %10), <code>quantity</code>, <code>desi</code>, <code>active</code></li>
				<li><code>description</code>, <code>detail</code></li>
				<li><code>image1</code>…<code>image12</code></li>
				<li><code>variants/variant</code> → <code>name1/value1</code>, <code>name2/value2</code>, <code>quantity</code>, <code>barcode</code></li>
			</ul>
		</div>
	</div>
</div>

<script>
(function () {
	var fileRadio = document.getElementById('xmlSourceFile');
	var urlRadio = document.getElementById('xmlSourceUrl');
	var fileWrap = document.getElementById('xmlFileWrap');
	var urlWrap = document.getElementById('xmlUrlWrap');
	if (!fileRadio || !urlRadio || !fileWrap || !urlWrap) return;
	function sync() {
		var useUrl = urlRadio.checked;
		fileWrap.classList.toggle('d-none', useUrl);
		urlWrap.classList.toggle('d-none', !useUrl);
	}
	fileRadio.addEventListener('change', sync);
	urlRadio.addEventListener('change', sync);
	sync();
})();
</script>
