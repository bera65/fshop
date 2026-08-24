{if $flash}
<div class="alert alert-{$flashType|default:'success'|escape} mb-3">{$flash|escape}</div>
{/if}

{if $updateRemoteAvailable}
<div class="alert alert-warning mb-3">
	<strong>{'Remote update available'|adminT}:</strong>
	{$updateOffer.version|escape}
	— {'Go to System update to install.'|adminT|default:'Kurmak için aşağıdaki butonu kullanın.'}
</div>
{/if}

{if $upgradeLogs|@count}
<div class="admin-panel mb-3">
	<h2 class="h6 mb-2">{'Update log'|adminT}</h2>
	<pre class="small mb-0 bg-light border rounded p-3" style="max-height:240px;overflow:auto;">{foreach $upgradeLogs as $line}{$line|escape}
{/foreach}</pre>
</div>
{/if}

<div class="row g-3">
	<div class="col-lg-5">
		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Version status'|adminT}</h2>
			<dl class="row mb-0 small">
				<dt class="col-5 text-muted">{'Installed schema'|adminT}</dt>
				<dd class="col-7"><code>{$upgradeInstalled|escape}</code> <span class="text-muted">(FS_VERSION)</span></dd>
				<dt class="col-5 text-muted">{'Code version'|adminT}</dt>
				<dd class="col-7"><code>{$upgradeCode|escape}</code> <span class="text-muted">(FShop::VERSION)</span></dd>
				<dt class="col-5 text-muted">{'Status'|adminT}</dt>
				<dd class="col-7">
					{if $upgradeUpToDate && !$updateRemoteAvailable}
					<span class="badge text-bg-success">{'Up to date'|adminT}</span>
					{elseif $updateRemoteAvailable}
					<span class="badge text-bg-warning">{'Remote update available'|adminT}</span>
					{else}
					<span class="badge text-bg-warning">{'Update pending'|adminT}</span>
					{/if}
				</dd>
				{if $updateOffer}
				<dt class="col-5 text-muted">{'Latest remote'|adminT}</dt>
				<dd class="col-7">
					<code>{$updateOffer.version|escape}</code>
					{if $updateOffer.title}<span class="text-muted"> — {$updateOffer.title|escape}</span>{/if}
				</dd>
				{/if}
				{if $updateLastCheck}
				<dt class="col-5 text-muted">{'Last checked'|adminT}</dt>
				<dd class="col-7"><span class="text-muted">{$updateLastCheck|escape}</span></dd>
				{/if}
			</dl>

			{if !$updateCanZip || !$updateCanHttp}
			<div class="alert alert-danger small mt-3 mb-0">
				{if !$updateCanZip}{'PHP ZipArchive extension is required for one-click updates.'|adminT}<br>{/if}
				{if !$updateCanHttp}{'cURL or allow_url_fopen is required to download updates.'|adminT}{/if}
			</div>
			{/if}

			<div class="alert alert-warning small mt-3 mb-3">
				{'One-click update downloads a package over HTTPS, replaces application files (env.php and img/ are kept), then runs database migrations. Take a backup first.'|adminT}
			</div>

			<div class="d-flex flex-wrap gap-2 align-items-center mb-2">
				<form method="post" class="d-inline">
					<input type="hidden" name="token" value="{$adminToken}">
					<button type="submit" name="checkUpdate" value="1" class="btn btn-outline-dark btn-sm"{if !$updateCanHttp} disabled{/if}>
						{'Check for updates'|adminT}
					</button>
				</form>

				{if $updateRemoteAvailable && $updateCanZip && $updateCanHttp}
				<form method="post" class="d-inline">
					<input type="hidden" name="token" value="{$adminToken}">
					<button type="submit" name="runFullUpdate" value="1" class="btn btn-dark btn-sm js-admin-confirm js-admin-busy"
						data-confirm-title="{'Update now'|adminT}"
						data-confirm-message="{'Download and install the update now? The storefront will briefly enter maintenance mode.'|adminT}">
						{'Update now'|adminT} ({$updateOffer.version|escape})
					</button>
				</form>
				{/if}
			</div>

			{if $updateOffer && $updateOffer.changelog_url}
			<p class="small mb-0">
				<a href="{$updateOffer.changelog_url|escape}" target="_blank" rel="noopener">{'Release notes'|adminT}</a>
			</p>
			{/if}
		</div>

		<div class="admin-panel mt-3">
			<h2 class="h6 mb-3">{'Database only'|adminT}</h2>
			<p class="small text-muted">{'If you already uploaded files via FTP/git, apply pending migrations only.'|adminT}</p>
			{if !$upgradeUpToDate}
			<form method="post" class="d-flex flex-wrap gap-2 align-items-center">
				<input type="hidden" name="token" value="{$adminToken}">
				<button type="submit" name="runUpgrade" value="1" class="btn btn-outline-dark btn-sm js-admin-confirm js-admin-busy"
					data-confirm-title="{'Database only'|adminT}"
					data-confirm-message="{'Run database update now?'|adminT}">
					{'Apply database update'|adminT}
				</button>
				<span class="small text-muted">{$upgradePending|@count} {'step(s)'|adminT}</span>
			</form>
			{else}
			<p class="small text-muted mb-0">{'No pending migrations.'|adminT}</p>
			{/if}

			{if $upgradePending|@count}
			<ul class="list-group list-group-flush small mt-3">
				{foreach $upgradePending as $step}
				<li class="list-group-item px-0 d-flex justify-content-between gap-2">
					<span>{$step.title|escape}</span>
					<code>{$step.version|escape}</code>
				</li>
				{/foreach}
			</ul>
			{/if}
		</div>
	</div>

	<div class="col-lg-7">
		<div class="admin-panel">
			<h2 class="h6 mb-3">{'Changelog'|adminT}{if !$upgradeUpToDate || $updateRemoteAvailable} <span class="text-muted fw-normal">({$upgradeInstalled|escape} → {if $updateRemoteAvailable}{$updateOffer.version|escape}{else}{$upgradeCode|escape}{/if})</span>{/if}</h2>
			{if $upgradeChangelog|@count}
				{foreach $upgradeChangelog as $block}
				<div class="mb-4 pb-3 border-bottom">
					<h3 class="h6 mb-1">{$block.version|escape}{if $block.date} <span class="text-muted fw-normal">— {$block.date|escape}</span>{/if}</h3>
					<pre class="small mb-0" style="white-space:pre-wrap;font-family:inherit;">{$block.body|escape}</pre>
				</div>
				{/foreach}
			{elseif $upgradeUpToDate && !$updateRemoteAvailable}
			<p class="small text-muted mb-0">{'You are on the latest code version. See CHANGELOG.md for history.'|adminT}</p>
			{else}
			<p class="small text-muted mb-0">{'No changelog entries found for this range.'|adminT}</p>
			{/if}
		</div>
		<p class="small text-muted mt-2 mb-0">
			{'New modules are not auto-installed. After a core update, install new modules from Modules if needed.'|adminT}
			· docs/UPGRADE.md
		</p>
	</div>
</div>
