<footer class="bg-brand-900 text-secondary mt-auto pt-5 pb-4 border-top border-dark" role="contentinfo">
    <div class="container custom-container">
        <div class="row g-4 text-small">
            
            <!-- 1. Kolon: Marka ve Hakkında -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 text-white fw-bold h4 mb-4">
                    <img src="{$domain}img/logoFooter.png" alt="{$siteName|escape}" height="65px"/>
                </div>
                <p class="mb-4 text-secondary small">
                    {'Footer description'|translate}
                </p>
                <div class="d-flex gap-3">
                    {if $facebookLink}
                    <a href="{$facebookLink|escape}" title="Facebook" target="_blank" rel="noopener noreferrer" class="bg-white bg-opacity-10 rounded-2 d-flex align-items-center justify-content-center text-white text-decoration-none hover-bg-primary transition" style="width: 40px; height: 40px;">
                        <span class="fw-bold small"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-facebook-icon lucide-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></span>
                    </a>
                    {/if}
                    {if $youtubeLink}
                    <a href="{$youtubeLink|escape}" target="_blank" title="Youtube" rel="noopener noreferrer" class="bg-white bg-opacity-10 rounded-2 d-flex align-items-center justify-content-center text-white text-decoration-none hover-bg-primary transition" style="width: 40px; height: 40px;">
                        <span class="fw-bold small"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-youtube-icon lucide-youtube"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/></svg></span>
                    </a>
                    {/if}
                    {if $instagramLink}
                    <a href="{$instagramLink|escape}" title="Instagram" target="_blank" rel="noopener noreferrer" class="bg-white bg-opacity-10 rounded-2 d-flex align-items-center justify-content-center text-white text-decoration-none hover-bg-primary transition" style="width: 40px; height: 40px;">
                        <span class="fw-bold small"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-instagram-icon lucide-instagram"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg></span>
                    </a>
                    {/if}
                    {if $xLink}
                    <a href="{$xLink|escape}" title="X" target="_blank" rel="noopener noreferrer" class="bg-white bg-opacity-10 rounded-2 d-flex align-items-center justify-content-center text-white text-decoration-none hover-bg-primary transition" style="width: 40px; height: 40px;">
                        <span class="fw-bold small"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4l11.5 16h4.5L8.5 4z"/><path d="M4 20L16.5 4"/></svg></span>
                    </a>
                    {/if}
                </div>
            </div>

            <!-- 2. Kolon: Kurumsal -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5 class="text-white fw-bold mb-4 h6">{'Corporate'|translate}</h5>
                <ul class="list-unstyled d-flex flex-column gap-2 small">
                    {foreach $cmsFooterLinks as $cmsLink}
                    <li><a href="{$cmsLink.url|escape}" title="{$cmsLink.title|escape}" class="text-decoration-none text-secondary hover-text-white d-flex align-items-center gap-2 transition">
                        <span class="bg-primary rounded-circle" style="width: 4px; height: 4px;"></span> {$cmsLink.title|escape}
                    </a></li>
                    {/foreach}
                    <li><a href="{$domain}contact" title="{'Contact Us'|translate}" class="text-decoration-none text-secondary hover-text-white d-flex align-items-center gap-2 transition">
                        <span class="bg-primary rounded-circle" style="width: 4px; height: 4px;"></span> {'Contact Us'|translate}
                    </a></li>
                    <li><a href="{$domain}truck" title="{'Order Traking'|translate}" class="text-decoration-none text-secondary hover-text-white d-flex align-items-center gap-2 transition">
                        <span class="bg-primary rounded-circle" style="width: 4px; height: 4px;"></span> {'Order Traking'|translate}
                    </a></li>
                </ul>
            </div>

            <!-- 3. Kolon: Popüler Kategoriler -->
            <div class="col-lg-3 col-md-6 col-6">
                <h5 class="text-white fw-bold mb-4 h6">{'Popular Categories'|translate}</h5>
                <ul class="list-unstyled d-flex flex-column gap-2 small">
                    {foreach $menuCategories as $cat name=footerCats}
                    {if $smarty.foreach.footerCats.iteration > 6}{break}{/if}
                    <li><a href="{$domain}{$cat.category_link|escape}" title="{$cat.category_name|escape}" class="text-decoration-none text-secondary hover-text-white d-flex align-items-center gap-2 transition">
                        <span class="bg-primary rounded-circle" style="width: 4px; height: 4px;"></span> {$cat.category_name|escape}
                    </a></li>
                    {/foreach}
                    <li><a href="{$domain}special" title="{'Specilas'|translate}" class="text-decoration-none text-secondary hover-text-white d-flex align-items-center gap-2 transition">
                        <span class="bg-primary rounded-circle" style="width: 4px; height: 4px;"></span> {'Specilas'|translate}
                    </a></li>
                </ul>
            </div>

            <!-- 4. Kolon: Bülten -->
            <div class="col-lg-3 col-md-6">
                <h5 class="text-white fw-bold mb-4 h6">{'Subscribe to newsletter'|translate}</h5>
                <p class="small text-secondary mb-3">{'Newsletter description'|translate}</p>
                <form id="footerNewsletterForm" data-api-url="{$newsletterApiUrl|escape}" method="post" action="#">
                    <div class="input-group">
                        <input type="email" name="email" class="form-control text-light placeholder-secondary small" placeholder="{'Your Email'|translate}" required>
                        <button class="btn bg-primary text-white fw-bold small" type="submit">{'Register'|translate}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Alt Telif ve Ödeme İkonları -->
        <div class="border-top border-secondary border-opacity-25 mt-5 pt-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 small" style="font-size: 11px;">
            <span>&copy; {$year} {$siteName|escape}. {'All rights reserved.'|translate}</span>
            <div class="d-flex gap-3 opacity-50">
                <img src="{$img_dir}odemeLogo.png" alt="{'Payment logos'|translate}" height="20px" width="auto" />
            </div>
        </div>
    </div>
	
</footer>
