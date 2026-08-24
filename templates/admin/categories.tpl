{if $sonuc}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast dark show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">
					{$sonuc|escape}
				</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}
<div class="admin-toolbar d-flex flex-wrap gap-2 mb-3">
	<a href="{$adminUrl}categories" class="btn btn-sm {if $activeFilter == -1}btn-dark{else}btn-outline-dark{/if}">{'All'|adminT}</a>
	<a href="{$adminUrl}categories?active=1" class="btn btn-sm {if $activeFilter == 1}btn-dark{else}btn-outline-dark{/if}">{'Active'|adminT}</a>
	<a href="{$adminUrl}categories?active=0" class="btn btn-sm {if $activeFilter == 0}btn-dark{else}btn-outline-dark{/if}">{'Inactive'|adminT}</a>
	<a href="{$adminUrl}category" class="btn btn-sm btn-primary ms-auto">{'Add New'|adminT}</a>
</div>

<div class="admin-panel mb-3">
	<p class="small text-muted mb-0">
		{'Homepage category blocks: enable “Show on homepage” on categories you want on the store home page. If none are selected, all menu categories are shown.'|adminT}
	</p>
</div>

<div class="admin-panel">
	<div class="table-responsive">
		<table class="table table-sm align-middle mb-0">
			<thead>
				<tr>
					<th>ID</th>
					<th>{'Name'|adminT}</th>
					<th>URL</th>
					<th>{'Parent category'|adminT}</th>
					<th>{'Homepage'|adminT}</th>
					<th>{'Status'|adminT}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{if $categories|@count}
				{foreach $categories as $row}
				<tr>
					<td>{$row.id_category}</td>
					<td>{$row.category_name|escape}</td>
					<td>{$row.category_link|escape}</td>
					<td>{if $row.parent_name}{$row.parent_name|escape}{else}—{/if}</td>
					<td>
						<form method="post" class="d-inline">
							<input type="hidden" name="token" value="{$adminToken}">
							<input type="hidden" name="idCategory" value="{$row.id_category}">
							{if $row.show_on_home|default:0}
							<input type="hidden" name="show_on_home" value="0">
							<button type="submit" name="toggleHomeCategory" value="1" class="btn btn-sm btn-success" title="{'Show on homepage'|adminT}">
								{'On'|adminT}
								{if $row.home_position|default:0 > 0}<span class="opacity-75">#{$row.home_position}</span>{/if}
							</button>
							{else}
							<input type="hidden" name="show_on_home" value="1">
							<button type="submit" name="toggleHomeCategory" value="1" class="btn btn-sm btn-outline-secondary" title="{'Show on homepage'|adminT}">
								{'Off'|adminT}
							</button>
							{/if}
						</form>
					</td>
					<td>{if $row.active}{'Active'|adminT}{else}<span class="text-danger">{'Inactive'|adminT}</span>{/if}</td>
					<td class="text-end text-nowrap">
						<form action="" method="POST" class="adm-row-actions">
							<input type="hidden" name="token" value="{$adminToken}">
							<a href="{$adminUrl}category?id={$row.id_category}" class="adm-icon-btn" title="{'Edit'|adminT}" aria-label="{'Edit'|adminT}">
								<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
							</a>
							{if $row.id_category > 1}
							<input type="hidden" name="idCategory" value="{$row.id_category}">
							<button type="submit" name="deleteCategory" value="{$adminToken}" class="adm-icon-btn adm-icon-btn--danger js-admin-confirm" title="{'Delete'|adminT}" aria-label="{'Delete'|adminT}" data-confirm-title="{'Delete category'|adminT}" data-confirm-message="{'Are you sure you want to delete this category? The category record will be permanently removed.'|adminT}">
								<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
							</button>
							{/if}
						</form>
					</td>
				</tr>
				{/foreach}
				{else}
				<tr><td colspan="7" class="text-muted">{'No records found.'|adminT}</td></tr>
				{/if}
			</tbody>
		</table>
	</div>
</div>
