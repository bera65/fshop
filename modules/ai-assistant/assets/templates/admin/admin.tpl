<link rel="stylesheet" href="{$domain}modules/ai-assistant/assets/css/admin.css?v={$smarty.now}">

<div class="mb-4">
	<h1 class="h4 mb-1">{$moduleTitle|escape}</h1>
	<p class="text-muted small mb-0">API anahtarı ve model ayarları. AI çubuğu yalnızca dashboard, sipariş, iptal, iade, blog, CMS, SEO ve ürün düzenleme sayfalarında görünür.</p>
</div>

{if $flash}
<div class="alert alert-{$flashType|default:'success'}">{$flash|escape}</div>
{/if}

{if $testResult && $testResult.content}
<div class="alert alert-info small">
	<strong>Test yanıtı:</strong> {$testResult.content|escape}
	{if $testResult.model}<span class="text-muted"> · model: {$testResult.model|escape}</span>{/if}
</div>
{/if}

<div class="row g-4">
	<div class="col-lg-7">
		<form method="post" class="admin-panel p-3 ai-settings-card">
			<input type="hidden" name="saveAiAssistant" value="1">
			<input type="hidden" name="token" value="{$adminToken}">

			<div class="d-flex justify-content-between align-items-start gap-2 mb-3">
				<div>
					<h2 class="h6 mb-1">API ayarları</h2>
					<p class="text-muted small mb-0">OpenAI uyumlu sağlayıcılar (OpenAI, Groq, OpenRouter…)</p>
				</div>
				{if $configured}
				<span class="badge text-bg-success">Anahtar tanımlı</span>
				{else}
				<span class="badge text-bg-warning">Anahtar yok</span>
				{/if}
			</div>

			<div class="mb-3">
				<label class="form-label">Sağlayıcı</label>
				<select name="provider" id="aiProvider" class="form-select">
					{foreach $providers as $key => $p}
					<option value="{$key|escape}"{if $provider == $key} selected{/if}
						data-base="{$p.base_url|escape}"
						data-model="{$p.model|escape}">{$p.label|escape}</option>
					{/foreach}
				</select>
			</div>

			<div class="mb-3">
				<label class="form-label">API anahtarı</label>
				<input type="password" name="api_key" class="form-control" value="" autocomplete="new-password"
					placeholder="{if $hasApiKey}******** (değiştirmek için yeni anahtar yazın){else}sk-...{/if}">
			</div>

			<div class="mb-3">
				<label class="form-label">Base URL</label>
				<input type="url" name="base_url" id="aiBaseUrl" class="form-control" value="{$baseUrl|escape}" placeholder="https://api.openai.com/v1">
			</div>

			<div class="row g-3 mb-3">
				<div class="col-md-8">
					<label class="form-label">Model</label>
					<input type="text" name="model" id="aiModel" class="form-control" value="{$model|escape}" placeholder="gpt-4o-mini">
				</div>
				<div class="col-md-4">
					<label class="form-label">Max tokens</label>
					<input type="number" name="max_tokens" class="form-control" value="{$maxTokens}" min="256" max="4000">
				</div>
			</div>

			<div class="row g-3 mb-3">
				<div class="col-md-6">
					<label class="form-label">Metin tonu</label>
					<select name="tone" class="form-select">
						<option value="professional"{if $tone == 'professional'} selected{/if}>Profesyonel</option>
						<option value="friendly"{if $tone == 'friendly'} selected{/if}>Samimi</option>
						<option value="premium"{if $tone == 'premium'} selected{/if}>Premium / lüks</option>
						<option value="short"{if $tone == 'short'} selected{/if}>Kısa ve net</option>
					</select>
				</div>
				<div class="col-md-6">
					<label class="form-label">Dil</label>
					<select name="lang" class="form-select">
						<option value="tr"{if $lang == 'tr'} selected{/if}>Türkçe</option>
						<option value="en"{if $lang == 'en'} selected{/if}>English</option>
					</select>
				</div>
			</div>

			<button type="submit" class="btn btn-dark btn-sm">Kaydet</button>
		</form>

		<form method="post" class="mt-3">
			<input type="hidden" name="testAiAssistant" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<button type="submit" class="btn btn-outline-dark btn-sm"{if !$configured} disabled{/if}>Bağlantıyı test et</button>
		</form>
	</div>

	<div class="col-lg-5">
		<div class="admin-panel p-3 ai-settings-card">
			<h2 class="h6 mb-2">Token / API key nereden alınır?</h2>
			<p class="text-muted small">Test için önce <strong>Groq</strong> önerilir.</p>
			<ul class="list-unstyled ai-guide-list mb-0">
				{foreach $tokenGuides as $g}
				<li class="mb-3">
					<a href="{$g.url|escape}" target="_blank" rel="noopener" class="fw-semibold">{$g.title|escape}</a>
					<div class="small text-muted">{$g.note|escape}</div>
					<div class="small"><code>{$g.url|escape}</code></div>
				</li>
				{/foreach}
			</ul>
		</div>

		<div class="admin-panel p-3 ai-settings-card mt-3">
			<h2 class="h6 mb-2">Nerede çalışır?</h2>
			<ul class="small text-muted mb-0 ps-3">
				<li>Dashboard, siparişler, iptal, iade → sayfa özeti / analiz</li>
				<li>SEO → meta başlık &amp; açıklama</li>
				<li>CMS düzenle → sayfa içeriği</li>
				<li>Blog (yazılar) → fikirden yazı / mevcut yazıyı düzenle</li>
				<li>Ürün düzenle → ürün metinleri</li>
			</ul>
		</div>
	</div>
</div>

{literal}
<script>
(function () {
	var sel = document.getElementById('aiProvider');
	var base = document.getElementById('aiBaseUrl');
	var model = document.getElementById('aiModel');
	if (!sel || !base || !model) return;
	sel.addEventListener('change', function () {
		var opt = sel.options[sel.selectedIndex];
		if (!opt) return;
		var b = opt.getAttribute('data-base') || '';
		var m = opt.getAttribute('data-model') || '';
		if (b) base.value = b;
		if (m) model.value = m;
	});
})();
</script>
{/literal}
