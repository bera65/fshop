{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">{$flash|escape}</div>
			</div>
			<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
		</div>
	</div>
{/if}

<div class="row">
	<div class="col-md-6">
		<div class="card mb-4">
			<div class="card-header bg-dark text-white">
				<h5 class="mb-0">Cron Ayarları</h5>
			</div>
			<div class="card-body">
				<form method="post">
					<input type="hidden" name="saveSettings" value="1">
					<input type="hidden" name="token" value="{$adminToken}">

					<div class="mb-3">
						<label class="form-label">Cron URL</label>
						<div class="input-group">
							<input type="text" class="form-control font-monospace bg-light" id="cronUrlInput" value="{$cronUrl|escape}" readonly>
							<button class="btn btn-outline-secondary" type="button" onclick="copyCronUrl()">Kopyala</button>
						</div>
						<div class="form-text">Bu URL'yi sunucunuza cron job olarak ekleyin.</div>
					</div>

					<div class="mb-3">
						<label class="form-label">Cron Token</label>
						<input type="text" name="cron_token" class="form-control font-monospace" value="{$cronToken|escape}">
						<div class="form-text">URL içindeki token'dır. Değiştirirseniz cron URL değişir.</div>
					</div>

					<div class="mb-3 form-check">
						<input type="checkbox" name="cron_enabled" class="form-check-input" id="cronEnabled" {if $cronEnabled}checked{/if}>
						<label class="form-check-label" for="cronEnabled">Cron işlemini aktifleştir</label>
					</div>

					<button type="submit" class="btn btn-dark">Ayarları Kaydet</button>
				</form>
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="card">
			<div class="card-header bg-dark text-white">
				<h5 class="mb-0">Nasıl Çalışır?</h5>
			</div>
			<div class="card-body">
				<ol class="mb-0">
					<li>Müşteri ürün sayfasından hedef fiyat belirler</li>
					<li>Cron her çalıştığında tüm bekleyen talepleri kontrol eder</li>
					<li>Fiyat hedefe düştüğünde e-posta gönderilir</li>
					<li>Her müşteriye aynı ürün için sadece 1 kez bildirim gider</li>
				</ol>
				<hr>
				<strong>Cron önerisi:</strong>
				<code class="d-block bg-light p-2 mt-2 font-monospace small">
					*/30 * * * * curl -s "{$cronUrl|escape}" > /dev/null
				</code>
			</div>
		</div>
	</div>
</div>

<script>
function copyCronUrl() {
	var input = document.getElementById('cronUrlInput');
	input.select();
	input.setSelectionRange(0, 99999);
	document.execCommand('copy');
	if (typeof showAdminLiveToast === 'function') {
		showAdminLiveToast({ type: 'success', message: 'Cron URL kopyalandı' });
	} else {
		alert('Cron URL kopyalandı!');
	}
}
</script>
