<div class="admin-panel mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
	<div class="d-flex gap-2">
		<a href="{$adminUrl}cms-edit" class="btn btn-dark btn-sm">{'Add page'|adminT}</a>
		<a href="{$adminUrl}languages" class="btn btn-outline-secondary btn-sm">{'Manage languages'|adminT}</a>
	</div>
</div>

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

<div class="admin-panel">
	<div class="table-responsive">
		<table class="table table-sm align-middle mb-0">
			<thead>
				<tr>
					<th>{'Page'|adminT}</th>
					<th>{'Slug'|adminT}</th>
					<th>{'Status'|adminT}</th>
					<th>{'Footer'|adminT}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{foreach $cmsPages as $page}
				<tr>
					<td>{$page.title|default:$page.slug|escape}</td>
					<td><code>{$page.slug|escape}</code></td>
					<td>{if $page.active}<span class="badge bg-success">{'Active'|adminT}</span>{else}<span class="badge bg-secondary">{'Inactive'|adminT}</span>{/if}</td>
					<td>{if $page.show_footer}{'Yes'|adminT}{else}{'No'|adminT}{/if}</td>
					<td class="text-end text-nowrap">
						<div class="adm-row-actions">
							<a href="{$page.edit_url}" class="adm-icon-btn" title="{'Edit'|adminT}" aria-label="{'Edit'|adminT}">
								<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
							</a>
							<a href="{$domain}{$page.slug|escape}" class="adm-icon-btn" target="_blank" rel="noopener" title="{'View'|adminT}" aria-label="{'View'|adminT}">
								<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
							</a>
							<form method="post" class="d-inline">
								<input type="hidden" name="deleteCms" value="1">
								<input type="hidden" name="id" value="{$page.id_cms}">
								<input type="hidden" name="token" value="{$adminToken}">
								<button type="submit" class="adm-icon-btn adm-icon-btn--danger js-admin-confirm" title="{'Delete'|adminT}" aria-label="{'Delete'|adminT}" data-confirm-title="{'Delete'|adminT}" data-confirm-message="{'Delete this page?'|adminT}">
									<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
								</button>
							</form>
						</div>
					</td>
				</tr>
				{foreachelse}
				<tr><td colspan="5" class="text-muted">{'No CMS pages yet.'|adminT}</td></tr>
				{/foreach}
			</tbody>
		</table>
	</div>
</div>

<p class="text-muted small mt-3 mb-0">{'CMS content is stored in the database. You can enter separate title and content for each language.'|adminT}</p>
