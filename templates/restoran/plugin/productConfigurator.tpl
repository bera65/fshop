{* Ortak ürün seçim kutusu — ürün sayfası ve hızlı görünüm modal *}

<div class="product-configurator" id="productConfigurator"

	data-product-id="{$product.id_product}"

	data-required-groups="{$variationGroups|@count}"

	data-required-option-groups="{$requiredOptionGroups|default:0}"

	data-select-hint="{'Select product options'|translate|escape}"

	data-out-hint="{'Out Of Stock'|translate|escape}"

	data-required-label="{'Required'|translate|escape}">

	{if $hasVariations}

	<div class="product-variations mb-3" id="productVariations">

		{foreach from=$variationGroups item=group name=varGroup}

		<div class="product-variation-group mb-3" role="radiogroup">

			<div class="product-variation-label d-flex justify-content-between align-items-center">

				<div>

					<span class="product-variation-name fw-semibold">{$group.name|escape}</span>

					<span class="product-variation-selected text-muted" data-group-label="{$group.name|escape}"></span>

				</div>

				<span class="badge bg-light text-muted border">{'Required'|translate}</span>

			</div>

			<div class="product-variation-options">

				{foreach $group.values as $val}

				<button type="button" role="radio" aria-checked="false" class="product-variation-option" data-group="{$group.name|escape}" data-value="{$val|escape}">{$val|escape}</button>

				{/foreach}

			</div>

		</div>

		{/foreach}

		<p class="product-variation-summary small text-muted mb-1 d-none" id="variationSummary"></p>

		<p class="small text-muted mb-0" id="variationHint">{'Select product options'|translate}</p>

		<input type="hidden" id="selectedVariationId" value="0">

	</div>

	<script type="application/json" id="variationItemsData">{$variationItemsJson nofilter}</script>

	{/if}



	{if $hasOptions}

	<div class="product-options mb-3" id="productOptions">

		{foreach from=$optionGroups item=group}

		<div class="product-option-group product-variation-group mb-3" role="radiogroup" data-required="{if $group.required}1{else}0{/if}">

			<div class="product-variation-label d-flex justify-content-between align-items-center">

				<div>

					<span class="product-variation-name fw-semibold">{$group.name|escape}</span>

					<span class="product-variation-selected text-muted product-option-selected" data-group-label="{$group.name|escape}"></span>

				</div>

				{if $group.required}

				<span class="badge bg-light text-muted border">{'Required'|translate}</span>

				{/if}

			</div>

			<div class="product-variation-options">

				{foreach $group.values as $val}

				<button type="button" role="radio" aria-checked="false" class="product-variation-option product-option-btn" data-group="{$group.name|escape}" data-value="{$val|escape}">{$val|escape}</button>

				{/foreach}

			</div>

		</div>

		{/foreach}

		<p class="small text-muted mb-0" id="optionHint">{'Select product options'|translate}</p>

	</div>

	{/if}



	{if $shortDescription}

	<p class="text-muted small mb-3 product-short-desc">{$shortDescription|escape}</p>

	{/if}



	<div class="product-buy-bar d-flex flex-wrap align-items-center justify-content-between gap-3">

		{if $inStock}

		<div class="d-flex align-items-center gap-3">

			<div class="qty-picker{if $hasVariations} d-none{/if}" id="qtyPicker">

				<button type="button" class="qty-btn" data-qty-action="decrease">-</button>

				<input type="text" value="1" id="qty-input" class="qty-input" readonly data-max="{$stock}">

				<button type="button" class="qty-btn" data-qty-action="increase">+</button>

			</div>

			<div class="product-total-label small text-muted">

				{'Total'|translate}: <strong class="text-dark" id="productTotalPrice">{Tools::displayPrice($price)}</strong>

			</div>

		</div>

		<button type="button" class="btn btn-primary px-4 addtocart{if $hasVariations} requires-variation{/if}{if $hasOptions} requires-options{/if}" data-id="{$product.id_product}" data-variation="0"{if $hasVariations || $hasOptions} disabled{/if}>

			<i class="bi bi-bag-plus me-1"></i>{'Add To Cart'|translate}

		</button>

		{else}

		<button type="button" class="btn btn-secondary" disabled>{'Out Of Stock'|translate}</button>

		{/if}

	</div>

</div>


