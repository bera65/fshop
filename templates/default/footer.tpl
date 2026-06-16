</div>
<footer id="footer" class="site-footer footer-premium">
	<div class="footer-main">
		<div class="container site-container">
			<div class="row g-4 g-lg-5">
				<div class="col-lg-4 footer-brand-col">
					<a href="{$domain}" title="{$siteName}" class="d-block">
						<img src="{$siteLogos.footer|escape}?v={$minute}" class="img-fluid" alt="{$siteName}" width="180px" height="auto" />
					</a>
					<p class="footer-desc">{$siteName|escape}, temalar, modüller ve dijital ürünler için güvenilir alışveriş platformudur. Lisanslı yazılım, hızlı teslimat ve destek ile yanınızdayız.</p>
					<div class="footer-social">
						<a href="#" class="footer-social-btn" title="Facebook" aria-label="Facebook">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-1.65 0-3 1.35-3 3v2H8v3h2v7h3v-7h2.5l.5-3H13V9.5c0-.28.22-.5.5-.5z"/></svg>
						</a>
						<a href="#" class="footer-social-btn" title="YouTube" aria-label="YouTube">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M21.6 7.2a2.5 2.5 0 0 0-1.76-1.77C18.08 5 12 5 12 5s-6.08 0-7.84.43A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.76 1.77C5.92 19 12 19 12 19s6.08 0 7.84-.43a2.5 2.5 0 0 0 1.76-1.77A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8zM10 15.5v-7l6 3.5-6 3.5z"/></svg>
						</a>
						<a href="#" class="footer-social-btn" title="Instagram" aria-label="Instagram">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
						</a>
					</div>
				</div>

				<div class="col-6 col-lg-3">
					<h6 class="footer-title">Kurumsal</h6>
					<ul class="footer-links footer-links-marked">
						{foreach $cmsFooterLinks as $cmsLink}
						<li><a href="{$cmsLink.url}">{$cmsLink.title|escape}</a></li>
						{/foreach}
						<li><a href="{$domain}contact">İletişim</a></li>
						<li><a href="{$domain}truck">Kargo Takip</a></li>
					</ul>
				</div>

				<div class="col-6 col-lg-2">
					<h6 class="footer-title">Popüler Kategoriler</h6>
					<ul class="footer-links footer-links-marked">
						{foreach $menuCategories as $cat}
						<li><a href="{$domain}{$cat.category_link}">{$cat.category_name|escape}</a></li>
						{/foreach}
						<li><a href="{$domain}special">Kampanyalar</a></li>
					</ul>
				</div>

				{if $hooks.footer}
				<div class="col-lg-3">
					{$hooks.footer nofilter}
				</div>
				{/if}
			</div>
		</div>
	</div>

	<div class="footer-bottom">
		<div class="container site-container">
			<div class="footer-bottom-inner d-flex flex-wrap justify-content-between align-items-center gap-3">
				<p class="footer-copy mb-0">&copy; {$year} {$siteName|escape}. Tüm hakları saklıdır.</p>
				<div class="footer-payments" aria-label="Ödeme yöntemleri">
					<span class="footer-pay-icon" title="Mastercard">
						<svg viewBox="0 0 38 24" xmlns="http://www.w3.org/2000/svg"><circle cx="15" cy="12" r="7" fill="currentColor" opacity=".85"/><circle cx="23" cy="12" r="7" fill="currentColor" opacity=".55"/></svg>
					</span>
					<span class="footer-pay-icon" title="Visa">
						<svg viewBox="0 0 38 24" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M16 16.5h-3.2l2-12.2h3.2l-2 12.2zm8.9-11.9c-.6-.2-1.6-.5-2.8-.5-3.1 0-5.3 1.6-5.3 4 0 1.7 1.6 2.7 2.8 3.3 1.2.6 1.6 1 1.6 1.5 0 .8-1 1.2-1.9 1.2-1.3 0-2-.2-3-.7l-.4-.2-.5 2.8c.8.3 2.2.6 3.7.6 3.3 0 5.4-1.6 5.5-4.1.1-1.4-.8-2.4-2.6-3.3-1.1-.6-1.7-1-1.7-1.6 0-.5.6-1.1 1.8-1.1 1 0 1.8.2 2.4.5l.3.1.5-2.7zM32 4.3h-2.5c-.8 0-1.4.2-1.7 1l-4.9 11.2h3.4l.7-1.9h4.1l.4 1.9H35l-3-12.2zm-3.9 7.9l1.7-4.6.9 4.6h-2.6zM13.2 4.3L10.1 16.5H7l1.6-12.2h4.6z"/></svg>
					</span>
					<span class="footer-pay-icon" title="American Express">
						<svg viewBox="0 0 38 24" xmlns="http://www.w3.org/2000/svg"><rect width="38" height="24" rx="3" fill="currentColor" opacity=".15"/><text x="5" y="15" fill="currentColor" font-size="7" font-weight="700" font-family="Arial,sans-serif">AMEX</text></svg>
					</span>
					<span class="footer-pay-icon footer-pay-troy" title="Troy">
						<svg viewBox="0 0 38 24" xmlns="http://www.w3.org/2000/svg"><text x="4" y="16" fill="currentColor" font-size="10" font-weight="700" font-family="Arial,sans-serif">troy</text></svg>
					</span>
				</div>
			</div>
		</div>
	</div>
</footer>
<div id="mobileMenu">
	<a href="{$domain}" title="Ana Sayfa">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-house-icon lucide-house"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
		<span>Ana Sayfa</span>
	</a>
	<a href="{$domain}special" title="Kampanyalar">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bell-icon lucide-bell"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
		<span>Kampanyalar</span>
	</a>
	<a title="Sepet" href="#" onclick="showCart(); return false;">
		<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-basket-icon lucide-shopping-basket"><path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4"/><path d="M4.5 15.5h15"/><path d="m5 11 4-7"/><path d="m9 11 1 9"/></svg>
		<span>Sepet</span>
		<span class="badge{if $cart.count == 0} d-none{/if}" id="mobileCartBadge">{$cart.count}</span>
	</a>
</div>

<script src="{$js_dir}popper.min.js"></script>
<script src="{$js_dir}bootstrap.min.js"></script>
<script src="{$js_dir}app.js?v={$minute}"></script>
{foreach $moduleAssets.js as $moduleJs}
<script src="{$moduleJs}?v={$minute}"></script>
{/foreach}
{if $js}
	<script src="{$js_dir}{$js}"></script>
{/if}

<div id="tostAlert" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
  <div class="d-flex">
	<div class="toast-body">
	  
	</div>
	<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
  </div>
</div>
<div id="attrLoading" style="display:none; text-align:center;">
  <div class="loader"></div>
</div>
</body>
</html>