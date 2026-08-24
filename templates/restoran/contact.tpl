<div class="prime-container prime-page">

	<div class="panel">

		<div class="panel__body">

	<h1 class="prime-page__title">{'Contact Us'|translate}</h1>



	{if $contactSuccess}

	<div class="alert alert-success">{$contactSuccess|escape}</div>

	{/if}



	{if $contactError}

	<div class="alert alert-danger">{$contactError|escape}</div>

	{/if}



	<div class="row g-4">

		<div class="col-lg-5">

			<div class="prime-page-card h-100">

				<h2 class="prime-page__subtitle">{'Get in Touch'|translate}</h2>

				<p class="mb-3"><strong>{'Email'|translate}</strong><br><a href="mailto:{$contactEmail}">{$contactEmail}</a></p>

				<p class="mb-3"><strong>{'Phone'|translate}</strong><br><a href="tel:{$contactPhoneTel|escape}">{$contactPhone|escape}</a></p>

				<p class="mb-0"><strong>{'Working Hours'|translate}</strong><br>{'Monday to Friday, 09:00 – 18:00'|translate}</p>

			</div>

		</div>

		<div class="col-lg-7">

			<form method="post" action="{$domain}contact" class="prime-page-card">

				<h2 class="prime-page__subtitle">{'Send a Message'|translate}</h2>

				<input type="hidden" name="sendContact" value="1">

				<input type="hidden" name="token" value="{$token}">

				<input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;" aria-hidden="true">



				<div class="mb-3">

					<label class="form-label">{'Full Name'|translate}</label>

					<input type="text" name="full_name" class="form-control" placeholder="{'Your full name'|translate}" value="{$formData.full_name|escape}" required>

				</div>

				<div class="mb-3">

					<label class="form-label">{'Email'|translate}</label>

					<input type="email" name="email" class="form-control" placeholder="{'Email placeholder'|translate}" value="{$formData.email|escape}" required>

				</div>

				<div class="mb-3">

					<label class="form-label">{'Phone (Optional)'|translate}</label>

					<input type="tel" name="phone" class="form-control phone-input" placeholder="{'Phone placeholder'|translate}" value="{$formData.phone|escape}">

				</div>

				<div class="mb-3">

					<label class="form-label">{'Subject (Optional)'|translate}</label>

					<input type="text" name="subject" class="form-control" placeholder="{'Subject placeholder'|translate}" value="{$formData.subject|escape}">

				</div>

				<div class="mb-3">

					<label class="form-label">{'Your Message'|translate}</label>

					<textarea name="message" class="form-control" rows="5" placeholder="{'Message placeholder'|translate}" required minlength="10">{$formData.message|escape}</textarea>

				</div>

				<button type="submit" class="prime-btn prime-btn--primary">{'Send Message'|translate}</button>

			</form>

		</div>

	</div>

		</div>

	</div>

</div>

