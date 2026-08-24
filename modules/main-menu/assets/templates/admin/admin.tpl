{if $flash}
<div class="alert alert-{$flashType|default:'success'}">{$flash|escape}</div>
{/if}

<div class="row g-4">
	<div class="col-lg-5">
		<form method="post" class="admin-panel p-3">
			<input type="hidden" name="saveMenuItem" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<input type="hidden" name="id_menu_item" value="{$editItem.id_menu_item|default:0}">

			<h2 class="h6 mb-3">{if $editItem}Menü öğesini düzenle{else}Yeni menü öğesi{/if}</h2>

			<div class="mb-3">
				<label class="form-label">Etiket</label>
				<input type="text" name="label" class="form-control" required value="{$editItem.label|default:''|escape}">
			</div>
			<div class="mb-3">
				<label class="form-label">Tür</label>
				<select name="link_type" id="mmLinkType" class="form-select">
					{assign var=lt value=$editItem.link_type|default:'custom'}
					<option value="home"{if $lt == 'home'} selected{/if}>Ana sayfa</option>
					<option value="category"{if $lt == 'category'} selected{/if}>Kategori</option>
					<option value="cms"{if $lt == 'cms'} selected{/if}>CMS sayfa</option>
					<option value="blog"{if $lt == 'blog'} selected{/if}>Blog (liste)</option>
					<option value="custom"{if $lt == 'custom'} selected{/if}>Özel yol (contact, special…)</option>
					<option value="url"{if $lt == 'url'} selected{/if}>Tam URL</option>
				</select>
			</div>
			<div class="mb-3" id="mmValueWrap">
				<label class="form-label">Değer</label>
				<input type="text" name="link_value" id="mmLinkValue" class="form-control" value="{$editItem.link_value|default:''|escape}" placeholder="örn: contact veya https://…">
				<div class="form-text">Kategori/CMS için aşağıdan seçebilir veya ID yazabilirsiniz.</div>
			</div>
			<div class="mb-3" id="mmCatWrap" style="display:none;">
				<label class="form-label">Kategori seç</label>
				<select class="form-select" id="mmCatSelect">
					<option value="">—</option>
					{foreach $categoryOptions as $cat}
					<option value="{$cat.id_category}">{$cat.category_name|escape}</option>
					{/foreach}
				</select>
			</div>
			<div class="mb-3" id="mmCmsWrap" style="display:none;">
				<label class="form-label">CMS seç</label>
				<select class="form-select" id="mmCmsSelect">
					<option value="">—</option>
					{foreach $cmsOptions as $cms}
					<option value="{$cms.id_cms}">#{$cms.id_cms} — {$cms.slug|escape}</option>
					{/foreach}
				</select>
			</div>
			<div class="row g-2 mb-3">
				<div class="col-6">
					<label class="form-label">Sıra</label>
					<input type="number" name="position" class="form-control" value="{$editItem.position|default:0}">
				</div>
				<div class="col-6">
					<label class="form-label">Hedef</label>
					<select name="target" class="form-select">
						<option value="_self"{if ($editItem.target|default:'_self') == '_self'} selected{/if}>Aynı sekme</option>
						<option value="_blank"{if ($editItem.target|default:'_self') == '_blank'} selected{/if}>Yeni sekme</option>
					</select>
				</div>
			</div>
			<div class="form-check mb-2">
				<input type="hidden" name="active" value="0">
				<input class="form-check-input" type="checkbox" name="active" value="1" id="mmActive"{if !$editItem || $editItem.active} checked{/if}>
				<label class="form-check-label" for="mmActive">Aktif</label>
			</div>
			<div class="border rounded p-2 mb-3">
				<div class="small fw-semibold mb-2">Gösterildiği yerler</div>
				{assign var=sh value=$editItem.show_header|default:1}
				{assign var=sm value=$editItem.show_mobile|default:1}
				{assign var=sf value=$editItem.show_footer|default:0}
				<div class="form-check">
					<input type="hidden" name="show_header" value="0">
					<input class="form-check-input" type="checkbox" name="show_header" value="1" id="mmShowHeader"{if $sh} checked{/if}>
					<label class="form-check-label" for="mmShowHeader">Header (üst menü)</label>
				</div>
				<div class="form-check">
					<input type="hidden" name="show_mobile" value="0">
					<input class="form-check-input" type="checkbox" name="show_mobile" value="1" id="mmShowMobile"{if $sm} checked{/if}>
					<label class="form-check-label" for="mmShowMobile">Mobil menü</label>
				</div>
				<div class="form-check">
					<input type="hidden" name="show_footer" value="0">
					<input class="form-check-input" type="checkbox" name="show_footer" value="1" id="mmShowFooter"{if $sf} checked{/if}>
					<label class="form-check-label" for="mmShowFooter">Footer</label>
				</div>
			</div>
			<button type="submit" class="btn btn-dark btn-sm">Kaydet</button>
			{if $editItem}
			<a href="{$adminUrl}module-main-menu" class="btn btn-outline-secondary btn-sm">Vazgeç</a>
			{/if}
		</form>
	</div>
	<div class="col-lg-7">
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">Menü öğeleri</h2>
			<p class="small text-muted">Her öğe için <strong>Header</strong>, <strong>Mobil</strong> ve <strong>Footer</strong> konumlarını işaretleyin. Header hook’u masaüstü menüyü; mobil drawer <code>$mainMenuItems</code> listesini; footer <code>footer_menu</code> hook’unu kullanır.</p>
			<div class="table-responsive">
				<table class="table table-sm align-middle">
					<thead>
						<tr><th>Sıra</th><th>Etiket</th><th>Tür</th><th>Konum</th><th></th></tr>
					</thead>
					<tbody>
						{foreach $menuItems as $row}
						<tr>
							<td>{$row.position}</td>
							<td>{$row.label|escape}{if !$row.active} <span class="badge text-bg-secondary">pasif</span>{/if}</td>
							<td><code>{$row.link_type|escape}</code></td>
							<td class="small text-nowrap">
								{if $row.show_header|default:1}<span class="badge text-bg-light border">Header</span>{/if}
								{if $row.show_mobile|default:1}<span class="badge text-bg-light border">Mobil</span>{/if}
								{if $row.show_footer|default:0}<span class="badge text-bg-light border">Footer</span>{/if}
							</td>
							<td class="text-end text-nowrap">
								<a href="{$adminUrl}module-main-menu?edit={$row.id_menu_item}" class="btn btn-sm btn-outline-dark">Düzenle</a>
								<form method="post" class="d-inline" data-confirm-message="Silinsin mi?">
									<input type="hidden" name="deleteMenuItem" value="1">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="hidden" name="id_menu_item" value="{$row.id_menu_item}">
									<button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
								</form>
							</td>
						</tr>
						{foreachelse}
						<tr><td colspan="5" class="text-muted">Henüz öğe yok.</td></tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

{literal}
<script>
(function () {
	var typeEl = document.getElementById('mmLinkType');
	var valueEl = document.getElementById('mmLinkValue');
	var catWrap = document.getElementById('mmCatWrap');
	var cmsWrap = document.getElementById('mmCmsWrap');
	var catSel = document.getElementById('mmCatSelect');
	var cmsSel = document.getElementById('mmCmsSelect');
	function sync() {
		var t = typeEl ? typeEl.value : 'custom';
		if (catWrap) catWrap.style.display = t === 'category' ? '' : 'none';
		if (cmsWrap) cmsWrap.style.display = t === 'cms' ? '' : 'none';
		if (valueEl && (t === 'home' || t === 'blog')) valueEl.value = '';
	}
	if (typeEl) typeEl.addEventListener('change', sync);
	if (catSel) catSel.addEventListener('change', function () { if (valueEl) valueEl.value = catSel.value; });
	if (cmsSel) cmsSel.addEventListener('change', function () { if (valueEl) valueEl.value = cmsSel.value; });
	sync();
})();
</script>
{/literal}
