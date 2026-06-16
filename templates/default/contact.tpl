<h1 class="page-heading">İletişim</h1>

{if $contactSuccess}
<div class="alert alert-success">{$contactSuccess}</div>
{/if}

{if $contactError}
<div class="alert alert-danger">{$contactError}</div>
{/if}

<div class="row g-4">
	<div class="col-lg-5">
		<div class="border rounded p-4 bg-white h-100">
			<p class="mb-2"><strong>E-posta</strong><br><a href="mailto:{$contactEmail}">{$contactEmail}</a></p>
			<p class="mb-2"><strong>Telefon</strong><br><a href="tel:{$contactPhoneTel|escape}">{$contactPhone|escape}</a></p>
			<p class="mb-0"><strong>Çalışma Saatleri</strong><br>Pazartesi – Cuma, 09:00 – 18:00</p>
		</div>
	</div>
	<div class="col-lg-7">
		<form method="post" action="{$domain}contact" class="border rounded p-4 bg-white">
			<input type="hidden" name="sendContact" value="1">
			<input type="hidden" name="token" value="{$token}">
			<input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;" aria-hidden="true">

			<div class="mb-3">
				<label class="form-label">Ad Soyad</label>
				<input type="text" name="full_name" class="form-control" placeholder="Adınız Soyadınız" value="{$formData.full_name|escape}" required>
			</div>
			<div class="mb-3">
				<label class="form-label">E-posta</label>
				<input type="email" name="email" class="form-control" placeholder="ornek@mail.com" value="{$formData.email|escape}" required>
			</div>
			<div class="mb-3">
				<label class="form-label">Telefon (Opsiyonel)</label>
				<input type="tel" name="phone" class="form-control phone-input" placeholder="05__ ___ __ __" value="{$formData.phone|escape}">
			</div>
			<div class="mb-3">
				<label class="form-label">Konu (Opsiyonel)</label>
				<input type="text" name="subject" class="form-control" placeholder="Konu başlığı" value="{$formData.subject|escape}">
			</div>
			<div class="mb-3">
				<label class="form-label">Mesajınız</label>
				<textarea name="message" class="form-control" rows="5" placeholder="Mesajınızı yazın" required minlength="10">{$formData.message|escape}</textarea>
			</div>
			<button type="submit" class="btn btn-dark">Mesaj Gönder</button>
		</form>
	</div>
</div>
