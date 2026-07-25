<section class="container py-4 py-lg-5">
	<div class="row justify-content-center">
		<div class="col-lg-8">
			<article class="card border-0 shadow-sm">
				<div class="card-body p-4 p-lg-5">
					<p class="text-muted small mb-2">{$notification.date_formatted|escape}</p>
					<h1 class="h4 mb-3">{$notification.title|escape}</h1>
					<div class="notification-detail-body mb-4">
						{$notification.message|escape|nl2br nofilter}
					</div>
					<div class="d-flex flex-wrap gap-2">
						<a href="{$domain}my-account#notifications" class="btn btn-outline-secondary btn-sm">{'Notifications'|translate}</a>
					</div>
				</div>
			</article>
		</div>
	</div>
</section>
