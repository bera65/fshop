(function ($) {
	'use strict';

	var eyeIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>';
	var eyeOffIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.205"/><path d="m2 2 20 20"/></svg>';

	$(document).on('click', '.auth-password-toggle', function () {
		var $btn = $(this);
		var target = $btn.data('target');
		var $input = $(target);

		if (!$input.length) {
			return;
		}

		var isPassword = $input.attr('type') === 'password';
		$input.attr('type', isPassword ? 'text' : 'password');
		$btn.toggleClass('is-visible', isPassword);
		$btn.html(isPassword ? eyeOffIcon : eyeIcon);

		var showLabel = $btn.data('showLabel') || 'Show password';
		var hideLabel = $btn.data('hideLabel') || 'Hide password';
		$btn.attr('aria-label', isPassword ? hideLabel : showLabel);
	});
})(jQuery);
