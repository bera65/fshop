{if $flash}
<div class="alert alert-info">{$flash|escape}</div>
{/if}

<p class="mb-3"><a href="{$moduleDetailUrl}">&larr; Modül detayına dön</a></p>

<div class="admin-panel p-3 mb-4">
	<h2 class="h6 mb-3">Slayt Ayarları</h2>
	<form method="post" class="row g-3 align-items-end">
		<input type="hidden" name="saveSliderSettings" value="1">
		<input type="hidden" name="token" value="{$adminToken}">
		<div class="col-md-6">
			<label class="form-label">Kampanya slaytı başlığı</label>
			<input type="text" name="promo_title" class="form-control" value="{$promoTitle|escape}" placeholder="En Avantajlı Fırsatlar">
		</div>
		<div class="col-md-3">
			<button type="submit" class="btn btn-dark">Kaydet</button>
		</div>
	</form>
</div>

<ul class="nav nav-tabs mb-3">
	{foreach $sliderGroups as $groupKey => $groupLabel}
	<li class="nav-item">
		<a class="nav-link{if $sliderGroup == $groupKey} active{/if}" href="{$moduleConfigUrl}?group={$groupKey|escape}">
			{$groupLabel|escape}
		</a>
	</li>
	{/foreach}
</ul>

<div class="row g-4">
	<div class="col-lg-5">
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">{if $editSlide}Slayt Düzenle{else}Yeni Slayt Ekle{/if}</h2>

			<form method="post" enctype="multipart/form-data">
				<input type="hidden" name="saveSlide" value="1">
				<input type="hidden" name="token" value="{$adminToken}">
				<input type="hidden" name="group" value="{$sliderGroup|escape}">
				{if $editSlide}
				<input type="hidden" name="id_slide" value="{$editSlide.id_slide}">
				{/if}

				<div class="mb-3">
					<label class="form-label">Görsel {if !$editSlide}<span class="text-danger">*</span>{/if}</label>
					<input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp"{if !$editSlide} required{/if}>
					{if $editSlide && $editSlide.image_url}
					<img src="{$editSlide.image_url|escape}" alt="" class="img-fluid mt-2 rounded border" style="max-height:120px;">
					{/if}
					<small class="text-muted d-block mt-1">JPG, PNG veya WEBP. Hero için geniş (1920px), promo için yatay banner önerilir.</small>
				</div>

				<div class="mb-3">
					<label class="form-label">Üst metin (kicker)</label>
					<input type="text" name="subtitle" class="form-control" value="{if $editSlide}{$editSlide.subtitle|escape}{/if}" placeholder="KARACA HOME ÜRÜNLERİNDE">
				</div>

				<div class="mb-3">
					<label class="form-label">Ana başlık</label>
					<textarea name="title" class="form-control" rows="2" placeholder="Büyük başlık (satır atlayabilirsiniz)">{if $editSlide}{$editSlide.title|escape}{/if}</textarea>
				</div>

				{if $sliderGroup == 'hero'}
				<div class="mb-3">
					<label class="form-label">Promosyon kutuları</label>
					<textarea name="promo_lines" class="form-control" rows="4" placeholder="Her satır bir kutu olur&#10;3.000 TL ve üzerine 400 TL İNDİRİM&#10;6.000 TL ve üzerine 800 TL İNDİRİM">{if $editSlide}{$editSlide.promo_lines|escape}{/if}</textarea>
					<small class="text-muted">Sadece üst slayt için. Her satır ayrı kutu olarak gösterilir.</small>
				</div>
				{/if}

				<div class="mb-3">
					<label class="form-label">Buton metni</label>
					<input type="text" name="button_text" class="form-control" value="{if $editSlide}{$editSlide.button_text|escape}{else}Keşfet{/if}">
				</div>

				<div class="mb-3">
					<label class="form-label">Link URL</label>
					<input type="text" name="link_url" class="form-control" value="{if $editSlide}{$editSlide.link_url|escape}{/if}" placeholder="/special veya https://...">
				</div>

				<div class="row g-3 mb-3">
					<div class="col-6">
						<label class="form-label">Sıra</label>
						<input type="number" name="position" class="form-control" value="{if $editSlide}{$editSlide.position}{else}0{/if}" min="0">
					</div>
					<div class="col-6 d-flex align-items-end">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="active" id="slideActive" value="1"{if !$editSlide || $editSlide.active} checked{/if}>
							<label class="form-check-label" for="slideActive">Aktif</label>
						</div>
					</div>
				</div>

				<div class="d-flex gap-2">
					<button type="submit" class="btn btn-dark">{if $editSlide}Güncelle{else}Ekle{/if}</button>
					{if $editSlide}
					<a href="{$moduleConfigUrl}?group={$sliderGroup|escape}" class="btn btn-outline-secondary">İptal</a>
					{/if}
				</div>
			</form>
		</div>
	</div>

	<div class="col-lg-7">
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">Mevcut Slaytlar</h2>

			{if $slides|@count == 0}
			<p class="text-muted mb-0">Bu grupta henüz slayt yok.</p>
			{else}
			<div class="table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead>
						<tr>
							<th>Görsel</th>
							<th>Başlık</th>
							<th>Sıra</th>
							<th>Durum</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{foreach $slides as $slide}
						<tr>
							<td style="width:90px;">
								{if $slide.image_url}
								<img src="{$slide.image_url|escape}" alt="" class="rounded border" style="width:72px;height:48px;object-fit:cover;">
								{/if}
							</td>
							<td>
								<strong>{$slide.title|default:'—'|escape|truncate:40}</strong>
								{if $slide.subtitle}<br><small class="text-muted">{$slide.subtitle|escape}</small>{/if}
							</td>
							<td>{$slide.position}</td>
							<td>
								{if $slide.active}
								<span class="badge bg-success">Aktif</span>
								{else}
								<span class="badge bg-secondary">Pasif</span>
								{/if}
							</td>
							<td class="text-end text-nowrap">
								<a href="{$moduleConfigUrl}?group={$sliderGroup|escape}&edit={$slide.id_slide}" class="btn btn-sm btn-outline-dark">Düzenle</a>
								<form method="post" class="d-inline">
									<input type="hidden" name="toggleSlide" value="1">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="hidden" name="id_slide" value="{$slide.id_slide}">
									<button type="submit" class="btn btn-sm btn-outline-secondary">{if $slide.active}Pasif yap{else}Aktif yap{/if}</button>
								</form>
								<form method="post" class="d-inline" onsubmit="return confirm('Bu slayt silinsin mi?');">
									<input type="hidden" name="deleteSlide" value="1">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="hidden" name="id_slide" value="{$slide.id_slide}">
									<button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
								</form>
							</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
			{/if}
		</div>
	</div>
</div>
