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
<p class="mb-3"><a href="{$adminUrl}modules">{'← Back to module list'|adminT}</a></p>

<div class="row g-4">
	<div class="col-lg-8">
		<div class="admin-panel mb-4">
			<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
				<div>
					<h2 class="h5 mb-1">{$mod.title|escape}</h2>
					<p class="text-muted small mb-0">{$mod.name|escape} · v{$mod.version|escape}{if $mod.author} · {$mod.author|escape}{/if}</p>
				</div>
				{if $mod.installed && $mod.active}
				<span class="badge bg-success">{'Active'|adminT}</span>
				{elseif $mod.installed}
				<span class="badge bg-secondary">{'Inactive'|adminT}</span>
				{else}
				<span class="badge bg-light text-dark">{'Not installed'|adminT}</span>
				{/if}
			</div>
			<p class="mb-0">{$mod.description|escape}</p>
		</div>

		{if $mod.display_hooks|@count}
		<div class="admin-panel mb-4">
			<h3 class="h6 mb-3">{'Display hook assignment'|adminT}</h3>
			<p class="text-muted small mb-3">
				{'Choose where the module appears on the site. On the storefront'|adminT}
				<code>{ldelim}$hooks.footer{rdelim}</code>, {'in the admin panel'|adminT}
				<code>{ldelim}$adminHooks.admin_product_button{rdelim}</code>{' hook points are used.'|adminT}
			</p>
			{if $mod.installed}
			<form method="post" class="mb-0">
				<input type="hidden" name="moduleAction" value="1">
				<input type="hidden" name="token" value="{$adminToken}">
				<div class="d-flex flex-column gap-2 mb-3">
					{foreach $displayHookCatalog as $hookKey => $hookTpl}
					<label class="d-flex align-items-start gap-2 border rounded p-3 mb-0{if !isset($mod.display_hooks[$hookKey])} opacity-50{/if}">
						<input type="checkbox" name="displayHooks[]" value="{$hookKey|escape}"
							{if isset($mod.assigned_hooks_map[$hookKey])}checked{/if}
							{if !isset($mod.display_hooks[$hookKey])}disabled{/if}
							class="mt-1">
						<span>
							<strong><code>{$hookKey|escape}</code></strong>
							<span class="d-block small text-muted">{$hookTpl|escape}</span>
							{if isset($mod.display_hooks[$hookKey])}
							<span class="d-block small">{$mod.display_hooks[$hookKey]|escape}</span>
							{else}
							<span class="d-block small text-muted">{'This module does not support this hook'|adminT}</span>
							{/if}
						</span>
					</label>
					{/foreach}
				</div>
				<button type="submit" name="action" value="save_hooks" class="btn btn-sm btn-dark">{'Save hook assignments'|adminT}</button>
			</form>
			{else}
			<ul class="list-unstyled mb-0">
				{foreach $mod.display_hooks as $hookKey => $hookLabel}
				<li class="mb-2">
					<code>{$hookKey|escape}</code>
					<span class="text-muted">— {$hookLabel|escape}</span>
				</li>
				{/foreach}
			</ul>
			<p class="text-muted small mt-2 mb-0">{'Default hooks are assigned on install; you can change them here after installation.'|adminT}</p>
			{/if}
		</div>
		{/if}

		{if $mod.hooks_meta|@count}
		<div class="admin-panel mb-4">
			<h3 class="h6 mb-3">{'Registered hooks'|adminT}</h3>
			<p class="text-muted small">{'Hooks are registered in the module <code>boot()</code> method and affect storefront/admin flow.'|adminT}</p>
			<ul class="list-unstyled mb-0">
				{foreach $mod.hooks_meta as $hookName => $hookDesc}
				<li class="mb-2">
					<strong><code>{$hookName|escape}</code></strong>
					{if $hookCatalog[$hookName]}<span class="text-muted small d-block">{$hookCatalog[$hookName]|escape}</span>{/if}
					<span class="d-block small">{$hookDesc|escape}</span>
				</li>
				{/foreach}
			</ul>
		</div>
		{/if}

		{if $mod.installed}
		<div class="admin-panel">
			<h3 class="h6 mb-3">{'Configuration'|adminT}</h3>
			<p class="text-muted small mb-3">
				{'Module settings are managed via <code>adminPage()</code> and <code>assets/templates/admin/admin.tpl</code> in the module folder.'|adminT}
			</p>
			<a href="{$mod.configure_url}" class="btn btn-sm btn-dark">{$mod.title|escape}{' — Configure'|adminT}</a>
		</div>
		{/if}
	</div>

	<div class="col-lg-4">
		{if $mod.installed}
		<div class="admin-panel mb-4">
			{if $mod.logo_url}
			<img src="{$mod.logo_url|escape}" alt="" width="48" height="48" class="rounded mb-3">
			{/if}
			<a href="{$mod.configure_url}" class="btn btn-primary w-100">{'Configure'|adminT}</a>
			<p class="small text-muted mt-2 mb-0"><code>/admin/module-{$mod.name|escape}</code></p>
		</div>
		{/if}

		<div class="admin-panel mb-4">
			<h3 class="h6 mb-3">{'Status and actions'|adminT}</h3>
			<form method="post" class="d-flex flex-column gap-2">
				<input type="hidden" name="moduleAction" value="1">
				<input type="hidden" name="token" value="{$adminToken}">
				{if !$mod.installed}
				<button type="submit" name="action" value="install" class="btn btn-dark">{'Install and enable'|adminT}</button>
				{else}
					{if !$mod.active}
					<button type="submit" name="action" value="enable" class="btn btn-dark">{'Enable'|adminT}</button>
					{else}
					<button type="submit" name="action" value="disable" class="btn btn-outline-secondary">{'Disable'|adminT}</button>
					{/if}
					<button type="submit" name="action" value="uninstall" class="btn btn-outline-danger" onclick="return confirm('{'Uninstall this module? Data may be deleted.'|adminT}');">{'Uninstall'|adminT}</button>
				{/if}
			</form>
		</div>

		{if $mod.api_actions|@count}
		<div class="admin-panel">
			<h3 class="h6 mb-3">{'API endpoints'|adminT}</h3>
			<ul class="list-unstyled small mb-0">
				{foreach $mod.api_actions as $api}
				<li class="mb-2">
					<code>{$api.action|escape}</code>
					<div class="text-muted text-break">{$api.endpoint|escape}</div>
				</li>
				{/foreach}
			</ul>
		</div>
		{/if}
	</div>
</div>
