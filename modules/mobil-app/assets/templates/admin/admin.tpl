<div class="card border-0 shadow-sm mb-4">
	<div class="card-body">
		<h2 class="h5 mb-3">Mobil uygulama (PWA)</h2>
		<p class="text-muted small mb-0">
			Mağazanızı gerçek bir PWA olarak kurun: tam ekran açılış, splash ekranı, adres çubuğu yok.
			Android Chrome’da menüden veya kurulum penceresinden yüklenir; iOS’ta Safari kısayolu kullanılır.
		</p>
	</div>
</div>

{if $flash}
<div class="alert alert-{$flashType|default:'info'} alert-dismissible fade show" role="alert">
	{$flash|escape}
	<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
</div>
{/if}

<form method="post" enctype="multipart/form-data" class="mobil-app-admin">
	<input type="hidden" name="token" value="{$adminToken|escape}">
	<input type="hidden" name="saveMobilApp" value="1">

	<div class="card border-0 shadow-sm mb-4">
		<div class="card-header bg-white">
			<strong>Genel</strong>
		</div>
		<div class="card-body">
			<div class="form-check form-switch mb-3">
				<input class="form-check-input" type="checkbox" id="enabled" name="enabled" value="1"{if $settings.enabled} checked{/if}>
				<label class="form-check-label" for="enabled">PWA aktif</label>
			</div>
			<div class="row g-3">
				<div class="col-md-6">
					<label class="form-label" for="app_name">Uygulama adı</label>
					<input type="text" class="form-control" id="app_name" name="app_name" maxlength="128" value="{$settings.app_name|escape}" required>
				</div>
				<div class="col-md-6">
					<label class="form-label" for="short_name">Kısa ad (ana ekran)</label>
					<input type="text" class="form-control" id="short_name" name="short_name" maxlength="64" value="{$settings.short_name|escape}" required>
				</div>
				<div class="col-12">
					<label class="form-label" for="description">Açıklama</label>
					<input type="text" class="form-control" id="description" name="description" maxlength="255" value="{$settings.description|escape}">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="theme_color">Tema rengi</label>
					<input type="color" class="form-control form-control-color w-100" id="theme_color" name="theme_color" value="{$settings.theme_color|escape}">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="background_color">Arka plan rengi</label>
					<input type="color" class="form-control form-control-color w-100" id="background_color" name="background_color" value="{$settings.background_color|escape}">
				</div>
				<div class="col-md-4">
					<label class="form-label" for="orientation">Yönelim</label>
					<select class="form-select" id="orientation" name="orientation">
						{foreach $orientations as $value => $label}
						<option value="{$value|escape}"{if $settings.orientation == $value} selected{/if}>{$label|escape}</option>
						{/foreach}
					</select>
				</div>
			</div>
		</div>
	</div>

	<div class="card border-0 shadow-sm mb-4">
		<div class="card-header bg-white">
			<strong>Mobil menü</strong>
		</div>
		<div class="card-body">
			<div class="form-check form-switch mb-3">
				<input class="form-check-input" type="checkbox" id="menu_enabled" name="menu_enabled" value="1"{if $settings.menu_enabled} checked{/if}>
				<label class="form-check-label" for="menu_enabled">Mobil menüde PWA kurulum butonu göster</label>
			</div>
			<div class="row g-3">
				<div class="col-md-6">
					<label class="form-label" for="menu_label">Menü metni</label>
					<input type="text" class="form-control" id="menu_label" name="menu_label" maxlength="128" value="{$settings.menu_label|escape}" placeholder="Uygulamayı yükle">
				</div>
				<div class="col-md-6">
					<label class="form-label" for="menu_hint_ios">iOS yardım metni</label>
					<input type="text" class="form-control" id="menu_hint_ios" name="menu_hint_ios" maxlength="255" value="{$settings.menu_hint_ios|escape}">
				</div>
			</div>
		</div>
	</div>

	<div class="card border-0 shadow-sm mb-4">
		<div class="card-header bg-white">
			<strong>İkonlar</strong>
		</div>
		<div class="card-body">
			<p class="text-muted small">512×512 PNG önerilir. Tek dosya yüklerseniz 192 ve Apple ikonları otomatik oluşturulur.</p>
			<div class="row g-3">
				<div class="col-md-6">
					<label class="form-label" for="icon_master">Ana ikon (512 px)</label>
					<input type="file" class="form-control" id="icon_master" name="icon_master" accept="image/png,image/jpeg,image/webp">
					{if $iconUrls.512}
					<img src="{$iconUrls.512|escape}?v={$minute}" alt="" class="mobil-app-admin__preview mt-2">
					{/if}
				</div>
				<div class="col-md-6">
					<label class="form-label" for="icon_192">192 px ikon (isteğe bağlı)</label>
					<input type="file" class="form-control" id="icon_192" name="icon_192" accept="image/png,image/jpeg,image/webp">
					{if $iconUrls.192}
					<img src="{$iconUrls.192|escape}?v={$minute}" alt="" class="mobil-app-admin__preview mt-2">
					{/if}
				</div>
				<div class="col-md-6">
					<label class="form-label" for="icon_apple">Apple touch (180 px)</label>
					<input type="file" class="form-control" id="icon_apple" name="icon_apple" accept="image/png,image/jpeg,image/webp">
					{if $iconUrls.apple}
					<img src="{$iconUrls.apple|escape}?v={$minute}" alt="" class="mobil-app-admin__preview mt-2">
					{/if}
				</div>
			</div>
		</div>
	</div>

	<div class="card border-0 shadow-sm mb-4">
		<div class="card-header bg-white">
			<strong>Çevrimdışı sayfa</strong>
		</div>
		<div class="card-body row g-3">
			<div class="col-md-6">
				<label class="form-label" for="offline_title">Başlık</label>
				<input type="text" class="form-control" id="offline_title" name="offline_title" maxlength="128" value="{$settings.offline_title|escape}">
			</div>
			<div class="col-md-6">
				<label class="form-label" for="offline_message">Mesaj</label>
				<input type="text" class="form-control" id="offline_message" name="offline_message" maxlength="255" value="{$settings.offline_message|escape}">
			</div>
		</div>
	</div>

	<div class="card border-0 shadow-sm mb-4">
		<div class="card-body small text-muted">
			<div><strong>Manifest:</strong> <a href="{$manifestPreviewUrl|escape}" target="_blank" rel="noopener">{$manifestPreviewUrl|escape}</a></div>
			<div><strong>Service worker:</strong> <a href="{$swPreviewUrl|escape}" target="_blank" rel="noopener">{$swPreviewUrl|escape}</a></div>
			<div><strong>Kapsam (scope):</strong> <code>{$scopePath|escape}</code></div>
		</div>
	</div>

	<button type="submit" class="btn btn-primary">Kaydet</button>
</form>
