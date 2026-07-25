<link rel="manifest" href="{$manifestUrl|escape}">
<meta name="theme-color" content="{$settings.theme_color|escape}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{$settings.short_name|default:$settings.app_name|escape}">
{if $appleIcon}
<link rel="apple-touch-icon" href="{$appleIcon|escape}">
{/if}
<script>
(function () {
	window.__mobilAppInstall = window.__mobilAppInstall || { prompt: null, ready: false };

	window.addEventListener('beforeinstallprompt', function (e) {
		e.preventDefault();
		window.__mobilAppInstall.prompt = e;
		window.__mobilAppInstall.ready = true;
		document.dispatchEvent(new CustomEvent('mobil-app:ready'));
	});

	window.addEventListener('appinstalled', function () {
		window.__mobilAppInstall.prompt = null;
		window.__mobilAppInstall.ready = false;
		document.dispatchEvent(new CustomEvent('mobil-app:installed'));
	});

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('#mobil-app-install');
		if (!btn || btn.hidden || btn.disabled) {
			return;
		}

		var installEvent = window.__mobilAppInstall && window.__mobilAppInstall.prompt;
		if (!installEvent) {
			return;
		}

		e.preventDefault();
		e.stopPropagation();
		btn.disabled = true;

		installEvent.prompt();

		Promise.resolve(installEvent.userChoice).finally(function () {
			window.__mobilAppInstall.prompt = null;
			window.__mobilAppInstall.ready = false;
			btn.disabled = false;
			document.dispatchEvent(new CustomEvent('mobil-app:installed'));
		});
	}, true);

	if ('serviceWorker' in navigator) {
		var swUrl = {$swUrl|@json_encode nofilter};
		var scopePath = {$scopePath|@json_encode nofilter};
		navigator.serviceWorker.register(swUrl, { scope: scopePath }).catch(function () {});
	}

	if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
		if ('serviceWorker' in navigator) {
			navigator.serviceWorker.ready.then(function (reg) {
				if (reg.active) {
					reg.active.postMessage({ type: 'I_AM_PWA' });
				}
			});
		}
	}
})();
</script>
{if $pushEnabled && $pushPublicKey && $isLoggedIn}
<template id="mobil-app-push-prompt-template">
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
</template>
<script>
window.__mobilAppPush = {
	enabled: true,
	publicKey: {$pushPublicKey|@json_encode nofilter},
	subscribeUrl: {$pushSubscribeUrl|@json_encode nofilter},
	csrfToken: {$csrfToken|@json_encode nofilter},
	isLoggedIn: true,
	promptDelayMs: 3000,
	dismissDays: 7
};
</script>
{elseif $pushEnabled && $pushPublicKey}
<script>
window.__mobilAppPush = {
	enabled: true,
	publicKey: {$pushPublicKey|@json_encode nofilter},
	subscribeUrl: {$pushSubscribeUrl|@json_encode nofilter},
	csrfToken: {$csrfToken|@json_encode nofilter},
	isLoggedIn: false,
	promptDelayMs: 3000,
	dismissDays: 7
};
</script>
{/if}
