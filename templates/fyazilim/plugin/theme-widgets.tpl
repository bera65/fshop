{if $gotoTop|default:'1' == '1'}
<button type="button" id="backToTopBtn" class="btn btn-primary rounded-circle shadow-lg position-fixed" style="bottom:24px;right:24px;z-index:1050;width:44px;height:44px;display:none;align-items:center;justify-content:center;" aria-label="Yukarı çık">
	<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="20" height="20"><polyline points="18 15 12 9 6 15"/></svg>
</button>
{/if}

{if $showCookie|default:'0' == '1'}
<div id="cookieBanner" class="position-fixed bottom-0 start-0 w-100 bg-dark text-white p-3 shadow-lg" style="z-index:1060;display:none;">
	<div class="fy-container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
		<span class="small" data-ftheme="cookie-text">{$cookieText|default:''|escape}</span>
		<button type="button" class="btn btn-primary btn-sm rounded-pill px-4" id="cookieAcceptBtn">{'Accept'|translate}</button>
	</div>
</div>
{/if}

<script>
(function () {
	function hidePagePreloader() {
		var loader = document.getElementById('pagePreloader');
		if (!loader || loader.dataset.hidden === '1') {
			return;
		}
		loader.dataset.hidden = '1';
		loader.classList.add('is-hidden');
		setTimeout(function () { loader.style.display = 'none'; }, 400);
	}

	window.addEventListener('load', hidePagePreloader);
	document.addEventListener('DOMContentLoaded', function () {
		setTimeout(hidePagePreloader, 4000);
	});
	var topBtn = document.getElementById('backToTopBtn');
	if (topBtn) {
		window.addEventListener('scroll', function () {
			topBtn.style.setProperty('display', window.scrollY > 300 ? 'flex' : 'none', 'important');
		});
		topBtn.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	}
	var cookieBanner = document.getElementById('cookieBanner');
	var cookieBtn = document.getElementById('cookieAcceptBtn');
	if (cookieBanner && cookieBtn) {
		try {
			if (!localStorage.getItem('fshop_cookie_ok')) {
				cookieBanner.style.display = 'block';
			}
		} catch (e) {}
		cookieBtn.addEventListener('click', function () {
			try { localStorage.setItem('fshop_cookie_ok', '1'); } catch (e) {}
			cookieBanner.style.display = 'none';
		});
	}
})();
</script>
