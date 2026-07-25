{if $flash}
<div class="alert alert-{$flashType|default:'success'} py-2">{$flash|escape}</div>
{/if}

<div class="admin-panel">
	<h2 class="h6 mb-3">Facebook (Meta) Pixel</h2>
	<p class="text-muted small mb-4">
		Pixel, ziyaretçilerin sitede ne yaptığını Meta’ya bildirir; Facebook/Instagram reklamlarınızın dönüşümünü ölçmenize ve remarketing yapmanıza yardımcı olur.
		Pixel ID’yi <a href="https://business.facebook.com/events_manager" target="_blank" rel="noopener">Meta Events Manager</a> → Veri Kümeleri → Pixel bölümünden alın.
	</p>

	<form method="post">
		<input type="hidden" name="saveFacebookPixel" value="1">
		<input type="hidden" name="token" value="{$adminToken}">

		<div class="form-check form-switch mb-4">
			<input class="form-check-input" type="checkbox" name="enabled" value="1" id="fbPixelEnabled"{if $settings.enabled} checked{/if}>
			<label class="form-check-label" for="fbPixelEnabled">Pixel etkin</label>
		</div>

		<div class="mb-3">
			<label class="form-label">Pixel ID</label>
			<input type="text" name="pixel_id" class="form-control" value="{$settings.pixel_id|escape}" placeholder="123456789012345" autocomplete="off">
			<div class="form-text">Yalnızca rakamlardan oluşur (15–16 hane).</div>
		</div>

		<p class="fw-semibold mb-2">İzlenecek olaylar</p>
		<div class="row g-2 mb-4">
			<div class="col-md-6">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" name="track_view" value="1" id="fbTrackView"{if $settings.track_view} checked{/if}>
					<label class="form-check-label" for="fbTrackView">ViewContent — ürün detay</label>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" name="track_cart" value="1" id="fbTrackCart"{if $settings.track_cart} checked{/if}>
					<label class="form-check-label" for="fbTrackCart">AddToCart — sepete ekle</label>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" name="track_checkout" value="1" id="fbTrackCheckout"{if $settings.track_checkout} checked{/if}>
					<label class="form-check-label" for="fbTrackCheckout">InitiateCheckout — ödeme sayfası</label>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" name="track_purchase" value="1" id="fbTrackPurchase"{if $settings.track_purchase} checked{/if}>
					<label class="form-check-label" for="fbTrackPurchase">Purchase — sipariş tamamlandı</label>
				</div>
			</div>
		</div>

		<button type="submit" class="btn btn-dark">Kaydet</button>
	</form>

	<div class="alert alert-light border small mt-4 mb-0">
		<strong>Nasıl çalışır?</strong>
		<ul class="mb-0 ps-3">
			<li><strong>PageView</strong> — her sayfa yüklemesinde otomatik</li>
			<li><strong>ViewContent</strong> — ürün sayfasında</li>
			<li><strong>AddToCart</strong> — “Sepete ekle” tıklanınca</li>
			<li><strong>InitiateCheckout</strong> — checkout sayfasında</li>
			<li><strong>Purchase</strong> — sipariş oluşunca (sonraki sayfa yüklemesinde)</li>
		</ul>
	</div>
</div>
