<nav class="sm-bottom-nav d-lg-none" aria-label="{'Menu'|translate}">
	<a href="{$domain}" class="sm-bottom-nav__item{if $pageName == 'home'} is-active{/if}">
		<span class="sm-bottom-nav__icon">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
		</span>
		{'Home Page'|translate}
	</a>
	<a href="{$domain}favorites" class="sm-bottom-nav__item{if $pageName == 'favorites'} is-active{/if}">
		<span class="sm-bottom-nav__icon">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
		</span>
		{'Favorites'|translate}
	</a>
	<a href="{$domain}contact" class="sm-bottom-nav__item{if $pageName == 'contact'} is-active{/if}">
		<span class="sm-bottom-nav__icon">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
		</span>
		{'Contact'|translate}
	</a>
</nav>
