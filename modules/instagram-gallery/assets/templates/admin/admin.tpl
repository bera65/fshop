{if $flash}
<div class="alert alert-{$flashType|default:'success'} py-2">{$flash|escape}</div>
{/if}

<div class="admin-panel mb-4">
	<h2 class="h6 mb-3">Instagram Galeri — Ayarlar</h2>
	<form method="post">
		<input type="hidden" name="saveInstagramGallery" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="form-check form-switch mb-3">
			<input class="form-check-input" type="checkbox" name="enabled" value="1" id="igEnabled"{if $settings.enabled} checked{/if}>
			<label class="form-check-label" for="igEnabled">Galeri etkin</label>
		</div>

		<div class="row g-3">
			<div class="col-md-6">
				<label class="form-label">Başlık</label>
				<input type="text" name="title" class="form-control" value="{$settings.title|escape}">
			</div>
			<div class="col-md-6">
				<label class="form-label">Profil etiketi</label>
				<input type="text" name="profile_label" class="form-control" value="{$settings.profile_label|escape}" placeholder="@magaza">
			</div>
			<div class="col-12">
				<label class="form-label">Alt başlık</label>
				<input type="text" name="subtitle" class="form-control" value="{$settings.subtitle|escape}">
			</div>
			<div class="col-12">
				<label class="form-label">Instagram profil linki</label>
				<input type="url" name="profile_url" class="form-control" value="{$settings.profile_url|escape}" placeholder="https://instagram.com/magaza">
			</div>
		</div>

		<button type="submit" class="btn btn-dark mt-3">Ayarları kaydet</button>
	</form>
</div>

<div class="admin-panel mb-4">
	<h2 class="h6 mb-3">Yeni görsel ekle</h2>

	<div class="alert alert-light border small">
		<strong>En kolay yol:</strong> Telefonunuzdan veya bilgisayarınızdan JPG/PNG yükleyin.<br>
		<strong>Alternatif:</strong> Instagram’da gönderiyi açın → <em>Paylaş → Bağlantıyı kopyala</em> → aşağıya yapıştırın; görsel otomatik indirilir.
	</div>

	<form method="post" enctype="multipart/form-data" class="row g-3">
		<input type="hidden" name="addInstagramItem" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="col-12">
			<label class="form-label">1) Bilgisayardan / telefondan yükle</label>
			<input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
			<div class="form-text">Kare fotoğraf önerilir. JPG, PNG veya WEBP.</div>
		</div>

		<div class="col-12">
			<label class="form-label">veya 2) Instagram gönderi linki</label>
			<input type="url" name="instagram_post_url" class="form-control" placeholder="https://www.instagram.com/p/... veya /reel/...">
			<div class="form-text">Görsel linki kopyalamanıza gerek yok; gönderi URL’si yeterli (herkese açık gönderiler).</div>
		</div>

		<div class="col-md-6">
			<label class="form-label">Tıklanınca gidilecek link (isteğe bağlı)</label>
			<input type="url" name="link_url" class="form-control" placeholder="Boş bırakılırsa Instagram linki kullanılır">
		</div>
		<div class="col-md-6">
			<label class="form-label">Açıklama (isteğe bağlı)</label>
			<input type="text" name="caption" class="form-control">
		</div>

		<div class="col-12">
			<button type="submit" class="btn btn-outline-dark">Galeriye ekle</button>
		</div>
	</form>
</div>

<div class="admin-panel">
	<h2 class="h6 mb-3">Galeri görselleri ({$items|@count})</h2>
	{if $items|@count == 0}
	<p class="text-muted mb-0">Henüz görsel yok.</p>
	{else}
	<div class="table-responsive">
		<table class="table table-sm align-middle">
			<thead>
				<tr>
					<th>Görsel</th>
					<th>Link</th>
					<th>Durum</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{foreach $items as $item}
				<tr>
					<td style="width:72px">
						<img src="{$item.image_url|escape}" alt="" class="ig-admin-thumb">
					</td>
					<td class="small">
						{if $item.link_url}<a href="{$item.link_url|escape}" target="_blank" rel="noopener">{$item.link_url|escape|truncate:40}</a>{else}<span class="text-muted">—</span>{/if}
						{if $item.caption}<div class="text-muted">{$item.caption|escape}</div>{/if}
					</td>
					<td>{if $item.active}<span class="badge text-bg-success">Aktif</span>{else}<span class="badge text-bg-secondary">Pasif</span>{/if}</td>
					<td class="text-end text-nowrap">
						<a href="{$adminUrl}module-instagram-gallery?toggle_item={$item.id}&token={$adminToken|escape:url}" class="btn btn-sm btn-outline-secondary">Durum</a>
						<a href="{$adminUrl}module-instagram-gallery?delete_item={$item.id}&token={$adminToken|escape:url}" class="btn btn-sm btn-outline-danger js-admin-confirm" data-confirm-message="Silinsin mi?">Sil</a>
					</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	{/if}
</div>
