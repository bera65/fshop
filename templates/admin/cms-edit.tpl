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

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
	<p class="text-muted small mb-0">{'Content tabs are created for active store languages'|adminT} ({$shopLanguages|@count} dil).</p>
	<a href="{$adminUrl}languages" class="btn btn-sm btn-outline-secondary">{'Manage languages'|adminT}</a>
</div>

<div class="admin-panel">
	<form method="post">
		<input type="hidden" name="saveCms" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="row g-3 mb-4">
			<div class="col-md-2">
				<label class="form-label">{'Sort order'|adminT}</label>
				<input type="number" name="position" class="form-control" value="{$cmsPage.position|default:0}">
			</div>
			<div class="col-md-3">
				<label class="form-label d-block">{'Status'|adminT}</label>
				<div class="form-check form-switch mt-2">
					<input class="form-check-input" type="checkbox" name="active" value="1" id="cmsActive"{if $cmsPage.active|default:1} checked{/if}>
					<label class="form-check-label" for="cmsActive">{'Published'|adminT}</label>
				</div>
			</div>
			<div class="col-md-3">
				<label class="form-label d-block">Footer</label>
				<div class="form-check form-switch mt-2">
					<input class="form-check-input" type="checkbox" name="show_footer" value="1" id="cmsFooter"{if $cmsPage.show_footer|default:1} checked{/if}>
					<label class="form-check-label" for="cmsFooter">{'Show in footer'|adminT}</label>
				</div>
			</div>
		</div>

		<ul class="nav nav-tabs mb-3" id="cmsLangTabs" role="tablist">
			{foreach $cmsLangForms as $langCode => $langForm}
			<li class="nav-item" role="presentation">
				<button class="nav-link{if $langForm@first} active{/if}" id="cms-tab-{$langCode|escape}" data-bs-toggle="tab" data-bs-target="#cms-pane-{$langCode|escape}" type="button" role="tab">{$langForm.label|escape}</button>
			</li>
			{/foreach}
		</ul>

		<div class="tab-content" id="cmsLangTabContent">
			{foreach $cmsLangForms as $langCode => $langForm}
			<div class="tab-pane fade{if $langForm@first} show active{/if}" id="cms-pane-{$langCode|escape}" role="tabpanel">
				<div class="mb-3">
					<label class="form-label">URL Slug ({$langForm.label|escape})</label>
					<input type="text" name="langs[{$langCode|escape}][slug]" class="form-control cms-lang-slug" data-lang="{$langCode|escape}" value="{$langForm.slug|escape}" placeholder="hakkimizda">
					<div class="form-text">{'Address:'|adminT} {$domain}<span class="cms-slug-preview" data-lang="{$langCode|escape}">{$langForm.slug|escape}</span></div>
				</div>
				<div class="mb-3">
					<label class="form-label">{'Title'|adminT} ({$langForm.label|escape})</label>
					<input type="text" name="langs[{$langCode|escape}][title]" class="form-control" value="{$langForm.title|escape}" maxlength="255">
				</div>
				<div class="mb-3">
					<label class="form-label">{'Short description'|adminT}</label>
					<input type="text" name="langs[{$langCode|escape}][summary]" class="form-control" value="{$langForm.summary|escape}" maxlength="512">
				</div>
				<div class="row g-3 mb-3">
					<div class="col-md-6">
						<label class="form-label">{'Meta title'|adminT}</label>
						<input type="text" name="langs[{$langCode|escape}][meta_title]" class="form-control" value="{$langForm.meta_title|escape}" maxlength="255">
					</div>
					<div class="col-md-6">
						<label class="form-label">{'Meta description'|adminT}</label>
						<input type="text" name="langs[{$langCode|escape}][meta_description]" class="form-control" value="{$langForm.meta_description|escape}" maxlength="512">
					</div>
				</div>
				<label class="form-label">{'Content'|adminT}</label>
				<textarea name="langs[{$langCode|escape}][content]" class="form-control wysiwyg-editor" rows="18">{$langForm.content|escape}</textarea>
			</div>
			{/foreach}
		</div>

		<div class="mt-4 d-flex gap-2">
			<button type="submit" class="btn btn-dark">{'Save'|adminT}</button>
			<a href="{$adminUrl}cms" class="btn btn-outline-secondary">{'Back'|adminT}</a>
			{if !$isNewCms && $cmsPage.url}
			<a href="{$cmsPage.url|escape}" class="btn btn-outline-dark" target="_blank" rel="noopener">{'View on site'|adminT}</a>
			{/if}
		</div>
	</form>
</div>

<script>
document.querySelectorAll('.cms-lang-slug').forEach(function (input) {
	input.addEventListener('input', function () {
		var lang = this.getAttribute('data-lang');
		var preview = document.querySelector('.cms-slug-preview[data-lang="' + lang + '"]');
		if (preview) preview.textContent = this.value;
	});
});
</script>
