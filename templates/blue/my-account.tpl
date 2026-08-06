<div class="prime-container prime-page prime-account-page">

<div class="dress-account">

	<h1 class="dress-account__title">{'My Account'|translate}</h1>



	<div class="row g-4">

		<div class="col-lg-4 col-xl-3">

			<aside class="dress-account-sidebar">

				<div class="dress-account-profile">

					<div class="dress-account-avatar" id="accountAvatar">{$accountInitial|escape}</div>

					<strong id="sidebarFullName">{$customer.user_full_name|escape}</strong>

					<small id="sidebarEmail">{if $customer.email}{$customer.email|escape}{else}{'Email not specified'|translate}{/if}</small>

				</div>



				<nav class="dress-account-nav" aria-label="{'Account menu'|translate}">

					<a href="{$domain}my-account" class="dress-account-nav__item">
						<span class="dress-account-nav__icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
						</span>
						{'My Orders'|translate}
					</a>

					<button type="button" class="dress-account-nav__item{if $activeTab == 'profile'} is-active{/if}" data-account-tab="profile">

						<span class="dress-account-nav__icon" aria-hidden="true">

							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>

						</span>

						{'Profile'|translate}

					</button>

					<button type="button" class="dress-account-nav__item" data-account-tab="addresses">

						<span class="dress-account-nav__icon" aria-hidden="true">

							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>

						</span>

						{'My Addresses'|translate}

					</button>

					<button type="button" class="dress-account-nav__item" data-account-tab="notifications">

						<span class="dress-account-nav__icon" aria-hidden="true">

							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>

						</span>

						{'Notifications'|translate}

						{if $unreadNotificationCount > 0}<span class="dress-account-nav__badge" id="notificationTabBadge">{$unreadNotificationCount}</span>{/if}

					</button>

					<button type="button" class="dress-account-nav__item" data-account-tab="password">

						<span class="dress-account-nav__icon" aria-hidden="true">

							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>

						</span>

						{'Password'|translate}

					</button>

					<a href="{$domain}returns" class="dress-account-nav__item">

						<span class="dress-account-nav__icon" aria-hidden="true">

							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>

						</span>

						{'My Returns'|translate}

					</a>

					<a href="{$domain}favorites" class="dress-account-nav__item">

						<span class="dress-account-nav__icon" aria-hidden="true">

							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>

						</span>

						{'Favorites'|translate}

					</a>

					<a href="{$domain}contact" class="dress-account-nav__item">

						<span class="dress-account-nav__icon" aria-hidden="true">

							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>

						</span>

						{'Contact Us'|translate}

					</a>

					<a href="{$domain}iade-degisim" class="dress-account-nav__item">

						<span class="dress-account-nav__icon" aria-hidden="true">

							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>

						</span>

						{'Return request'|translate}

					</a>

				</nav>



				<button type="button" class="dress-account-logout" id="logoutBtn">

					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>

					{'Sign Out'|translate}

				</button>

			</aside>

		</div>



		<div class="col-lg-8 col-xl-9">

			<div class="dress-account-main">

				<div class="dress-account-panel{if $activeTab == 'orders'} is-active{/if}" data-account-panel="orders">
					{include file='blue/partials/account-orders.tpl'}
				</div>

				{* Profil *}

				<div class="dress-account-panel{if $activeTab == 'profile'} is-active{/if}" data-account-panel="profile">

					<div class="dress-account-stats">

						<div class="dress-account-stat">

							<span class="dress-account-stat__icon dress-account-stat__icon--orders" aria-hidden="true">

								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/></svg>

							</span>

							<span>

								<span class="dress-account-stat__label">{'Orders stat'|translate}</span>

								<span class="dress-account-stat__value">{$accountStats.orders}</span>

							</span>

						</div>

						<div class="dress-account-stat">

							<span class="dress-account-stat__icon dress-account-stat__icon--support" aria-hidden="true">

								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bell-icon lucide-bell"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>

							</span>

							<span>

								<span class="dress-account-stat__label">{'Notifications'|translate}</span>

								<span class="dress-account-stat__value">{$accountStats.support}</span>

							</span>

						</div>

						<div class="dress-account-stat">

							<span class="dress-account-stat__icon dress-account-stat__icon--address" aria-hidden="true">

								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>

							</span>

							<span>

								<span class="dress-account-stat__label">{'Address'|translate}</span>

								<span class="dress-account-stat__value">{$accountStats.addresses}</span>

							</span>

						</div>

						<div class="dress-account-stat">

							<span class="dress-account-stat__icon dress-account-stat__icon--coupon" aria-hidden="true">

								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="8" width="18" height="12" rx="2"/><path d="M7 8V6a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"/><line x1="12" x2="12" y1="14" y2="18"/></svg>

							</span>

							<span>

								<span class="dress-account-stat__label">{'Gift voucher'|translate}</span>

								<span class="dress-account-stat__value">{$accountStats.coupons}</span>

							</span>

						</div>

					</div>



					<div class="dress-account-welcome">

						<strong id="welcomeFullName">{$customer.user_full_name|escape}</strong> — {'Account welcome'|translate}

					</div>



					<h2 class="dress-account-section-title">{'Personal Information'|translate}</h2>

					<form id="profileForm" class="dress-account-form">

						<div class="row g-3">

							<div class="col-md-6">

								<label class="form-label" for="profileFullName">{'Full Name'|translate}</label>

								<input type="text" id="profileFullName" name="full_name" class="form-control" value="{$customer.user_full_name|escape}" required>

							</div>

							<div class="col-md-6">

								<label class="form-label" for="profileEmail">{'Email'|translate}</label>

								<input type="email" id="profileEmail" name="email" class="form-control" value="{$customer.email|escape}" placeholder="{'Email placeholder'|translate}">

							</div>

							<div class="col-md-6">

								<label class="form-label" for="profilePhone">{'Phone'|translate}</label>

								<input type="tel" id="profilePhone" name="phone" class="form-control phone-input" value="{$customer.phone|escape}" required>

							</div>

							<div class="col-12">

								<button type="submit" class="btn btn-submit">{'Update'|translate}</button>

							</div>

						</div>

					</form>



					<p class="d-none">

						<span id="overviewFullName">{$customer.user_full_name|escape}</span>

						<span id="overviewPhone">{$customer.phone|escape}</span>

						<span id="overviewEmail">{if $customer.email}{$customer.email|escape}{else}{'Not specified'|translate}{/if}</span>

					</p>

				</div>



				{* Adresler *}

				<div class="dress-account-panel" data-account-panel="addresses">

					<h2 class="dress-account-section-title">{'My Addresses'|translate}</h2>

					<div id="addressList" class="mb-4">

						{if $addresses|@count}

							{foreach $addresses as $addr}

							<div class="dress-account-card address-card" data-id="{$addr.id_address}">

								<div class="dress-account-card__head">

									<div>

										<strong>{if $addr.label}{$addr.label|escape}{else}{'Address'|translate}{/if}</strong>

										{if $addr.is_default}<span class="badge bg-dark ms-1">{'Default'|translate}</span>{/if}

									</div>

									<div class="dress-account-address-actions">

										{if !$addr.is_default}

										<button type="button" class="btn btn-sm btn-outline-secondary set-default-address" data-id="{$addr.id_address}">{'Default'|translate}</button>

										{/if}

										<button type="button" class="btn btn-sm btn-outline-dark edit-address"

											data-id="{$addr.id_address}"

											data-label="{$addr.label|escape}"

											data-full-name="{$addr.full_name|escape}"

											data-phone="{$addr.phone|escape}"

											data-city="{$addr.city|escape}"

											data-district="{$addr.district|escape}"

											data-address-text="{$addr.address_text|escape}"

											data-company-name="{$addr.company_name|escape}"

											data-tax-office="{$addr.tax_office|escape}"

											data-tax-number="{$addr.tax_number|escape}"

											data-is-default="{$addr.is_default}">{'Edit'|translate}</button>

										<button type="button" class="btn btn-sm btn-outline-danger delete-address" data-id="{$addr.id_address}">{'Delete'|translate}</button>

									</div>

								</div>

								<p class="mb-1 fw-semibold">{$addr.full_name|escape} · {$addr.phone|escape}</p>

								<p class="mb-0 text-muted small">{$addr.city|escape} / {$addr.district|escape} — {$addr.address_text|escape}</p>

								{if $addr.company_name || $addr.tax_number}

								<p class="mb-0 text-muted small mt-1">

									{if $addr.company_name}{'Company Name'|translate}: {$addr.company_name|escape}{/if}

									{if $addr.tax_office}{if $addr.company_name} · {/if}{'Tax Office'|translate}: {$addr.tax_office|escape}{/if}

									{if $addr.tax_number}{if $addr.company_name || $addr.tax_office} · {/if}{'Tax ID'|translate}: {$addr.tax_number|escape}{/if}

								</p>

								{/if}

							</div>

							{/foreach}

						{else}

							<div class="dress-account-empty" id="emptyAddressState">{'No saved addresses'|translate}</div>

						{/if}

					</div>



					<div class="dress-account-card">

						<h3 class="fs-6 mb-3" id="addressFormTitle">{'Add New Address'|translate}</h3>

						<form id="addressForm" class="dress-account-form">

							<input type="hidden" name="id_address" id="addressIdInput" value="0">

							<div class="row g-3">

								<div class="col-md-6">

									<label class="form-label">{'Address Title'|translate}</label>

									<input type="text" name="label" class="form-control" placeholder="{'Address label placeholder'|translate}">

								</div>

								<div class="col-md-6">

									<label class="form-label">{'Full Name'|translate}</label>

									<input type="text" name="full_name" class="form-control" value="{$customer.user_full_name|escape}" required>

								</div>

								<div class="col-md-6">

									<label class="form-label">{'Phone'|translate}</label>

									<input type="tel" name="phone" class="form-control phone-input" value="{$customer.phone|escape}" required>

								</div>

								<div class="col-md-6">

									<label class="form-label">{'City'|translate}</label>

									<input type="text" name="city" class="form-control" required>

								</div>

								<div class="col-md-6">

									<label class="form-label">{'District'|translate}</label>

									<input type="text" name="district" class="form-control" required>

								</div>

								<div class="col-12">

									<label class="form-label">{'Street Address'|translate}</label>

									<textarea name="address_text" class="form-control" rows="3" required></textarea>

								</div>

								<div class="col-12">

									<h4 class="fs-6 mb-2">{'Billing Information'|translate} <span class="text-muted fw-normal">({'Billing Information Optional'|translate})</span></h4>

								</div>

								<div class="col-12">

									<label class="form-label">{'Company Name'|translate}</label>

									<input type="text" name="company_name" class="form-control" placeholder="{'Company placeholder'|translate}">

								</div>

								<div class="col-md-6">

									<label class="form-label">{'Tax Office'|translate}</label>

									<input type="text" name="tax_office" class="form-control">

								</div>

								<div class="col-md-6">

									<label class="form-label">{'Tax ID'|translate}</label>

									<input type="text" name="tax_number" class="form-control" maxlength="20" inputmode="numeric">

								</div>

								<div class="col-12">

									<div class="form-check">

										<input class="form-check-input" type="checkbox" name="is_default" id="addressDefaultCheck" value="1">

										<label class="form-check-label" for="addressDefaultCheck">{'Save as default address'|translate}</label>

									</div>

								</div>

								<div class="col-12 d-flex gap-2">

									<button type="submit" class="btn btn-submit">{'Save'|translate}</button>

									<button type="button" class="btn btn-outline-secondary d-none" id="cancelAddressEdit">{'Cancel'|translate}</button>

								</div>

							</div>

						</form>

					</div>

				</div>



				{* Şifre *}

				<div class="dress-account-panel" data-account-panel="password">

					<h2 class="dress-account-section-title">{'Change Password'|translate}</h2>

					<div class="dress-account-card">

						<form id="passwordForm" class="dress-account-form">

							<div class="row g-3">

								<div class="col-md-6">

									<label class="form-label">{'Current Password'|translate}</label>

									<input type="password" name="current_password" class="form-control" required>

								</div>

								<div class="col-md-6">

									<label class="form-label">{'New Password'|translate}</label>

									<input type="password" name="new_password" class="form-control" minlength="6" required>

								</div>

								<div class="col-md-6">

									<label class="form-label">{'Password repeat'|translate}</label>

									<input type="password" name="new_password2" class="form-control" minlength="6" required>

								</div>

								<div class="col-12">

									<button type="submit" class="btn btn-submit">{'Update Password'|translate}</button>

								</div>

							</div>

						</form>

					</div>

				</div>



				{* Bildirimler *}

				<div class="dress-account-panel" data-account-panel="notifications">

					<div class="d-flex justify-content-between align-items-center mb-3">

						<h2 class="dress-account-section-title mb-0">{'Notifications'|translate}</h2>

						{if $unreadNotificationCount > 0}

						<button type="button" class="btn btn-sm btn-outline-secondary" id="markAllNotificationsRead">{'Mark all read'|translate}</button>

						{/if}

					</div>

					<div id="notificationList">

						{if $notifications|@count}

							{foreach $notifications as $n}

							<div class="dress-account-card notification-item mb-3{if !$n.is_read} is-unread{/if}" data-id="{$n.id_notification}">

								<div class="d-flex justify-content-between align-items-start gap-2 mb-1">

									<strong class="notification-title">{$n.title|escape}</strong>

									<small class="text-muted">{$n.date_formatted}</small>

								</div>

								<p class="mb-2 small notification-message">{$n.message|escape|nl2br}</p>

								{if $n.link}

								<a href="{$domain}{$n.link|escape}" class="btn btn-sm btn-outline-dark notification-link">{'Details'|translate}</a>

								{/if}

								{if !$n.is_read}

								<button type="button" class="btn btn-sm btn-link mark-notification-read" data-id="{$n.id_notification}">{'Read'|translate}</button>

								{/if}

							</div>

							{/foreach}

						{else}

							<div class="dress-account-empty" id="emptyNotificationState">{'No notifications yet'|translate}</div>

						{/if}

					</div>

				</div>

			</div>

		</div>

	</div>

</div>

</div>

