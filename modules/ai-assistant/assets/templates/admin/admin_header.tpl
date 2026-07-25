<div class="ai-assist-header" id="aiAssistHeader"{if !$configured} data-locked="1"{/if}>
	<div class="ai-assist-header__main">
		<span class="ai-assist-header__brand">
			<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v3"/><path d="m16.5 5.5-1.5 2"/><path d="M19 10h-3"/><path d="m16.5 14.5-1.5-2"/><path d="M12 15v3"/><path d="m7.5 14.5 1.5-2"/><path d="M5 10h3"/><path d="m7.5 5.5 1.5 2"/><circle cx="12" cy="10" r="2.5"/></svg>
			Yapay Zeka
		</span>
		{if $configured}
		<span class="badge text-bg-success">Hazır</span>
		{else}
		<a href="{$settingsUrl|escape}" class="badge text-bg-warning text-decoration-none">API ayarla</a>
		{/if}
		<span class="ai-assist-header__hint text-muted d-none d-lg-inline">{$modeHint|escape}</span>
	</div>
	<div class="ai-assist-header__actions">
		{if $mode == 'blog'}
		<input type="text" id="aiBlogIdea" class="form-control form-control-sm ai-assist-header__idea" placeholder="Konu / fikir (ör. yaz kampanyası ipuçları)"{if !$configured} disabled{/if}>
		{/if}
		{if $mode == 'translate'}
		<select id="aiTranslateMode" class="form-select form-select-sm ai-assist-header__idea" style="max-width:220px"{if !$configured} disabled{/if}>
			<option value="translate">Boşları hedef dile çevir</option>
			<option value="polish_en">İngilizce kaynağı iyileştir</option>
		</select>
		{/if}
		<button type="button" class="btn btn-sm btn-dark" id="aiHeaderPrimaryBtn" data-action="{$mode|escape}"{if !$configured} disabled{/if}>{$primaryLabel|escape}</button>
		<a href="{$settingsUrl|escape}" class="btn btn-sm btn-outline-secondary">Ayarlar</a>
	</div>
	<p class="ai-assist-header__status small mb-0" id="aiHeaderStatus"></p>
</div>

<script>
window.AiAssistantHeader = {
	mode: {$mode|@json_encode nofilter},
	pageName: {$pageName|@json_encode nofilter},
	pageTitle: {$pageTitle|@json_encode nofilter},
	configured: {if $configured}true{else}false{/if},
	token: {$adminToken|@json_encode nofilter},
	tone: {$tone|@json_encode nofilter},
	blogEditing: {if $blogEditing}true{else}false{/if},
	settingsUrl: {$settingsUrl|@json_encode nofilter},
	targetLang: {$targetLang|default:''|@json_encode nofilter},
	apiSummary: {$apiSummaryUrl|@json_encode nofilter},
	apiSeo: {$apiSeoUrl|@json_encode nofilter},
	apiCms: {$apiCmsUrl|@json_encode nofilter},
	apiBlog: {$apiBlogUrl|@json_encode nofilter},
	apiProduct: {$apiProductUrl|@json_encode nofilter},
	apiTranslate: {$apiTranslateUrl|@json_encode nofilter}
};
</script>
<link rel="stylesheet" href="{$domain}modules/ai-assistant/assets/css/admin.css?v={$smarty.now}">
<script src="{$domain}modules/ai-assistant/assets/js/modal.js?v={$smarty.now}"></script>
<script src="{$domain}modules/ai-assistant/assets/js/header.js?v={$smarty.now}"></script>
