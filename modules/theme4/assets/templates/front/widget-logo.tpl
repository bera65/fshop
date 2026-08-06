{assign var=logoSrc value=$image|default:''}
{if $logoSrc == '' && $t4Logo|default:'' != ''}
	{assign var=logoSrc value=$t4Logo}
{/if}
{if $logoSrc == '' && $t4FooterLogo|default:'' != ''}
	{assign var=logoSrc value=$t4FooterLogo}
{/if}
{if $logoSrc == '' && $siteLogos.header|default:'' != ''}
	{assign var=logoSrc value=$siteLogos.header}
{/if}

{if $logoSrc != ''}
<div class="t4-widget-logo">
	{if $link|default:'' != ''}
	<a href="{$link|escape}" class="t4-widget-logo__link" title="{$alt|default:$siteName|escape}">
		<img src="{$logoSrc|escape}" alt="{$alt|default:$siteName|escape}" class="t4-widget-logo__img" loading="lazy">
	</a>
	{else}
	<a href="{$domain}" class="t4-widget-logo__link" title="{$alt|default:$siteName|escape}">
		<img src="{$logoSrc|escape}" alt="{$alt|default:$siteName|escape}" class="t4-widget-logo__img" loading="lazy">
	</a>
	{/if}
	{if $caption|default:'' != ''}
	<p class="t4-widget-logo__caption">{$caption|escape}</p>
	{/if}
</div>
{/if}
