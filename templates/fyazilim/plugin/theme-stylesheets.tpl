{if $themeCssBundleUrl}
<link rel="stylesheet" href="{$themeCssBundleUrl|escape}" />
{else}
<link rel="stylesheet" href="{($css_dir|cat:'colors.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'custom.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'style.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'pages.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'notifications.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'cart-modal.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'fyazilim.css')|asset_url}" />
{if $css}
<link rel="stylesheet" href="{($css_dir|cat:$css)|asset_url}" />
{/if}
{/if}
