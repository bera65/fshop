<!DOCTYPE html>
<html lang="tr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Ekran Kilitli — {$posSiteName|escape}</title>
	<link rel="stylesheet" href="{$posCssUrl|escape}">
</head>
<body class="pos-body pos-body--login">
<div class="pos-login">
	<div class="pos-login__card pos-lock-card">
		<div class="pos-lock-icon">🔒</div>
		<h1>Ekran Kilitli</h1>
		<p class="pos-login__sub">Devam etmek için kasa PIN'inizi girin.</p>

		{if $posError}
		<div class="pos-alert pos-alert--error">{$posError|escape}</div>
		{/if}

		<form method="post" class="pos-login__form">
			<input type="hidden" name="token" value="{$posToken|escape}">
			<label for="pos_pin">PIN</label>
			<input type="password" id="pos_pin" name="pos_pin" class="pos-field" inputmode="numeric" pattern="[0-9]*" maxlength="8" autocomplete="off" required autofocus>
			<button type="submit" name="posUnlock" value="1" class="pos-checkout pos-checkout--inline">Kilidi Aç</button>
		</form>
	</div>
</div>
</body>
</html>
