<div class="fshop-recaptcha mb-3" data-recaptcha-form="{$formKey|escape}">
	{if $version == 'v2'}
	<div class="fshop-recaptcha-widget" id="fshop-recaptcha-{$formKey|escape}"></div>
	{else}
	<input type="hidden" name="recaptcha_token" class="fshop-recaptcha-token" value="">
	{/if}
</div>
