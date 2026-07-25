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

<div class="admin-panel p-3">
	<h2 class="h6 mb-2">{'Page SEO settings'|adminT}</h2>
	<p class="text-muted small mb-4">
		{'Empty fields use the default title and description.'|adminT}
		{'Product, category and brand SEO is managed on their edit screens.'|adminT}
	</p>

	<form method="post">
		<input type="hidden" name="saveSeo" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		{foreach $seoPages as $pageId => $page}
		<div class="border rounded p-3 mb-3">
			<h3 class="h6 mb-3">{$page.label|escape}</h3>
			<div class="row g-3">
				<div class="col-md-6">
					<label class="form-label">{'Meta title'|adminT}</label>
					<input type="text" name="seo_{$pageId|escape}_title" class="form-control"
						value="{$seoValues[$pageId].title|escape}" maxlength="255"
						placeholder="{$page.default_title|escape}">
				</div>
				<div class="col-md-6">
					<label class="form-label">{'Meta description'|adminT}</label>
					<input type="text" name="seo_{$pageId|escape}_description" class="form-control"
						value="{$seoValues[$pageId].description|escape}" maxlength="512"
						placeholder="{$page.default_desc|escape}">
				</div>
			</div>
		</div>
		{/foreach}
		<button type="submit" class="btn btn-dark">{'Save SEO settings'|adminT}</button>
	</form>
</div>
