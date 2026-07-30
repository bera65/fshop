{if $pageName != 'home'}</div>{/if}
    <footer>
        <div class="container footer-top">
            <div class="row g-5">
                <div class="col-lg-4 col-md-12">
                    <a href="{$domain}" class="footer-logo">{$siteName|escape}</a>
                    <p class="small opacity-75 mb-4" style="line-height: 1.8;">
                        {if $pageDesc}{$pageDesc|escape}{else}En taze ürünler, hızlı teslimat ve güvenli alışveriş deneyimi.{/if}
                    </p>
                    <div class="d-flex gap-3">
                        {if $facebookLink}<a href="{$facebookLink|escape}" class="social-circle" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>{/if}
                        {if $instagramLink}<a href="{$instagramLink|escape}" class="social-circle" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>{/if}
                        {if $xLink}<a href="{$xLink|escape}" class="social-circle" target="_blank" rel="noopener"><i class="bi bi-twitter-x"></i></a>{/if}
                        {if $youtubeLink}<a href="{$youtubeLink|escape}" class="social-circle" target="_blank" rel="noopener"><i class="bi bi-youtube"></i></a>{/if}
                    </div>
                </div>

                <div class="col-lg-2 col-md-4 col-6">
                    <h6 class="footer-title">{'Home Page'|translate}</h6>
                    <a href="{$domain}" class="footer-link">{'Home Page'|translate}</a>
                    <a href="{$domain}contact" class="footer-link">{'Contact'|translate}</a>
                    <a href="{$domain}special" class="footer-link">{'Specilas'|translate}</a>
                    <a href="{$domain}cart" class="footer-link">{'Cart'|translate}</a>
                </div>

                <div class="col-lg-2 col-md-4 col-6">
                    <h6 class="footer-title">Kategoriler</h6>
                    {foreach from=$menuCategories item=cat name=footerCat}
                    {if $smarty.foreach.footerCat.index < 6}
                    <a href="{$domain}{$cat.category_link|escape}" class="footer-link">{$cat.category_name|escape}</a>
                    {/if}
                    {/foreach}
                </div>

                <div class="col-lg-4 col-md-4">
                    <h6 class="footer-title">{'Contact'|translate}</h6>
                    {if $contactPhone}<p class="small opacity-75 mb-2"><i class="bi bi-telephone me-2"></i>{$contactPhone|escape}</p>{/if}
                    {if $contactEmail}<p class="small opacity-75 mb-2"><i class="bi bi-envelope me-2"></i>{$contactEmail|escape}</p>{/if}
                    {if $contactAddress}<p class="small opacity-75 mb-0"><i class="bi bi-geo-alt me-2"></i>{$contactAddress|escape}</p>{/if}
                </div>
            </div>
        </div>

        {if $newsletterApiUrl}
        <div class="container py-5">
            <div class="newsletter-box">
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <h5 class="fw-bold mb-1">Bültene katılın</h5>
                        <p class="small opacity-75 mb-0">Kampanya ve fırsatlardan haberdar olun.</p>
                    </div>
                    <form class="col-md-6" data-api-url="{$newsletterApiUrl|escape}" method="post" action="#">
                        <div class="input-group">
                            <input type="email" name="email" class="form-control bg-dark border-0 text-white py-3 px-4" placeholder="{'Your Email'|translate}" required style="border-radius: 12px 0 0 12px;">
                            <button class="btn btn-warning fw-bold px-4" style="border-radius: 0 12px 12px 0;" type="submit">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        {/if}

        <div class="footer-bottom">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; {$year} {$siteName|escape}. {'All rights reserved.'|translate}
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <a href="{$domain}contact" class="text-white-50 text-decoration-none me-3">{'Contact'|translate}</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    {include file='./plugin/cart.tpl'}
    {include file='./plugin/productModal.tpl'}
    <script src="{$js_dir}jquery-3.2.1.min.js"></script>
    <script src="{$js_dir}bootstrap.bundle.min.js"></script>
    <script src="{$js_dir}product-configurator.js"></script>
    <script src="{$js_dir}product-modal.js"></script>
    <script src="{$js_dir}style.js"></script>
    {foreach $moduleAssets.js as $moduleJs}
    <script src="{$moduleJs}"></script>
    {/foreach}
    {if $js}
    <script src="{$js_dir}{$js}"></script>
    {/if}
    <div id="tostAlert" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</body>
</html>
