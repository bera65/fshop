function getBackupApiUrl(action) {
    if (window.backupProApiBase) {
        var url = window.backupProApiBase + action;
    } else {
        let base = window.location.origin + '/';
        if (typeof domain !== 'undefined' && domain) {
            base = domain;
        } else if (typeof adminUrl !== 'undefined' && adminUrl) {
            base = adminUrl.split('/admin')[0] + '/';
        }
        var url = base.replace(/\/+$/, '/') + 'api/module.php?m=backup-pro&action=' + action;
    }
    var token = window.backupProAdminToken || window.adminToken || '';
    if (token) {
        url += (url.indexOf('?') >= 0 ? '&' : '?') + 'token=' + encodeURIComponent(token);
    }
    return url;
}

var BackupPro = window.BackupPro || {
    isProcessing: false,
    isPaused: false,
    currentBackupId: null,
    startTime: null,
    currentPage: 1,
    _queueTimer: null,
    _elapsedTimer: null,

    init() {
        this.bindEvents();
        this.loadDashboard();
        this.loadBackups(1);
        this.loadLogs();
        this.loadSchedules();
        this.loadDestinations();
        this.startPolling();

        // Sayfa yenilendiyse ve yedekleme devam ediyorsa kaldığı yerden devam et
        const savedStart  = localStorage.getItem('bp_start_time');
        const savedBkpId  = localStorage.getItem('bp_backup_id');
        if (savedStart && savedBkpId) {
            // Sunucudan durumu kontrol et
            fetch(getBackupApiUrl('progress&backup_id=' + savedBkpId))
                .then(r => r.json())
                .then(d => {
                    if (d.success && d.is_running && d.status === 'in_progress') {
                        this.startTime       = parseInt(savedStart, 10);
                        this.currentBackupId = parseInt(savedBkpId, 10);
                        this.isProcessing    = true;
                        this.isPaused        = false;
                        // Progress kartını göster
                        const card = document.getElementById('bp-progress-card');
                        if (card) card.classList.remove('d-none');
                        this.setStartStopBtn('running');
                        this.runQueueBatch();
                    } else {
                        // Bitti veya başka durum — localStorage temizle
                        localStorage.removeItem('bp_start_time');
                        localStorage.removeItem('bp_backup_id');
                    }
                })
                .catch(() => {
                    localStorage.removeItem('bp_start_time');
                    localStorage.removeItem('bp_backup_id');
                });
        }
    },

    bindEvents() {
        const self = this;
        const searchInput = document.getElementById('bp-search');
        if (searchInput) {
            searchInput.addEventListener('input', () => self.loadBackups(1));
        }
        const typeSelect = document.getElementById('bp-type-filter');
        if (typeSelect) {
            typeSelect.addEventListener('change', () => self.loadBackups(1));
        }
    },

    loadDashboard() {
        fetch(getBackupApiUrl('stats'))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.updateStatsUI(data.stats, data.health);
                }
            })
            .catch(err => console.error('BP Dashboard error:', err));
    },

    updateStatsUI(stats, health) {
        document.getElementById('stat-total-backups').innerText = stats.total;
        document.getElementById('stat-successful').innerText = stats.successful;
        document.getElementById('stat-failed').innerText = stats.failed;
        document.getElementById('stat-total-size').innerText = stats.formatted_total_bytes;

        document.getElementById('stat-free-disk').innerText = stats.formatted_free_disk;
        document.getElementById('stat-disk-pct').innerText = '%' + stats.disk_usage_pct + ' Kullanımda';

        const diskBar = document.getElementById('stat-disk-bar');
        if (diskBar) {
            diskBar.style.width = stats.disk_usage_pct + '%';
        }

        if (health) {
            document.getElementById('health-php-ver').innerText = health.php_version;
            document.getElementById('health-zip').innerHTML = health.zip_supported ? '<span class="text-success">✔ Mevcut</span>' : '<span class="text-danger">✖ Yok</span>';
            document.getElementById('health-memory').innerText = health.memory_limit;
            document.getElementById('health-exec-time').innerText = health.max_execution_time + 's';
        }
    },

    loadBackups(page = 1) {
        this.currentPage = page;
        const search = document.getElementById('bp-search') ? document.getElementById('bp-search').value : '';
        const type = document.getElementById('bp-type-filter') ? document.getElementById('bp-type-filter').value : '';

        fetch(getBackupApiUrl('backups&page=' + page + '&search=' + encodeURIComponent(search) + '&type=' + type))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.renderBackupsTable(data.backups);
                    this.renderPagination(data.page, data.total_pages, data.total, data.limit);
                }
            })
            .catch(err => console.error('BP Backups load error:', err));
    },

    renderBackupsTable(backups) {
        const tbody = document.getElementById('backups-table-body');
        if (!tbody) return;

        if (backups.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Henüz oluşturulmuş yedek bulunmuyor.</td></tr>';
            return;
        }

        const domainUrl = window.optDomain || (typeof domain !== 'undefined' ? domain : '/');
        tbody.innerHTML = '';

        backups.forEach(b => {
            let badge = `<span class="badge badge-status-${b.status}">${b.status.toUpperCase()}</span>`;
            let actions = '';

            if (b.status === 'in_progress') {
                actions = `
                    <button type="button" class="btn btn-outline-warning" onclick="BackupPro.cancelBackup(${b.id})">İptal Et</button>
                    <a href="${getBackupApiUrl('download&id=' + b.id)}" class="btn btn-outline-secondary">İndir</a>
                    <button type="button" class="btn btn-outline-danger" onclick="BackupPro.deleteBackup(${b.id})">Sil</button>
                `;
            } else {
                actions = `
                    <button type="button" class="btn btn-outline-primary" onclick="BackupPro.startRestore(${b.id})">Geri Yükle</button>
                    <a href="${getBackupApiUrl('download&id=' + b.id)}" class="btn btn-outline-secondary">İndir</a>
                    <button type="button" class="btn btn-outline-danger" onclick="BackupPro.deleteBackup(${b.id})">Sil</button>
                `;
            }

            let displayName = b.backup_name;
            const ext = '.' + (b.archive_format || 'zip');
            if (!displayName.toLowerCase().endsWith(ext)) {
                displayName += ext;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${displayName}</strong></td>
                <td><span class="badge bg-light text-dark border">${b.type.toUpperCase()}</span></td>
                <td>${b.formatted_size}</td>
                <td>${b.formatted_duration}</td>
                <td><small class="text-muted">${b.created_at}</small></td>
                <td>${badge}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        ${actions}
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    },

    renderPagination(current, totalPages, totalCount, limit) {
        const list = document.getElementById('bp-pagination-list');
        const info = document.getElementById('bp-pagination-info');
        if (!list) return;

        list.innerHTML = '';
        const start = (current - 1) * limit + 1;
        const end = Math.min(current * limit, totalCount);
        if (info) {
            info.innerText = `Gösterilen: ${totalCount > 0 ? start : 0} - ${end} / Toplam: ${totalCount}`;
        }

        if (totalPages <= 1) return;

        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            li.className = 'page-item ' + (i === current ? 'active' : '');
            li.innerHTML = `<a class="page-link" href="#" onclick="BackupPro.loadBackups(${i}); return false;">${i}</a>`;
            list.appendChild(li);
        }
    },

    // ──────────────────────────────────────────
    // Button toggle helper
    // ──────────────────────────────────────────
    setStartStopBtn(state) {
        const btn  = document.getElementById('bp-start-stop-btn');
        const icon = document.getElementById('bp-start-stop-icon');
        const text = document.getElementById('bp-start-stop-text');
        if (!btn) return;

        if (state === 'running') {
            btn.className  = 'btn btn-danger btn-sm w-100';
            if (icon) icon.textContent = '⏹';
            if (text) text.textContent = 'Yedeklemeyi Durdur';
            btn.disabled = false;
        } else {
            btn.className  = 'btn btn-primary btn-sm w-100';
            if (icon) icon.textContent = '▶';
            if (text) text.textContent = 'Yedeklemeyi Başlat';
            btn.disabled = false;
        }
    },

    // Called by the form's onsubmit — starts or stops depending on state
    toggleBackup() {
        if (this.isProcessing) {
            this.stopBackup();
        } else {
            this.startBackupWizard();
        }
    },

    // Stop / cancel the running backup
    stopBackup() {
        if (!this.isProcessing) return;

        this.showConfirm('Yedeklemeyi Durdur', 'Devam eden yedekleme işlemini durdurmak istediğinize emin misiniz? Yarıda kalan geçici dosyalar temizlenecektir.', () => {
            const backupId = this.currentBackupId || 0;
            fetch(getBackupApiUrl('cancel' + (backupId ? '&backup_id=' + backupId : '')))
                .then(res => res.json())
                .then(data => {
                    clearTimeout(this._queueTimer);
                    this.isProcessing = false;
                    this.currentBackupId = null;
                    localStorage.removeItem('bp_start_time');
                    localStorage.removeItem('bp_backup_id');
                    this.setStartStopBtn('idle');

                    const bar = document.getElementById('bp-progress-bar');
                    if (bar) {
                        bar.style.width = '0%';
                        bar.innerText  = '';
                        bar.classList.remove('bg-primary');
                        bar.classList.add('bg-warning');
                    }
                    const progressCard = document.getElementById('bp-progress-card');
                    if (progressCard) progressCard.classList.add('d-none');

                    this.showToast('⛔ Yedekleme işlemi durduruldu.', 'warning');
                    this.loadDashboard();
                    this.loadBackups(1);
                    this.loadLogs();
                })
                .catch(() => this.showToast('❌ Durdurma isteği başarısız oldu.', 'danger'));
        });
    },



    startBackupWizard() {
        const type   = document.getElementById('wizard-backup-type').value;
        const name   = document.getElementById('wizard-backup-name').value;
        const format = document.getElementById('wizard-archive-format').value;

        // Disable button while request is in-flight
        const btn = document.getElementById('bp-start-stop-btn');
        if (btn) { btn.disabled = true; }

        fetch(getBackupApiUrl('create&type=' + type + '&name=' + encodeURIComponent(name) + '&format=' + format))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Switch to Dashboard tab
                    const dashTabTrigger = document.querySelector('button[data-bs-target="#tab-bp-dashboard"]');
                    if (dashTabTrigger) {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                            new bootstrap.Tab(dashTabTrigger).show();
                        } else {
                            dashTabTrigger.click();
                        }
                    }

                    // Reset progress card styling & content
                    const progressCard = document.getElementById('bp-progress-card');
                    if (progressCard) {
                        progressCard.classList.remove('d-none', 'border-success', 'border-danger');
                        progressCard.classList.add('border-primary');
                        
                        // Reset header title and restore spinner
                        const title = progressCard.querySelector('h5');
                        if (title) {
                            title.className = "card-title h6 text-primary mb-0";
                            title.innerHTML = `
                                <span class="spinner-border spinner-border-sm me-1" role="status" style="width:14px;height:14px;border-width:2px;"></span>
                                Yedekleme İşlemi Devam Ediyor...
                            `;
                        }
                    }

                    const progressBar = document.getElementById('bp-progress-bar');
                    if (progressBar) {
                        progressBar.style.width = '5%';
                        progressBar.innerText = ''; // Clear text inside progress bar
                        progressBar.classList.remove('bg-danger', 'bg-warning', 'bg-success');
                        progressBar.classList.add('bg-primary', 'progress-bar-animated', 'progress-bar-striped');
                    }

                    // Reset labels & elapsed timer display to zero state
                    const elEl = document.getElementById('bp-elapsed-time');
                    if (elEl) elEl.textContent = '⏱ 00:00:00';
                    const labelEl = document.getElementById('bp-progress-label');
                    if (labelEl) labelEl.textContent = 'Başlatılıyor...';
                    const pctEl = document.getElementById('bp-progress-pct-text');
                    if (pctEl) pctEl.textContent = '0%';

                    // Toggle button to "Stop" state
                    this.setStartStopBtn('running');

                    this.showToast('✅ ' + (data.message || 'Yedekleme işlemi başarıyla başlatıldı!'), 'success');

                    this.currentBackupId = data.backup_id;
                    this.isProcessing    = true;
                    this.isPaused        = false;
                    this.startTime       = Date.now();
                    // Sayfa yenilense de süre devam etsin
                    localStorage.setItem('bp_start_time', this.startTime);
                    localStorage.setItem('bp_backup_id', data.backup_id);
                    this.runQueueBatch();
                } else {
                    if (btn) btn.disabled = false;
                    this.showToast('❌ ' + (data.message || 'Hata oluştu.'), 'danger');
                }
            })
            .catch(() => {
                if (btn) btn.disabled = false;
                this.showToast('❌ Yedekleme başlatılırken hata oluştu.', 'danger');
            });
    },

    runQueueBatch() {
        if (!this.isProcessing || this.isPaused) return;

        const backupId = this.currentBackupId || '';
        fetch(getBackupApiUrl('progress' + (backupId ? '&backup_id=' + backupId : '')))
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    // Retry after short delay
                    if (this.isProcessing) {
                        this._queueTimer = setTimeout(() => this.runQueueBatch(), 1000);
                    }
                    return;
                }

                 const pct = data.pct || 5;
                 const bar = document.getElementById('bp-progress-bar');
                 if (bar) {
                     bar.style.width = pct + '%';
                     bar.innerText = ''; // Clear text inside for sleek look
                 }
                 const pctEl = document.getElementById('bp-progress-pct-text');
                 if (pctEl) {
                     pctEl.textContent = Math.round(pct) + '%';
                 }

                // Geçen süre hesapla
                if (this.startTime) {
                    const secTotal = Math.floor((Date.now() - this.startTime) / 1000);
                    const h = Math.floor(secTotal / 3600);
                    const m = Math.floor((secTotal % 3600) / 60);
                    const s = secTotal % 60;
                    
                    // Dijital saat formatı (00:00:17)
                    const pad = (num) => String(num).padStart(2, '0');
                    const digitalTime = pad(h) + ':' + pad(m) + ':' + pad(s);

                    const elEl = document.getElementById('bp-elapsed-time');
                    if (elEl) elEl.textContent = '⏱ ' + digitalTime;

                    const labelEl = document.getElementById('bp-progress-label');
                    if (labelEl) {
                        const currentSize = data.formatted_size || '0 B';
                        labelEl.textContent = currentSize + ' yedeklendi — ' + digitalTime + ' saniye (' + (data.batches_done || 0) + ' adım)';
                    }
                }

                if (data.status === 'completed' || pct >= 100) {
                    // Progress bar → yeşil
                    if (bar) {
                        bar.style.width = '100%';
                        bar.innerText = '';
                        bar.classList.remove('bg-primary', 'bg-danger', 'bg-warning', 'progress-bar-animated');
                        bar.classList.add('bg-success');
                    }

                    // Kart başlığını güncelle
                    const card = document.getElementById('bp-progress-card');
                    if (card) {
                        card.classList.remove('border-primary', 'border-danger');
                        card.classList.add('border-success');
                        const title = card.querySelector('h5');
                        if (title) {
                            title.innerHTML = '<span class="text-success">✅ Yedekleme Başarıyla Tamamlandı</span>';
                        }
                        const spinner = card.querySelector('.spinner-border');
                        if (spinner) spinner.remove();
                    }

                    // Geçen süre — son güncelleme
                    const elEl = document.getElementById('bp-elapsed-time');
                    const labelEl = document.getElementById('bp-progress-label');
                    if (labelEl) {
                        const finalSize = data.formatted_size || '0 B';
                        labelEl.textContent = 'Toplam ' + finalSize + ' başarıyla yedeklendi.';
                    }
                    const pctEl = document.getElementById('bp-progress-pct-text');
                    if (pctEl) pctEl.textContent = '100%';

                    this.isProcessing = false;
                    this.currentBackupId = null;
                    clearTimeout(this._queueTimer);
                    localStorage.removeItem('bp_start_time');
                    localStorage.removeItem('bp_backup_id');
                    this.setStartStopBtn('idle');
                    this.showToast('✅ Yedekleme işlemi başarıyla tamamlandı!', 'success');
                    this.loadDashboard();
                    this.loadBackups(1);
                    this.loadLogs();

                    // 6 saniye sonra progress kartı gizle
                    setTimeout(() => {
                        if (card) card.classList.add('d-none');
                    }, 6000);
                } else if (data.status === 'failed') {
                    this.isProcessing = false;
                    this.currentBackupId = null;
                    clearTimeout(this._queueTimer);
                    localStorage.removeItem('bp_start_time');
                    localStorage.removeItem('bp_backup_id');
                    this.setStartStopBtn('idle');

                     if (bar) {
                         bar.style.width = '100%';
                         bar.innerText = '';
                         bar.classList.remove('bg-primary', 'bg-success', 'progress-bar-animated');
                         bar.classList.add('bg-danger');
                     }

                    // Kart başlığını kırmızıya çevir
                    const failCard = document.getElementById('bp-progress-card');
                    if (failCard) {
                        failCard.classList.remove('border-primary', 'border-success');
                        failCard.classList.add('border-danger');
                        const failTitle = failCard.querySelector('h5');
                        if (failTitle) {
                            failTitle.innerHTML = '<span class="text-danger">❌ Yedekleme Başarısız Oldu</span>';
                        }
                        const spinner = failCard.querySelector('.spinner-border');
                        if (spinner) spinner.remove();
                    }

                    const pctEl = document.getElementById('bp-progress-pct-text');
                    if (pctEl) pctEl.textContent = '0%';

                    this.showToast('❌ Yedekleme başarısız oldu. İşlem Günlükleri sekmesini kontrol edin.', 'danger');
                    this.loadDashboard();
                    this.loadBackups(1);
                    this.loadLogs();
                } else {
                    // Still running — poll again quickly (sunucu zaten 25s çalıştı, hemen tekrar)
                    if (this.isProcessing) {
                        this._queueTimer = setTimeout(() => this.runQueueBatch(), 1000);
                    }
                }
            })
            .catch(err => {
                console.error('Queue progress error:', err);
                // Network error — retry
                if (this.isProcessing) {
                    this._queueTimer = setTimeout(() => this.runQueueBatch(), 5000);
                }
            });
    },

    startRestore(backupId) {
        if (!confirm('Bu yedeği geri yüklemek istediğinize emin misiniz? Mevcut verilerin üzerine yazılacaktır.')) return;

        fetch(getBackupApiUrl('restore&backup_id=' + backupId + '&mode=dry_run'))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (confirm(`Simülasyon Tamamlandı:\n${data.simulation_status}\n\nGerçek geri yüklemeyi başlatmak istiyor musunuz?`)) {
                        fetch(getBackupApiUrl('restore&backup_id=' + backupId + '&mode=execute'))
                            .then(r => r.json())
                            .then(resData => {
                                alert(resData.message);
                                this.loadDashboard();
                                this.loadBackups(1);
                                this.loadLogs();
                            });
                    }
                } else {
                    alert(data.message);
                }
            });
    },

    showConfirm(title, message, callback) {
        const modalEl = document.getElementById('bp-confirm-modal');
        if (!modalEl) {
            if (window.confirm(message)) {
                callback();
            }
            return;
        }

        if (modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }

        document.getElementById('bp-confirm-title').innerText = title;
        document.getElementById('bp-confirm-message').innerText = message;

        const confirmBtn = document.getElementById('bp-confirm-btn');
        const newBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

        const closeModal = () => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const bsModal = bootstrap.Modal.getInstance(modalEl);
                if (bsModal) bsModal.hide();
            }
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.remove();
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        };

        newBtn.addEventListener('click', () => {
            closeModal();
            callback();
        });

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
        }
    },

    cancelBackup(id) {
        this.showConfirm('Yedeklemeyi İptal Et', 'Devam eden bu yedekleme işlemini iptal etmek istediğinize emin misiniz?', () => {
            fetch(getBackupApiUrl('cancel&backup_id=' + id))
                .then(res => res.json())
                .then(data => {
                    this.showToast('⛔ ' + (data.message || 'Yedekleme işlemi iptal edildi.'), 'warning');
                    this.isProcessing = false;
                    localStorage.removeItem('bp_start_time');
                    localStorage.removeItem('bp_backup_id');
                    this.loadDashboard();
                    this.loadBackups(this.currentPage);
                    this.loadLogs();
                })
                .catch(err => {
                    console.error('Cancel error:', err);
                    this.showToast('❌ İptal işlemi sırasında hata oluştu.', 'danger');
                });
        });
    },

    deleteBackup(id) {
        this.showConfirm('Yedeği Sil', 'Bu yedeği silmek istediğinize emin misiniz? Dosya ve veritabanı kaydı kalıcı olarak kaldırılacaktır.', () => {
            fetch(getBackupApiUrl('delete&id=' + id))
                .then(res => res.json())
                .then(data => {
                    this.showToast('🗑️ ' + (data.message || 'Yedek başarıyla silindi.'), 'success');
                    this.loadDashboard();
                    this.loadBackups(this.currentPage);
                    this.loadLogs();
                })
                .catch(err => {
                    console.error('Delete error:', err);
                    this.showToast('❌ Silme işlemi sırasında hata oluştu.', 'danger');
                });
        });
    },

    clearLogs() {
        if (!confirm('Tüm işlem günlükleri silinecek. Devam etmek istiyor musunuz?')) return;
        fetch(getBackupApiUrl('clean&type=clear_logs'))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.showToast('🗑️ Günlükler başarıyla temizlendi.', 'success');
                    this.loadLogs();
                } else {
                    this.showToast('❌ ' + (data.message || 'Günlükler temizlenemedi.'), 'danger');
                }
            })
            .catch(() => this.showToast('❌ Günlük temizleme isteği başarısız.', 'danger'));
    },

    loadLogs() {
        fetch(getBackupApiUrl('logs&limit=100'))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('bp-live-logs-container');
                    if (container) {
                        if (data.logs.length === 0) {
                            container.innerHTML = '[SYSTEM] Henüz işlem kaydı bulunmuyor.';
                            return;
                        }
                        container.innerHTML = data.logs.map(log => {
                            let color = '#ffffff';
                            if (log.level === 'success') color = '#10b981';
                            if (log.level === 'warning') color = '#f59e0b';
                            if (log.level === 'error') color = '#ef4444';
                            return `<div style="color: ${color}">[${log.created_at}] [${log.action.toUpperCase()}] ${log.message}</div>`;
                        }).join('');
                        container.scrollTop = container.scrollHeight;
                    }
                }
            });
    },




    loadSchedules() {
        fetch(getBackupApiUrl('schedules'))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.schedules) {
                    const tbody = document.getElementById('schedules-table-body');
                    if (!tbody) return;
                    tbody.innerHTML = data.schedules.map(s => `
                        <tr>
                            <td><strong>${s.name}</strong></td>
                            <td>${s.backup_type.toUpperCase()}</td>
                            <td><code>${s.cron_expression}</code></td>
                            <td>${s.keep_count} adet</td>
                            <td>${s.active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>'}</td>
                            <td>
                                <button class="btn btn-xs btn-outline-danger" onclick="BackupPro.deleteSchedule(${s.id})">Sil</button>
                            </td>
                        </tr>
                    `).join('');
                }
            });
    },

    loadDestinations() {
        fetch(getBackupApiUrl('destinations'))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.destinations) {
                    const tbody = document.getElementById('destinations-table-body');
                    if (!tbody) return;
                    tbody.innerHTML = data.destinations.map(d => `
                        <tr>
                            <td><strong>${d.name}</strong></td>
                            <td><span class="badge bg-info text-dark">${d.driver.toUpperCase()}</span></td>
                            <td>${d.active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>'}</td>
                            <td>
                                <button class="btn btn-xs btn-outline-danger" onclick="BackupPro.deleteDestination(${d.id})">Sil</button>
                            </td>
                        </tr>
                    `).join('');
                }
            });
    },

    saveSettings(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        fetch(getBackupApiUrl('settings'), {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.showToast('⚙️ ' + (data.message || 'Ayarlar başarıyla kaydedildi.'), 'success');
                } else {
                    this.showToast('❌ ' + (data.message || 'Ayarlar kaydedilemedi.'), 'danger');
                }
                this.loadDashboard();
            })
            .catch(() => this.showToast('❌ Ayarlar kaydedilirken bir hata oluştu.', 'danger'));
    },

    saveSchedule(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        fetch(getBackupApiUrl('schedules&sub_action=save'), {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.showToast('⏱️ ' + (data.message || 'Zamanlayıcı başarıyla kaydedildi.'), 'success');
                } else {
                    this.showToast('❌ ' + (data.message || 'Zamanlayıcı kaydedilemedi.'), 'danger');
                }
                this.loadSchedules();
            })
            .catch(() => this.showToast('❌ Zamanlayıcı kaydedilirken bir hata oluştu.', 'danger'));
    },

    saveDestination(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        fetch(getBackupApiUrl('destinations&sub_action=save'), {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.showToast('💾 ' + (data.message || 'Depolama sürücüsü başarıyla kaydedildi.'), 'success');
                } else {
                    this.showToast('❌ ' + (data.message || 'Depolama sürücüsü kaydedilemedi.'), 'danger');
                }
                this.loadDestinations();
            })
            .catch(() => this.showToast('❌ Depolama sürücüsü kaydedilirken bir hata oluştu.', 'danger'));
    },

    deleteSchedule(id) {
        this.showConfirm('Zamanlayıcıyı Sil', 'Bu zamanlayıcıyı silmek istediğinize emin misiniz?', () => {
            fetch(getBackupApiUrl('schedules&sub_action=delete&id=' + id))
                .then(res => res.json())
                .then(data => {
                    this.showToast('🗑️ Zamanlayıcı başarıyla silindi.', 'success');
                    this.loadSchedules();
                })
                .catch(() => this.showToast('❌ Silme işlemi sırasında bir hata oluştu.', 'danger'));
        });
    },

    deleteDestination(id) {
        this.showConfirm('Sürücüyü Sil', 'Bu depolama sürücüsünü silmek istediğinize emin misiniz?', () => {
            fetch(getBackupApiUrl('destinations&sub_action=delete&id=' + id))
                .then(res => res.json())
                .then(data => {
                    this.showToast('🗑️ Depolama sürücüsü başarıyla silindi.', 'success');
                    this.loadDestinations();
                })
                .catch(() => this.showToast('❌ Silme işlemi sırasında bir hata oluştu.', 'danger'));
        });
    },

    startPolling() {
        setInterval(() => {
            if (!this.isProcessing) {
                this.loadDashboard();
            }
        }, 15000);
    },

    showToast(message, type = 'success') {
        // Create or reuse toast container
        let container = document.getElementById('bp-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'bp-toast-container';
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
            document.body.appendChild(container);
        }

        const colors = { success: '#198754', danger: '#dc3545', info: '#0dcaf0', warning: '#ffc107' };
        const toast = document.createElement('div');
        toast.style.cssText = `background:${colors[type] || colors.info};color:#fff;padding:12px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.3);max-width:380px;font-size:14px;animation:slideIn 0.3s ease;`;
        toast.innerText = message;
        container.appendChild(toast);

        // Add animation
        if (!document.getElementById('bp-toast-style')) {
            const style = document.createElement('style');
            style.id = 'bp-toast-style';
            style.innerText = '@keyframes slideIn{from{opacity:0;transform:translateX(100%)}to{opacity:1;transform:translateX(0)}}';
            document.head.appendChild(style);
        }

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
};

window.BackupPro = BackupPro;

function runBackupProInit() {
    if (window._backupProInitialized) return;
    if (document.getElementById('backup-pro-app')) {
        window._backupProInitialized = true;
        BackupPro.init();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runBackupProInit);
} else {
    runBackupProInit();
}
