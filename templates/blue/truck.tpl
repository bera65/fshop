<div class="prime-container prime-page">

	<h1 class="prime-page__title">{'Track Order'|translate}</h1>



	<div class="prime-page-card prime-track-search">

		<form method="get" action="{$domain}truck" class="prime-track-form">

			<label class="form-label" for="trackReference">{'Order Number'|translate}</label>

			<div class="prime-track-form__row">

				<input type="text" name="reference" id="trackReference" class="form-control text-uppercase" placeholder="FS260604XXXX" value="{$reference|escape}" required>

				<button type="submit" class="prime-btn prime-btn--primary">{'Query'|translate}</button>

			</div>

			<p class="prime-page__hint">{'Track hint'|translate}</p>

		</form>

	</div>



	{if $trackError}

	<div class="alert alert-warning">{$trackError|escape}</div>

	{/if}



	{if $trackResult}

	<div class="prime-page-card">

		<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">

			<div>

				<p class="prime-page__label">{'Order Number'|translate}</p>

				<p class="prime-page__value">#{$trackResult.reference}</p>

			</div>

			<span class="badge bg-secondary align-self-start">{$trackResult.status_label}</span>

		</div>

		<p class="mb-2"><strong>{'Date'|translate}:</strong> {$trackResult.date_formatted}</p>



		{if isset($trackResult.total_formatted)}

		<p class="mb-2"><strong>{'Total'|translate}:</strong> {$trackResult.total_formatted}</p>

		<p class="mb-3"><strong>{'Payment'|translate}:</strong> {$trackResult.payment_label}</p>

		<a href="{$domain}my-account?order={$trackResult.id_order}" class="prime-btn prime-btn--primary prime-btn--sm">{'Order Detail'|translate}</a>

		{else}

		<p class="prime-page__hint mb-0">{'Login required for details'|translate}</p>

		{/if}

	</div>

	{/if}



	{if $recentOrders|@count}

	<div class="prime-page-card prime-page-card--soft">

		<h2 class="prime-page__subtitle">{'Recent Orders'|translate}</h2>

		{foreach $recentOrders as $o}

		<div class="prime-list-row">

			<div>

				<strong>#{$o.reference}</strong>

				<span class="text-muted small ms-2">{$o.date_formatted}</span>

			</div>

			<div class="d-flex align-items-center gap-2">

				<span class="badge bg-secondary">{$o.status_label}</span>

				<a href="{$domain}truck?reference={$o.reference}" class="prime-btn prime-btn--outline prime-btn--sm">{'Track'|translate}</a>

			</div>

		</div>

		{/foreach}

	</div>

	{/if}

</div>

