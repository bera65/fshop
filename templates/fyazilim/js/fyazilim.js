(function () {
	'use strict';

	function initSliders() {
		document.querySelectorAll('[data-fy-slider]').forEach(function (root) {
			var track = root.querySelector('[data-fy-track]');
			if (!track) return;

			var prev = root.querySelector('[data-fy-prev]');
			var next = root.querySelector('[data-fy-next]');
			var step = function () {
				var item = track.querySelector('.fy-slider__item');
				return item ? item.getBoundingClientRect().width + 20 : 280;
			};

			if (prev) {
				prev.addEventListener('click', function () {
					track.scrollBy({ left: -step(), behavior: 'smooth' });
				});
			}
			if (next) {
				next.addEventListener('click', function () {
					track.scrollBy({ left: step(), behavior: 'smooth' });
				});
			}
		});
	}

	function initReveal() {
		var nodes = document.querySelectorAll('.fy-reveal');
		if (!('IntersectionObserver' in window)) {
			nodes.forEach(function (n) { n.classList.add('is-visible'); });
			return;
		}
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					io.unobserve(entry.target);
				}
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

		nodes.forEach(function (n) { io.observe(n); });
	}

	function initSearchSuggest() {
		if (!window.searchSuggestUrl) {
			return;
		}

		document.querySelectorAll('[data-fy-search]').forEach(function (wrap) {
			var input = wrap.querySelector('[data-fy-search-input]');
			var box = wrap.querySelector('[data-fy-search-results]');
			if (!input || !box) {
				return;
			}

			var timer = null;

			function close() {
				box.classList.remove('is-open');
				box.innerHTML = '';
				box.hidden = true;
			}

			function escapeHtml(value) {
				return String(value || '')
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;');
			}

			function render(items) {
				if (!items.length) {
					box.innerHTML = '<div class="fy-header-search__empty">Ürün bulunamadı</div>';
					box.hidden = false;
					box.classList.add('is-open');
					return;
				}

				var fallbackImg = 'https://placehold.co/80x80/e2e8f0/64748b?text=+';

				box.innerHTML = items.map(function (item) {
					var image = item.image || fallbackImg;

					return '<a class="fy-header-search__item" href="' + escapeHtml(item.url) + '" role="option">' +
						'<img class="fy-header-search__thumb" src="' + escapeHtml(image) + '" alt="" loading="lazy">' +
						'<div class="fy-header-search__body">' +
							'<div class="fy-header-search__name">' + escapeHtml(item.name) + '</div>' +
							(item.category ? '<div class="fy-header-search__meta">' + escapeHtml(item.category) + '</div>' : '') +
						'</div>' +
						'<div class="fy-header-search__price">' + escapeHtml(item.price) + '</div></a>';
				}).join('');
				box.hidden = false;
				box.classList.add('is-open');
			}

			input.addEventListener('input', function () {
				var q = input.value.trim();
				clearTimeout(timer);

				if (q.length < 2) {
					close();
					return;
				}

				timer = setTimeout(function () {
					fetch(window.searchSuggestUrl + '?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
						.then(function (r) { return r.json(); })
						.then(function (res) { render(res.items || []); })
						.catch(function () { close(); });
				}, 280);
			});

			document.addEventListener('click', function (e) {
				if (!wrap.contains(e.target)) {
					close();
				}
			});

			input.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') {
					close();
				}
			});
		});
	}

	function initHeaderSearchToggle() {
		var header = document.querySelector('.fy-header--with-search-icon');
		var openBtn = document.querySelector('[data-fy-search-open]');
		var slot = document.querySelector('[data-fy-search-slot]');

		if (!header || !openBtn || !slot) {
			return;
		}

		var input = slot.querySelector('[data-fy-search-input]');

		function closeSuggest() {
			var box = slot.querySelector('[data-fy-search-results]');
			if (!box) {
				return;
			}

			box.classList.remove('is-open');
			box.innerHTML = '';
			box.hidden = true;
		}

		function openSearch() {
			header.classList.add('fy-header--search-active');
			slot.hidden = false;
			openBtn.classList.add('is-active');
			openBtn.setAttribute('aria-expanded', 'true');

			if (input) {
				window.setTimeout(function () {
					input.focus();
				}, 50);
			}
		}

		function closeSearch() {
			header.classList.remove('fy-header--search-active');
			slot.hidden = true;
			openBtn.classList.remove('is-active');
			openBtn.setAttribute('aria-expanded', 'false');
			closeSuggest();

			if (input) {
				input.value = '';
			}
		}

		openBtn.addEventListener('click', function () {
			if (header.classList.contains('fy-header--search-active')) {
				closeSearch();
				return;
			}

			openSearch();
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && header.classList.contains('fy-header--search-active')) {
				closeSearch();
				openBtn.focus();
			}
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		initSliders();
		initReveal();
		initSearchSuggest();
		initHeaderSearchToggle();
	});
})();
