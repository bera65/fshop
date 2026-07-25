(function () {
	'use strict';

	var cfg = window.CustomerNotifyPushConfig || {};

	if (!cfg.enabled || !cfg.loggedIn) {
		return;
	}

	// Aynı sayfada script iki kez yüklenirse ikinci koşuyu atla
	if (window.__fshopCnPushBooted) {
		return;
	}
	window.__fshopCnPushBooted = true;

	var storageKey = 'fshop_cn_device_key';
	var permKey = 'fshop_cn_perm_asked';
	var pollMs = Math.max(15000, parseInt(cfg.pollMs || 25000, 10) || 25000);
	var banner = document.getElementById('cnPushBanner');
	var enableBtn = document.getElementById('cnPushEnableBtn');
	var dismissBtn = document.getElementById('cnPushDismissBtn');
	var iconUrl = cfg.iconUrl || '';
	var useOneSignal = !!(cfg.oneSignal && cfg.oneSignal.appId);
	/** Sunucu OneSignal REST ile gönderebiliyorsa true; değilse yerel poll yedek kalır. */
	var serverPush = !!(cfg.oneSignal && cfg.oneSignal.serverPush);
	var pollTimer = null;

	function deviceKey() {
		try {
			var key = localStorage.getItem(storageKey);
			if (key) {
				return key;
			}
			key = 'd' + Math.random().toString(36).slice(2) + Date.now().toString(36);
			localStorage.setItem(storageKey, key);
			return key;
		} catch (e) {
			return 'session';
		}
	}

	function postForm(url, fields) {
		var body = new FormData();
		Object.keys(fields || {}).forEach(function (k) {
			body.append(k, fields[k]);
		});
		return fetch(url, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (res) { return res.json(); });
	}

	function hideBanner() {
		if (banner) {
			banner.classList.remove('is-visible');
			window.setTimeout(function () {
				banner.hidden = true;
			}, 280);
		}
		try {
			localStorage.setItem(permKey, '1');
		} catch (e) {}
	}

	function showBanner() {
		if (!banner) {
			return;
		}
		banner.hidden = false;
		window.requestAnimationFrame(function () {
			banner.classList.add('is-visible');
		});
	}

	function subscribeDevice() {
		if (!cfg.subscribeUrl) {
			return Promise.resolve();
		}
		return postForm(cfg.subscribeUrl, {
			op: 'enable',
			device_key: deviceKey(),
			subscription: useOneSignal ? 'onesignal' : ''
		});
	}

	function isAlreadyInitializedError(err) {
		var msg = String((err && err.message) || err || '');
		return /already initialized/i.test(msg);
	}

	function linkExternalId(OneSignal) {
		if (!cfg.userId || !OneSignal || typeof OneSignal.login !== 'function') {
			return Promise.resolve();
		}
		return OneSignal.login(String(cfg.userId)).catch(function (err) {
			console.warn('OneSignal.login failed', err);
		});
	}

	function optInPush(OneSignal) {
		try {
			if (OneSignal && OneSignal.User && OneSignal.User.PushSubscription && typeof OneSignal.User.PushSubscription.optIn === 'function') {
				return OneSignal.User.PushSubscription.optIn().catch(function () {});
			}
		} catch (e) {}
		return Promise.resolve();
	}

	function isOneSignalReady(OneSignal) {
		return !!(OneSignal && OneSignal.Notifications);
	}

	function initOneSignal() {
		if (!useOneSignal) {
			return Promise.resolve(null);
		}

		// Global tek init — footer/script çift yüklemede ikinci init engellenir
		if (window.__fshopOneSignalReady) {
			return window.__fshopOneSignalReady;
		}

		window.OneSignalDeferred = window.OneSignalDeferred || [];

		window.__fshopOneSignalReady = new Promise(function (resolve) {
			window.OneSignalDeferred.push(async function (OneSignal) {
				try {
					if (!isOneSignalReady(OneSignal) && !window.__fshopOneSignalInited) {
						var initOpts = {
							appId: cfg.oneSignal.appId,
							allowLocalhostAsSecureOrigin: true,
							notifyButton: { enable: false },
							promptOptions: {
								slidedown: { prompts: [] }
							}
						};

						if (cfg.oneSignal.safariWebId) {
							initOpts.safari_web_id = cfg.oneSignal.safariWebId;
						}

						if (cfg.oneSignal.serviceWorkerPath) {
							initOpts.serviceWorkerPath = cfg.oneSignal.serviceWorkerPath;
							initOpts.serviceWorkerParam = {
								scope: cfg.oneSignal.serviceWorkerScope || '/onesignal/'
							};
						}

						try {
							await OneSignal.init(initOpts);
							window.__fshopOneSignalInited = true;
						} catch (err) {
							if (isAlreadyInitializedError(err)) {
								// Dashboard snippet veya önceki sayfa yüklemesi init etmiş olabilir
								window.__fshopOneSignalInited = true;
							} else {
								throw err;
							}
						}
					}

					await linkExternalId(OneSignal);
					resolve(OneSignal);
				} catch (err) {
					console.warn('OneSignal init failed', err);
					// Init kırılsa bile login denemesi için SDK nesnesini ver
					if (isOneSignalReady(OneSignal)) {
						linkExternalId(OneSignal).finally(function () {
							resolve(OneSignal);
						});
						return;
					}
					resolve(null);
				}
			});
		});

		return window.__fshopOneSignalReady;
	}

	function requestOneSignalPermission() {
		return initOneSignal().then(function (OneSignal) {
			if (!OneSignal || !OneSignal.Notifications) {
				return false;
			}

			return OneSignal.Notifications.requestPermission().then(function () {
				var granted = !!(OneSignal.Notifications.permission || OneSignal.Notifications.permissionNative === 'granted');
				return optInPush(OneSignal).then(function () {
					return linkExternalId(OneSignal).then(function () {
						return granted;
					});
				});
			});
		});
	}

	function getRegistration() {
		if (!('serviceWorker' in navigator)) {
			return Promise.resolve(null);
		}
		return navigator.serviceWorker.ready.catch(function () {
			return null;
		});
	}

	function showLocal(item) {
		var title = item.title || 'Bildirim';
		var body = item.body || '';
		var url = item.url || cfg.homeUrl || '/';
		var options = {
			body: body,
			icon: iconUrl,
			badge: iconUrl,
			tag: 'cn-' + (item.id || Date.now()) + '-' + Date.now(),
			renotify: true,
			data: { url: url }
		};

		return getRegistration().then(function (reg) {
			if (reg && typeof reg.showNotification === 'function') {
				return reg.showNotification(title, options);
			}
			if ('Notification' in window && Notification.permission === 'granted') {
				var n = new Notification(title, options);
				n.onclick = function () {
					window.focus();
					window.location.href = url;
					n.close();
				};
			}
		});
	}

	function shouldPollLocal() {
		// OneSignal sunucu push hazırsa poll kapalı (çift bildirim olmasın).
		// REST key yoksa poll açık kalır — aksi halde hiç bildirim düşmez.
		return !serverPush;
	}

	function pollLocal() {
		if (!shouldPollLocal() || !cfg.pollUrl || document.hidden) {
			return;
		}
		if (!('Notification' in window) || Notification.permission !== 'granted') {
			return;
		}

		fetch(cfg.pollUrl, {
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
			.then(function (res) { return res.json(); })
			.then(function (data) {
				if (!data || !data.success || !data.items || !data.items.length) {
					return;
				}
				data.items.forEach(function (item) {
					showLocal(item);
				});
			})
			.catch(function () {});
	}

	function startLocalPoll() {
		if (!shouldPollLocal() || pollTimer) {
			return;
		}
		pollLocal();
		pollTimer = setInterval(pollLocal, pollMs);
		document.addEventListener('visibilitychange', function () {
			if (!document.hidden) {
				pollLocal();
			}
		});
	}

	function enableNotifications() {
		hideBanner();

		if (useOneSignal) {
			requestOneSignalPermission().then(function (granted) {
				subscribeDevice();
				if (granted && !serverPush) {
					startLocalPoll();
				}
			});
			return;
		}

		if (!('Notification' in window)) {
			alert('Tarayıcınız bildirim desteklemiyor.');
			return;
		}

		Notification.requestPermission().then(function (perm) {
			if (perm !== 'granted') {
				return;
			}
			subscribeDevice();
			startLocalPoll();
		});
	}

	if (enableBtn) {
		enableBtn.addEventListener('click', function (e) {
			e.preventDefault();
			enableNotifications();
		});
	}

	if (dismissBtn) {
		dismissBtn.addEventListener('click', function (e) {
			e.preventDefault();
			hideBanner();
		});
	}

	var asked = false;
	try {
		asked = localStorage.getItem(permKey) === '1';
	} catch (e) {}

	if (useOneSignal) {
		initOneSignal().then(function (OneSignal) {
			var needPrompt = true;
			var granted = false;

			if (OneSignal && OneSignal.Notifications) {
				var native = OneSignal.Notifications.permissionNative || '';
				if (native === 'granted' || OneSignal.Notifications.permission === true) {
					needPrompt = false;
					granted = true;
					optInPush(OneSignal).then(function () {
						return linkExternalId(OneSignal);
					}).then(function () {
						subscribeDevice();
					});
				} else if (native === 'denied') {
					needPrompt = false;
				}
			}

			if (needPrompt && !asked && banner) {
				showBanner();
			}

			if (granted && !serverPush) {
				startLocalPoll();
			}
		});
	} else if (
		banner
		&& 'Notification' in window
		&& Notification.permission === 'default'
		&& !asked
	) {
		showBanner();
	}

	if (!useOneSignal && 'Notification' in window && Notification.permission === 'granted') {
		subscribeDevice();
		startLocalPoll();
	}
})();
