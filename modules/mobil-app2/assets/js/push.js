(function () {
	'use strict';

	var config = window.__mobilAppPush;

	if (!config || !config.enabled || !config.publicKey || !config.subscribeUrl) {
		return;
	}

	if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
		return;
	}

	if (!config.isLoggedIn) {
		return;
	}

	var modal = null;
	var acceptBtn = null;
	var dismissMs = (config.dismissDays || 7) * 86400000;
	var storageDismissed = 'fshop_push_dismissed_at';
	var storageSubscribed = 'fshop_push_subscribed';
	var swRegistration = null;
	var modalOpen = false;

	function mountModal() {
		modal = document.getElementById('mobil-app-push-prompt');

		if (modal) {
			acceptBtn = document.getElementById('mobil-app-push-accept');
			return modal;
		}

		var tpl = document.getElementById('mobil-app-push-prompt-template');

		if (tpl && tpl.content && tpl.content.firstElementChild) {
			document.body.appendChild(tpl.content.cloneNode(true));
			modal = document.getElementById('mobil-app-push-prompt');
			acceptBtn = document.getElementById('mobil-app-push-accept');
		}

		return modal;
	}

	function urlBase64ToUint8Array(base64String) {
		var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
		var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
		var rawData = window.atob(base64);
		var outputArray = new Uint8Array(rawData.length);

		for (var i = 0; i < rawData.length; ++i) {
			outputArray[i] = rawData.charCodeAt(i);
		}

		return outputArray;
	}

	function postJson(url, body) {
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-Token': config.csrfToken || '',
			},
			body: JSON.stringify(body),
		}).then(function (res) {
			return res.json().catch(function () {
				return { success: false };
			});
		});
	}

	function saveSubscription(subscription) {
		var json = subscription.toJSON ? subscription.toJSON() : subscription;

		return postJson(config.subscribeUrl, json).then(function (result) {
			if (result && result.success) {
				try {
					localStorage.setItem(storageSubscribed, '1');
					localStorage.removeItem(storageDismissed);
				} catch (e) {
					// ignore
				}
			}

			return result;
		});
	}

	function resubscribeFresh(registration) {
		return registration.pushManager.getSubscription().then(function (existing) {
			var createSub = function () {
				return registration.pushManager.subscribe({
					userVisibleOnly: true,
					applicationServerKey: urlBase64ToUint8Array(config.publicKey),
				});
			};

			if (existing) {
				return existing.unsubscribe().catch(function () {}).then(createSub);
			}

			return createSub();
		}).then(function (subscription) {
			return saveSubscription(subscription).then(function () {
				return subscription;
			});
		});
	}

	function subscribe(registration) {
		return registration.pushManager.getSubscription().then(function (existing) {
			if (existing) {
				return saveSubscription(existing).then(function () {
					return existing;
				});
			}

			return registration.pushManager.subscribe({
				userVisibleOnly: true,
				applicationServerKey: urlBase64ToUint8Array(config.publicKey),
			}).then(function (subscription) {
				return saveSubscription(subscription).then(function () {
					return subscription;
				});
			});
		});
	}

	function isDismissedRecently() {
		try {
			var raw = localStorage.getItem(storageDismissed);

			if (!raw) {
				return false;
			}

			var ts = parseInt(raw, 10);

			return !isNaN(ts) && Date.now() - ts < dismissMs;
		} catch (e) {
			return false;
		}
	}

	function markDismissed() {
		try {
			localStorage.setItem(storageDismissed, String(Date.now()));
		} catch (e) {
			// ignore
		}
	}

	function openModal() {
		if (!modal || modalOpen) {
			return;
		}

		modal.hidden = false;
		modal.setAttribute('aria-hidden', 'false');
		modal.classList.add('is-visible');
		modalOpen = true;
		document.body.classList.add('mobil-app-push-open');
	}

	function closeModal() {
		if (!modal) {
			return;
		}

		modal.classList.remove('is-visible');
		modal.hidden = true;
		modal.setAttribute('aria-hidden', 'true');
		modalOpen = false;
		document.body.classList.remove('mobil-app-push-open');
	}

	function shouldShowModal() {
		if (!modal) {
			return false;
		}

		if (Notification.permission !== 'default') {
			return false;
		}

		if (isDismissedRecently()) {
			return false;
		}

		try {
			if (localStorage.getItem(storageSubscribed) === '1') {
				return false;
			}
		} catch (e) {
			// ignore
		}

		return true;
	}

	function requestPermissionAndSubscribe() {
		if (!swRegistration) {
			return Promise.resolve(null);
		}

		return Notification.requestPermission().then(function (permission) {
			if (permission !== 'granted') {
				if (permission === 'denied') {
					markDismissed();
				}
				return null;
			}

			return resubscribeFresh(swRegistration);
		});
	}

	function onAcceptClick() {
		if (!acceptBtn) {
			return;
		}

		acceptBtn.disabled = true;

		requestPermissionAndSubscribe()
			.then(function (subscription) {
				closeModal();

				if (subscription) {
					document.dispatchEvent(new CustomEvent('mobil-app:push-subscribed'));
				}
			})
			.catch(function () {
				closeModal();
			})
			.finally(function () {
				acceptBtn.disabled = false;
			});
	}

	function bindModalEvents() {
		if (!modal) {
			return;
		}

		modal.querySelectorAll('[data-push-dismiss]').forEach(function (el) {
			el.addEventListener('click', function () {
				markDismissed();
				closeModal();
			});
		});

		if (acceptBtn) {
			acceptBtn.addEventListener('click', onAcceptClick);
		}
	}

	function init() {
		mountModal();
		bindModalEvents();

		navigator.serviceWorker.ready.then(function (registration) {
			swRegistration = registration;

			if (Notification.permission === 'granted') {
				subscribe(registration).catch(function () {
					resubscribeFresh(registration).catch(function () {});
				});
				return;
			}

			if (Notification.permission === 'denied' || !modal) {
				return;
			}

			if (!shouldShowModal()) {
				return;
			}

			setTimeout(function () {
				if (shouldShowModal()) {
					openModal();
				}
			}, config.promptDelayMs || 3000);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
