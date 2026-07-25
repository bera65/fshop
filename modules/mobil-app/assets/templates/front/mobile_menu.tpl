<div class="mobil-app-menu" data-mobil-app-root>
	<p class="mobil-app-menu__section">Uygulama</p>
	<button type="button" class="mobil-app-menu__btn" id="mobil-app-install" data-mobil-app-install>
		<span class="mobil-app-menu__ico" aria-hidden="true">
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
		</span>
		<span class="mobil-app-menu__label">{$settings.menu_label|escape}</span>
	</button>
	<button type="button" class="mobil-app-menu__btn mobil-app-menu__btn--hint" id="mobil-app-ios-hint" data-mobil-app-ios hidden>
		<span class="mobil-app-menu__ico" aria-hidden="true">
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="14" height="20" x="5" y="2" rx="2"/><path d="M12 18h.01"/></svg>
		</span>
		<span class="mobil-app-menu__label">{$settings.menu_label|escape}</span>
	</button>
	<p class="mobil-app-menu__hint" id="mobil-app-ios-text" hidden>{$settings.menu_hint_ios|escape}</p>
	<p class="mobil-app-menu__hint" id="mobil-app-generic-hint" hidden>Uygulama indirilemedi. Lütfen daha sonra tekrar deneyin.</p>
</div>
