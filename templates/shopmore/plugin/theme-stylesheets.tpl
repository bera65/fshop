{if $themeCssBundleUrl}
<link rel="stylesheet" href="{$themeCssBundleUrl|escape}" />
{else}
<link rel="stylesheet" href="{($css_dir|cat:'colors.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'custom.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'style.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'pages.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'notifications.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'cart-modal.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'shopmore.css')|asset_url}" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
{if $css}
<link rel="stylesheet" href="{($css_dir|cat:$css)|asset_url}" />
{/if}
{if $themeOptions.header == 'header2'}
<link rel="stylesheet" href="{($css_dir|cat:'header2.css')|asset_url}" />
{/if}
{/if}
