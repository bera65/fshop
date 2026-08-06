<div class="row">
	<div class="col-md-6">
		<div class="admin-panel">
			<form action="" method="POST">
			  <input type="hidden" name="token" value="{$adminToken}">
			  <div class="mb-3">
				<label class="form-label">Token</label>
				<input type="password" name="basitKargoToken" value="{$basitKargoToken}" class="form-control">
			  </div>
			  <div class="mb-3">
				<label class="form-label">Link Token</label>
				<input type="text" name="basitLinkToken" value="{$basitLinkToken}" class="form-control">
				<small>Sadece harf ve rakam kullanın örnek : {Tools::hash($time)}</small>
			  </div>
			  <div class="mb-3">
				<label class="form-label">Webhook Link</label>
				<div class="form-control">{$domain}api/module.php?m=basitkargo&action=webhook&token={$basitLinkToken}</div>
				<small>Link token tanımladıktan sonra bu linki basitkargo panelinizden webhook kısmına ekleyiniz</small>
			  </div>
			  <button type="submit" name="saveKargo" value="{$adminToken}" class="btn btn-dark">Kaydet</button>
			</form>
		</div>
	</div>
	<div class="col-md-6">
		<div class="alert alert-info">
			<h4>Dikkat !</h4>
			<p>Basitkargo token bilgisi girdikten sonra panelinizden webhook ayarlarını yapınız.</p>
			<p>Link token oluşturun bu token linkinize yetkisiz erişim yapılmasını engeller token için <b>sadece harf ve rakam kullanın</b> özel karakter kullanmayın</p>
		</div>
	</div>
</div>
<p><br /></p>
<div class="row">
	<div class="col-md-6">
		<div class="admin-panel">
			<form action="" method="POST">
			  <input type="hidden" name="token" value="{$adminToken}">
			  <div class="alert alert-warning">Test Kargo oluştur</div>
			  <button type="submit" name="testOrder" value="{$adminToken}" class="btn btn-dark">Test Et</button>
			</form>
		</div>
	</div>
	<div class="col-md-6">
		{if $sonuc != '""'}
			<div class="alert alert-success">
				<pre class="mb-0">{$sonuc|escape}</pre>
			</div>
		{else}
			<div class="alert alert-info">
				Bu kısımdan basit kargo entegrasyonunuzu test edebilirsiniz. Bir test gönderisi oluşturarak entegrasyondan emin olun.
			</div>
		{/if}
		</div>
</div>
