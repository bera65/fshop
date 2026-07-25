<div class="prime-container prime-page">

	{if $view == 'detail'}

	<h1 class="prime-page__title">{'Return Request'|translate} #{$returnItem.id_return}</h1>

	{if $flash}
	<div class="alert alert-{$flashType|default:'info'}">{$flash|escape}</div>
	{/if}

	<div class="row g-4">
		<div class="col-lg-8">
			<div class="prime-page-card mb-4">
				<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
					<div>
						<strong>{'Order'|translate} #{$returnItem.reference|escape}</strong>
						<span class="text-muted small ms-2">{$returnItem.date_formatted}</span>
					</div>
					<span class="badge {$returnItem.status_badge}">{$returnItem.status_label|escape}</span>
				</div>
				<p class="mb-2"><strong>{'Your message'|translate}:</strong></p>
				<p class="mb-0 text-muted">{$returnItem.customer_message|escape|nl2br}</p>
			</div>

			{if $returnItem.images|@count}
			<div class="prime-page-card mb-4">
				<h2 class="prime-page__subtitle">{'Uploaded images'|translate}</h2>
				<div class="d-flex flex-wrap gap-2">
					{foreach $returnItem.images as $img}
					<a href="{$img.url|escape}" target="_blank" rel="noopener" class="d-block">
						<img src="{$img.url|escape}" alt="" class="rounded border" style="width:120px;height:120px;object-fit:cover;">
					</a>
					{/foreach}
				</div>
			</div>
			{/if}

			{if $returnItem.admin_message}
			<div class="prime-page-card{if $returnItem.admin_receipt_url} mb-4{else}{/if}">
				<h2 class="prime-page__subtitle">{'Store response'|translate}</h2>
				<p class="mb-0">{$returnItem.admin_message|escape|nl2br}</p>
				{if $returnItem.resolved_formatted}
				<p class="small text-muted mt-2 mb-0">{$returnItem.resolved_formatted}</p>
				{/if}
			</div>
			{/if}

			{if $returnItem.admin_receipt_url}
			<div class="prime-page-card">
				<h2 class="prime-page__subtitle">{'Return receipt'|translate}</h2>
				<a href="{$returnItem.admin_receipt_url|escape}" target="_blank" rel="noopener" class="d-inline-block">
					<img src="{$returnItem.admin_receipt_url|escape}" alt="{'Return receipt'|translate}" class="rounded border" style="max-width:100%;max-height:360px;object-fit:contain;">
				</a>
			</div>
			{/if}
		</div>

		<div class="col-lg-4">
			<div class="prime-page-card prime-page-card--soft">
				<p class="mb-2 d-flex justify-content-between"><span>{'Order total'|translate}</span><strong>{$returnItem.total_formatted}</strong></p>
				<p class="mb-0 d-flex justify-content-between"><span>{'Status'|translate}</span><strong>{$returnItem.status_label|escape}</strong></p>
			</div>
		</div>
	</div>

	<div class="mt-4 d-flex flex-wrap gap-2">
		<a href="{$domain}returns" class="prime-btn prime-btn--outline prime-btn--sm">← {'My Returns'|translate}</a>
		<a href="{$domain}my-account?order={$returnItem.id_order}" class="prime-btn prime-btn--outline prime-btn--sm">{'Order Detail'|translate}</a>
	</div>

	{else}

	<h1 class="prime-page__title">{'My Returns'|translate}</h1>

	{if $flash}
	<div class="alert alert-{$flashType|default:'info'}">{$flash|escape}</div>
	{/if}

	{if $returnDays > 0}
	<p class="text-muted">{'Return request window info'|translate|replace:'%days%':$returnDays}</p>
	{/if}

	{if $canCreate}
	<div class="mb-4">
		<a href="{$domain}return-request" class="prime-btn prime-btn--primary prime-btn--sm">{'New Return Request'|translate}</a>
	</div>
	{/if}

	{if !$returns|@count}
	<div class="prime-empty">
		<i class="fa-solid fa-rotate-left"></i>
		<p>{'No return requests yet'|translate}</p>
		{if $canCreate}
		<a href="{$domain}return-request" class="prime-btn prime-btn--primary prime-btn--sm">{'New Return Request'|translate}</a>
		{else}
		<a href="{$domain}my-account" class="prime-btn prime-btn--outline prime-btn--sm">{'My Orders'|translate}</a>
		{/if}
	</div>
	{else}
	<div class="order-list">
		{foreach $returns as $r}
		<div class="prime-page-card prime-list-card">
			<div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
				<div>
					<strong>#{$r.id_return}</strong>
					<span class="text-muted small ms-2">{'Order'|translate} #{$r.reference|escape}</span>
				</div>
				<span class="badge {$r.status_badge}">{$r.status_label|escape}</span>
			</div>
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
				<div class="small text-muted">{$r.date_formatted} · {$r.total_formatted}</div>
				<a href="{$domain}returns?id={$r.id_return}" class="prime-btn prime-btn--outline prime-btn--sm">{'View detail'|translate}</a>
			</div>
		</div>
		{/foreach}
	</div>
	{/if}

	{/if}

</div>
