			</div>
		</div>
	</div>
</div>

<div class="cmdk-overlay" id="adminCmdk" hidden>
	<div class="cmdk-modal" role="dialog" aria-modal="true" aria-label="{'Search'|adminT}">
		<div class="cmdk-input-wrap">
			<i data-lucide="search"></i>
			<input type="text" id="adminCmdkInput" class="cmdk-input" placeholder="{'Search products, customers, orders…'|adminT}" autocomplete="off" spellcheck="false">
			<kbd class="cmdk-kbd">esc</kbd>
		</div>
		<div class="cmdk-list" id="adminCmdkList"></div>
		<div class="cmdk-footer">
			<span><kbd>↑↓</kbd> {'Navigate'|adminT}</span>
			<span><kbd>↵</kbd> {'Open'|adminT}</span>
			<span><kbd>esc</kbd> {'Close'|adminT}</span>
		</div>
	</div>
</div>

<div class="modal fade" id="admin-confirm-modal" tabindex="-1" aria-hidden="true" aria-labelledby="admin-confirm-title">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header py-2 bg-light">
				<h5 class="modal-title h6 mb-0 text-dark" id="admin-confirm-title">{'Confirm action'|adminT}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|adminT}"></button>
			</div>
			<div class="modal-body py-3" id="admin-confirm-message">
				{'Are you sure you want to perform this action?'|adminT}
			</div>
			<div class="modal-footer py-2">
				<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{'Cancel'|adminT}</button>
				<button type="button" class="btn btn-danger btn-sm" id="admin-confirm-btn">{'Yes, confirm'|adminT}</button>
			</div>
		</div>
	</div>
</div>

{if !empty($adminHooks.admin_footer)}
<div class="admin-layout-hook admin-layout-hook--footer">
	{$adminHooks.admin_footer nofilter}
</div>
{/if}
<p class="admin-footer-meta text-center">
	<a href="https://frisay.com/" target="_blank" rel="noopener">FriSay E-Commerce</a>
	<span class="text-muted"> · {$fshopName|escape} v{$fshopVersion|escape}</span>
</p>
<script src="{$adminJsDir|default:''}popper.min.js"></script>
<script src="{$adminJsDir|default:''}bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.469.0/dist/umd/lucide.min.js"></script>
<script src="{$adminJsDir|default:''}admin.js?v={$smarty.now}"></script>
<script>
window.__adminI18n = {$adminI18n|@json_encode nofilter};
window.__adminCmdk = {
	adminUrl: {$adminUrl|@json_encode nofilter},
	storeUrl: {$domain|@json_encode nofilter}
};
window.__adminLivePoll = {
	url: "{$adminUrl|escape:'javascript'}live-poll",
	adminUrl: {$adminUrl|@json_encode nofilter},
	intervalMs: 30000
};
if (window.lucide) { window.lucide.createIcons(); }
</script>
<script src="{$adminJsDir|default:''}admin-command.js?v={$smarty.now}"></script>
{if $marketplaceAdminAssets.js|@count}
{foreach $marketplaceAdminAssets.js as $marketplaceJs}
<script src="{$marketplaceJs}?v={$smarty.now}"></script>
{/foreach}
{/if}
{if $moduleAdminAssets.js|@count}
{foreach $moduleAdminAssets.js as $moduleJs}
<script src="{$moduleJs}?v={$smarty.now}"></script>
{/foreach}
{/if}
{if $adminUseCharts}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{$adminJsDir|default:''}admin-charts.js?v={$smarty.now}"></script>
{/if}
{if $adminUseOrderStatus}
<script>
window.__adminOrderStatus = {
	apiUrl: {$orderStatusApiUrl|@json_encode nofilter},
	token: {$adminToken|@json_encode nofilter}
};
</script>
<script src="{$adminJsDir|default:''}order-status.js?v={$smarty.now}"></script>
{/if}
{if $adminUseEditor}
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js"></script>
<script src="{$adminJsDir|default:''}admin-editor.js?v={$smarty.now}"></script>
{/if}
<script type="text/javascript">
if (window.history.replaceState) { window.history.replaceState(null, null, window.location.href); }
</script>
</body>
</html>
