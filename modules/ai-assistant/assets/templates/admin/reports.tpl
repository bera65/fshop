{if !$aiReportConfigured}
<div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
	<span>{'Add an API key in AI Assistant settings before running reports.'|adminT}</span>
	<a href="{$aiReportSettingsUrl|escape}" class="btn btn-sm btn-outline-dark">{'AI Assistant settings'|adminT}</a>
</div>
{/if}

<div class="ai-reports-page">
	<div class="mb-4">
		<h1 class="h4 mb-1">{'AI Reports'|adminT}</h1>
		<p class="text-muted small mb-0">{'Generate sales, SEO and cancel/return insights powered by AI.'|adminT}</p>
	</div>

	<ul class="nav nav-tabs ai-reports-tabs mb-4" role="tablist">
		<li class="nav-item" role="presentation">
			<button class="nav-link active" id="ai-tab-sales" data-bs-toggle="tab" data-bs-target="#ai-panel-sales" type="button" role="tab">{'Sales reports'|adminT}</button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="ai-tab-seo" data-bs-toggle="tab" data-bs-target="#ai-panel-seo" type="button" role="tab">{'Product SEO reports'|adminT}</button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="ai-tab-cancel" data-bs-toggle="tab" data-bs-target="#ai-panel-cancel" type="button" role="tab">{'Cancel and return reports'|adminT}</button>
		</li>
	</ul>

	<div class="tab-content">
		<section class="tab-pane fade show active" id="ai-panel-sales" role="tabpanel" aria-labelledby="ai-tab-sales">
			<div class="admin-panel p-3 mb-3">
				<h2 class="h6 mb-3">{'Sales report'|adminT}</h2>
				<p class="small text-muted">{'Pick a date range and sales channel; AI summarizes orders, revenue and top products.'|adminT}</p>
				<form id="aiReportSalesForm" class="row g-3 align-items-end">
					<div class="col-md-3">
						<label class="form-label small" for="aiSalesDateFrom">{'From'|adminT}</label>
						<input type="date" class="form-control form-control-sm" id="aiSalesDateFrom" name="date_from" value="{$aiReportDateFrom|escape}">
					</div>
					<div class="col-md-3">
						<label class="form-label small" for="aiSalesDateTo">{'To'|adminT}</label>
						<input type="date" class="form-control form-control-sm" id="aiSalesDateTo" name="date_to" value="{$aiReportDateTo|escape}">
					</div>
					<div class="col-md-4">
						<label class="form-label small" for="aiSalesChannel">{'Marketplace / channel'|adminT}</label>
						<select class="form-select form-select-sm" id="aiSalesChannel" name="channel">
							{foreach $aiReportMarketplaces as $key => $label}
							<option value="{$key|escape}">{$label|escape}</option>
							{/foreach}
						</select>
					</div>
					<div class="col-md-2">
						<button type="submit" class="btn btn-dark btn-sm w-100" {if !$aiReportConfigured}disabled{/if}>{'Generate report'|adminT}</button>
					</div>
				</form>
				<p class="small text-muted mt-2 mb-0" id="aiSalesStatus"></p>
			</div>
		</section>

		<section class="tab-pane fade" id="ai-panel-seo" role="tabpanel" aria-labelledby="ai-tab-seo">
			<div class="admin-panel p-3 mb-3">
				<h2 class="h6 mb-3">{'Product SEO report'|adminT}</h2>
				<p class="small text-muted">{'Analyzes meta titles, descriptions and content gaps; suggests improvements per product.'|adminT}</p>
				<form id="aiReportSeoForm" class="row g-3 align-items-end">
					<div class="col-md-3">
						<label class="form-label small" for="aiSeoLimit">{'Product sample size'|adminT}</label>
						<select class="form-select form-select-sm" id="aiSeoLimit" name="limit">
							<option value="30">30</option>
							<option value="60" selected>60</option>
							<option value="90">90</option>
						</select>
					</div>
					<div class="col-md-3">
						<button type="submit" class="btn btn-dark btn-sm" {if !$aiReportConfigured}disabled{/if}>{'Analyze SEO'|adminT}</button>
					</div>
				</form>
				<p class="small text-muted mt-2 mb-0" id="aiSeoStatus"></p>
			</div>
		</section>

		<section class="tab-pane fade" id="ai-panel-cancel" role="tabpanel" aria-labelledby="ai-tab-cancel">
			<div class="admin-panel p-3 mb-3">
				<h2 class="h6 mb-3">{'Cancel and return report'|adminT}</h2>
				<p class="small text-muted">{'Analyzes cancellations and returns by date and channel; highlights patterns and prevention actions.'|adminT}</p>
				<form id="aiReportCancelForm" class="row g-3 align-items-end">
					<div class="col-md-3">
						<label class="form-label small" for="aiCancelDateFrom">{'From'|adminT}</label>
						<input type="date" class="form-control form-control-sm" id="aiCancelDateFrom" name="date_from" value="{$aiReportDateFrom|escape}">
					</div>
					<div class="col-md-3">
						<label class="form-label small" for="aiCancelDateTo">{'To'|adminT}</label>
						<input type="date" class="form-control form-control-sm" id="aiCancelDateTo" name="date_to" value="{$aiReportDateTo|escape}">
					</div>
					<div class="col-md-4">
						<label class="form-label small" for="aiCancelChannel">{'Marketplace / channel'|adminT}</label>
						<select class="form-select form-select-sm" id="aiCancelChannel" name="channel">
							{foreach $aiReportMarketplaces as $key => $label}
							<option value="{$key|escape}">{$label|escape}</option>
							{/foreach}
						</select>
					</div>
					<div class="col-md-2">
						<button type="submit" class="btn btn-dark btn-sm w-100" {if !$aiReportConfigured}disabled{/if}>{'Generate report'|adminT}</button>
					</div>
				</form>
				<p class="small text-muted mt-2 mb-0" id="aiCancelStatus"></p>
			</div>
		</section>
	</div>
</div>

<link rel="stylesheet" href="{$aiReportAssetCss|escape}?v={$smarty.now}">
<script src="{$domain}modules/ai-assistant/assets/js/modal.js?v={$smarty.now}"></script>
<script>
window.AiAssistantReports = {
	token: {$adminToken|@json_encode nofilter},
	configured: {if $aiReportConfigured}true{else}false{/if},
	settingsUrl: {$aiReportSettingsUrl|@json_encode nofilter},
	apiSales: {$aiReportApiSales|@json_encode nofilter},
	apiProductsSeo: {$aiReportApiProductsSeo|@json_encode nofilter},
	apiCancelReturns: {$aiReportApiCancelReturns|@json_encode nofilter}
};
</script>
<script src="{$aiReportAssetJs|escape}?v={$smarty.now}"></script>
