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

<style>
.themes-header {
	margin-bottom: 24px;
}
.theme-card {
	position: relative;
	background: #ffffff;
	border: 1px solid var(--adm-border, #e2e8f0);
	border-radius: 12px;
	overflow: hidden;
	box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	height: 100%;
	display: flex;
	flex-direction: column;
}
.theme-card:hover {
	transform: translateY(-5px);
	box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}
.theme-card.active {
	border: 2px solid #0056b3;
	box-shadow: 0 10px 20px -5px rgba(0, 86, 179, 0.15);
}
.theme-thumbnail-container {
	position: relative;
	padding-top: 62.5%; /* 16:10 Aspect Ratio */
	background: #f1f5f9;
	overflow: hidden;
}
.theme-thumbnail {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	object-fit: cover;
	transition: transform 0.5s ease;
}
.theme-card:hover .theme-thumbnail {
	transform: scale(1.03);
}
.theme-card-overlay {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(15, 23, 42, 0.6);
	backdrop-filter: blur(3px);
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12px;
	opacity: 0;
	transition: opacity 0.3s ease;
	z-index: 2;
}
.theme-card:hover .theme-card-overlay {
	opacity: 1;
}
.theme-card-footer {
	padding: 16px;
	background: #ffffff;
	border-top: 1px solid #f1f5f9;
	margin-top: auto;
}
.theme-card.active .theme-card-footer {
	background: #0056b3;
	color: #ffffff;
	border-top: none;
}
.add-theme-card {
	border: 2px dashed #cbd5e1;
	background: transparent;
	border-radius: 12px;
	height: 100%;
	min-height: 250px;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	transition: all 0.3s ease;
	color: #64748b;
}
.add-theme-card:hover {
	border-color: #0056b3;
	background: rgba(0, 86, 179, 0.02);
	color: #0056b3;
}
.add-theme-icon {
	font-size: 40px;
	margin-bottom: 8px;
	line-height: 1;
}
</style>

<!-- ================= GRID VIEW ================= -->
<!-- Themes Grid Header Section -->
<div class="themes-header d-flex flex-wrap justify-content-between align-items-center gap-3">
	<div class="d-flex align-items-center gap-3">
		<h2 class="h5 mb-0 d-flex align-items-center gap-2 text-dark fw-bold">
			{'Templates'|adminT}
			<span class="badge bg-secondary rounded-pill font-monospace" style="font-size:12px;">{$themes|@count}</span>
		</h2>
		<button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addThemeModal">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" class="me-1"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
			{'Add New'|adminT}
		</button>
	</div>
	
	<!-- Search bar -->
	<div class="d-flex align-items-center gap-2">
		<span class="text-muted small d-none d-sm-inline">{'Search'|adminT}</span>
		<div class="position-relative">
			<input type="text" id="themeSearchInput" class="form-control form-control-sm rounded-pill ps-4" placeholder="{'Search..'|adminT}" style="width: 200px;">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" class="position-absolute text-muted" style="left: 10px; top: 50%; transform: translateY(-50%);">
				<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
			</svg>
		</div>
	</div>
</div>

<!-- Themes Grid -->
<div class="row g-4 mb-5" id="themesGrid">
	{foreach $themes as $theme}
	<div class="col-md-6 col-lg-4 theme-card-wrapper" data-theme-name="{$theme.name|escape}" data-theme-label="{$theme.label|escape}">
		<div class="theme-card{if $activeTheme == $theme.name} active{/if}">
			<div class="theme-thumbnail-container">
				<img src="{$theme.screenshot}" alt="{$theme.label|escape}" class="theme-thumbnail">
				
				<!-- Action Overlays for Inactive Themes -->
				{if $activeTheme != $theme.name}
				<div class="theme-card-overlay">
					<a href="{$domain}?theme_preview={$theme.name|escape:url}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light rounded-pill px-3 shadow-sm">{'Preview'|adminT}</a>
					<form method="post" style="display:inline;">
						<input type="hidden" name="saveTheme" value="1">
						<input type="hidden" name="token" value="{$adminToken}">
						<input type="hidden" name="active_theme" value="{$theme.name|escape}">
						<button type="submit" class="btn btn-sm btn-light rounded-pill px-3 shadow-sm">{'Active'|adminT}</button>
					</form>
					{if $theme.edit_module}
					<a href="{$adminUrl}module-{$theme.edit_module|escape}" class="btn btn-sm btn-dark rounded-pill px-3 shadow-sm">{'Edit'|adminT}</a>
					{elseif $theme.has_schema}
					<a href="{$adminUrl}theme-customize?theme={$theme.name|escape:url}" class="btn btn-sm btn-dark rounded-pill px-3 shadow-sm">{'Customize'|adminT}</a>
					{/if}
				</div>
				{/if}
			</div>
			
			<div class="theme-card-footer d-flex align-items-center justify-content-between">
				{if $activeTheme == $theme.name}
				<div class="d-flex align-items-center gap-2">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
					<span class="fw-semibold">{'Active'|adminT}: {$theme.label|escape}</span>
				</div>
				<div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
				{if $theme.edit_module}
				<a href="{$adminUrl}module-{$theme.edit_module|escape}" class="btn btn-sm btn-outline-light rounded-pill px-3">{'Edit'|adminT}</a>
				{elseif $theme.has_schema}
				<a href="{$adminUrl}theme-customize?theme={$theme.name|escape:url}" class="btn btn-sm btn-outline-light rounded-pill px-3">{'Customize'|adminT}</a>
				{/if}
				<a href="{$domain}?theme_preview={$theme.name|escape:url}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light rounded-pill px-3">{'Preview'|adminT}</a>
				</div>
				{else}
				<span class="fw-semibold text-dark">{$theme.label|escape}</span>
				<a href="{$domain}?theme_preview={$theme.name|escape:url}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary rounded-pill px-3">{'Preview'|adminT}</a>
				{/if}
			</div>
		</div>
	</div>
	{/foreach}
	
	<!-- Dotted Plus Card for Adding Theme -->
	<div class="col-md-6 col-lg-4" id="addThemeCardWrapper">
		<div class="add-theme-card" data-bs-toggle="modal" data-bs-target="#addThemeModal">
			<div class="add-theme-icon">+</div>
			<div class="fw-semibold">{'Add New'|adminT}</div>
		</div>
	</div>
</div>

<!-- ================= ADD THEME MODAL ================= -->
<div class="modal fade" id="addThemeModal" tabindex="-1" aria-labelledby="addThemeModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content border-0 shadow-lg">
			<div class="modal-header bg-light border-0">
				<h5 class="modal-title fw-bold" id="addThemeModalLabel">{'Add New'|adminT}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body p-4">
				<ul class="nav nav-pills mb-4 d-flex justify-content-center gap-2" id="addThemeTab" role="tablist">
					<li class="nav-item" role="presentation">
						<button class="nav-link active rounded-pill px-4" id="zip-tab" data-bs-toggle="pill" data-bs-target="#zip-upload" type="button" role="tab" aria-controls="zip-upload" aria-selected="true">
							{'Upload zip file'|adminT}
						</button>
					</li>
					<li class="nav-item" role="presentation">
						<button class="nav-link rounded-pill px-4" id="clone-tab" data-bs-toggle="pill" data-bs-target="#clone-theme" type="button" role="tab" aria-controls="clone-theme" aria-selected="false">
							{'Copy Theme'|adminT}
						</button>
					</li>
				</ul>
				
				<div class="tab-content" id="addThemeTabContent">
					<!-- ZIP Upload Tab -->
					<div class="tab-pane fade show active" id="zip-upload" role="tabpanel" aria-labelledby="zip-tab">
						<form method="post" enctype="multipart/form-data">
							<input type="hidden" name="uploadTheme" value="1">
							<input type="hidden" name="token" value="{$adminToken}">
							
							<div class="mb-3">
								<label class="form-label small fw-semibold text-muted">{'Theme Folder Name'|adminT}</label>
								<input type="text" name="theme_name" class="form-control rounded-3" placeholder="eg: my-new-theme" required pattern="^[a-z0-9_-]+$">
							</div>
							
							<div class="mb-3">
								<label class="form-label small fw-semibold text-muted">{'Theme Name'|adminT}</label>
								<input type="text" name="theme_label" class="form-control rounded-3" placeholder="eg: New Theme" required>
							</div>
							
							<div class="mb-4">
								<label class="form-label small fw-semibold text-muted">{'Zip File'|adminT}</label>
								<input type="file" name="theme_zip" class="form-control rounded-3" accept=".zip" required>
								<div class="form-text text-muted small mt-1">{'Ensure the files are complete.'|adminT}</div>
							</div>
							
							<div class="d-grid">
								<button type="submit" class="btn btn-primary rounded-pill py-2 fw-semibold">{'Save'|adminT}</button>
							</div>
						</form>
					</div>
					
					<!-- Clone Theme Tab -->
					<div class="tab-pane fade" id="clone-theme" role="tabpanel" aria-labelledby="clone-tab">
						<form method="post">
							<input type="hidden" name="createTheme" value="1">
							<input type="hidden" name="token" value="{$adminToken}">
							
							<div class="mb-3">
								<label class="form-label small fw-semibold text-muted">{'Theme to clone (reference)'|adminT}</label>
								<select name="clone_from" class="form-select rounded-3" required>
									{foreach $themes as $t}
									<option value="{$t.name|escape}">{$t.label|escape}</option>
									{/foreach}
								</select>
							</div>
							
							<div class="mb-3">
								<label class="form-label small fw-semibold text-muted">{'New theme folder name'|adminT}</label>
								<input type="text" name="theme_name" class="form-control rounded-3" placeholder="{'E.g. my-custom-theme'|adminT}" required pattern="^[a-z0-9_-]+$">
							</div>
							
							<div class="mb-4">
								<label class="form-label small fw-semibold text-muted">{'New theme display name'|adminT}</label>
								<input type="text" name="theme_label" class="form-control rounded-3" placeholder="{'E.g. New design theme'|adminT}" required>
							</div>
							
							<div class="d-grid">
								<button type="submit" class="btn btn-primary rounded-pill py-2 fw-semibold">{'Clone new theme'|adminT}</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


<script>
{literal}
(function () {
	var searchInput = document.getElementById('themeSearchInput');
	var themeCards = document.querySelectorAll('.theme-card-wrapper');
	var addThemeCard = document.getElementById('addThemeCardWrapper');

	if (searchInput) {
		searchInput.addEventListener('input', function () {
			var query = searchInput.value.toLowerCase().trim();

			themeCards.forEach(function (card) {
				var name = card.getAttribute('data-theme-name').toLowerCase();
				var label = card.getAttribute('data-theme-label').toLowerCase();

				if (name.includes(query) || label.includes(query)) {
					card.style.display = '';
				} else {
					card.style.display = 'none';
				}
			});

			if (addThemeCard) {
				if (query === '' || 'tema ekle'.includes(query) || 'ekle'.includes(query) || 'yeni'.includes(query)) {
					addThemeCard.style.display = '';
				} else {
					addThemeCard.style.display = 'none';
				}
			}
		});
	}
})();
{/literal}
</script>
