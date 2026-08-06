{* Flower footer *}
<footer class="fl-footer" role="contentinfo">
	<div class="fl-newsletter">
		<div class="fl-container fl-newsletter__row">
			<div class="fl-newsletter__copy">
				<strong class="fl-newsletter__title">{'Subscribe to newsletter'|translate}</strong>
				<p class="fl-newsletter__desc">{'Newsletter description'|translate}</p>
			</div>
			<form class="fl-newsletter__form" id="footerNewsletterForm" data-api-url="{$newsletterApiUrl|escape}" method="post" action="#">
				<input type="email" name="email" class="fl-newsletter__input" placeholder="{'Your Email'|translate}" required>
				<button type="submit" class="fl-newsletter__btn"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send-horizontal-icon lucide-send-horizontal"><path d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z"/><path d="M6 12h16"/></svg></button>
			</form>
		</div>
	</div>

	<div class="fl-footer__main">
		<div class="fl-container">
			<div class="row g-4">
				<div class="col-12 col-md-6 col-lg-4">
					<a href="{$domain}" class="fl-footer-brand" title="{$siteName|escape}">
						<img src="{$siteLogos.footer|escape}?v={$minute|default:1}" alt="{$siteName|escape}" class="fl-footer-brand__logo" onerror="this.style.display='none'">
					</a>
					{if $footerDescription|default:'' != ''}
					<p class="fl-footer-brand__text">{$footerDescription|escape}</p>
					{elseif $themeOptions.tagline|default:'' != ''}
					<p class="fl-footer-brand__text">{$themeOptions.tagline|escape}</p>
					{/if}
					<ul class="fl-contact-list">
						{if $contactPhone}
						<li>
							<a href="tel:{$contactPhoneTel|default:$contactPhone|escape}">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
								{$contactPhone|escape}
							</a>
						</li>
						{if $contactPhoneTel}
						<li>
							<a href="https://wa.me/{$contactPhoneTel|escape}" target="_blank" rel="noopener noreferrer">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
								{'Write on WhatsApp'|translate}
							</a>
						</li>
						{/if}
						{/if}
						{if $contactEmail}
						<li>
							<a href="mailto:{$contactEmail|escape}">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
								{$contactEmail|escape}
							</a>
						</li>
						{/if}
						{if $contactCity || $contactAddress}
						<li>
							<span>
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
								{if $contactAddress}{$contactAddress|escape}{if $contactCity}, {/if}{/if}{$contactCity|escape}
							</span>
						</li>
						{/if}
					</ul>
				</div>

				<div class="col-6 col-lg-2">
					<h4 class="fl-footer__title">{'Categories'|translate}</h4>
					<ul class="fl-footer__links">
						{foreach $menuCategories as $cat name=flFooterCats}
						{if $smarty.foreach.flFooterCats.iteration > 7}{break}{/if}
						<li><a href="{$domain}{$cat.category_link|escape}">{$cat.category_name|escape}</a></li>
						{/foreach}
					</ul>
				</div>

				<div class="col-6 col-lg-3">
					<h4 class="fl-footer__title">{'Specilas'|translate}</h4>
					<ul class="fl-footer__links">
						<li><a href="{$domain}special">{'Specilas'|translate}</a></li>
						{foreach $cmsFooterLinks as $cmsLink name=flCmsMid}
						{if $smarty.foreach.flCmsMid.iteration > 5}{break}{/if}
						<li><a href="{$cmsLink.url|escape}">{$cmsLink.title|escape}</a></li>
						{/foreach}
					</ul>
				</div>

				<div class="col-12 col-lg-3">
					<h4 class="fl-footer__title">{'Corporate'|translate}</h4>
					<ul class="fl-footer__links">
						<li><a href="{$domain}contact">{'Contact Us'|translate}</a></li>
						<li><a href="{$domain}truck">{'Order Traking'|translate}</a></li>
						{foreach $cmsFooterLinks as $cmsLink}
						<li><a href="{$cmsLink.url|escape}">{$cmsLink.title|escape}</a></li>
						{/foreach}
					</ul>
				</div>
			</div>
		</div>
	</div>

	<div class="fl-trust">
		<div class="fl-container fl-trust__row">
			<div class="fl-trust__item">
				<span class="fl-trust__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
				</span>
				<div>
					<strong>{'Same day delivery'|translate}</strong>
					<p>{'Same day delivery info'|translate}</p>
				</div>
			</div>
			<div class="fl-trust__item">
				<span class="fl-trust__icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-undo2-icon lucide-undo-2"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5a5.5 5.5 0 0 1-5.5 5.5H11"/></svg>
				</span>
				<div>
					<strong>{'Easy Returns'|translate}</strong>
					<p>{'You can easily return your order'|translate}</p>
				</div>
			</div>
			<div class="fl-trust__item">
				<span class="fl-trust__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
				</span>
				<div>
					<strong>{'Secure payment'|translate}</strong>
					<p>{'Secure payment info'|translate}</p>
				</div>
			</div>
		</div>
	</div>

	<div class="fl-footer__bottom">
		<div class="fl-container fl-footer__bottom-row">
			<span class="fl-footer__copy">&copy; {$year} {$siteName|escape}. {'All rights reserved.'|translate}</span>
			<div class="fl-footer__payments">
				<img src="{$img_dir}payment-logos.png" alt="{'Payment logos'|translate}" class="fl-footer__payments-img" width="220" height="28" loading="lazy" decoding="async" onerror="this.parentElement.style.display='none'">
			</div>
		</div>
	</div>
</footer>
