{if $flash}
<div class="alert alert-{$flashType|default:'info'} py-2">{$flash|escape}</div>
{/if}

<link rel="stylesheet" href="{$domain}templates/admin/css/media-library.css?v={$smarty.now}">

{capture assign=t4HooksJson}[{foreach $hookChoices as $h}"{$h|escape}"{if !$h@last},{/if}{/foreach}]{/capture}
{capture assign=t4WidgetsHomeJson}[{foreach $widgetsHome as $w}"{$w|escape}"{if !$w@last},{/if}{/foreach}]{/capture}
{capture assign=t4WidgetsHeaderJson}[{foreach $widgetsHeader as $w}"{$w|escape}"{if !$w@last},{/if}{/foreach}]{/capture}
{capture assign=t4WidgetsFooterJson}[{foreach $widgetsFooter as $w}"{$w|escape}"{if !$w@last},{/if}{/foreach}]{/capture}

<ul class="nav nav-tabs mb-3" role="tablist">
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'home'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabHome" role="tab">{'Homepage settings'|t4l}</button>
	</li>
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'header'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabHeader" role="tab">{'Header'|t4l}</button>
	</li>
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'footer'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabFooter" role="tab">{'Footer'|t4l}</button>
	</li>
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'theme'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabTheme" role="tab">{'Theme settings'|t4l}</button>
	</li>
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'colors'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabColors" role="tab">{'Colors'|t4l}</button>
	</li>
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'custom'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabCustom" role="tab">{'Custom CSS & JS'|t4l}</button>
	</li>
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'export'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabExport" role="tab">{'Import / Export'|t4l}</button>
	</li>
</ul>

<div class="tab-content">

	{* ——— Homepage ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'home'} show active{/if}" id="t4TabHome" role="tabpanel">
		<div class="t4-builder" data-t4-builder="home" data-hooks='{$t4HooksJson nofilter}' data-widgets='{$t4WidgetsHomeJson nofilter}'>
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
				<div>
					<h2 class="h5 mb-1">{'Homepage editor'|t4l}</h2>
					<p class="text-muted small mb-0">{'Add rows, columns and widgets. Drag to reorder. Pick banner images from the media library, then save.'|t4l}</p>
				</div>
				<div class="d-flex gap-2">
					<a href="{$theme4PreviewUrl|escape}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">{'Preview'|t4l}</a>
					<button type="button" class="btn btn-sm btn-outline-dark" data-t4-add-row>+ {'Add row'|t4l}</button>
				</div>
			</div>
			<form method="post">
				<input type="hidden" name="saveTheme4Layout" value="1">
				<input type="hidden" name="tab" value="home">
				<input type="hidden" name="token" value="{$adminToken}">
				<input type="hidden" name="layout_json" data-layout-json value="">
				<script type="application/json" data-layout-boot>{$layoutJson nofilter}</script>
				<script type="application/json" data-layout-default>{$defaultHomeLayoutJson nofilter}</script>
				<div data-t4-rows class="t4-builder__rows"></div>
				<div class="mt-3 d-flex gap-2">
					<button type="submit" class="btn btn-dark">{'Save'|t4l}</button>
					<button type="button" class="btn btn-outline-secondary" data-t4-reset>{'Reset to default'|t4l}</button>
				</div>
			</form>
		</div>
	</div>

	{* ——— Header ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'header'} show active{/if}" id="t4TabHeader" role="tabpanel">
		<div class="t4-builder" data-t4-builder="header" data-hooks='{$t4HooksJson nofilter}' data-widgets='{$t4WidgetsHeaderJson nofilter}'>
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
				<div>
					<h2 class="h5 mb-1">{'Header editor'|t4l}</h2>
					<p class="text-muted small mb-0">{'Build the header with logo, search, links, menu hook and tools. Same row/column options as the homepage.'|t4l}</p>
				</div>
				<div class="d-flex gap-2">
					<a href="{$theme4PreviewUrl|escape}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">{'Preview'|t4l}</a>
					<button type="button" class="btn btn-sm btn-outline-dark" data-t4-add-row>+ {'Add row'|t4l}</button>
				</div>
			</div>
			<form method="post">
				<input type="hidden" name="saveTheme4Header" value="1">
				<input type="hidden" name="tab" value="header">
				<input type="hidden" name="token" value="{$adminToken}">
				<input type="hidden" name="layout_json" data-layout-json value="">
				<script type="application/json" data-layout-boot>{$headerLayoutJson nofilter}</script>
				<script type="application/json" data-layout-default>{$defaultHeaderLayoutJson nofilter}</script>
				<div data-t4-rows class="t4-builder__rows"></div>
				<div class="mt-3 d-flex gap-2">
					<button type="submit" class="btn btn-dark">{'Save'|t4l}</button>
					<button type="button" class="btn btn-outline-secondary" data-t4-reset>{'Reset to default'|t4l}</button>
				</div>
			</form>
		</div>
	</div>

	{* ——— Footer ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'footer'} show active{/if}" id="t4TabFooter" role="tabpanel">
		<div class="t4-builder" data-t4-builder="footer" data-hooks='{$t4HooksJson nofilter}' data-widgets='{$t4WidgetsFooterJson nofilter}'>
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
				<div>
					<h2 class="h5 mb-1">{'Footer editor'|t4l}</h2>
					<p class="text-muted small mb-0">{'Build footer columns with logo, text, links and the footer hook (e.g. social follow modules).'|t4l}</p>
				</div>
				<div class="d-flex gap-2">
					<a href="{$theme4PreviewUrl|escape}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">{'Preview'|t4l}</a>
					<button type="button" class="btn btn-sm btn-outline-dark" data-t4-add-row>+ {'Add row'|t4l}</button>
				</div>
			</div>
			<form method="post">
				<input type="hidden" name="saveTheme4Footer" value="1">
				<input type="hidden" name="tab" value="footer">
				<input type="hidden" name="token" value="{$adminToken}">
				<input type="hidden" name="layout_json" data-layout-json value="">
				<script type="application/json" data-layout-boot>{$footerLayoutJson nofilter}</script>
				<script type="application/json" data-layout-default>{$defaultFooterLayoutJson nofilter}</script>
				<div data-t4-rows class="t4-builder__rows"></div>
				<div class="card border-0 shadow-sm mt-3">
					<div class="card-body py-3">
						<label class="form-label mb-1" for="t4CopyrightInput">{'Copyright text'|t4l}</label>
						<label class="form-label mb-1" for="t4CopyrightInput">{'Copyright text'|t4l}</label>
						<input type="text" name="copyright_text" id="t4CopyrightInput" class="form-control" value="{$t4Copyright|escape}" placeholder="{literal}© {year} {site}. All rights reserved.{/literal}">
						<div class="form-text">{'Shown at the bottom of the footer. Placeholders:'|t4l} {ldelim}year{rdelim}, {ldelim}site{rdelim}</div>
					</div>
				</div>
				<div class="mt-3 d-flex gap-2">
					<button type="submit" class="btn btn-dark">{'Save'|t4l}</button>
					<button type="button" class="btn btn-outline-secondary" data-t4-reset>{'Reset to default'|t4l}</button>
				</div>
			</form>
		</div>
	</div>

	{* ——— Theme settings ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'theme'} show active{/if}" id="t4TabTheme" role="tabpanel">
		<form method="post" class="card border-0 shadow-sm">
			<div class="card-body">
				<input type="hidden" name="saveTheme4Theme" value="1">
				<input type="hidden" name="tab" value="theme">
				<input type="hidden" name="token" value="{$adminToken}">

				<p class="text-muted small">{'Global theme options. Logo widgets fall back to the default logo when empty.'|t4l}</p>

				<div class="row g-3">
					<div class="col-md-6">
						<label class="form-label">{'Site width'|t4l}</label>
						<input type="text" name="site_width" class="form-control" value="{$t4Theme.site_width|escape}" placeholder="1320px">
						<div class="form-text">{'Max container width (e.g. 1320px or 100%)'|t4l}</div>
					</div>
					<div class="col-md-6">
						<label class="form-label">{'Font family'|t4l}</label>
						<input type="text" name="font_family" class="form-control" value="{$t4Theme.font_family|escape}">
						<div class="form-text">{'CSS font-family value'|t4l}</div>
					</div>
					<div class="col-md-6">
						<label class="form-label">{'Default logo'|t4l}</label>
						<div class="d-flex gap-2 flex-wrap align-items-start">
							<input type="text" name="logo" id="t4LogoInput" class="form-control" value="{$t4Theme.logo|escape}" placeholder="{'Select from media'|t4l}" readonly>
							<button type="button" class="btn btn-outline-dark text-nowrap" data-t4-media-pick data-input="#t4LogoInput" data-preview="#t4LogoPreview" data-confirm-label="{'Select logo'|t4l|escape}">{'Select logo'|t4l}</button>
						</div>
						{if $t4Theme.logo}
						<img src="{$t4Theme.logo|escape}" alt="" class="t4-banner-preview mt-2" id="t4LogoPreview">
						{else}
						<img src="" alt="" class="t4-banner-preview mt-2 d-none" id="t4LogoPreview">
						{/if}
					</div>
					<div class="col-md-6">
						<label class="form-label">{'Favicon'|t4l}</label>
						<div class="d-flex gap-2 flex-wrap align-items-start">
							<input type="text" name="favicon" id="t4FaviconInput" class="form-control" value="{$t4Theme.favicon|escape}" placeholder="{'Select from media'|t4l}" readonly>
							<button type="button" class="btn btn-outline-dark text-nowrap" data-t4-media-pick data-input="#t4FaviconInput" data-preview="#t4FaviconPreview" data-confirm-label="{'Select favicon'|t4l|escape}">{'Select favicon'|t4l}</button>
						</div>
						{if $t4Theme.favicon}
						<img src="{$t4Theme.favicon|escape}" alt="" class="t4-banner-preview mt-2" id="t4FaviconPreview" style="max-width:48px;max-height:48px">
						{else}
						<img src="" alt="" class="t4-banner-preview mt-2 d-none" id="t4FaviconPreview" style="max-width:48px;max-height:48px">
						{/if}
					</div>
				</div>
			</div>
			<div class="card-footer bg-white">
				<button type="submit" class="btn btn-dark">{'Save'|t4l}</button>
			</div>
		</form>
	</div>

	{* ——— Colors ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'colors'} show active{/if}" id="t4TabColors" role="tabpanel">
		<form method="post" class="card border-0 shadow-sm">
			<div class="card-body">
				<input type="hidden" name="saveTheme4Colors" value="1">
				<input type="hidden" name="tab" value="colors">
				<input type="hidden" name="token" value="{$adminToken}">

				<p class="text-muted small mb-3">{'These names map to :root variables in colors.css. Use them in style.css as var(--name).'|t4l}</p>

				{foreach $t4ColorGroups as $group}
				<div class="mb-4">
					<h3 class="h6 border-bottom pb-2">{$group.label|escape}</h3>
					<div class="row g-3">
						{foreach $group.fields as $field}
						<div class="col-md-6 col-lg-4">
							<label class="form-label small mb-1">{$field.label|escape}</label>
							<div class="input-group input-group-sm">
								<input type="color" class="form-control form-control-color" value="{$field.hex|escape}" data-t4-color-picker data-target="t4c_{$field.var|escape}" title="{$field.label|escape}">
								<span class="input-group-text font-monospace small">--{$field.var|escape}</span>
								<input type="text" name="colors[{$field.var|escape}]" id="t4c_{$field.var|escape}" class="form-control font-monospace" value="{$field.value|escape}">
							</div>
							<div class="form-text">{'CSS variable'|t4l}: <code>var(--{$field.var|escape})</code></div>
						</div>
						{/foreach}
					</div>
				</div>
				{/foreach}
			</div>
			<div class="card-footer bg-white">
				<button type="submit" class="btn btn-dark">{'Save'|t4l}</button>
			</div>
		</form>
	</div>

	{* ——— Custom CSS / JS ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'custom'} show active{/if}" id="t4TabCustom" role="tabpanel">
		<form method="post" class="card border-0 shadow-sm">
			<div class="card-body">
				<input type="hidden" name="saveTheme4Custom" value="1">
				<input type="hidden" name="tab" value="custom">
				<input type="hidden" name="token" value="{$adminToken}">

				<div class="mb-3">
					<label class="form-label">{'Custom CSS'|t4l}</label>
					<textarea name="custom_css" class="form-control font-monospace" rows="14" spellcheck="false">{$t4CustomCss|escape}</textarea>
					<div class="form-text">{'Written to templates/theme4/css/custom.css'|t4l}</div>
				</div>
				<div class="mb-0">
					<label class="form-label">{'Custom JS'|t4l}</label>
					<textarea name="custom_js" class="form-control font-monospace" rows="14" spellcheck="false">{$t4CustomJs|escape}</textarea>
					<div class="form-text">{'Written to templates/theme4/js/custom.js'|t4l}</div>
				</div>
			</div>
			<div class="card-footer bg-white">
				<button type="submit" class="btn btn-dark">{'Save'|t4l}</button>
			</div>
		</form>
	</div>

	{* ——— Import / Export ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'export'} show active{/if}" id="t4TabExport" role="tabpanel">
		{*<div class="alert alert-light border mb-3">
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
				<div>
					<strong>{'Premium License Store'|t4l}</strong>
					<div class="small text-muted mb-0">{'One-click premium look: navy palette, refined header/footer, banner cards and typography. Uses your media under img/media/.'|t4l}</div>
				</div>
				<form method="post" class="m-0">
					<input type="hidden" name="applyTheme4Premium" value="1">
					<input type="hidden" name="tab" value="export">
					<input type="hidden" name="token" value="{$adminToken}">
					<button type="submit" class="btn btn-primary" data-t4-import-confirm="{'Apply the Premium preset and overwrite current Theme4 settings?'|t4l|escape}" onclick="return window.confirm(this.getAttribute('data-t4-import-confirm') || '');">{'Apply Premium preset'|t4l}</button>
				</form>
			</div>
		</div>*}
		<div class="row g-3">
			<div class="col-lg-6">
				<form method="post" class="card border-0 shadow-sm h-100">
					<div class="card-body">
						<input type="hidden" name="exportTheme4" value="1">
						<input type="hidden" name="tab" value="export">
						<input type="hidden" name="token" value="{$adminToken}">
						<h2 class="h5">{'Export'|t4l}</h2>
						<p class="text-muted small">{'Download a JSON file with homepage, header, footer layouts, theme settings, colors and custom CSS/JS. Share it with another store running Theme4.'|t4l}</p>
						<ul class="small text-muted mb-0">
							<li>{'Layouts'|t4l} (home / header / footer)</li>
							<li>{'Theme settings'|t4l} + {'Copyright text'|t4l}</li>
							<li>{'Colors'|t4l}</li>
							<li>{'Custom CSS & JS'|t4l}</li>
						</ul>
					</div>
					<div class="card-footer bg-white">
						<button type="submit" class="btn btn-dark">{'Download JSON'|t4l}</button>
					</div>
				</form>
			</div>
			<div class="col-lg-6">
				<form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm h-100">
					<div class="card-body">
						<input type="hidden" name="importTheme4" value="1">
						<input type="hidden" name="tab" value="export">
						<input type="hidden" name="token" value="{$adminToken}">
						<h2 class="h5">{'Import'|t4l}</h2>
						<p class="text-muted small">{'Upload a Theme4 JSON export or paste it below. Media paths and category IDs may need updating on the target shop.'|t4l}</p>

						<div class="mb-3">
							<label class="form-label">{'JSON file'|t4l}</label>
							<input type="file" name="import_file" class="form-control" accept=".json,application/json,text/plain">
						</div>
						<div class="mb-3">
							<label class="form-label">{'Or paste JSON'|t4l}</label>
							<textarea name="import_json" class="form-control font-monospace" rows="8" spellcheck="false" placeholder="{literal}{ &quot;format&quot;: &quot;fshop-theme4&quot;, ... }{/literal}"></textarea>
						</div>
						<div class="mb-0">
							<div class="fw-semibold small mb-2">{'Import sections'|t4l}</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="import_layouts" id="t4ImpLayouts" value="1" checked>
								<label class="form-check-label" for="t4ImpLayouts">{'Layouts'|t4l} (home / header / footer)</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="import_theme" id="t4ImpTheme" value="1" checked>
								<label class="form-check-label" for="t4ImpTheme">{'Theme settings'|t4l} + {'Copyright text'|t4l}</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="import_colors" id="t4ImpColors" value="1" checked>
								<label class="form-check-label" for="t4ImpColors">{'Colors'|t4l}</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="import_custom" id="t4ImpCustom" value="1" checked>
								<label class="form-check-label" for="t4ImpCustom">{'Custom CSS & JS'|t4l}</label>
							</div>
						</div>
					</div>
					<div class="card-footer bg-white">
						<button type="submit" class="btn btn-dark" data-t4-import-confirm="{'Overwrite selected settings with the import file?'|t4l|escape}" onclick="return window.confirm(this.getAttribute('data-t4-import-confirm') || '');">{'Import'|t4l}</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<script type="application/json" id="t4CategoriesJson">{$categoryOptionsJson nofilter}</script>
<script type="application/json" id="t4ColWidthsJson">{$colWidthOptionsJson nofilter}</script>
<script type="application/json" id="t4LinkOptionsJson">{$t4LinkOptionsJson nofilter}</script>
<script type="application/json" id="t4I18n">{$t4I18nJson nofilter}</script>

<template id="t4TplRow">
	<div class="t4-row card mb-3" data-row>
		<div class="card-header d-flex justify-content-between align-items-center py-2">
			<div class="d-flex align-items-center gap-2">
				<span class="t4-drag-handle" data-row-handle title="{'Drag'|t4l}" aria-hidden="true">⠿</span>
				<strong class="small">{'Row'|t4l}</strong>
				<span class="badge text-bg-secondary d-none" data-hide-badge>{'Hidden'|t4l}</span>
				<button type="button" class="btn btn-sm btn-link text-secondary p-0 t4-settings-btn" data-row-settings title="{'Settings'|t4l}" aria-label="{'Settings'|t4l}">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.901 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.115 2.693l.319.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.115l-.094.319c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.693-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.693-1.115l.094-.319z"/></svg>
				</button>
			</div>
			<div class="d-flex gap-1">
				<button type="button" class="btn btn-sm btn-outline-primary" data-add-col>+ {'Add column'|t4l}</button>
				<button type="button" class="btn btn-sm btn-outline-danger" data-del-row>{'Delete'|t4l}</button>
			</div>
		</div>
		<div class="card-body">
			<div class="row g-2" data-cols></div>
		</div>
	</div>
</template>

<template id="t4TplCol">
	<div class="col-12 col-md-6 col-lg-6" data-col>
		<div class="t4-col border rounded p-2 h-100 bg-light">
			<div class="d-flex justify-content-between align-items-center mb-2 gap-2">
				<div class="d-flex align-items-center gap-2">
					<span class="t4-drag-handle" data-col-handle title="{'Drag'|t4l}" aria-hidden="true">⠿</span>
					<strong class="small">{'Column'|t4l}</strong>
					<span class="badge text-bg-secondary d-none" data-hide-badge>{'Hidden'|t4l}</span>
					<button type="button" class="btn btn-sm btn-link text-secondary p-0 t4-settings-btn" data-col-settings title="{'Settings'|t4l}" aria-label="{'Settings'|t4l}">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.901 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.115 2.693l.319.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.115l-.094.319c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.693-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.693-1.115l.094-.319z"/></svg>
					</button>
				</div>
				<button type="button" class="btn btn-sm btn-outline-danger text-nowrap" data-del-col>{'Delete column'|t4l}</button>
			</div>
			<div class="vstack gap-2" data-widgets></div>
			<button type="button" class="btn btn-sm btn-dark mt-2" data-add-widget>+ {'Add widget'|t4l}</button>
		</div>
	</div>
</template>

<div class="modal fade" id="t4BlockSettingsModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header py-2">
				<h5 class="modal-title" id="t4SetTitle">{'Settings'|t4l}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Cancel'|t4l}"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label" for="t4SetId">{'ID'|t4l}</label>
					<input type="text" class="form-control form-control-sm font-monospace" id="t4SetId" autocomplete="off" placeholder="hero">
					<div class="form-text">{'Letters, numbers, hyphen and underscore. Optional.'|t4l}</div>
				</div>
				<div class="mb-3">
					<label class="form-label" for="t4SetClass">{'CSS class'|t4l}</label>
					<input type="text" class="form-control form-control-sm font-monospace" id="t4SetClass" autocomplete="off" placeholder="my-section dark-bg">
					<div class="form-text">{'Space-separated class names'|t4l}</div>
				</div>
				<div id="t4SetWidths" class="mb-3 d-none">
					<div class="fw-semibold small mb-2">{'Widths'|t4l}</div>
					<div class="row g-2">
						<div class="col-4">
							<label class="form-label small mb-1" for="t4SetWMobile">{'Mobile'|t4l} <span class="text-muted font-monospace">col-*</span></label>
							<select class="form-select form-select-sm" id="t4SetWMobile"></select>
						</div>
						<div class="col-4">
							<label class="form-label small mb-1" for="t4SetWTablet">{'Tablet'|t4l} <span class="text-muted font-monospace">col-md-*</span></label>
							<select class="form-select form-select-sm" id="t4SetWTablet"></select>
						</div>
						<div class="col-4">
							<label class="form-label small mb-1" for="t4SetWDesktop">{'Desktop'|t4l} <span class="text-muted font-monospace">col-lg-*</span></label>
							<select class="form-select form-select-sm" id="t4SetWDesktop"></select>
						</div>
					</div>
				</div>
				<div>
					<div class="fw-semibold small mb-2">{'Visibility'|t4l}</div>
					<div class="d-flex flex-column gap-2">
						<div class="d-flex align-items-center justify-content-between gap-3 t4-switch-row">
							<label class="mb-0" for="t4SetHideMobile">{'Hide on mobile'|t4l}</label>
							<div class="d-flex align-items-center gap-2">
								<span class="small text-muted" data-switch-no>{'No'|t4l}</span>
								<div class="form-check form-switch m-0">
									<input class="form-check-input" type="checkbox" role="switch" id="t4SetHideMobile">
								</div>
								<span class="small text-muted" data-switch-yes>{'Yes'|t4l}</span>
							</div>
						</div>
						<div class="d-flex align-items-center justify-content-between gap-3 t4-switch-row">
							<label class="mb-0" for="t4SetHideTablet">{'Hide on tablet'|t4l}</label>
							<div class="d-flex align-items-center gap-2">
								<span class="small text-muted" data-switch-no>{'No'|t4l}</span>
								<div class="form-check form-switch m-0">
									<input class="form-check-input" type="checkbox" role="switch" id="t4SetHideTablet">
								</div>
								<span class="small text-muted" data-switch-yes>{'Yes'|t4l}</span>
							</div>
						</div>
						<div class="d-flex align-items-center justify-content-between gap-3 t4-switch-row">
							<label class="mb-0" for="t4SetHideDesktop">{'Hide on desktop'|t4l}</label>
							<div class="d-flex align-items-center gap-2">
								<span class="small text-muted" data-switch-no>{'No'|t4l}</span>
								<div class="form-check form-switch m-0">
									<input class="form-check-input" type="checkbox" role="switch" id="t4SetHideDesktop">
								</div>
								<span class="small text-muted" data-switch-yes>{'Yes'|t4l}</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer py-2">
				<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">{'Cancel'|t4l}</button>
				<button type="button" class="btn btn-sm btn-dark" id="t4SetApply">{'Apply'|t4l}</button>
			</div>
		</div>
	</div>
</div>

<template id="t4TplWidget">
	<div class="t4-widget-card border rounded bg-white p-2" data-widget>
		<div class="d-flex justify-content-between align-items-center mb-2 gap-2">
			<div class="d-flex align-items-center gap-2 flex-grow-1">
				<span class="t4-drag-handle" data-widget-handle title="{'Drag'|t4l}" aria-hidden="true">⠿</span>
				<select class="form-select form-select-sm" data-widget-type style="max-width:220px"></select>
			</div>
			<button type="button" class="btn btn-sm btn-outline-danger" data-del-widget>{'Delete'|t4l}</button>
		</div>
		<div data-widget-fields></div>
	</div>
</template>

{include file='admin/partials/media-library-modal.tpl'}
<script src="{$domain}templates/admin/js/media-picker.js?v={$smarty.now}"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
	document.querySelectorAll('[data-t4-color-picker]').forEach(function (picker) {
		var targetId = picker.getAttribute('data-target');
		var input = targetId ? document.getElementById(targetId) : null;
		if (!input) return;
		picker.addEventListener('input', function () {
			input.value = picker.value;
		});
		input.addEventListener('change', function () {
			if (/^#[0-9a-fA-F]{6}$/.test(input.value)) {
				picker.value = input.value;
			}
		});
	});

	document.querySelectorAll('[data-t4-media-pick]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			if (!window.FShopMediaPicker || !FShopMediaPicker.available) return;
			var inputSel = btn.getAttribute('data-input');
			var previewSel = btn.getAttribute('data-preview');
			FShopMediaPicker.open({
				multi: false,
				confirmLabel: btn.getAttribute('data-confirm-label') || 'Select',
				onSelect: function (items) {
					if (!items || !items.length) return;
					var url = items[0].url || '';
					var input = inputSel ? document.querySelector(inputSel) : null;
					var preview = previewSel ? document.querySelector(previewSel) : null;
					if (input) input.value = url;
					if (preview) {
						if (url) {
							preview.src = url;
							preview.classList.remove('d-none');
						} else {
							preview.removeAttribute('src');
							preview.classList.add('d-none');
						}
					}
				}
			});
		});
	});
})();
</script>
