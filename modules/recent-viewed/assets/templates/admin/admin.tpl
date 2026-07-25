{if $flash}
<div class="alert alert-{$flashType|default:'success'} py-2">{$flash|escape}</div>
{/if}

<div class="admin-panel">
	<h2 class="h6 mb-3">Son Bakılan Ürünler</h2>
	<p class="text-muted small mb-4">
		Ziyaretçinin baktığı ürünler tarayıcı çerezinde tutulur ve ana sayfada kompakt liste olarak gösterilir.
		Blok, <code>home_promo_slider</code> hook noktasında görünür (fyazilim, blue, restoran temalarında promo alanı).
	</p>

	<form method="post">
		<input type="hidden" name="saveRecentViewed" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="form-check form-switch mb-4">
			<input class="form-check-input" type="checkbox" name="enabled" value="1" id="rvEnabled"{if $settings.enabled} checked{/if}>
			<label class="form-check-label" for="rvEnabled">Modül etkin</label>
		</div>

		<div class="mb-3">
			<label class="form-label">Başlık</label>
			<input type="text" name="title" class="form-control" value="{$settings.title|escape}">
		</div>

		<div class="row g-3">
			<div class="col-md-6">
				<label class="form-label">Ana sayfada gösterilecek adet</label>
				<input type="number" name="limit" class="form-control" min="1" max="20" value="{$settings.limit|escape}">
			</div>
			<div class="col-md-6">
				<label class="form-label">Çerezde saklanacak max. ürün</label>
				<input type="number" name="store" class="form-control" min="5" max="50" value="{$settings.store|escape}">
				<div class="form-text">Ziyaretçi geçmişinde tutulacak üst sınır.</div>
			</div>
		</div>

		<button type="submit" class="btn btn-dark mt-4">Kaydet</button>
	</form>
</div>
