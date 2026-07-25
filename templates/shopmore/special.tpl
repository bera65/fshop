<div class="prime-container prime-page">
	<div class="panel">
		<div class="panel__body">
			<h1 class="prime-page__title">{'Specilas'|translate}</h1>

			<div class="prime-promo-cards mb-4">
				<div class="prime-promo-cards__item page-card">
					<i class="fa-solid fa-gift"></i>
					<div>
						<strong>{$freeShippingMin|escape} TL {'Free shipping over'|translate}</strong>
						<p class="mb-0 small text-muted">{'Free shipping cart info'|translate}</p>
					</div>
				</div>
				<div class="prime-promo-cards__item page-card">
					<i class="fa-solid fa-percent"></i>
					<div>
						<strong>{'Wire transfer extra discount'|translate}</strong>
						<p class="mb-0 small text-muted">{'Wire transfer discount info'|translate}</p>
					</div>
				</div>
			</div>

			<h2 class="prime-page__subtitle">{'Discounted Products'|translate}</h2>
			{assign var="listTitle" value=""}
			{assign var="emptyMessage" value="No discounted products yet."}

			{include file='./plugin/catalogToolbar.tpl'}

			{if !$products|@count}
			<div class="prime-empty">
				<p>{'No discounted products yet.'|translate}</p>
				<a href="{$domain}" class="prime-btn prime-btn--primary">{'Back to Home'|translate}</a>
			</div>
			{else}
			{include file='./productGrid.tpl' products=$products}
			{include file='./plugin/pagination.tpl'}
			{/if}
		</div>
	</div>
</div>
