(function () {
	'use strict';

	var scopeAll = document.getElementById('pctScopeAll');
	var scopeSelected = document.getElementById('pctScopeSelected');
	var productPicker = document.getElementById('pctProductPicker');
	var productSearch = document.getElementById('pctProductSearch');

	function syncScope() {
		if (!productPicker || !scopeSelected) {
			return;
		}

		var show = scopeSelected.checked;
		productPicker.classList.toggle('d-none', !show);
	}

	if (scopeAll) {
		scopeAll.addEventListener('change', syncScope);
	}

	if (scopeSelected) {
		scopeSelected.addEventListener('change', syncScope);
	}

	if (productSearch) {
		productSearch.addEventListener('input', function () {
			var q = productSearch.value.trim().toLowerCase();

			document.querySelectorAll('.pct-product-item').forEach(function (item) {
				var name = item.getAttribute('data-name') || '';
				item.classList.toggle('is-hidden', q !== '' && name.indexOf(q) === -1);
			});
		});
	}
})();
