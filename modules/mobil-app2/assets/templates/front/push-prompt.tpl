<div id="mobil-app-push-prompt" class="mobil-app-push" hidden aria-hidden="true">
	<div class="mobil-app-push__backdrop" data-push-dismiss></div>
	<div class="mobil-app-push__dialog" role="dialog" aria-modal="true" aria-labelledby="mobil-app-push-title">
		<button type="button" class="mobil-app-push__close" data-push-dismiss aria-label="Kapat">&times;</button>
		<div class="mobil-app-push__icon" aria-hidden="true">
			<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
				<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
				<path d="M13.73 21a2 2 0 0 1-3.46 0"/>
			</svg>
		</div>
		<h2 id="mobil-app-push-title" class="mobil-app-push__title">Sipariş bildirimleri</h2>
		<p class="mobil-app-push__text">
			<strong>{$siteName|escape}</strong> üzerinden kargo ve sipariş bildirimlerini anında almak ister misiniz?
		</p>
		<ul class="mobil-app-push__list">
			<li>Sipariş onayı ve hazırlık</li>
			<li>Kargoya verildi bildirimi</li>
			<li>Teslimat ve kampanya duyuruları</li>
		</ul>
		<div class="mobil-app-push__actions">
			<button type="button" class="mobil-app-push__accept" id="mobil-app-push-accept">
				Evet, bildirim almak istiyorum
			</button>
			<button type="button" class="mobil-app-push__dismiss" data-push-dismiss>
				Şimdi değil
			</button>
		</div>
		<p class="mobil-app-push__hint">Devam ettiğinizde tarayıcı bildirim izni isteyecektir.</p>
	</div>
</div>
