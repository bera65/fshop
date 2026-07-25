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

<div class="admin-panel p-3 mb-3">
	<div class="d-flex flex-wrap gap-2 align-items-end justify-content-between mb-3">
		<div>
			<h2 class="h6 mb-1">{'UI Translations'|adminT}</h2>
			<p class="text-muted small mb-0">
				{if $uiScope == 'admin'}
				{'Admin panel strings use adminT in templates and are stored in lang/admin/.'|adminT}
				{else}
				{'Storefront strings use translate in templates and are stored in lang/.'|adminT}
				{/if}
			</p>
		</div>
		<div class="small text-muted">
			{$uiTotalKeys} {'keys'|adminT}
			· <span class="{if $uiMissingCount > 0}text-warning{else}text-success{/if}">{$uiMissingCount} {'missing'|adminT}</span>
			{if $uiTotalPages > 1}
			· {'Page'|adminT} {$uiPage}/{$uiTotalPages}
			{/if}
		</div>
	</div>

	<ul class="nav nav-tabs mb-0">
		<li class="nav-item">
			<a class="nav-link{if $uiScope == 'storefront'} active{/if}" href="{$adminUrl}translations?scope=storefront&amp;lang={$uiTargetLang|escape:'url'}">{'Storefront'|adminT}</a>
		</li>
		<li class="nav-item">
			<a class="nav-link{if $uiScope == 'admin'} active{/if}" href="{$adminUrl}translations?scope=admin&amp;lang={$uiTargetLang|escape:'url'}">{'Admin panel'|adminT}</a>
		</li>
	</ul>
</div>

<div class="admin-panel p-3 mb-3">
	<h3 class="h6 mb-2">{'Add new key'|adminT}</h3>
	<p class="text-muted small mb-3">
		{if $uiScope == 'admin'}
		{'Use the exact text from your template adminT call. Saved to lang/admin/en.php and the target language file.'|adminT}
		{else}
		{'Use the exact text from your template translate call. Saved to lang/en.php and the target language file.'|adminT}
		{/if}
	</p>
	<form method="post" class="row g-2 align-items-end">
		<input type="hidden" name="token" value="{$adminToken}">
		<input type="hidden" name="addUiTranslationKey" value="1">
		<input type="hidden" name="scope" value="{$uiScope|escape}">
		<input type="hidden" name="lang" value="{$uiTargetLang|escape}">
		<div class="col-lg-4">
			<label class="form-label small" for="uiNewKey">{'Key'|adminT}</label>
			<input type="text" class="form-control form-control-sm" id="uiNewKey" name="new_key" value="" required maxlength="512" placeholder="{'My new message'|adminT}">
		</div>
		<div class="col-lg-3">
			<label class="form-label small" for="uiNewEn">{'English (source)'|adminT}</label>
			<input type="text" class="form-control form-control-sm" id="uiNewEn" name="new_en" value="" maxlength="512" placeholder="{'Optional — defaults to key'|adminT}">
		</div>
		<div class="col-lg-3">
			<label class="form-label small" for="uiNewTr">{'Translation'|adminT} ({$uiTargetLang|escape})</label>
			<input type="text" class="form-control form-control-sm" id="uiNewTr" name="new_translation" value="" maxlength="512" placeholder="{'Optional'|adminT}">
		</div>
		<div class="col-lg-2">
			<button type="submit" class="btn btn-dark btn-sm w-100">{'Add new key'|adminT}</button>
		</div>
	</form>
</div>

<form method="get" action="{$adminUrl}translations" class="admin-panel p-3 mb-3">
	<input type="hidden" name="scope" value="{$uiScope|escape}">
	<div class="row g-2 align-items-end">
		<div class="col-md-3">
			<label class="form-label" for="uiLang">{'Target language'|adminT}</label>
			<select name="lang" id="uiLang" class="form-select form-select-sm" onchange="this.form.submit()">
				{foreach $uiLanguages as $lang}
					{if $lang.code != 'en'}
					<option value="{$lang.code|escape}"{if $lang.code == $uiTargetLang} selected{/if}>{$lang.label|escape} ({$lang.code|escape})</option>
					{/if}
				{/foreach}
			</select>
		</div>
		<div class="col-md-3">
			<label class="form-label" for="uiFilter">{'Filter'|adminT}</label>
			<select name="filter" id="uiFilter" class="form-select form-select-sm" onchange="this.form.submit()">
				<option value="all"{if $uiFilter == 'all'} selected{/if}>{'All keys'|adminT}</option>
				<option value="missing"{if $uiFilter == 'missing'} selected{/if}>{'Missing only'|adminT}</option>
			</select>
		</div>
		<div class="col-md-4">
			<label class="form-label" for="uiQuery">{'Search'|adminT}</label>
			<input type="search" name="q" id="uiQuery" class="form-control form-control-sm" value="{$uiQuery|escape}" placeholder="{'Search key or text…'|adminT}">
		</div>
		<div class="col-md-2">
			<button type="submit" class="btn btn-outline-dark btn-sm w-100">{'Apply'|adminT}</button>
		</div>
	</div>
</form>

<form method="post" id="uiTranslationsForm">
	<input type="hidden" name="token" value="{$adminToken}">
	<input type="hidden" name="saveUiTranslations" value="1">
	<input type="hidden" name="scope" value="{$uiScope|escape}">
	<input type="hidden" name="lang" value="{$uiTargetLang|escape}">
	<input type="hidden" name="filter" value="{$uiFilter|escape}">
	<input type="hidden" name="q" value="{$uiQuery|escape}">
	<input type="hidden" name="page" value="{$uiPage|escape}">

	<div class="admin-panel p-0 overflow-hidden mb-3">
		<div class="table-responsive" style="max-height:70vh;">
			<table class="table table-sm align-middle mb-0" id="uiTranslationsTable">
				<thead class="table-light sticky-top">
					<tr>
						<th style="width:22%">{'Key'|adminT}</th>
						<th style="width:39%">{'English (source)'|adminT}</th>
						<th style="width:39%">{'Translation'|adminT} <span class="text-muted fw-normal">({$uiTargetLang|escape})</span></th>
					</tr>
				</thead>
				<tbody>
					{foreach $uiRows as $row}
					<tr class="ui-tr-row{if $row.missing} table-warning{/if}" data-key="{$row.key|escape}">
						<td class="small">
							<code class="user-select-all">{$row.key|escape}</code>
						</td>
						<td>
							<textarea
								name="en[{$row.key|escape}]"
								class="form-control form-control-sm ui-en-input"
								rows="2"
								data-key="{$row.key|escape}"
							>{$row.en|escape}</textarea>
						</td>
						<td>
							<textarea
								name="tr[{$row.key|escape}]"
								class="form-control form-control-sm ui-tr-input"
								rows="2"
								data-key="{$row.key|escape}"
								data-missing="{if $row.missing}1{else}0{/if}"
							>{$row.translation|escape}</textarea>
						</td>
					</tr>
					{foreachelse}
					<tr>
						<td colspan="3" class="text-muted text-center py-4">{'No matching keys'|adminT}</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>

	<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
		<div class="d-flex flex-wrap gap-2">
			<button type="submit" class="btn btn-primary btn-sm">{'Save this page'|adminT}</button>
			{if $uiScope == 'storefront'}
			<a href="{$adminUrl}languages" class="btn btn-outline-dark btn-sm">{'Languages'|adminT}</a>
			{/if}
		</div>
		{if $uiTotalPages > 1}
		<nav class="d-flex gap-1">
			{if $uiPage > 1}
			<a class="btn btn-outline-dark btn-sm" href="{$adminUrl}translations?scope={$uiScope|escape:'url'}&amp;lang={$uiTargetLang|escape:'url'}&amp;filter={$uiFilter|escape:'url'}&amp;q={$uiQuery|escape:'url'}&amp;page={$uiPage-1}">←</a>
			{/if}
			<span class="btn btn-sm btn-light disabled">{$uiPage} / {$uiTotalPages}</span>
			{if $uiPage < $uiTotalPages}
			<a class="btn btn-outline-dark btn-sm" href="{$adminUrl}translations?scope={$uiScope|escape:'url'}&amp;lang={$uiTargetLang|escape:'url'}&amp;filter={$uiFilter|escape:'url'}&amp;q={$uiQuery|escape:'url'}&amp;page={$uiPage+1}">→</a>
			{/if}
		</nav>
		{/if}
	</div>
</form>

<script>
window.UiTranslationsPage = {
	scope: {$uiScope|@json_encode nofilter},
	targetLang: {$uiTargetLang|@json_encode nofilter},
	filter: {$uiFilter|@json_encode nofilter}
};
</script>
