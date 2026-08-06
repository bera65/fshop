<footer class="fy-footer" role="contentinfo">
	<div class="fy-container">
		<div class="fy-footer__grid">
			<div class="fy-footer__brand">
				<a href="{$domain}" class="fy-logo" title="Frisay">
					<img src="{$domain}img/logoFooter.png" alt="{$siteName}" />
				</a>
				<p data-ftheme="footer-text">{$fText|default:''}</p>
				{if $contactAddress}
				<p class="fy-footer__contact">{$contactAddress|escape}{if $contactCity}, {$contactCity|escape}{/if}</p>
				{/if}
				{if $contactEmail}
				<p class="fy-footer__contact"><a href="mailto:{$contactEmail|escape}">{$contactEmail|escape}</a></p>
				{/if}
				{if $contactPhone}
				<p class="fy-footer__contact"><a href="tel:{$contactPhoneTel|default:$contactPhone|escape}">{$contactPhone|escape}</a></p>
				{/if}
				<div class="fy-footer__social">
					{if $facebookLink}<a href="{$facebookLink|escape}" target="_blank" rel="noopener" aria-label="Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>{/if}
					{if $xLink}<a href="{$xLink|escape}" target="_blank" rel="noopener" aria-label="X"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4l11.5 16h4.5L8.5 4z"/><path d="M4 20L16.5 4"/></svg></a>{/if}
					{if $instagramLink}<a href="{$instagramLink|escape}" target="_blank" rel="noopener" aria-label="Instagram"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg></a>{/if}
					{if $youtubeLink}<a href="{$youtubeLink|escape}" target="_blank" rel="noopener" aria-label="YouTube"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/></svg></a>{/if}
				</div>
			</div>

			<div>
				<h5>Hızlı Menü</h5>
				<ul>
					<li><a href="{$domain}">Ana Sayfa</a></li>
					<li><a href="{$domain}special">{'Specilas'|translate}</a></li>
					<li><a href="{$domain}cart">{'Cart'|translate}</a></li>
					<li><a href="{$domain}my-account">{'My Account'|translate}</a></li>
				</ul>
			</div>

			<div>
				<h5>Hizmetlerimiz</h5>
				<ul>
					{foreach $menuCategories as $cat name=svcCats}
					{if $smarty.foreach.svcCats.iteration > 5}{break}{/if}
					<li><a href="{$domain}{$cat.category_link|escape}">{$cat.category_name|escape}</a></li>
					{/foreach}
				</ul>
			</div>

			<div class="fy-footer__cta">
				{if $hooks.footer}
				{$hooks.footer nofilter}
				{/if}
			</div>
		</div>

		<div class="fy-footer__bottom">
			<span>
				&copy; {$year} Frisay. {'All rights reserved.'|translate}
			</span>
			<div>
				<img src="{$img_dir}odemeLogo.png" alt="{'Payment logos'|translate}" height="22" width="auto" />
			</div>
		</div>
	</div>
</footer>
