function updateQty(val) {
	const input = document.getElementById('qty-input');
	if (!input) return;

	const max = parseInt(input.dataset.max, 10) || 99;
	let current = parseInt(input.value, 10) || 1;
	const next = current + val;

	if (next >= 1 && next <= max) {
		input.value = next;
	}
}

document.querySelectorAll('.thumb-img').forEach(function (thumb) {
	thumb.addEventListener('click', function () {
		document.querySelectorAll('.thumb-img').forEach(function (item) {
			item.classList.remove('active');
		});
		thumb.classList.add('active');

		var mainImg = document.getElementById('main-display');
		var nextSrc = thumb.getAttribute('data-image') || thumb.src;

		if (mainImg && nextSrc) {
			mainImg.src = nextSrc;
		}
	});
});
