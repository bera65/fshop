(function () {
	'use strict';

	var root = document.querySelector('[data-mobil-app-root]');
	var installBtn = document.getElementById('mobil-app-install');
	var iosBtn = document.getElementById('mobil-app-ios-hint');
	var iosText = document.getElementById('mobil-app-ios-text');
	var genericHint = document.getElementById('mobil-app-generic-hint');
	var failMessage = 'Uygulama indirilemedi. Lütfen daha sonra tekrar deneyin.';
	var installedKey = 'fshop_mobil_app_installed';

	function markInstalled() {
		try {
			sessionStorage.setItem(installedKey, '1');
		} catch (e) {
			// ignore
		}
		hideRoot();
	}

	function wasInstalled() {
		try {
			return sessionStorage.getItem(installedKey) === '1';
		} catch (e) {
			return false;
		}
	}

	function isStandalone() {
		return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
	}

	function isIos() {
		return /iphone|ipad|ipod/i.test(navigator.userAgent);
	}

	function isAndroid() {
		return /android/i.test(navigator.userAgent);
	}

	function isMobile() {
		return window.matchMedia('(max-width: 991px)').matches || isIos() || isAndroid();
	}

	function canNativeInstall() {
		return !!(window.__mobilAppInstall && window.__mobilAppInstall.ready && window.__mobilAppInstall.prompt);
	}

	function hideRoot() {
		if (root) {
			root.hidden = true;
			root.style.display = 'none';
		}
	}

	function hide(el) {
		if (el) {
			el.hidden = true;
		}
	}

	function show(el) {
		if (el) {
			el.hidden = false;
		}
	}

	function showFailMessage() {
		if (!genericHint) {
			return;
		}
		genericHint.textContent = failMessage;
		genericHint.hidden = false;
	}

	function updateUi() {
		if (!root) {
			return;
		}

		if (isStandalone() || wasInstalled()) {
			hideRoot();
			return;
		}

		root.hidden = false;
		root.style.display = '';

		if (!isMobile()) {
			hideRoot();
			return;
		}

		hide(genericHint);

		if (isIos()) {
			hide(installBtn);
			show(iosBtn);
			return;
		}

		show(installBtn);
		hide(iosBtn);
		hide(iosText);

		if (installBtn) {
			installBtn.classList.toggle('is-ready', canNativeInstall());
			installBtn.disabled = false;
		}
	}

	if (installBtn) {
		installBtn.addEventListener('click', function () {
			if (canNativeInstall()) {
				return;
			}

			showFailMessage();
		});
	}

	if (iosBtn && iosText) {
		iosBtn.addEventListener('click', function () {
			iosText.hidden = !iosText.hidden;
		});
	}

	document.addEventListener('mobil-app:ready', function () {
		hide(genericHint);
		updateUi();
	});

	document.addEventListener('mobil-app:installed', markInstalled);

	window.addEventListener('appinstalled', markInstalled);

	window.addEventListener('resize', updateUi);
	updateUi();
})();
