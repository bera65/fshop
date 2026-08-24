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
<div class="row g-4">
	<div class="col-lg-7">
		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Active languages'|adminT}</h2>
			<p class="text-muted small">{'CMS, product and category translation tabs use this language list.'|adminT}</p>
			<div class="table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead>
						<tr>
							<th>{'Code'|adminT}</th>
							<th>{'Display name'|adminT}</th>
							<th>{'File'|adminT}</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{foreach $shopLanguages as $lang}
						<tr>
							<td>
								<code>{$lang.code|escape}</code>
								{if $lang.is_default}<span class="badge bg-primary ms-1">{'Default'|adminT}</span>{/if}
							</td>
							<td>
								<form method="post" class="d-flex gap-2 align-items-center">
									<input type="hidden" name="langAction" value="1">
									<input type="hidden" name="action" value="rename">
									<input type="hidden" name="code" value="{$lang.code|escape}">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="text" name="label" class="form-control form-control-sm" value="{$lang.label|escape}" maxlength="64">
									<button type="submit" class="btn btn-sm btn-outline-dark">{'Save'|adminT}</button>
								</form>
							</td>
							<td>{if $lang.has_file}<span class="text-success">lang/{$lang.code|escape}.php</span>{else}<span class="text-danger">{'Missing'|adminT}</span>{/if}</td>
							<td class="text-end text-nowrap">
								{if !$lang.is_default}
								<form method="post" class="d-inline">
									<input type="hidden" name="langAction" value="1">
									<input type="hidden" name="action" value="default">
									<input type="hidden" name="code" value="{$lang.code|escape}">
									<input type="hidden" name="token" value="{$adminToken}">
									<button type="submit" class="btn btn-sm btn-outline-primary">{'Set as default'|adminT}</button>
								</form>
								<form method="post" class="d-inline" data-confirm-title="{'Delete'|adminT}" data-confirm-message="{'Remove this language? CMS and translation records will be deleted.'|adminT}">
									<input type="hidden" name="langAction" value="1">
									<input type="hidden" name="action" value="remove">
									<input type="hidden" name="code" value="{$lang.code|escape}">
									<input type="hidden" name="token" value="{$adminToken}">
									<button type="submit" class="btn btn-sm btn-outline-danger">{'Delete'|adminT}</button>
								</form>
								{/if}
							</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="col-lg-5">
		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Add language'|adminT}</h2>
			<form method="post">
				<input type="hidden" name="langAction" value="1">
				<input type="hidden" name="action" value="add">
				<input type="hidden" name="token" value="{$adminToken}">
				<div class="mb-3">
					<label class="form-label">{'Language code'|adminT}</label>
					<input type="text" name="code" class="form-control" placeholder="de" required maxlength="5">
					<div class="form-text">{'ISO code: en, tr, de, fr, es …'|adminT}</div>
				</div>
				<div class="mb-3">
					<label class="form-label">{'Display name'|adminT}</label>
					<input type="text" name="label" class="form-control" placeholder="Deutsch" maxlength="64">
				</div>
				<button type="submit" class="btn btn-dark">{'Add language'|adminT}</button>
			</form>
			<p class="text-muted small mt-3 mb-0">
				{'When a new language is added'|adminT} <code>lang/en.php</code> {' file is created; empty translation tabs are added for CMS, products, categories and brands.'|adminT}
				{'Add UI translations to this file.'|adminT}
			</p>
		</div>
	</div>
</div>

<div class="admin-panel mt-4">
	<h2 class="h6 mb-3">{'Admin panel language'|adminT}</h2>
	<p class="text-muted small mb-3">{'Admin interface language. Switch instantly from the header; set the default here.'|adminT}</p>
	<form method="post" class="row g-3 align-items-end">
		<input type="hidden" name="langAction" value="1">
		<input type="hidden" name="action" value="admin_default">
		<input type="hidden" name="token" value="{$adminToken}">
		<div class="col-sm-6 col-md-4">
			<label class="form-label">{'Default admin language'|adminT}</label>
			<select name="code" class="form-select">
				{foreach $adminLangOptions as $code}
				<option value="{$code|escape}"{if $adminDefaultLang == $code} selected{/if}>{if $code == 'tr'}{'Turkish'|adminT}{else}{'English'|adminT}{/if}</option>
				{/foreach}
			</select>
		</div>
		<div class="col-sm-auto">
			<button type="submit" class="btn btn-dark">{'Save'|adminT}</button>
		</div>
	</form>
</div>

<p class="mt-3">
	<a href="{$adminUrl}translations">{'UI Translations'|adminT}</a>
	·
	<a href="{$adminUrl}cms">{'← Back to CMS pages'|adminT}</a>
</p>
