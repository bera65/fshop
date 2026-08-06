{* Tema şeması seçenekleri — Admin > Temalar'dan gelen $themeOptions *}
<style id="theme-runtime-options">
{if $themeOptions.border_radius|default:'' == 'none'}
:root { --border-radius-val: 0px; }
{elseif $themeOptions.border_radius|default:'' == 'light'}
:root { --border-radius-val: 4px; }
{elseif $themeOptions.border_radius|default:'' == 'large'}
:root { --border-radius-val: 12px; }
{else}
:root { --border-radius-val: 6px; }
{/if}
.btn, .form-control, .form-select, .card, .product-card, .panel, .fy-cart-pill, .fy-nav__drop {
	border-radius: var(--border-radius-val, 6px) !important;
}
{if $themeOptions.enable_animations|default:'1' == '0'}
*:not(.offcanvas):not(.offcanvas-backdrop)::before,
*:not(.offcanvas):not(.offcanvas-backdrop)::after,
*:not(.offcanvas):not(.offcanvas-backdrop) {
	transition: none !important;
	animation: none !important;
}
/* Mobil menü her zaman kayarak açılsın/kapansın */
.fy-offcanvas.offcanvas {
	transition: transform 0.38s cubic-bezier(0.32, 0.72, 0, 1),
		visibility 0.38s cubic-bezier(0.32, 0.72, 0, 1) !important;
}
.offcanvas-backdrop {
	transition: opacity 0.38s ease !important;
}
{/if}
{if $themeOptions.sticky_header|default:'1' == '1'}
.fy-header {
	position: sticky !important;
	top: 0 !important;
	z-index: 1020 !important;
	background: var(--header-bg, #fff) !important;
	box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06) !important;
}
{/if}
{if $themeOptions.uppercase_menu|default:'0' == '1'}
.fy-nav a { text-transform: uppercase !important; letter-spacing: 0.02em; }
{/if}
{if $themeOptions.dropdown_subcategories|default:'1' == '0'}
.fy-nav__drop { display: none !important; }
{/if}
{if $themeOptions.social_icon_style|default:'' == 'simple'}
.fy-footer__social a, .fy-topbar__social a {
	background: transparent !important;
	border: none !important;
	padding: 0 !important;
	width: auto !important;
	height: auto !important;
}
{elseif $themeOptions.social_icon_style|default:'' == 'circle'}
.fy-footer__social a, .fy-topbar__social a { border-radius: 50% !important; }
{elseif $themeOptions.social_icon_style|default:'' == 'rounded'}
.fy-footer__social a, .fy-topbar__social a { border-radius: 12px !important; }
{else}
.fy-footer__social a, .fy-topbar__social a { border-radius: 4px !important; }
{/if}
</style>
