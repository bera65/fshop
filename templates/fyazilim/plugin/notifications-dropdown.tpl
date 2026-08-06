{if $isLoggedIn}
<div class="notification-dropdown-wrap" id="notificationDropdownWrap" data-empty="{'No notifications yet'|translate}">
	<button type="button" class="notification-bell-btn action-icon" id="notificationBellBtn" aria-expanded="false" aria-haspopup="true" title="{'Notifications'|translate}">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
			<path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
		</svg>
		<span class="notification-bell-badge{if $notificationCount <= 0} d-none{/if}" id="headerNotificationBadge">{$notificationCount}</span>
	</button>

	<div class="notification-dropdown" id="notificationDropdown" hidden>
		<div class="notification-dropdown__head">
			<strong>{'Notifications'|translate}</strong>
			<button type="button" class="btn btn-link btn-sm p-0{if $notificationCount <= 0} d-none{/if}" id="headerMarkAllRead">{'Mark all read'|translate}</button>
		</div>
		<div class="notification-dropdown__list" id="notificationDropdownList">
			{if $headerNotifications|@count}
				{foreach $headerNotifications as $n}
				<a href="{$domain}{$n.link|escape}" class="notification-dropdown__item{if !$n.is_read} is-unread{/if}" data-id="{$n.id_notification}">
					<strong class="notification-dropdown__title">{$n.title|escape}</strong>
					<span class="notification-dropdown__message">{$n.message|escape|truncate:90}</span>
					<span class="notification-dropdown__time">{$n.date_formatted}</span>
				</a>
				{/foreach}
			{else}
				<div class="notification-dropdown__empty" id="notificationDropdownEmpty">{'No notifications yet'|translate}</div>
			{/if}
		</div>
		<div class="notification-dropdown__foot">
			<a href="{$domain}my-account#notifications">{'View all notifications'|translate}</a>
		</div>
	</div>
</div>
{/if}
