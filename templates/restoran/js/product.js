(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var root = document.querySelector('.product-configurator');

		if (root && window.ProductConfigurator) {
			ProductConfigurator.init(root);
		}
	});
})();
