<h1 class="page-heading">Hesabım</h1>

<ul class="nav nav-tabs account-tabs mb-4" id="accountTabs" role="tablist">
	<li class="nav-item" role="presentation">
		<button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview-pane" type="button" role="tab">Genel</button>
	</li>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-pane" type="button" role="tab">Profil</button>
	</li>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-pane" type="button" role="tab">Şifre</button>
	</li>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="addresses-tab" data-bs-toggle="tab" data-bs-target="#addresses-pane" type="button" role="tab">Adreslerim</button>
	</li>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications-pane" type="button" role="tab">
			Bildirimler
			{if $unreadNotificationCount > 0}<span class="badge bg-danger ms-1" id="notificationTabBadge">{$unreadNotificationCount}</span>{/if}
		</button>
	</li>
</ul>

<div class="tab-content" id="accountTabContent">
	<div class="tab-pane fade show active" id="overview-pane" role="tabpanel">
		<div class="page-card account-card">
			<p class="mb-1 text-muted">Ad Soyad</p>
			<p class="fw-semibold fs-5 mb-3" id="overviewFullName">{$customer.user_full_name|escape}</p>

			<p class="mb-1 text-muted">Telefon</p>
			<p class="fw-semibold mb-3" id="overviewPhone">{$customer.phone|escape}</p>

			<p class="mb-1 text-muted">E-posta</p>
			<p class="fw-semibold mb-4" id="overviewEmail">{if $customer.email}{$customer.email|escape}{else}<span class="text-muted">Belirtilmemiş</span>{/if}</p>

			<div class="d-flex flex-wrap gap-2">
				<a href="{$domain}siparislerim" class="btn btn-dark btn-sm">Siparişlerim</a>
				<a href="{$domain}hesabim#notifications" class="btn btn-outline-secondary btn-sm">Bildirimler{if $unreadNotificationCount > 0} ({$unreadNotificationCount}){/if}</a>
				<a href="{$domain}favoriler" class="btn btn-outline-secondary btn-sm">Favorilerim</a>
				<button type="button" class="btn btn-outline-danger btn-sm" id="logoutBtn">Çıkış Yap</button>
			</div>
		</div>
	</div>

	<div class="tab-pane fade" id="profile-pane" role="tabpanel">
		<div class="page-card">
			<form id="profileForm">
				<div class="mb-3">
					<label class="form-label">Ad Soyad</label>
					<input type="text" name="full_name" class="form-control" value="{$customer.user_full_name|escape}" required>
				</div>
				<div class="mb-3">
					<label class="form-label">Telefon</label>
					<input type="tel" name="phone" class="form-control phone-input" value="{$customer.phone|escape}" required>
				</div>
				<div class="mb-3">
					<label class="form-label">E-posta</label>
					<input type="email" name="email" class="form-control" value="{$customer.email|escape}" placeholder="Bildirimler için e-posta">
				</div>
				<button type="submit" class="btn btn-dark">Kaydet</button>
			</form>
		</div>
	</div>

	<div class="tab-pane fade" id="password-pane" role="tabpanel">
		<div class="page-card">
			<form id="passwordForm">
				<div class="mb-3">
					<label class="form-label">Mevcut Şifre</label>
					<input type="password" name="current_password" class="form-control" required>
				</div>
				<div class="mb-3">
					<label class="form-label">Yeni Şifre</label>
					<input type="password" name="new_password" class="form-control" minlength="6" required>
				</div>
				<div class="mb-3">
					<label class="form-label">Yeni Şifre Tekrar</label>
					<input type="password" name="new_password2" class="form-control" minlength="6" required>
				</div>
				<button type="submit" class="btn btn-dark">Şifreyi Güncelle</button>
			</form>
		</div>
	</div>

	<div class="tab-pane fade" id="addresses-pane" role="tabpanel">
		<div id="addressList" class="mb-4">
			{if $addresses|@count}
				{foreach $addresses as $addr}
				<div class="address-card page-card mb-3" data-id="{$addr.id_address}">
					<div class="d-flex justify-content-between align-items-start gap-2 mb-2">
						<div>
							<strong>{if $addr.label}{$addr.label|escape}{else}Adres{/if}</strong>
							{if $addr.is_default}<span class="badge bg-dark ms-1">Varsayılan</span>{/if}
						</div>
						<div class="d-flex gap-1 flex-wrap">
							{if !$addr.is_default}
							<button type="button" class="btn btn-sm btn-outline-secondary set-default-address" data-id="{$addr.id_address}">Varsayılan Yap</button>
							{/if}
							<button type="button" class="btn btn-sm btn-outline-dark edit-address"
								data-id="{$addr.id_address}"
								data-label="{$addr.label|escape}"
								data-full-name="{$addr.full_name|escape}"
								data-phone="{$addr.phone|escape}"
								data-city="{$addr.city|escape}"
								data-district="{$addr.district|escape}"
								data-address-text="{$addr.address_text|escape}"
								data-is-default="{$addr.is_default}">Düzenle</button>
							<button type="button" class="btn btn-sm btn-outline-danger delete-address" data-id="{$addr.id_address}">Sil</button>
						</div>
					</div>
					<p class="mb-1 fw-semibold">{$addr.full_name|escape} · {$addr.phone|escape}</p>
					<p class="mb-0 text-muted small">{$addr.city|escape} / {$addr.district|escape} — {$addr.address_text|escape}</p>
				</div>
				{/foreach}
			{else}
				<div class="page-card text-muted" id="emptyAddressState">Henüz kayıtlı adresiniz yok.</div>
			{/if}
		</div>

		<div class="page-card">
			<h2 class="fs-6 mb-3" id="addressFormTitle">Yeni Adres Ekle</h2>
			<form id="addressForm">
				<input type="hidden" name="id_address" id="addressIdInput" value="0">
				<div class="row g-3">
					<div class="col-md-6">
						<label class="form-label">Adres Başlığı</label>
						<input type="text" name="label" class="form-control" placeholder="Ev, İş, Ofis...">
					</div>
					<div class="col-md-6">
						<label class="form-label">Ad Soyad</label>
						<input type="text" name="full_name" class="form-control" value="{$customer.user_full_name|escape}" required>
					</div>
					<div class="col-md-6">
						<label class="form-label">Telefon</label>
						<input type="tel" name="phone" class="form-control phone-input" value="{$customer.phone|escape}" required>
					</div>
					<div class="col-md-6">
						<label class="form-label">İl</label>
						<input type="text" name="city" class="form-control" required>
					</div>
					<div class="col-md-6">
						<label class="form-label">İlçe</label>
						<input type="text" name="district" class="form-control" required>
					</div>
					<div class="col-12">
						<label class="form-label">Açık Adres</label>
						<textarea name="address_text" class="form-control" rows="3" required></textarea>
					</div>
					<div class="col-12">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="is_default" id="addressDefaultCheck" value="1">
							<label class="form-check-label" for="addressDefaultCheck">Varsayılan adres olarak kaydet</label>
						</div>
					</div>
				</div>
				<div class="d-flex gap-2 mt-3">
					<button type="submit" class="btn btn-dark">Kaydet</button>
					<button type="button" class="btn btn-outline-secondary d-none" id="cancelAddressEdit">İptal</button>
				</div>
			</form>
		</div>
	</div>

	<div class="tab-pane fade" id="notifications-pane" role="tabpanel">
		<div class="d-flex justify-content-between align-items-center mb-3">
			<h2 class="fs-6 mb-0">Bildirimlerim</h2>
			{if $unreadNotificationCount > 0}
			<button type="button" class="btn btn-sm btn-outline-secondary" id="markAllNotificationsRead">Tümünü okundu işaretle</button>
			{/if}
		</div>
		<div id="notificationList">
			{if $notifications|@count}
				{foreach $notifications as $n}
				<div class="notification-item page-card mb-3{if !$n.is_read} is-unread{/if}" data-id="{$n.id_notification}">
					<div class="d-flex justify-content-between align-items-start gap-2 mb-1">
						<strong class="notification-title">{$n.title|escape}</strong>
						<small class="text-muted">{$n.date_formatted}</small>
					</div>
					<p class="mb-2 small notification-message">{$n.message|escape|nl2br}</p>
					{if $n.link}
					<a href="{$domain}{$n.link|escape}" class="btn btn-sm btn-outline-dark notification-link">Detay</a>
					{/if}
					{if !$n.is_read}
					<button type="button" class="btn btn-sm btn-link mark-notification-read" data-id="{$n.id_notification}">Okundu</button>
					{/if}
				</div>
				{/foreach}
			{else}
				<div class="page-card text-muted" id="emptyNotificationState">Henüz bildiriminiz yok.</div>
			{/if}
		</div>
	</div>
</div>
