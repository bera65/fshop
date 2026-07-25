{if $cnPushEnabled && $cnPushLoggedIn}
<div id="cnPushBanner" class="cn-push-prompt" hidden role="dialog" aria-labelledby="cnPushPromptTitle" aria-modal="false">
	<div class="cn-push-prompt__card">
		<div class="cn-push-prompt__icon" aria-hidden="true">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
		</div>
		<div class="cn-push-prompt__body">
			<p class="cn-push-prompt__title" id="cnPushPromptTitle">{$cnPushText|escape}</p>
		</div>
		<div class="cn-push-prompt__actions">
			<button type="button" class="cn-push-prompt__btn cn-push-prompt__btn--yes" id="cnPushEnableBtn">{$cnPushEnableLabel|escape}</button>
			<button type="button" class="cn-push-prompt__btn cn-push-prompt__btn--no" id="cnPushDismissBtn">{$cnPushDismissLabel|escape}</button>
		</div>
	</div>
</div>
{literal}
<style>
.cn-push-prompt{
	position:fixed;
	top:20px;
	left:50%;
	transform:translateX(-50%) translateY(-12px);
	z-index:1090;
	width:min(520px, calc(100vw - 24px));
	opacity:0;
	pointer-events:none;
	transition:opacity .28s ease, transform .28s ease;
}
.cn-push-prompt.is-visible{
	opacity:1;
	pointer-events:auto;
	transform:translateX(-50%) translateY(0);
}
.cn-push-prompt__card{
	display:flex;
	align-items:center;
	gap:14px;
	padding:14px 16px;
	background:#fff;
	border:1px solid rgba(15,23,42,.08);
	border-radius:16px;
	box-shadow:0 18px 50px rgba(15,23,42,.18), 0 2px 8px rgba(15,23,42,.06);
}
.cn-push-prompt__icon{
	flex:0 0 42px;
	width:42px;
	height:42px;
	border-radius:12px;
	display:flex;
	align-items:center;
	justify-content:center;
	background:linear-gradient(145deg,#eff6ff,#dbeafe);
	color:#2563eb;
}
.cn-push-prompt__body{flex:1;min-width:0}
.cn-push-prompt__title{
	margin:0;
	font-size:.95rem;
	line-height:1.45;
	font-weight:600;
	color:#0f172a;
}
.cn-push-prompt__actions{
	display:flex;
	gap:8px;
	flex-shrink:0;
}
.cn-push-prompt__btn{
	appearance:none;
	border:0;
	border-radius:999px;
	padding:8px 16px;
	font-size:.875rem;
	font-weight:600;
	cursor:pointer;
	transition:background .15s ease, color .15s ease, transform .1s ease;
}
.cn-push-prompt__btn:active{transform:scale(.97)}
.cn-push-prompt__btn--yes{
	background:#2563eb;
	color:#fff;
}
.cn-push-prompt__btn--yes:hover{background:#1d4ed8}
.cn-push-prompt__btn--no{
	background:#f1f5f9;
	color:#475569;
}
.cn-push-prompt__btn--no:hover{background:#e2e8f0}
@media (max-width:575px){
	.cn-push-prompt{top:12px}
	.cn-push-prompt__card{flex-wrap:wrap;padding:14px}
	.cn-push-prompt__actions{width:100%;justify-content:stretch}
	.cn-push-prompt__btn{flex:1;text-align:center}
}
</style>
{/literal}
{if $cnPushOneSignal}
<script>
if (!window.__fshopOneSignalSdkRequested) {
	window.__fshopOneSignalSdkRequested = true;
	window.OneSignalDeferred = window.OneSignalDeferred || [];
	var __osSdk = document.createElement('script');
	__osSdk.src = 'https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js';
	__osSdk.defer = true;
	document.head.appendChild(__osSdk);
}
</script>
{/if}
<script>
window.CustomerNotifyPushConfig = {$cnPushConfigJson nofilter};
</script>
<script src="{$cnPushJsUrl|escape}"></script>
{/if}
