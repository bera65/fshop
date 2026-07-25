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

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
	<div>
		<h2 class="h5 mb-1">{'Performance & cache'|adminT}</h2>
		<p class="text-muted small mb-0">{'Site speed, cache and debugging settings.'|adminT}</p>
	</div>
	<form method="post">
		<input type="hidden" name="clearPerformanceCache" value="1">
		<input type="hidden" name="token" value="{$adminToken}">
		<button type="submit" class="btn btn-outline-danger">{'Clear cache'|adminT}</button>
	</form>
</div>

<div class="row g-4 mb-4">
	<div class="col-md-4">
		<div class="admin-panel p-3 h-100">
			<p class="text-muted small mb-1">{'Template compilation'|adminT}</p>
			<p class="h4 mb-0">{$perfStats.compile_files|escape}</p>
			<p class="small text-muted mb-0">{$perfStats.compile_size_kb|escape} KB — <code>cache/force/</code></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="admin-panel p-3 h-100">
			<p class="text-muted small mb-1">{'Page cache'|adminT}</p>
			<p class="h4 mb-0">{$perfStats.page_files|escape}</p>
			<p class="small text-muted mb-0">{$perfStats.page_size_kb|escape} KB — <code>cache/pages/</code></p>
		</div>
	</div>
	<div class="col-md-4">
		<div class="admin-panel p-3 h-100">
			<p class="text-muted small mb-1">{'Server'|adminT}</p>
			<p class="mb-1">
				OPcache:
				{if $perfStats.opcache_enabled}<span class="badge text-bg-success">{'Enabled'|adminT}</span>{else}<span class="badge text-bg-secondary">{'Disabled'|adminT}</span>{/if}
			</p>
			<p class="mb-1">
				Gzip:
				{if $perfStats.zlib_enabled}<span class="badge text-bg-success">{'Supported'|adminT}</span>{else}<span class="badge text-bg-warning">{'None'|adminT}</span>{/if}
			</p>
			<p class="mb-0 small text-muted">{'CSS bundle cache'|adminT}: {$perfStats.css_bundle_files|default:0|escape} — <code>cache/assets/css/</code></p>
		</div>
	</div>
</div>

<form method="post">
	<input type="hidden" name="savePerformance" value="1">
	<input type="hidden" name="token" value="{$adminToken}">

	<div class="row g-4">
		<div class="col-lg-6">
			<div class="admin-panel p-3 h-100">
				<h3 class="h6 mb-3">{'Cache'|adminT}</h3>

				<div class="form-check form-switch mb-3">
					<input class="form-check-input" type="checkbox" role="switch" id="perf_cache"
						name="PERF_CACHE_ENABLED" value="1"
						{if $perfConfig.PERF_CACHE_ENABLED != '0'}checked{/if}>
					<label class="form-check-label" for="perf_cache">
						<strong>{'Template cache'|adminT}</strong>
						<span class="d-block text-muted small">{'Compiles and stores Smarty templates. When off, templates recompile on every request (slow, for theme development).'|adminT}</span>
					</label>
				</div>

				<div class="form-check form-switch mb-3">
					<input class="form-check-input" type="checkbox" role="switch" id="perf_page_cache"
						name="PERF_PAGE_CACHE" value="1"
						{if $perfConfig.PERF_PAGE_CACHE == '1'}checked{/if}>
					<label class="form-check-label" for="perf_page_cache">
						<strong>{'Fast page mode'|adminT}</strong>
						<span class="d-block text-muted small">{'Full page cache for guests. Cart count may lag; product/cart/checkout pages are not cached.'|adminT}</span>
					</label>
				</div>

				<div class="mb-0">
					<label class="form-label small" for="perf_page_cache_ttl">{'Page cache TTL (minutes)'|adminT}</label>
					<input type="number" class="form-control form-control-sm" style="max-width:120px"
						id="perf_page_cache_ttl" name="PERF_PAGE_CACHE_TTL"
						min="1" max="1440" value="{$perfConfig.PERF_PAGE_CACHE_TTL|escape}">
				</div>
			</div>
		</div>

		<div class="col-lg-6">
			<div class="admin-panel p-3 h-100">
				<h3 class="h6 mb-3">{'Acceleration'|adminT}</h3>

				<div class="form-check form-switch mb-3">
					<input class="form-check-input" type="checkbox" role="switch" id="perf_gzip"
						name="PERF_GZIP" value="1"
						{if $perfConfig.PERF_GZIP != '0'}checked{/if}>
					<label class="form-check-label" for="perf_gzip">
						<strong>{'Gzip compression'|adminT}</strong>
						<span class="d-block text-muted small">{'Compresses HTML output to reduce page size.'|adminT}</span>
					</label>
				</div>

				<div class="form-check form-switch mb-3">
					<input class="form-check-input" type="checkbox" role="switch" id="perf_html_minify"
						name="PERF_HTML_MINIFY" value="1"
						{if $perfConfig.PERF_HTML_MINIFY == '1'}checked{/if}>
					<label class="form-check-label" for="perf_html_minify">
						<strong>{'HTML minification'|adminT}</strong>
						<span class="d-block text-muted small">{'Removes extra whitespace. script/style blocks are preserved.'|adminT}</span>
					</label>
				</div>

				<div class="form-check form-switch mb-0">
					<input class="form-check-input" type="checkbox" role="switch" id="perf_css_bundle"
						name="PERF_CSS_BUNDLE" value="1"
						{if $perfConfig.PERF_CSS_BUNDLE == '1'}checked{/if}>
					<label class="form-check-label" for="perf_css_bundle">
						<strong>{'CSS bundle'|adminT}</strong>
						<span class="d-block text-muted small">{'Combines theme CSS files into one cached file. Fewer render-blocking requests (PageSpeed). Module CSS stays separate. Clear cache after theme edits if needed.'|adminT}</span>
					</label>
				</div>
			</div>
		</div>

		<div class="col-12">
			<div class="admin-panel p-3">
				<h3 class="h6 mb-3">{'Debugging'|adminT}</h3>

				<div class="row g-3 align-items-end">
					<div class="col-md-6">
						<label class="form-label small" for="perf_debug_mode">{'Error display'|adminT}</label>
						<select class="form-select" id="perf_debug_mode" name="perf_debug_mode">
							<option value="env"{if $perfDebugMode == 'env'} selected{/if}>{'Use env.php setting (currently:'|adminT} {if $perfEnvDebug}{'on'|adminT}{else}{'off'|adminT}{/if})</option>
							<option value="1"{if $perfDebugMode == '1'} selected{/if}>{'On — PHP errors on screen'|adminT}</option>
							<option value="0"{if $perfDebugMode == '0'} selected{/if}>{'Off — errors hidden, written to log file'|adminT}</option>
						</select>
					</div>
					<div class="col-md-6">
						<p class="small text-muted mb-0">
							{'Current status:'|adminT}
							{if $perfDebugActive}
							<span class="badge text-bg-warning">{'Debugging enabled'|adminT}</span>
							{else}
							<span class="badge text-bg-success">{'Production mode'|adminT}</span>
							{/if}
							— Log: <code>logs/php-error.log</code>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<button type="submit" class="btn btn-dark mt-4">{'Save settings'|adminT}</button>
</form>
