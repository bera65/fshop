{include file='admin/marketplace/_nav.tpl'}

<div class="admin-panel p-3">
	<h2 class="h5 mb-3">Cron Linkleri</h2>
	<p class="text-muted small mb-3">Otomatik sipariş ve soru çekme işlemleri için bu bağlantıları cron job olarak kullanabilirsiniz.</p>
	<div class="row g-3 mb-4">
		<div class="col-lg-6">
			<div class="border rounded p-3 bg-light h-100">
				<div class="fw-semibold mb-2">Trendyol</div>
				<div class="small text-muted mb-1">Sipariş cron</div>
				<code class="d-block text-break user-select-all mb-2">{$cronOrdersUrl|escape}</code>
				<div class="small text-muted mb-1">Soru / cevap cron</div>
				<code class="d-block text-break user-select-all">{$cronQuestionsUrl|escape}</code>
			</div>
		</div>
		<div class="col-lg-6">
			<div class="border rounded p-3 bg-light h-100">
				<div class="fw-semibold mb-2">Hepsiburada</div>
				<div class="small text-muted mb-1">Sipariş cron</div>
				<code class="d-block text-break user-select-all mb-2">{$cronOrdersUrlHb|escape}</code>
				<div class="small text-muted mb-1">Soru / cevap cron</div>
				<code class="d-block text-break user-select-all">{$cronQuestionsUrlHb|escape}</code>
			</div>
		</div>
		<div class="col-lg-6">
			<div class="border rounded p-3 bg-light h-100">
				<div class="fw-semibold mb-2">N11</div>
				<div class="small text-muted mb-1">Sipariş cron</div>
				<code class="d-block text-break user-select-all mb-2">{$cronOrdersUrlN11|escape}</code>
				<div class="small text-muted mb-1">Soru / cevap cron</div>
				<code class="d-block text-break user-select-all">{$cronQuestionsUrlN11|escape}</code>
			</div>
		</div>
	</div>

	<h2 class="h5 mb-3">Video Rehber</h2>
	<p class="text-muted small mb-3">Pazaryeri entegrasyonu hakkında video rehber.</p>
	<div class="ratio ratio-16x9" style="max-width: 720px;">
		<iframe
			src="https://www.youtube.com/embed/192lxcv9A8M?si=tntQ1Y6pUXZC9ae2"
			title="YouTube video player"
			allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
			referrerpolicy="strict-origin-when-cross-origin"
			allowfullscreen></iframe>
	</div>

	<h2 class="h5 mb-3 mt-4">Ek Video</h2>
	<p class="text-muted small mb-3">İkinci anlatım videosu.</p>
	<div class="ratio ratio-16x9" style="max-width: 720px;">
		<iframe
			src="https://www.youtube.com/embed/Wygoab3qBLE?si=PoQB5LAlpcdKRXYf"
			title="YouTube video player"
			allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
			referrerpolicy="strict-origin-when-cross-origin"
			allowfullscreen></iframe>
	</div>
</div>

{include file='admin/marketplace/_close.tpl'}
