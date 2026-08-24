<link rel="stylesheet" href="{$domain}templates/admin/css/media-library.css?v={$smarty.now}">

{if $flash}
<div class="alert alert-{$flashType|default:'success'}">{$flash|escape}</div>
{/if}

<ul class="nav nav-tabs mb-3">
	<li class="nav-item">
		<a class="nav-link{if $blogTab == 'posts'} active{/if}" href="{$adminUrl}module-blog?tab=posts">Yazılar</a>
	</li>
	<li class="nav-item">
		<a class="nav-link{if $blogTab == 'categories'} active{/if}" href="{$adminUrl}module-blog?tab=categories">Kategoriler</a>
	</li>
</ul>

{if $blogTab == 'categories'}
<div class="row g-4">
	<div class="col-md-5">
		<form method="post" class="admin-panel p-3">
			<input type="hidden" name="saveBlogCategory" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<input type="hidden" name="id_blog_category" value="{$editCategory.id_blog_category|default:0}">

			<h2 class="h6 mb-3">{if $editCategory}Kategoriyi düzenle{else}Yeni kategori{/if}</h2>

			<div class="mb-3">
				<label class="form-label">Ad</label>
				<input type="text" name="name" class="form-control" required value="{$editCategory.name|default:''|escape}">
			</div>
			<div class="mb-3">
				<label class="form-label">Slug</label>
				<input type="text" name="slug" class="form-control" value="{$editCategory.slug|default:''|escape}" placeholder="otomatik">
				<div class="form-text">Link: <code>/blog/kategori/slug-ID</code></div>
			</div>
			<div class="mb-3">
				<label class="form-label">Açıklama</label>
				<textarea name="description" class="form-control" rows="2">{$editCategory.description|default:''|escape}</textarea>
			</div>
			<div class="mb-3">
				<label class="form-label">Sıra</label>
				<input type="number" name="position" class="form-control" value="{$editCategory.position|default:0}">
			</div>
			<div class="form-check mb-3">
				<input type="hidden" name="active" value="0">
				<input class="form-check-input" type="checkbox" name="active" value="1" id="blogCatActive"{if !$editCategory || $editCategory.active} checked{/if}>
				<label class="form-check-label" for="blogCatActive">Aktif</label>
			</div>
			<button type="submit" class="btn btn-dark btn-sm">Kaydet</button>
			{if $editCategory}
			<a href="{$adminUrl}module-blog?tab=categories" class="btn btn-outline-secondary btn-sm">Vazgeç</a>
			{/if}
		</form>
	</div>
	<div class="col-md-7">
		<div class="admin-panel p-3">
			<h2 class="h6 mb-3">Kategoriler</h2>
			<div class="table-responsive">
				<table class="table table-sm align-middle">
					<thead>
						<tr><th>Sıra</th><th>Ad</th><th>Durum</th><th></th></tr>
					</thead>
					<tbody>
						{foreach $blogCategories as $cat}
						<tr>
							<td>{$cat.position}</td>
							<td>
								<strong>{$cat.name|escape}</strong>
								<div class="small text-muted">/blog/kategori/{$cat.slug|escape}-{$cat.id_blog_category}</div>
							</td>
							<td>{if $cat.active}<span class="badge text-bg-success">Aktif</span>{else}<span class="badge text-bg-secondary">Pasif</span>{/if}</td>
							<td class="text-end text-nowrap">
								<a href="{$adminUrl}module-blog?tab=categories&edit_cat={$cat.id_blog_category}" class="btn btn-sm btn-outline-dark">Düzenle</a>
								<form method="post" class="d-inline" data-confirm-message="Kategori silinsin mi? Yazılar kategorisiz kalır.">
									<input type="hidden" name="deleteBlogCategory" value="1">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="hidden" name="id_blog_category" value="{$cat.id_blog_category}">
									<button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
								</form>
							</td>
						</tr>
						{foreachelse}
						<tr><td colspan="4" class="text-muted">Henüz kategori yok.</td></tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

{else}
<div class="row g-4">
	<div class="col-12 col-xl-7">
		<form method="post" class="admin-panel p-3" id="blogPostForm">
			<input type="hidden" name="saveBlogPost" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<input type="hidden" name="id_blog_post" value="{$editPost.id_blog_post|default:0}">

			<h2 class="h6 mb-3">{if $editPost}Yazıyı düzenle{else}Yeni blog yazısı{/if}</h2>

			<div class="mb-3">
				<label class="form-label">Başlık</label>
				<input type="text" name="title" class="form-control" required value="{$editPost.title|default:''|escape}">
			</div>
			<div class="mb-3">
				<label class="form-label">Kategori</label>
				<select name="id_blog_category" class="form-select">
					<option value="0">— Kategorisiz —</option>
					{foreach $blogCategories as $cat}
					<option value="{$cat.id_blog_category}"{if ($editPost.id_blog_category|default:0) == $cat.id_blog_category} selected{/if}>{$cat.name|escape}{if !$cat.active} (pasif){/if}</option>
					{/foreach}
				</select>
			</div>
			<div class="mb-3">
				<label class="form-label">Slug (URL)</label>
				<input type="text" name="slug" class="form-control" value="{$editPost.slug|default:''|escape}" placeholder="otomatik üretilir">
				<div class="form-text">Yayın linki: <code>/blog/slug-{$editPost.id_blog_post|default:'ID'}</code></div>
			</div>
			<div class="mb-3">
				<label class="form-label">Özet</label>
				<textarea name="excerpt" class="form-control" rows="2">{$editPost.excerpt|default:''|escape}</textarea>
			</div>
			<div class="mb-3">
				<label class="form-label">İçerik</label>
				<textarea name="content" id="blogContent" class="form-control wysiwyg-editor" rows="12">{$editPost.content|default:''|escape}</textarea>
				<div class="form-text">Görsel eklemek için araç çubuğundaki medya / resim butonunu kullanın.</div>
			</div>
			<div class="mb-3">
				<label class="form-label">Kapak görseli</label>
				<div class="input-group input-group-sm">
					<input type="text" name="cover_image" id="blogCoverInput" class="form-control" value="{$editPost.cover_image|default:''|escape}" placeholder="img/media/ornek.jpg">
					<button type="button" class="btn btn-outline-dark" data-media-pick data-media-target="#blogCoverInput" data-media-preview="#blogCoverPreview" data-media-label="Kapak seç">Medyadan seç</button>
				</div>
				{if $editPost.cover_url|default:''}
				<img id="blogCoverPreview" src="{$editPost.cover_url|escape}" alt="" class="img-fluid rounded mt-2" style="max-height:140px">
				{else}
				<img id="blogCoverPreview" src="" alt="" class="img-fluid rounded mt-2" style="max-height:140px" hidden>
				{/if}
			</div>
			<div class="mb-3">
				<label class="form-label">Meta başlık</label>
				<input type="text" name="meta_title" class="form-control" value="{$editPost.meta_title|default:''|escape}">
			</div>
			<div class="mb-3">
				<label class="form-label">Meta açıklama</label>
				<textarea name="meta_description" class="form-control" rows="2">{$editPost.meta_description|default:''|escape}</textarea>
			</div>
			<div class="form-check mb-3">
				<input type="hidden" name="active" value="0">
				<input class="form-check-input" type="checkbox" name="active" value="1" id="blogActive"{if !$editPost || $editPost.active} checked{/if}>
				<label class="form-check-label" for="blogActive">Yayında</label>
			</div>
			<button type="submit" class="btn btn-dark btn-sm">Kaydet</button>
			{if $editPost}
			<a href="{$adminUrl}module-blog?tab=posts" class="btn btn-outline-secondary btn-sm">Vazgeç</a>
			<a href="{$editPost.url|default:$blogListUrl|escape}" target="_blank" class="btn btn-outline-warning btn-sm">Önizle</a>
			{/if}
		</form>
	</div>
	<div class="col-12 col-xl-5">
		<div class="admin-panel p-3">
			<div class="d-flex justify-content-between align-items-center mb-3">
				<h2 class="h6 mb-0">Yazılar</h2>
				<a href="{$blogListUrl|escape}" target="_blank" class="btn btn-sm btn-outline-dark">Blog sayfasını aç</a>
			</div>
			<div class="table-responsive">
				<table class="table table-sm align-middle">
					<thead>
						<tr><th>Başlık</th><th>Kategori</th><th>Durum</th><th></th></tr>
					</thead>
					<tbody>
						{foreach $blogPosts as $row}
						<tr>
							<td>
								<strong>{$row.title|escape}</strong>
								<div class="small text-muted">/blog/{$row.slug|escape}-{$row.id_blog_post}</div>
							</td>
							<td class="small">{if $row.category_name}{$row.category_name|escape}{else}<span class="text-muted">—</span>{/if}</td>
							<td>{if $row.active}<span class="badge text-bg-success">Yayında</span>{else}<span class="badge text-bg-secondary">Taslak</span>{/if}</td>
							<td class="text-end text-nowrap">
								<a href="{$adminUrl}module-blog?tab=posts&edit={$row.id_blog_post}" class="btn btn-sm btn-outline-dark">Düzenle</a>
								<form method="post" class="d-inline" data-confirm-message="Silinsin mi?">
									<input type="hidden" name="deleteBlogPost" value="1">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="hidden" name="id_blog_post" value="{$row.id_blog_post}">
									<button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
								</form>
							</td>
						</tr>
						{foreachelse}
						<tr><td colspan="4" class="text-muted">Henüz yazı yok.</td></tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

{include file='admin/partials/media-library-modal.tpl'}
<script src="{$domain}templates/admin/js/media-picker.js?v={$smarty.now}"></script>
{/if}
