<section class="fy-section fy-reveal ftheme-block-wrap ftheme-banner-row-section" data-ftheme-banner-row="1">
	<div class="fy-container">
		<div class="ftheme-banner-row">
			{foreach $unit.banners as $banner}
			{assign var=bannerImg value=$banner.image|default:''}
			{if $bannerImg != '' && $bannerImg|substr:0:4 != 'http'}
				{assign var=bannerImg value=$domain|cat:$bannerImg}
			{/if}
			<div class="ftheme-banner-item" style="flex:0 0 {$banner.width|default:100}%;max-width:{$banner.width|default:100}%;" data-ftheme-block="{$banner.id|escape}">
				{if $banner.link|default:'' != ''}
				<a href="{$banner.link|escape}" class="ftheme-banner-card">
					{if $banner@first}
					<img src="{$bannerImg|escape}" alt="{$banner.label|default:'Banner'|escape}" fetchpriority="high" decoding="async">
					{else}
					<img src="{$bannerImg|escape}" alt="{$banner.label|default:'Banner'|escape}" loading="lazy" decoding="async">
					{/if}
				</a>
				{else}
				<div class="ftheme-banner-card">
					{if $banner@first}
					<img src="{$bannerImg|escape}" alt="{$banner.label|default:'Banner'|escape}" fetchpriority="high" decoding="async">
					{else}
					<img src="{$bannerImg|escape}" alt="{$banner.label|default:'Banner'|escape}" loading="lazy" decoding="async">
					{/if}
				</div>
				{/if}
			</div>
			{/foreach}
		</div>
	</div>
</section>
