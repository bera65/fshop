(function () {
	'use strict';

	var widgetIds = {};
	var pendingForms = {};
	var scriptRequested = false;

	window.fshopRecaptchaOnload = function () {
		renderV2Widgets();
		resumePendingForms();
	};

	function getConfig() {
		return window.fshopRecaptcha || null;
	}

	function isFormEnabled(formKey) {
		var cfg = getConfig();
		return !!(cfg && cfg.active && cfg.forms && cfg.forms[formKey]);
	}

	function getVersion() {
		var cfg = getConfig();
		return cfg && cfg.version === 'v2' ? 'v2' : 'v3';
	}

	function getSiteKey() {
		var cfg = getConfig();
		return cfg && cfg.siteKey ? cfg.siteKey : '';
	}

	function formKeyFromElement(form) {
		if (!form) return '';
		if (form.id === 'authModalLoginForm') return 'login';
		if (form.id === 'authModalRegisterForm') return 'register';
		if (form.id === 'loginPageForm') return 'login';
		if (form.id === 'registerPageForm') return 'register';
		if (form.classList && form.classList.contains('admin-login-form')) return 'admin';
		if (form.action && String(form.action).indexOf('contact') !== -1) return 'contact';
		if (form.querySelector('[name="sendContact"]')) return 'contact';
		if (form.querySelector('[name="loginUser"]')) return 'login';
		if (form.querySelector('[name="registerUser"]')) return 'register';
		if (form.querySelector('[name="adminLogin"]')) return 'admin';
		return '';
	}

	function setToken(form, token) {
		var input = form.querySelector('.fshop-recaptcha-token');
		if (!input) {
			input = document.createElement('input');
			input.type = 'hidden';
			input.name = 'recaptcha_token';
			input.className = 'fshop-recaptcha-token';
			form.appendChild(input);
		}
		input.value = token || '';
	}

	function isV2ApiReady() {
		return typeof grecaptcha !== 'undefined' && typeof grecaptcha.render === 'function';
	}

	function isV3ApiReady() {
		return typeof grecaptcha !== 'undefined' && typeof grecaptcha.execute === 'function';
	}

	function getRecaptchaScriptUrl() {
		if (getVersion() === 'v2') {
			return 'https://www.google.com/recaptcha/api.js?onload=fshopRecaptchaOnload&render=explicit&hl=tr';
		}

		return 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(getSiteKey()) + '&hl=tr';
	}

	function removeRecaptchaScripts() {
		document.querySelectorAll('script[src*="google.com/recaptcha/api.js"], script[src*="recaptcha.net/recaptcha/api.js"]').forEach(function (script) {
			script.parentNode.removeChild(script);
		});
	}

	function findRecaptchaScript() {
		return document.querySelector('script[src*="google.com/recaptcha/api.js"], script[src*="recaptcha.net/recaptcha/api.js"]');
	}

	function scriptMatchesVersion(script) {
		if (!script) return false;
		var src = script.src || '';
		if (getVersion() === 'v2') {
			return src.indexOf('render=explicit') !== -1;
		}
		return src.indexOf('render=explicit') === -1 && src.indexOf('render=') !== -1;
	}

	function waitForApiReady(callback, maxTries) {
		var tries = 0;
		var limit = maxTries || 80;
		var ready = getVersion() === 'v2' ? isV2ApiReady : isV3ApiReady;

		if (ready()) {
			callback();
			return;
		}

		var timer = setInterval(function () {
			tries += 1;
			if (ready()) {
				clearInterval(timer);
				callback();
			} else if (tries >= limit) {
				clearInterval(timer);
			}
		}, 100);
	}

	function ensureRecaptchaScript(callback) {
		var cfg = getConfig();
		callback = callback || function () {};

		if (!cfg || !cfg.active) {
			callback();
			return;
		}

		var existing = findRecaptchaScript();
		if (existing && !scriptMatchesVersion(existing)) {
			removeRecaptchaScripts();
			existing = null;
		}

		if (existing) {
			waitForApiReady(callback);
			return;
		}

		if (scriptRequested) {
			waitForApiReady(callback);
			return;
		}

		scriptRequested = true;
		var script = document.createElement('script');
		script.src = getRecaptchaScriptUrl();
		script.async = true;
		script.defer = true;

		if (getVersion() === 'v3') {
			script.onload = function () {
				waitForApiReady(callback);
			};
			script.onerror = function () {
				scriptRequested = false;
			};
			document.head.appendChild(script);
			return;
		}

		var previousOnload = window.fshopRecaptchaOnload;
		window.fshopRecaptchaOnload = function () {
			if (typeof previousOnload === 'function') {
				previousOnload();
			}
			renderV2Widgets();
			resumePendingForms();
			callback();
		};

		script.onerror = function () {
			scriptRequested = false;
		};
		document.head.appendChild(script);
	}

	function renderV2Widgets() {
		if (!isV2ApiReady()) {
			return;
		}

		document.querySelectorAll('.fshop-recaptcha-widget').forEach(function (el) {
			var wrap = el.closest('.fshop-recaptcha');
			if (!wrap || el.getAttribute('data-rendered') === '1') return;

			var formKey = wrap.getAttribute('data-recaptcha-form') || '';
			if (!isFormEnabled(formKey)) return;

			var widgetId = grecaptcha.render(el, {
				sitekey: getSiteKey(),
				hl: 'tr'
			});

			el.setAttribute('data-widget-id', String(widgetId));
			el.setAttribute('data-rendered', '1');
		});
	}

	function getV2ResponseForForm(form) {
		if (!isV2ApiReady() || !form) return '';

		var widget = form.querySelector('.fshop-recaptcha-widget[data-widget-id]');
		if (!widget) return '';

		var widgetId = parseInt(widget.getAttribute('data-widget-id'), 10);
		if (isNaN(widgetId)) return '';

		return grecaptcha.getResponse(widgetId) || '';
	}

	function resetV2ForForm(form) {
		if (!isV2ApiReady() || !form) return;

		var widget = form.querySelector('.fshop-recaptcha-widget[data-widget-id]');
		if (!widget) return;

		var widgetId = parseInt(widget.getAttribute('data-widget-id'), 10);
		if (!isNaN(widgetId)) {
			grecaptcha.reset(widgetId);
		}
	}

	function executeV3(formKey, done) {
		waitForApiReady(function () {
			grecaptcha.ready(function () {
				grecaptcha.execute(getSiteKey(), { action: formKey || 'submit' })
					.then(function (token) {
						done(null, token);
					})
					.catch(function () {
						done(new Error('recaptcha execute failed'));
					});
			});
		});
	}

	function resumePendingForms() {
		Object.keys(pendingForms).forEach(function (key) {
			var item = pendingForms[key];
			delete pendingForms[key];
			if (item && item.form) {
				submitWithCaptcha(item.form, item.formKey, true);
			}
		});
	}

	function markBusy(form, busy) {
		if (!form) return;
		form.classList.toggle('fshop-recaptcha--busy', !!busy);
	}

	function submitWithCaptcha(form, formKey, allowResubmit) {
		if (!form || !isFormEnabled(formKey)) {
			if (allowResubmit) {
				form.submit();
			}
			return;
		}

		if (getVersion() === 'v2') {
			if (!isV2ApiReady()) {
				pendingForms[formKey] = { form: form, formKey: formKey };
				ensureRecaptchaScript(function () {
					renderV2Widgets();
					submitWithCaptcha(form, formKey, allowResubmit);
				});
				return;
			}

			var token = getV2ResponseForForm(form);
			if (!token) {
				alert('Lütfen robot olmadığınızı doğrulayın.');
				return;
			}

			setToken(form, token);

			if (form.id === 'authModalLoginForm' || form.id === 'authModalRegisterForm') {
				triggerAuthModalSubmit(form);
				return;
			}

			form.submit();
			return;
		}

		markBusy(form, true);
		executeV3(formKey, function (err, token) {
			markBusy(form, false);
			if (err || !token) {
				alert('Captcha doğrulanamadı. Lütfen tekrar deneyin.');
				return;
			}

			setToken(form, token);

			if (form.id === 'authModalLoginForm' || form.id === 'authModalRegisterForm') {
				triggerAuthModalSubmit(form);
				return;
			}

			form.submit();
		});
	}

	function triggerAuthModalSubmit(form) {
		if (typeof jQuery === 'undefined') return;

		var payload = {
			action: form.id === 'authModalRegisterForm' ? 'register' : 'login',
			token: typeof csrfToken !== 'undefined' ? csrfToken : ''
		};

		if (form.id === 'authModalLoginForm') {
			payload.login = jQuery('#authModalLoginPhone').val();
			payload.phone = payload.login;
			payload.password = jQuery('#authModalLoginPassword').val();
			payload.remember = jQuery('#authModalRemember').is(':checked') ? '1' : '0';
		} else {
			payload.full_name = jQuery('#authModalRegisterName').val();
			payload.phone = jQuery('#authModalRegisterPhone').val();
			payload.email = jQuery('#authModalRegisterEmail').val();
			payload.password = jQuery('#authModalRegisterPassword').val();
		}

		var token = getV2ResponseForForm(form);
		if (!token) {
			token = form.querySelector('.fshop-recaptcha-token');
			token = token ? token.value : '';
		}
		if (token) {
			payload.recaptcha_token = token;
		}

		var $alert = jQuery(form.id === 'authModalRegisterForm' ? '#authRegisterAlert' : '#authLoginAlert');
		var apiUrl = typeof authApiUrl !== 'undefined' ? authApiUrl : (typeof domain !== 'undefined' ? domain + 'api/auth.php' : '');

		jQuery.post(apiUrl, payload).done(function (res) {
			if (res.success) {
				window.location.reload();
				return;
			}
			resetV2ForForm(form);
			if ($alert.length) {
				$alert.removeClass('d-none alert-success').addClass('alert-danger').text(res.message || 'Error');
			}
		}).fail(function () {
			resetV2ForForm(form);
			if ($alert.length) {
				$alert.removeClass('d-none alert-success').addClass('alert-danger').text('Request failed');
			}
		});
	}

	document.addEventListener('submit', function (event) {
		var form = event.target;
		if (!form || form.tagName !== 'FORM') return;

		var formKey = formKeyFromElement(form);
		if (!formKey || !isFormEnabled(formKey)) return;

		event.preventDefault();
		event.stopImmediatePropagation();

		ensureRecaptchaScript(function () {
			if (getVersion() === 'v2') {
				renderV2Widgets();
			}
			submitWithCaptcha(form, formKey, false);
		});
	}, true);

	ensureRecaptchaScript(function () {
		if (getVersion() === 'v2') {
			renderV2Widgets();
		}
	});
})();
