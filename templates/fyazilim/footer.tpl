{if $pageName != 'home'}
	</div>
{/if}
</section>
{include file="./_mini/footer{$ffoter|default:'1'}.tpl"}
{include file='./plugin/auth-modal.tpl'}
<script type="text/javascript" src="{($js_dir|cat:'jquery-3.2.1.min.js')|asset_url}"></script>
<script type="text/javascript" src="{($js_dir|cat:'bootstrap.bundle.min.js')|asset_url}"></script>
<script type="text/javascript" src="{($js_dir|cat:'style.js')|asset_url}"></script>
<script type="text/javascript" src="{($js_dir|cat:'fyazilim.js')|asset_url}"></script>
<script type="text/javascript" src="{($js_dir|cat:'custom.js')|asset_url}"></script>
{if $recaptchaConfigJson}
<script>window.fshopRecaptcha={$recaptchaConfigJson nofilter};</script>
{/if}
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
<script src="{($js_dir|cat:'auth-modal.js')|asset_url}"></script>
{/if}
{include file='./_mini/priceAllert.tpl'}
{include file='./plugin/theme-widgets.tpl'}
<div id="tostAlert" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
  <div class="d-flex">
	<div class="toast-body"></div>
	<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
  </div>
</div>
</body>
</html>
