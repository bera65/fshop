{* Shared channel badge: store | pos | marketplace *}
{if $channelType|default:'' == 'marketplace'}
<span class="adm-order-channel adm-order-channel--mp adm-order-channel--{$channelPlatform|default:'other'|escape}" title="{$channelLabel|default:''|escape}">
	{if $channelIconFile|default:''}
	<img src="{$domain}templates/admin/img/icons/{$channelIconFile|escape}" alt="{$channelLabel|default:''|escape}" class="adm-order-channel__img" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
	<span class="adm-order-channel__fallback" style="display:none;">{$channelIcon|default:'MP'|escape}</span>
	{else}
	<span class="adm-order-channel__fallback">{$channelIcon|default:'MP'|escape}</span>
	{/if}
	<span>{$channelLabel|default:''|escape}</span>
</span>
{elseif $channelType|default:'' == 'pos'}
<span class="adm-order-channel adm-order-channel--pos" title="{$channelLabel|default:'POS'|escape}">
	<i data-lucide="monitor-smartphone"></i>
	<span>{$channelLabel|default:'POS'|escape}</span>
</span>
{else}
<span class="adm-order-channel adm-order-channel--store" title="{$channelLabel|default:''|escape}">
	<i data-lucide="store"></i>
	<span>{$channelLabel|default:''|escape}</span>
</span>
{/if}
