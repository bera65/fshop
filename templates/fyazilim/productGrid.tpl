{assign var="listProducts" value=$products|default:[]}
{if $listProducts|@count == 0 && isset($product)}
	{assign var="listProducts" value=$product}
{/if}
{assign var="cardStyle" value=$cardStyle|default:'software'}

{if $listProducts|@count > 0}
<div class="fy-products" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
	{foreach $listProducts as $p name=gridLoop}
	<article class="fy-card{if $cardStyle == 'theme'} fy-card--theme{/if}">
		{if $p.label}
			<span class="fy-card__badge">{$p.label|escape}</span>
		{elseif $p.has_discount}
			<span class="fy-card__badge">%{Tools::getDiscount($p.old_price, $p.price)}</span>
		{/if}
		<a class="fy-card__media" href="{$p.url}" title="{$p.product_name|escape}">
			{if $p@first}
			<img src="{$p.image_url}" alt="{$p.product_name|escape}" fetchpriority="high" decoding="async" width="280" height="160" />
			{else}
			<img src="{$p.image_url}" alt="{$p.product_name|escape}" loading="lazy" decoding="async" width="280" height="160" />
			{/if}
		</a>
		<div class="fy-card__body">
			<a href="{$p.url}" class="fy-card__title">{$p.product_name|escape}</a>
			<p class="fy-card__desc">
				{if $p.short_description}{$p.short_description|escape|truncate:70}{else}Frisay açık kaynak e-ticaret paketi{/if}
			</p>
			<div class="fy-card__price">
				{if $p.has_discount}<del>{$p.old_price_formatted}</del>{/if}
				{$p.price_formatted}
			</div>
			<div class="fy-card__actions">
				<a href="{$p.url}" class="fy-btn fy-btn--primary fy-btn--sm">İncele</a>
				{if $p.in_stock}
					<button type="button" class="fy-btn fy-btn--secondary fy-btn--sm addtocart" data-id="{$p.id_product}">Satın Al</button>
				{else}
					<a href="{$p.url}" class="fy-btn fy-btn--secondary fy-btn--sm">{'Out of Stock'|translate}</a>
				{/if}
			</div>
		</div>
	</article>
	{/foreach}
</div>
{else}
<div class="fy-empty">Henüz ürün bulunmuyor.</div>
{/if}
