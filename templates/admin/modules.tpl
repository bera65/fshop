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

{if $demoMode}
<div class="alert alert-warning mb-4" role="alert">
	<strong>{'Demo mode'|adminT}</strong>
	{'Demo mode: module upload is not allowed'|adminT}
</div>
{/if}

<div class="row g-3 mb-4">
	<div class="col-6 col-md-3">
		<div class="admin-panel p-3 h-100">
			<div class="small text-muted">{'Total modules'|adminT}</div>
			<div class="h4 mb-0">{$moduleStats.total}</div>
		</div>
	</div>
	<div class="col-6 col-md-3">
		<div class="admin-panel p-3 h-100">
			<div class="small text-muted">{'Installed modules'|adminT}</div>
			<div class="h4 mb-0 text-primary">{$moduleStats.installed}</div>
		</div>
	</div>
	<div class="col-6 col-md-3">
		<div class="admin-panel p-3 h-100">
			<div class="small text-muted">{'Active modules'|adminT}</div>
			<div class="h4 mb-0 text-success">{$moduleStats.active}</div>
		</div>
	</div>
	<div class="col-6 col-md-3">
		<div class="admin-panel p-3 h-100">
			<div class="small text-muted">{'Inactive / not installed'|adminT}</div>
			<div class="h4 mb-0">
				<span class="text-secondary">{$moduleStats.inactive}</span>
				<span class="text-muted small"> / </span>
				<span class="text-muted">{$moduleStats.not_installed}</span>
			</div>
		</div>
	</div>
</div>

<div class="admin-panel module-toolbar mb-3">
	<div class="row g-2 align-items-center">
		<div class="col-md-7">
			<div class="input-group">
				<input type="search" class="form-control" id="moduleSearch" placeholder="{'Search modules…'|adminT}" autocomplete="off">
				<button type="button" class="btn btn-primary" tabindex="-1" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
				</button>
			</div>
		</div>
		<div class="col-md-3">
			<select class="form-select" id="moduleStatusFilter">
				<option value="all">{'Show all modules'|adminT}</option>
				<option value="installed">{'Installed modules'|adminT}</option>
				<option value="active">{'Active modules'|adminT}</option>
				<option value="not_installed">{'Not installed'|adminT}</option>
			</select>
		</div>
		<div class="col-md-2 d-grid">
			{if $demoMode}
			<button type="button" class="btn btn-dark" disabled title="{'Demo mode: module upload is not allowed'|adminT}">{'Add new module'|adminT}</button>
			{else}
			<button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#uploadModuleModal">
				{'Add new module'|adminT}
			</button>
			{/if}
		</div>
	</div>
</div>

<h2 class="h6 text-muted mb-2">{'Management'|adminT}</h2>

<div class="admin-panel p-0" id="moduleList">
{if $modules|@count}
	{foreach $modules as $mod}
	<div class="module-row d-flex flex-wrap align-items-center gap-3"
		data-module-name="{$mod.name|escape}"
		data-module-search="{$mod.title|escape} {$mod.name|escape} {$mod.description|escape}"
		data-module-status="{if $mod.installed && $mod.active}active{elseif $mod.installed}installed{else}not_installed{/if}">
		<div class="module-row-icon flex-shrink-0">
			{if $mod.icon_url}
			<img src="{$mod.icon_url|escape}" alt="" width="48" height="48">
			{else}
			<span class="module-row-letter">{$mod.icon_letter|escape}</span>
			{/if}
		</div>
		<div class="module-row-body flex-grow-1 min-w-0">
			<div class="module-row-title">{$mod.title|escape}</div>
			<div class="module-row-meta">
				v{$mod.version|escape} — {$mod.author|escape}{' by'|adminT}
				{if $mod.installed && $mod.active}
				· <span class="text-success">{'Active'|adminT}</span>
				{elseif $mod.installed}
				· <span class="text-secondary">{'Inactive'|adminT}</span>
				{/if}
				{if $mod.assigned_hooks|@count}
				· Hook: {foreach $mod.assigned_hooks as $hk name=hkLoop}<code>{$hk|escape}</code>{if !$smarty.foreach.hkLoop.last}, {/if}{/foreach}
				{/if}
			</div>
			<div class="module-row-desc text-muted">{$mod.description|escape}</div>
		</div>
		<div class="module-row-actions d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
			{if $mod.installed}
			<div class="btn-group">
				<a href="{$mod.configure_url}" class="btn btn-outline-primary module-btn-configure">{'Configure'|adminT}</a>
				<button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
					<span class="visually-hidden">{'More actions'|adminT}</span>
				</button>
				<ul class="dropdown-menu dropdown-menu-end">
					<li><a class="dropdown-item" href="{$mod.detail_url}">{'Module details'|adminT}</a></li>
					{if $mod.active}
					<li>
						<form method="post" class="px-3 py-1">
							<input type="hidden" name="moduleAction" value="1">
							<input type="hidden" name="token" value="{$adminToken}">
							<input type="hidden" name="name" value="{$mod.name|escape}">
							<button type="submit" name="action" value="disable" class="dropdown-item px-0 js-admin-busy">{'Disable'|adminT}</button>
						</form>
					</li>
					{else}
					<li>
						<form method="post" class="px-3 py-1">
							<input type="hidden" name="moduleAction" value="1">
							<input type="hidden" name="token" value="{$adminToken}">
							<input type="hidden" name="name" value="{$mod.name|escape}">
							<button type="submit" name="action" value="enable" class="dropdown-item px-0 js-admin-busy">{'Enable'|adminT}</button>
						</form>
					</li>
					{/if}
					<li><hr class="dropdown-divider"></li>
					<li>
						<form method="post" class="px-3 py-1" data-confirm-title="{'Uninstall'|adminT}" data-confirm-message="{'Uninstall this module?'|adminT}">
							<input type="hidden" name="moduleAction" value="1">
							<input type="hidden" name="token" value="{$adminToken}">
							<input type="hidden" name="name" value="{$mod.name|escape}">
							<button type="submit" name="action" value="uninstall" class="dropdown-item text-danger px-0">{'Uninstall'|adminT}</button>
						</form>
					</li>
				</ul>
			</div>
			{else}
			<form method="post" class="d-inline">
				<input type="hidden" name="moduleAction" value="1">
				<input type="hidden" name="token" value="{$adminToken}">
				<input type="hidden" name="name" value="{$mod.name|escape}">
				<button type="submit" name="action" value="install" class="btn btn-outline-primary module-btn-configure js-admin-busy">{'Install'|adminT}</button>
			</form>
			{/if}
		</div>
	</div>
	{/foreach}
{else}
	<div class="p-4 text-muted">{'No modules found yet.'|adminT} {'Add modules to the'|adminT} <code>modules/</code> {'folder.'|adminT}</div>
{/if}
	<div class="p-4 text-muted d-none" id="moduleListEmpty">{'No modules match your search or filter.'|adminT}</div>
</div>

{if !$demoMode}
<div class="modal fade" id="uploadModuleModal" tabindex="-1" aria-labelledby="uploadModuleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg">
		<div class="modal-content border-0 shadow-lg">
			<div class="modal-header border-0 pb-0">
				<h5 class="modal-title fw-bold" id="uploadModuleModalLabel">{'Upload module (ZIP)'|adminT}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|adminT}"></button>
			</div>
			<div class="modal-body p-4 pt-3">
				<div class="alert alert-info border-0 small mb-4">
					<strong>{'Security notice'|adminT}</strong>
					<ul class="mb-0 ps-3 mt-2">
						<li>{'The ZIP must contain one folder with the module main file inside, e.g. my-module/my-module.php'|adminT}</li>
						<li>{'Only install modules from sources you trust.'|adminT}</li>
						<li>{'Unknown modules may pose a security risk to your store.'|adminT}</li>
					</ul>
				</div>

				<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 p-3 rounded-3 module-upload-store">
					<div>
						<div class="fw-semibold">{'FriSay module store'|adminT}</div>
						<div class="small text-muted">{'Browse verified modules and download as ZIP.'|adminT}</div>
					</div>
					<a href="{$frisayModulesUrl|escape}" class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer">
						{'Browse FriSay modules'|adminT}
					</a>
				</div>

				<form method="post" enctype="multipart/form-data" id="uploadModuleForm">
					<input type="hidden" name="uploadModuleZip" value="1">
					<input type="hidden" name="token" value="{$adminToken}">

					<div class="mb-3">
						<label class="form-label fw-semibold" for="moduleZipInput">{'Module ZIP file'|adminT}</label>
						<input type="file" class="form-control" id="moduleZipInput" name="module_zip" accept=".zip,application/zip" required>
						<div class="form-text">{'Expected structure:'|adminT} <code>modul-adi/modul-adi.php</code></div>
					</div>

					<div class="d-flex flex-wrap gap-2 justify-content-end">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
						<button type="submit" class="btn btn-dark" id="uploadModuleSubmit">{'Upload and extract'|adminT}</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
{/if}
