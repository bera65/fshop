<style>
	body.sm-body#gate {
		margin: 0;
		min-height: 100vh;
		overflow: hidden;
		background: #1e293b;
	}
	body.sm-body#gate .auth-simple-header,
	body.sm-body#gate .auth-simple-footer,
	body.sm-body#gate .breadcrumb,
	body.sm-body#gate .sm-page-wrap {
		display: none !important;
	}
	body.sm-body#gate section.page {
		padding: 0 !important;
		margin: 0 !important;
	}
	.gate-shell {
		position: fixed;
		inset: 0;
		z-index: 2000;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 24px 16px 96px;
		box-sizing: border-box;
	}
	.gate-bg {
		position: absolute;
		inset: 0;
		background:
			linear-gradient(160deg, #0b4f8a 0%, #1e3a5f 42%, #0f172a 100%);
		pointer-events: none;
	}
	.gate-bg.has-image {
		background:
			linear-gradient(180deg, rgba(15, 23, 42, 0.35), rgba(15, 23, 42, 0.55)),
			var(--gate-bg-image) center / cover no-repeat;
		filter: blur(6px);
		transform: scale(1.06);
	}
	.gate-bg.has-image::after {
		content: "";
		position: absolute;
		inset: 0;
		background: rgba(30, 41, 59, 0.25);
	}
	.gate-card {
		position: relative;
		z-index: 1;
		width: 100%;
		max-width: 420px;
		background: #fff;
		border-radius: 18px;
		padding: 36px 32px 28px;
		box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
		text-align: center;
	}
	.gate-logo {
		display: block;
		max-width: 180px;
		max-height: 96px;
		width: auto;
		height: auto;
		margin: 0 auto 18px;
		object-fit: contain;
	}
	.gate-logo-fallback {
		margin: 0 auto 18px;
		font-size: 1.35rem;
		font-weight: 800;
		color: #0f172a;
		letter-spacing: -0.02em;
	}
	.gate-title {
		margin: 0 0 26px;
		color: #0b4f8a;
		font-size: 1.45rem;
		font-weight: 800;
		line-height: 1.25;
		letter-spacing: -0.02em;
	}
	.gate-btn {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 10px;
		width: 100%;
		padding: 14px 18px;
		border-radius: 10px;
		font-weight: 700;
		font-size: 1.05rem;
		text-decoration: none;
		margin-bottom: 12px;
		border: 0;
		transition: opacity .18s ease, transform .18s ease;
	}
	.gate-btn:hover {
		opacity: .92;
		color: #fff;
		transform: translateY(-1px);
	}
	.gate-btn svg {
		flex-shrink: 0;
	}
	.gate-btn-dark {
		background: #111827;
		color: #fff;
	}
	.gate-btn-teal {
		background: #2f7f8f;
		color: #fff;
	}
	.gate-features {
		margin-top: 22px;
		text-align: left;
	}
	.gate-feature-item {
		display: flex;
		align-items: flex-start;
		gap: 10px;
		margin-bottom: 12px;
		color: #4b5563;
		font-size: .95rem;
		font-weight: 500;
		line-height: 1.35;
	}
	.gate-feature-item:last-child {
		margin-bottom: 0;
	}
	.gate-feature-icon {
		width: 20px;
		height: 20px;
		border-radius: 50%;
		background: #22c55e;
		color: #fff;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
		margin-top: 1px;
	}
	.gate-feature-icon svg {
		width: 12px;
		height: 12px;
		display: block;
	}
	.gate-bottom {
		position: absolute;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 2;
		background: #0b4f8a;
		color: #fff;
		text-align: center;
		padding: 14px 16px 16px;
		font-size: .88rem;
		line-height: 1.45;
	}
	.gate-bottom__address {
		margin: 0 0 4px;
		font-weight: 500;
		opacity: .95;
	}
	.gate-bottom__copy {
		margin: 0;
		font-weight: 500;
	}
	.gate-bottom__phone {
		color: #facc15;
		font-weight: 700;
		text-decoration: none;
	}
	.gate-bottom__phone:hover {
		color: #fde047;
	}
	@media (max-width: 480px) {
		.gate-card {
			padding: 28px 20px 22px;
			border-radius: 14px;
		}
		.gate-title {
			font-size: 1.25rem;
		}
		.gate-shell {
			padding-bottom: 110px;
		}
	}
</style>

<div class="gate-shell" role="dialog" aria-modal="true" aria-label="{$gateTitle|escape}">
	<div class="gate-bg{if $gateBgExists} has-image{/if}"{if $gateBgExists} style="--gate-bg-image:url('{$domain}img/gate-bg.jpg')"{/if} aria-hidden="true"></div>

	<div class="gate-card">
		{if $siteLogos.header}
		<img src="{$siteLogos.header|escape}" alt="{$siteName|escape}" class="gate-logo">
		{else}
		<div class="gate-logo-fallback">{$siteName|escape}</div>
		{/if}

		<h1 class="gate-title">{$gateTitle|escape}</h1>

		<a href="{$domain}register" class="gate-btn gate-btn-dark">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
			{'Sign Up'|translate}
		</a>

		<a href="{$domain}login" class="gate-btn gate-btn-teal">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
			{'Member Sign In'|translate}
		</a>

		{if $gateFeatures|@count}
		<div class="gate-features">
			{foreach $gateFeatures as $feature}
			<div class="gate-feature-item">
				<span class="gate-feature-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
				</span>
				<span>{$feature|escape}</span>
			</div>
			{/foreach}
		</div>
		{/if}
	</div>

	<div class="gate-bottom">
		{if $gateAddress}
		<p class="gate-bottom__address">{$gateAddress|escape}</p>
		{elseif $contactAddress}
		<p class="gate-bottom__address">{$contactAddress|escape}{if $contactCity} {$contactCity|escape}{/if}</p>
		{/if}
		<p class="gate-bottom__copy">
			&copy; {$smarty.now|date_format:"%Y"} {$siteName|escape}
			{assign var=displayPhone value=$gatePhone|default:$contactPhone}
			{assign var=displayPhoneTel value=$gatePhoneTel|default:$contactPhoneTel}
			{if $displayPhone}
			|
			{if $displayPhoneTel}
			<a class="gate-bottom__phone" href="tel:{$displayPhoneTel|escape}">{$displayPhone|escape}</a>
			{else}
			<span class="gate-bottom__phone">{$displayPhone|escape}</span>
			{/if}
			{/if}
		</p>
	</div>
</div>
