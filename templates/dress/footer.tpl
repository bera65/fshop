{if isset($authMode) && $authMode == 'gate'}
{elseif !isset($authMode)}
{if $pageName != 'home'}</div>{/if}
</section>
{include file='./partials/mobile-bottom-nav.tpl'}
{include file='./plugin/schema-jsonld.tpl'}
{include file='./_mini/footer1.tpl'}
{include file='./plugin/cart.tpl'}
{include file='./plugin/auth-modal.tpl'}
{if $hooks.footer}{$hooks.footer nofilter}{/if}
{elseif isset($authMode) && ($authMode == 'login' || $authMode == 'register' || $authMode == 'forgot' || $authMode == 'reset')}
{else}
{if $pageName != 'home'}</div>{/if}
</section>
<footer class="auth-simple-footer text-center py-4 mt-5 border-top text-muted small">
	&copy; {$smarty.now|date_format:"%Y"} {$siteName|escape}. {'All rights reserved'|translate}
</footer>
{/if}
<script type="text/javascript" src="{($js_dir|cat:'jquery-3.2.1.min.js')|asset_url}"></script>
<script type="text/javascript" src="{($js_dir|cat:'bootstrap.bundle.min.js')|asset_url}"></script>
<script type="text/javascript" src="{($js_dir|cat:'style.js')|asset_url}"></script>
<script type="text/javascript" src="{($js_dir|cat:'shopmore.js')|asset_url}"></script>
{if $recaptchaConfigJson}
<script>window.fshopRecaptcha={$recaptchaConfigJson nofilter};</script>
{/if}
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
{foreach $moduleAssets.js as $moduleJs}
<script src="{$moduleJs}"></script>
{/foreach}
{if $js}
	<script src="{($js_dir|cat:$js)|asset_url}"></script>
{/if}
{if $isLoggedIn}
<script src="{($js_dir|cat:'notifications.js')|asset_url}"></script>
{/if}
{if !$isLoggedIn}
<script src="{($js_dir|cat:'auth-pages.js')|asset_url}"></script>
<script src="{($js_dir|cat:'auth-modal.js')|asset_url}"></script>
{/if}
{include file='./_mini/priceAllert.tpl'}
<div id="tostAlert" class="sm-toast toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3500" data-title-success="{'Success!'|translate|escape}" data-title-danger="{'Error!'|translate|escape}" data-title-warning="{'Warning!'|translate|escape}" data-title-info="{'Info!'|translate|escape}">
	<div class="sm-toast__bar" aria-hidden="true"></div>
	<div class="sm-toast__content">
		<div class="sm-toast__title"></div>
		<div class="sm-toast__message toast-body"></div>
	</div>
	<button type="button" class="sm-toast__close" data-bs-dismiss="toast" aria-label="{'Close'|translate}">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
	</button>
</div>
</body>
</html>
