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
