{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">{$flash|escape}</div>
			</div>
			<button class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
		</div>
	</div>
{/if}

{if $demoMode}
<div class="alert alert-warning mb-4" role="alert">
	<strong>{'Demo mode'|adminT}</strong>
	{'Demo mode: some edits are not allowed'|adminT}
</div>
{/if}

<div class="row g-4">
	<div class="col-lg-5">
		<form method="post" class="admin-panel p-3 mb-4"{if $demoMode} onsubmit="return false;"{/if}>
			<input type="hidden" name="saveProfile" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<h2 class="h6 mb-3">{'Profile'|adminT}</h2>
			<p class="text-muted small mb-3">{'Update your name and email used to sign in to the admin panel.'|adminT}</p>

			<div class="mb-3">
				<label class="form-label" for="accountFullName">{'Full name'|adminT}</label>
				<input type="text" name="full_name" id="accountFullName" class="form-control" required maxlength="128"
					value="{$account.full_name|escape}" autocomplete="name"{if $demoMode} disabled{/if}>
			</div>
			<div class="mb-3">
				<label class="form-label" for="accountEmail">{'Email'|adminT}</label>
				<input type="email" name="email" id="accountEmail" class="form-control" required maxlength="128"
					value="{$account.email|escape}" autocomplete="username"{if $demoMode} disabled{/if}>
			</div>
			<button type="submit" class="btn btn-primary btn-sm"{if $demoMode} disabled{/if}>{'Save profile'|adminT}</button>
		</form>

		<form method="post" class="admin-panel p-3 mb-4"{if $demoMode} onsubmit="return false;"{/if}>
			<input type="hidden" name="savePassword" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<h2 class="h6 mb-3">{'Change password'|adminT}</h2>
			<p class="text-muted small mb-3">{'Password must be at least 8 characters.'|adminT}</p>

			<div class="mb-3">
				<label class="form-label" for="currentPassword">{'Current password'|adminT}</label>
				<input type="password" name="current_password" id="currentPassword" class="form-control" required autocomplete="current-password"{if $demoMode} disabled{/if}>
			</div>
			<div class="mb-3">
				<label class="form-label" for="newPassword">{'New password'|adminT}</label>
				<input type="password" name="new_password" id="newPassword" class="form-control" required minlength="8" autocomplete="new-password"{if $demoMode} disabled{/if}>
			</div>
			<div class="mb-3">
				<label class="form-label" for="confirmPassword">{'Confirm password'|adminT}</label>
				<input type="password" name="confirm_password" id="confirmPassword" class="form-control" required minlength="8" autocomplete="new-password"{if $demoMode} disabled{/if}>
			</div>
			<button type="submit" class="btn btn-primary btn-sm"{if $demoMode} disabled{/if}>{'Update password'|adminT}</button>
		</form>
	</div>

	<div class="col-lg-7">
		<form method="post" class="admin-panel p-3 mb-4"{if $demoMode} onsubmit="return false;"{/if}>
			<input type="hidden" name="createAdmin" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<h2 class="h6 mb-3">{'Add admin'|adminT}</h2>
			<p class="text-muted small mb-3">{'Create another admin account that can access this panel.'|adminT}</p>

			<div class="row g-3">
				<div class="col-md-6">
					<label class="form-label" for="newFullName">{'Full name'|adminT}</label>
					<input type="text" name="new_full_name" id="newFullName" class="form-control" required maxlength="128"{if $demoMode} disabled{/if}>
				</div>
				<div class="col-md-6">
					<label class="form-label" for="newEmail">{'Email'|adminT}</label>
					<input type="email" name="new_email" id="newEmail" class="form-control" required maxlength="128"{if $demoMode} disabled{/if}>
				</div>
				<div class="col-md-6">
					<label class="form-label" for="newAdminPassword">{'Password'|adminT}</label>
					<input type="password" name="new_password" id="newAdminPassword" class="form-control" required minlength="8" autocomplete="new-password"{if $demoMode} disabled{/if}>
				</div>
				<div class="col-md-6">
					<label class="form-label" for="newAdminConfirm">{'Confirm password'|adminT}</label>
					<input type="password" name="new_confirm_password" id="newAdminConfirm" class="form-control" required minlength="8" autocomplete="new-password"{if $demoMode} disabled{/if}>
				</div>
			</div>
			<div class="mt-3">
				<button type="submit" class="btn btn-primary btn-sm"{if $demoMode} disabled{/if}>{'Create admin'|adminT}</button>
			</div>
		</form>

		<div class="admin-panel p-0 overflow-hidden">
			<div class="p-3 border-bottom">
				<h2 class="h6 mb-0">{'Admin users'|adminT}</h2>
			</div>
			{if $adminList|@count}
			<div class="table-responsive">
				<table class="table admin-table mb-0 align-middle">
					<thead>
						<tr>
							<th>{'Full name'|adminT}</th>
							<th>{'Email'|adminT}</th>
							<th>{'Status'|adminT}</th>
							<th class="text-end">{'Actions'|adminT}</th>
						</tr>
					</thead>
					<tbody>
						{foreach $adminList as $adminRow}
						<tr>
							<td>
								<strong>{$adminRow.full_name|escape}</strong>
								{if $adminRow.is_self}
								<span class="badge bg-primary-subtle text-primary ms-1">{'You'|adminT}</span>
								{/if}
							</td>
							<td class="text-muted">{$adminRow.email|escape}</td>
							<td>
								{if $adminRow.active}
								<span class="badge bg-success">{'Active'|adminT}</span>
								{else}
								<span class="badge bg-secondary">{'Inactive'|adminT}</span>
								{/if}
							</td>
							<td class="text-end text-nowrap">
								{if !$adminRow.is_self && !$demoMode}
								<form method="post" class="d-inline">
									<input type="hidden" name="toggleAdmin" value="1">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="hidden" name="id_admin" value="{$adminRow.id_admin}">
									<input type="hidden" name="activate" value="{if $adminRow.active}0{else}1{/if}">
									<button type="submit" class="btn btn-outline-dark btn-sm">
										{if $adminRow.active}{'Deactivate'|adminT}{else}{'Activate'|adminT}{/if}
									</button>
								</form>
								<form method="post" class="d-inline">
									<input type="hidden" name="deleteAdmin" value="1">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="hidden" name="id_admin" value="{$adminRow.id_admin}">
									<button type="submit" class="btn btn-outline-danger btn-sm js-admin-confirm"
										data-confirm-title="{'Delete admin'|adminT}"
										data-confirm-message="{'Are you sure you want to delete this admin account?'|adminT}">
										{'Delete'|adminT}
									</button>
								</form>
								{elseif $adminRow.is_self}
								<span class="text-muted small">—</span>
								{else}
								<span class="text-muted small">{'Demo mode'|adminT}</span>
								{/if}
							</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
			{else}
			<p class="text-muted p-3 mb-0">{'No admin users found.'|adminT}</p>
			{/if}
		</div>
	</div>
</div>
