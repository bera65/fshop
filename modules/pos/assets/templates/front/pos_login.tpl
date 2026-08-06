<!DOCTYPE html>
<html lang="tr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>POS Giriş — {$posSiteName|escape}</title>
	<link rel="stylesheet" href="{$posCssUrl|escape}">
</head>
<body class="pos-body pos-body--login">
<div class="pos-login">
	<div class="pos-login__card">
		<h1>POS Giriş</h1>
		<p class="pos-login__sub">Kasa ekranına erişmek için PIN girin.</p>

		{if $posError}
		<div class="pos-alert pos-alert--error">{$posError|escape}</div>
		{/if}

		{if !$posHasPin}
		<div class="pos-alert pos-alert--warn">
			PIN henüz ayarlanmamış.
			{if $posIsAdmin}
			<a href="{$posAdminConfigUrl|escape}">Modül ayarlarından</a> PIN belirleyin.
			{else}
			Yönetici PIN oluşturmalı.
			{/if}
		</div>
		{/if}

		<form method="post" class="pos-login__form">
			<input type="hidden" name="token" value="{$posToken|escape}">
			<label for="pos_pin">PIN</label>
			<input type="password" id="pos_pin" name="pos_pin" class="pos-field" inputmode="numeric" pattern="[0-9]*" maxlength="8" autocomplete="off" required>
			<button type="submit" name="posLogin" value="1" class="pos-btn pos-btn--pay pos-btn--block">Giriş yap</button>
		</form>

		<p class="pos-login__foot">
			<a href="{$posBackUrl|escape}">Geri dön</a>
		</p>
	</div>
</div>
</body>
</html>
