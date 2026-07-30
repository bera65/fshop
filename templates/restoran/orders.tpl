<div class="prime-container prime-page">

	<h1 class="prime-page__title">{'My Orders'|translate}</h1>



	{if !$orders|@count}

	<div class="prime-empty">

		<i class="fa-solid fa-box"></i>

		<p>{'No orders yet'|translate}</p>

		<a href="{$domain}" class="prime-btn prime-btn--primary prime-btn--sm">{'Start shopping'|translate}</a>

	</div>

	{else}

	<div class="order-list">

		{foreach $orders as $o}

		<div class="prime-page-card prime-list-card">

			<div class="d-flex flex-wrap justify-content-between gap-2 mb-2">

				<div>

					<strong>#{$o.reference}</strong>

					<span class="text-muted small ms-2">{$o.date_formatted}</span>

				</div>

				<span class="badge bg-secondary">{$o.status_label}</span>

			</div>

			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

				<div class="small text-muted">

					{$o.payment_label} · {$o.total_formatted}

				</div>

				<a href="{$domain}order?id={$o.id_order}" class="prime-btn prime-btn--outline prime-btn--sm">{'Order Detail'|translate}</a>

			</div>

		</div>

		{/foreach}

	</div>

	{/if}

</div>

