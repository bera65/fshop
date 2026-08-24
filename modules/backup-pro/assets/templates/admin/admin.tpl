<script>
    window.backupProDomain = {$domain|js nofilter};
    window.backupProAdminUrl = {$adminUrl|js nofilter};
    window.backupProApiBase = window.backupProDomain + 'api/module.php?m=backup-pro&action=';
    window.backupProAdminToken = {$adminToken|@json_encode nofilter};
</script>

<link rel="stylesheet" href="{$domain}modules/backup-pro/assets/css/admin.css?v={$smarty.now}">

<div class="container-fluid py-2" id="backup-pro-app">
    <!-- Navigation Tabs -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <ul class="nav nav-pills mb-0" id="backupProTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-bp-dashboard" type="button">Gösterge Paneli</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bp-schedules" type="button">Otomatik Zamanlayıcı</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bp-settings" type="button">Ayarlar</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bp-logs" type="button">İşlem Günlükleri</button>
            </li>
        </ul>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="BackupPro.loadDashboard(); BackupPro.loadBackups(1);">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: text-bottom; margin-right: 4px;"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
            Yenile
        </button>
    </div>

    <div class="tab-content" id="backupProTabContent">
        <!-- Dashboard Tab -->
        <div class="tab-pane fade show active" id="tab-bp-dashboard" role="tabpanel">
            <!-- Progress Card -->
            <div class="card mb-4 border-primary d-none" id="bp-progress-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title h6 text-primary mb-0">
                            <span class="spinner-border spinner-border-sm me-1" role="status" style="width:14px;height:14px;border-width:2px;"></span>
                            Yedekleme İşlemi Devam Ediyor...
                        </h5>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-light text-dark border fs-6 px-3 py-2" id="bp-elapsed-time" style="font-family:monospace;font-size:13px!important;letter-spacing:1px;">
                                ⏱ 00:00:00
                            </span>
                        </div>
                    </div>
                    <div class="progress mb-1" style="height: 22px; position: relative;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="bp-progress-bar" style="width: 0%;"></div>
                        <div class="progress-pct-centered" id="bp-progress-pct-text">0%</div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted" id="bp-progress-label">Başlatılıyor...</small>
                    </div>
                </div>
            </div>

            <!-- KPI Row -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-title">Toplam Yedek</div>
                        <div class="stat-value" id="stat-total-backups">0</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-title">Başarılı Yedekleme</div>
                        <div class="stat-value text-success" id="stat-successful">0</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-title">Hatalı Yedekleme</div>
                        <div class="stat-value text-danger" id="stat-failed">0</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-title">Kullanılan Alan</div>
                        <div class="stat-value text-primary" id="stat-total-size">0 MB</div>
                    </div>
                </div>
            </div>

            <!-- 4-Column Quick Backup Form Card -->
            <div class="card mb-4">
                <div class="card-body py-3">
                    <form onsubmit="BackupPro.toggleBackup(); return false;">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label font-weight-bold small mb-1">Yedekleme Adı</label>
                                <input type="text" class="form-control form-control-sm" id="wizard-backup-name" placeholder="Örn: manuel_full_yedek_2026">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label font-weight-bold small mb-1">Yedek Türü</label>
                                <select class="form-select form-select-sm" id="wizard-backup-type">
                                    <option value="full">Tam Yedek (Dosyalar + Veritabanı)</option>
                                    <option value="db_only">Sadece Veritabanı</option>
                                    <option value="files_only">Sadece Dosyalar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label font-weight-bold small mb-1">Arşiv Formatı</label>
                                <select class="form-select form-select-sm" id="wizard-archive-format" disabled>
                                    <option value="zip" selected>ZIP</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" id="bp-start-stop-btn" class="btn btn-primary btn-sm w-100">
                                    <span id="bp-start-stop-icon">▶</span>
                                    <span id="bp-start-stop-text">Yedeklemeyi Başlat</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Disk Usage Card & Health Check -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-white">Disk Alanı Durumu</div>
                        <div class="card-body">
                            <p class="mb-1 text-muted small">Boş Disk Alanı: <strong id="stat-free-disk">-</strong></p>
                            <div class="progress mb-2" style="height: 18px;">
                                <div class="progress-bar bg-info" id="stat-disk-bar" style="width: 0%;"></div>
                            </div>
                            <small class="text-muted" id="stat-disk-pct">%0 Kullanımda</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-white">Sistem Sağlık Bilgisi</div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item d-flex justify-content-between"><span>PHP Sürümü:</span><strong id="health-php-ver">-</strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Zip Desteği:</span><strong id="health-zip">-</strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Bellek Sınırı:</span><strong id="health-memory">-</strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Maksimum Çalışma Süresi:</span><strong id="health-exec-time">-</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backups List Section (at the bottom of Dashboard) -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span>Mevcut Yedekler & İndirme</span>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" id="bp-search" placeholder="Yedek adında ara...">
                        <select class="form-select form-select-sm" id="bp-type-filter">
                            <option value="">Tüm Türler</option>
                            <option value="full">Tam Yedek</option>
                            <option value="db_only">Veritabanı</option>
                            <option value="files_only">Dosyalar</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Yedek Adı</th>
                                <th>Tür</th>
                                <th>Boyut</th>
                                <th>Süre</th>
                                <th>Tarih</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="backups-table-body">
                            <tr><td colspan="7" class="text-center py-4 text-muted">Yükleniyor...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <small class="text-muted" id="bp-pagination-info">Gösterilen: 0 - 0 / Toplam: 0</small>
                    <nav><ul class="pagination pagination-sm mb-0" id="bp-pagination-list"></ul></nav>
                </div>
            </div>
        </div>

        <!-- Schedules Tab -->
        <div class="tab-pane fade" id="tab-bp-schedules" role="tabpanel">
            <div class="row g-3">
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header bg-white">Yeni Otomatik Zamanlayıcı</div>
                        <div class="card-body">
                            <form onsubmit="BackupPro.saveSchedule(event)">
                                <div class="mb-3">
                                    <label class="form-label font-weight-bold small">Zamanlayıcı Adı</label>
                                    <input type="text" class="form-control" name="name" required placeholder="Örn: Günlük Gece Yedeği">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label font-weight-bold small">Yedek Türü</label>
                                    <select class="form-select" name="backup_type">
                                        <option value="full">Tam Yedek</option>
                                        <option value="db_only">Sadece Veritabanı</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label font-weight-bold small">Cron İfadesi</label>
                                    <input type="text" class="form-control" name="cron_expression" value="0 2 * * *" required>
                                    <small class="text-muted">Varsayılan: Her gece 02:00 (`0 2 * * *`)</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label font-weight-bold small">Saklanacak Son Yedek Sayısı</label>
                                    <input type="number" class="form-control" name="keep_count" value="7">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Zamanlayıcıyı Kaydet</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header bg-white">Aktif Zamanlayıcılar</div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ad</th>
                                        <th>Tür</th>
                                        <th>Cron</th>
                                        <th>Saklama</th>
                                        <th>Durum</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="schedules-table-body">
                                    <tr><td colspan="6" class="text-center py-3 text-muted">Kayıtlı zamanlayıcı bulunamadı.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div class="tab-pane fade" id="tab-bp-settings" role="tabpanel">
            <div class="card">
                <div class="card-header bg-white">Genel Yapılandırma</div>
                <div class="card-body">
                    <form onsubmit="BackupPro.saveSettings(event)">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold small">Sıkıştırma Seviyesi</label>
                                <select class="form-select mb-1" name="compression_level">
                                    <option value="fast" {if $bpSettings.compression_level == 'fast'}selected{/if}>Hızlı (Fast)</option>
                                    <option value="balanced" {if $bpSettings.compression_level == 'balanced' || !$bpSettings.compression_level}selected{/if}>Dengeli (Balanced)</option>
                                    <option value="maximum" {if $bpSettings.compression_level == 'maximum'}selected{/if}>Maksimum (Maximum)</option>
                                </select>
                                <small class="text-muted d-block" style="font-size:11px; line-height: 1.4;">
                                    ⚡ <strong>Hızlı:</strong> En düşük sıkıştırma, maksimum işlem hızı. (Süre kazanmak için)<br>
                                    ⚖️ <strong>Dengeli:</strong> Standart sıkıştırma oranı ve işlem süresi. (Önerilen)<br>
                                    📦 <strong>Maksimum:</strong> En yüksek sıkıştırma, en uzun işlem süresi.<br>
                                    💡 <strong>Not:</strong> JPG, PNG, WEBP, PDF ve zip gibi zaten sıkıştırılmış binary dosyalar tekrar küçültülemez. Bu yüzden görsel ağırlıklı sitelerde toplam boyutta büyük bir fark oluşmayacaktır. En yüksek verim metin bazlı kod ve SQL dosyalarında elde edilir.
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold small">Hariç Tutulacak Klasörler</label>
                                <input type="text" class="form-control" name="exclude_folders" value="{$bpSettings.exclude_folders|escape:'html'}">
                                <small class="text-muted">Virgülle ayırın. Örn: `cache,logs,tmp,node_modules`</small>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-sm">Ayarları Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Logs Tab -->
        <div class="tab-pane fade" id="tab-bp-logs" role="tabpanel">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span>Canlı İşlem Günlüğü</span>
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm">
                            <a href="{$domain}api/module.php?m=backup-pro&action=logs&export=csv" class="btn btn-outline-secondary">CSV</a>
                            <a href="{$domain}api/module.php?m=backup-pro&action=logs&export=json" class="btn btn-outline-secondary">JSON</a>
                            <a href="{$domain}api/module.php?m=backup-pro&action=logs&export=txt" class="btn btn-outline-secondary">TXT</a>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="BackupPro.clearLogs()">Günlükleri Temizle</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="live-log-box" id="bp-live-logs-container">Yükleniyor...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Confirm Modal -->
<div class="modal fade" id="bp-confirm-modal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light">
                <h5 class="modal-title h6 mb-0 text-dark" id="bp-confirm-title">İşlem Onayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body py-3" id="bp-confirm-message">
                Bu işlemi gerçekleştirmek istediğinize emin misiniz?
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-danger btn-sm" id="bp-confirm-btn">Evet, Onayla</button>
            </div>
        </div>
    </div>
</div>

<script src="{$domain}modules/backup-pro/assets/js/admin.js?v={$smarty.now}"></script>
{literal}
<script>
    if (typeof runBackupProInit === 'function') {
        runBackupProInit();
    }
</script>
{/literal}
