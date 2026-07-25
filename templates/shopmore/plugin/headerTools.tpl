<div class="header-tools d-none d-lg-flex align-items-center">
	<a href="{$domain}favorites" class="header-tool" title="Favorilerim">
		<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/></svg>
		<span>Favorilerim</span>
	</a>

	<div class="header-tool-divider"></div>

	<div class="header-tool-dropdown">
		<button type="button" class="header-tool header-tool-trigger" id="accountMenuBtn" aria-expanded="false" aria-haspopup="true">
			<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
			<span>
				Hesabım
				{if $notificationCount > 0}<span class="header-tool__badge">{$notificationCount}</span>{/if}
			</span>
		</button>

		<div class="header-tool-menu" id="accountMenu">
			{if $isLoggedIn}
			<div class="header-tool-menu__user">
				<strong>{$customer.user_full_name|escape|truncate:28}</strong>
			</div>
			<a href="{$domain}my-account" class="header-tool-menu__item">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
				Hesabım
			</a>
			{else}
			<a class="header-tool-menu__item" href="{$domain}login" title="Giriş Yap">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
				Giriş Yap
			</a>
			<a class="header-tool-menu__item" href="{$domain}register" title="Üye Ol">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
				Üye Ol
			</a>
			{/if}

			<a href="{$domain}my-account" class="header-tool-menu__item">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
				Siparişlerim
			</a>
			<a href="{$domain}my-account#notifications" class="header-tool-menu__item">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
				Bildirimler ({$notificationCount})
			</a>
			<a href="{$domain}favorites" class="header-tool-menu__item">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
				Listelerim
			</a>
			<a href="{$domain}my-account#addresses" class="header-tool-menu__item">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
				Adreslerim
			</a>
			<a href="{$domain}contact" class="header-tool-menu__item">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
				Taleplerim
			</a>

			{if $isLoggedIn}
			<div class="header-tool-menu__divider"></div>
			<button type="button" class="header-tool-menu__item header-tool-menu__item--btn" id="logoutBtnHeader">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
				Çıkış Yap
			</button>
			{/if}
		</div>
	</div>

	<div class="header-tool-divider"></div>

	<a href="{$domain}cart" class="header-tool header-tool-trigger">
		<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
		<span>
			Sepet
			{if $cart.count > 0}<span id="items" class="header-tool__badge">{$cart.count}</span>{/if}
		</span>
	</a>
</div>
