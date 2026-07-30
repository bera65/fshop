{if $flash}
<div class="alert alert-info alert-dismissible fade show" role="alert">
    {$flash|escape}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
</div>
{/if}

<div class="row">
    <div class="col-md-6">
        <div class="admin-panel mb-4">
            <div class="admin-panel__head border-bottom pb-2 mb-3">
                <h5 class="mb-0"><i class="fas fa-university text-primary me-2"></i> KuveytTürk Sanal POS Ayarları</h5>
            </div>
            <div class="admin-panel__body">
                <form action="" method="POST">
                    <input type="hidden" name="token" value="{$adminToken|escape}">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Mağaza Kodu (Merchant ID)</label>
                        <input type="text" name="merchant_id" value="{$kuveytturkMerchantId|escape}" class="form-control" placeholder="Örn: 716912" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Müşteri Numarası (Customer ID)</label>
                        <input type="text" name="customer_id" value="{$kuveytturkCustomerId|escape}" class="form-control" placeholder="Örn: 95703320" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">API Kullanıcı Adı (UserName)</label>
                        <input type="text" name="username" value="{$kuveytturkUsername|escape}" class="form-control" placeholder="Örn: apiuser" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">API Şifresi (Password)</label>
                        <input type="password" name="password" value="{$kuveytturkPassword|escape}" class="form-control" placeholder="API Şifreniz" required>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="test_mode" id="test_mode" value="1" {if $kuveytturkTestMode}checked{/if}>
                        <label class="form-check-label font-weight-bold" for="test_mode">Test Modu (Boa Test Sunucuları: boatest.kuveytturk.com.tr)</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Geri Dönüş (Callback) URL</label>
                        <input type="text" class="form-control bg-light" value="{$kuveytturkCallbackUrl|escape}" readonly>
                        <small class="text-muted">Bu adresi KuveytTürk üye işyeri portalındaki OkUrl ve FailUrl tanımlarınızda kullanabilirsiniz.</small>
                    </div>

                    <button type="submit" name="saveKuveytturk" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Ayarları Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="admin-panel mb-4">
            <div class="admin-panel__head border-bottom pb-2 mb-3">
                <h5 class="mb-0"><i class="fas fa-info-circle text-info me-2"></i> Bilgilendirme & Hata Çözümü</h5>
            </div>
            <div class="admin-panel__body">
                <div class="alert alert-light border mb-3">
                    <h6><i class="fas fa-shield-alt text-success me-1"></i> KuveytTürk 3D Secure v2.0</h6>
                    <p class="small text-muted mb-2">
                        KuveytTürk Sanal POS entegrasyonu <strong>TDV2.0.0 3D Secure</strong> protokolünü kullanır. Müşteri ödeme yaptıktan sonra banka SMS doğrulama sayfasına yönlendirilir ve SMS kodunu girdikten sonra sipariş otomatik onaylanır.
                    </p>
                    <hr class="my-2">
                    <ul class="small text-muted ps-3 mb-0">
                        <li><strong>Test Sunucusu:</strong> <code>boatest.kuveytturk.com.tr</code></li>
                        <li><strong>Canlı Sunucu:</strong> <code>sanalpos.kuveytturk.com.tr</code></li>
                    </ul>
                </div>
                <div class="alert alert-warning border small mb-0">
                    <strong>Provizyon Hatası Alıyorsanız:</strong><br>
                    Gerçek canlı mağaza bilgileri (Merchant ID, Password vb.) kullanıyorsanız yukarıdaki <strong>Test Modu</strong> seçeneğini kapatıp kaydediniz. Canlı kart bilgileri Test sunucusunda onaylanmaz.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="admin-panel">
            <div class="admin-panel__head border-bottom pb-2 mb-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history text-secondary me-2"></i> Son İşlem Kayıtları & Banka Yanıt Detayları (Log)</h5>
                <span class="badge bg-secondary">{count($logs)} kayıt</span>
            </div>
            <div class="admin-panel__body">
                {if $logs}
                <div class="table-responsive">
                    <table class="table table-hover table-striped small align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Referans / ID</th>
                                <th>İşlem Tipi</th>
                                <th>Tutar</th>
                                <th>Kodu</th>
                                <th>Banka Yanıt Mesajı</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th>Detay</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $logs as $log}
                            <tr>
                                <td>{$log.id_log}</td>
                                <td>
                                    <strong>{$log.merchant_order_id|escape}</strong>
                                    {if $log.id_order}<br><span class="text-muted">Sipariş #{$log.id_order}</span>{/if}
                                </td>
                                <td><code>{$log.transaction_type|escape}</code></td>
                                <td><strong>{$log.amount|number_format:2:',':'.'} TL</strong></td>
                                <td>
                                    {if $log.response_code == '00'}
                                        <span class="badge bg-success">00</span>
                                    {elseif $log.response_code}
                                        <span class="badge bg-danger">{$log.response_code|escape}</span>
                                    {else}
                                        <span class="text-muted">-</span>
                                    {/if}
                                </td>
                                <td><strong class="text-dark">{$log.response_message|escape}</strong></td>
                                <td>
                                    {if $log.status == 'completed'}
                                        <span class="badge bg-success">Başarılı</span>
                                    {elseif $log.status == 'failed' || $log.status == 'error'}
                                        <span class="badge bg-danger">Başarısız</span>
                                    {else}
                                        <span class="badge bg-warning text-dark">{$log.status|escape}</span>
                                    {/if}
                                </td>
                                <td>{$log.date_add}</td>
                                <td>
                                    {if $log.response_data}
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#logModal_{$log.id_log}">
                                            <i class="fas fa-code me-1"></i> Yanıt
                                        </button>
                                    {/if}
                                </td>
                            </tr>
                            {foreachelse}
                            <tr>
                                <td colspan="9" class="text-center text-muted">Henüz kayıt yok</td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>

                {foreach $logs as $log}
                    {if $log.response_data}
                    <div class="modal fade" id="logModal_{$log.id_log}" tabindex="-1" aria-labelledby="logModalLabel_{$log.id_log}" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="logModalLabel_{$log.id_log}">
                                        <i class="fas fa-terminal me-2 text-primary"></i> Banka Yanıt Detayı - Referans: {$log.merchant_order_id|escape}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-2 mb-3 bg-light p-2 rounded border">
                                        <div class="col-md-3"><strong>İşlem Tipi:</strong> <code>{$log.transaction_type|escape}</code></div>
                                        <div class="col-md-3"><strong>Cevap Kodu:</strong> <span class="badge bg-secondary">{$log.response_code|escape}</span></div>
                                        <div class="col-md-3"><strong>Durum:</strong> {$log.status|escape}</div>
                                        <div class="col-md-3"><strong>Tarih:</strong> {$log.date_add}</div>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Banka Mesajı:</strong>
                                        <p class="text-dark bg-white p-2 rounded border mb-0 fw-bold">{$log.response_message|escape}</p>
                                    </div>
                                    <div>
                                        <strong>Ham Banka XML / Yanıt İçeriği (Raw Response):</strong>
                                        <pre class="bg-dark text-light p-3 rounded small mb-0 mt-1" style="max-height: 350px; overflow: auto; white-space: pre-wrap; word-break: break-all; font-family: monospace;">{$log.response_data|escape}</pre>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    {/if}
                {/foreach}

                {else}
                <p class="text-muted mb-0 small">Henüz işlem kaydı bulunmuyor.</p>
                {/if}
            </div>
        </div>
    </div>
</div>
