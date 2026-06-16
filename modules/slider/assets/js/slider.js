/**
 * FShop Slider Module
 */
(function () {
	'use strict';

	function initSlider(root) {
		var track = root.querySelector('.fshop-slider__track');
		var slides = Array.prototype.slice.call(root.querySelectorAll('.fshop-slider__slide'));
		var prevBtn = root.querySelector('.fshop-slider__arrow--prev');
		var nextBtn = root.querySelector('.fshop-slider__arrow--next');
		var currentEl = root.querySelector('.fshop-slider__current');
		var type = root.getAttribute('data-slider-type') || 'hero';
		var perView = parseInt(root.getAttribute('data-slider-per-view') || '1', 10);
		var index = 0;
		var timer = null;
		var autoplayMs = type === 'hero' ? 6000 : 0;

		if (!track || slides.length === 0) {
			return;
		}

		function getPerView() {
			if (type !== 'promo') {
				return 1;
			}

			return window.innerWidth >= 768 ? perView : 1;
		}

		function maxIndex() {
			return Math.max(0, slides.length - getPerView());
		}

		function updateHero() {
			for (var i = 0; i < slides.length; i++) {
				slides[i].classList.toggle('is-active', i === index);
			}

			if (currentEl) {
				currentEl.textContent = String(index + 1);
			}
		}

		function updatePromo() {
			var gap = 16;
			var slideWidth = slides[0].offsetWidth + gap;
			track.style.transform = 'translateX(-' + (index * slideWidth) + 'px)';
		}

		function render() {
			if (type === 'promo') {
				updatePromo();
			} else {
				updateHero();
			}
		}

		function goTo(newIndex) {
			index = newIndex;

			if (index < 0) {
				index = maxIndex();
			}

			if (index > maxIndex()) {
				index = 0;
			}

			render();
		}

		function next() {
			goTo(index + 1);
		}

		function prev() {
			goTo(index - 1);
		}

		function startAutoplay() {
			if (!autoplayMs || slides.length <= 1) {
				return;
			}

			stopAutoplay();
			timer = window.setInterval(next, autoplayMs);
		}

		function stopAutoplay() {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		}

		if (prevBtn) {
			prevBtn.addEventListener('click', function () {
				stopAutoplay();
				prev();
				startAutoplay();
			});
		}

		if (nextBtn) {
			nextBtn.addEventListener('click', function () {
				stopAutoplay();
				next();
				startAutoplay();
			});
		}

		root.addEventListener('mouseenter', stopAutoplay);
		root.addEventListener('mouseleave', startAutoplay);

		window.addEventListener('resize', function () {
			if (index > maxIndex()) {
				index = maxIndex();
			}

			render();
		});

		render();
		startAutoplay();
	}

	document.addEventListener('DOMContentLoaded', function () {
		var sliders = document.querySelectorAll('.fshop-slider');

		for (var i = 0; i < sliders.length; i++) {
			initSlider(sliders[i]);
		}
	});
})();
