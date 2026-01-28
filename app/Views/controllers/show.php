<div class="container">
    <div class="page-header">
        <h1><?php echo $title ?? 'Detail Kontroler'; ?></h1>
        <a href="<?= base_url('/controllers') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
    </div>

    <?php if ($controller): ?>
        <?php
            $isOnline = (time() - strtotime($controller['last_update'])) < 60;
            $waterLevel = $controller['latest_water_level'] ?? 0;
            
            // Ambil pengaturan warna dari database
            $settings = \app\Models\IndicatorSetting::getSettings();
            $fillColor = $settings['color_low']; // Default
            if ($waterLevel > $settings['threshold_medium']) {
                $fillColor = $settings['color_high'];
            } elseif ($waterLevel > $settings['threshold_low']) {
                $fillColor = $settings['color_medium'];
            }
        ?>

        <!-- Kartu Statistik Utama -->
        <div class="stat-cards-container" style="margin-bottom: 30px;">
            <!-- Kartu Status Pompa -->
            <div class="stat-card">
                <div class="stat-card-icon <?php echo ($controller['status'] === 'ON') ? 'bg-green' : 'bg-red'; ?>">
                    <i class="fas fa-power-off"></i>
                </div>
                <div>
                    <div class="stat-card-title">Status Pompa</div>
                    <div class="stat-card-value"><?php echo htmlspecialchars($controller['status']); ?></div>
                </div>
            </div>

            <!-- Kartu Mode Kontrol -->
            <div class="stat-card">
                <div class="stat-card-icon bg-blue">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <div>
                    <div class="stat-card-title">Mode Kontrol</div>
                    <div class="stat-card-value"><?php echo htmlspecialchars(strtoupper($controller['control_mode'])); ?></div>
                </div>
            </div>

            <!-- Kartu Konektivitas -->
            <div class="stat-card">
                <div class="stat-card-icon <?php echo $isOnline ? 'bg-green' : 'bg-red'; ?>">
                    <i class="fas fa-wifi"></i>
                </div>
                <div>
                    <div class="stat-card-title">Konektivitas</div>
                    <div class="stat-card-value"><?php echo $isOnline ? 'ONLINE' : 'OFFLINE'; ?></div>
                </div>
            </div>

            <!-- Kartu Sinyal WiFi -->
            <div class="stat-card">
                <div class="stat-card-icon bg-orange">
                    <i class="fas fa-signal"></i>
                </div>
                <div>
                    <div class="stat-card-title">Sinyal WiFi</div>
                    <div class="stat-card-value"><?php echo (!empty($controller['rssi']) && $controller['rssi'] != 0) ? $controller['rssi'] . ' dBm' : 'N/A'; ?></div>
                </div>
            </div>
        </div>

        <!-- Grid untuk Gauge dan Detail -->
        <div class="controller-detail-grid">
            <!-- Gauge Level Air -->
            <div id="gauge-container"></div>

            <!-- Detail Konfigurasi -->
            <div class="card">
                <h2>Detail Konfigurasi</h2>
                <ul style="list-style: none; padding: 0;">
                    <li><strong>MAC Address:</strong> <?php echo htmlspecialchars($controller['mac_address']); ?></li>
                    <li><strong>Versi Firmware:</strong> <?php echo htmlspecialchars($controller['firmware_version'] ?? 'Belum dilaporkan'); ?></li>
                    <li><strong>Update Terakhir:</strong> <?php echo htmlspecialchars($controller['last_update']); ?></li>
                    <hr style="border: none; border-top: 1px solid var(--light-gray); margin: 10px 0;">
                    <li><strong>Durasi Nyala Maks:</strong> <?php echo htmlspecialchars($controller['on_duration']); ?> menit</li>
                    <li><strong>Durasi Istirahat Min:</strong> <?php echo htmlspecialchars($controller['off_duration']); ?> menit</li>
                    <li><strong>Jarak Tangki Penuh:</strong> <?php echo htmlspecialchars($controller['full_tank_distance']); ?> cm</li>
                    <li><strong>Pemicu Pompa:</strong> <?php echo htmlspecialchars($controller['trigger_percentage']); ?>%</li>
                </ul>
            </div>
        </div>

        <!-- Riwayat Peristiwa -->
        <div class="card" style="margin-top: 20px;">
            <h2 style="text-align: left; font-weight: normal;">Riwayat Peristiwa Terbaru</h2>
            <ul class="log-list" style="max-height: 300px;">
                <?php if (!empty($eventLogs)): ?>
                    <?php foreach ($eventLogs as $log): ?>
                        <?php
                            $icon = 'fas fa-info-circle'; // Default icon
                            $color = ''; // Default color class
                            $eventType = strtolower($log['event_type']);

                            // Logika penentuan ikon dan warna berdasarkan tipe event
                            if (strpos($eventType, 'power on') !== false || strpos($eventType, 'boot') !== false) {
                                $icon = 'fas fa-bolt';
                                $color = 'log-power'; // Class khusus untuk Power On
                            } elseif (strpos($eventType, 'reconnected') !== false || strpos($eventType, 'online') !== false) {
                                $icon = 'fas fa-wifi';
                                $color = 'log-success';
                            } elseif (strpos($eventType, 'offline') !== false || strpos($eventType, 'lost') !== false) {
                                $icon = 'fas fa-exclamation-triangle';
                                $color = 'log-warning';
                            }
                        ?>
                        <li class="log-item <?php echo $color; ?>">
                            <div style="display: flex; align-items: center;">
                                <i class="<?php echo $icon; ?>"></i>
                                <span class="log-message"><?php echo htmlspecialchars($log['message'] ?? $log['event_type']); ?></span>
                            </div>
                            <span class="log-timestamp"><?php echo date('d M Y, H:i:s', strtotime($log['event_time'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="log-item">Tidak ada riwayat peristiwa.</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php else: ?>
        <p>Data kontroler tidak ditemukan.</p>
    <?php endif; ?>
</div>

<!-- Modal Konfirmasi Custom -->
<div id="confirmationModal" class="notif-modal">
    <div class="notif-modal-content">
        <div class="notif-icon" style="color: #f39c12;"><i class="fas fa-question-circle"></i></div>
        <h3 class="notif-title">Konfirmasi</h3>
        <p class="notif-message" id="confirmationMessage">Apakah Anda yakin?</p>
        <div style="display: flex; justify-content: center; gap: 10px;">
            <button id="confirmBtn" class="btn btn-primary">Ya</button>
            <button id="cancelBtn" class="btn btn-secondary">Batal</button>
        </div>
    </div>
</div>

<!-- Muat semua library yang mungkin diperlukan oleh template -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn3.devexpress.com/jslib/17.1.6/js/dx.all.js"></script>
<script src="https://code.jscharting.com/latest/jscharting.js"></script>

<script>
    // Fungsi untuk mengirim perintah ke API
    function sendApiCommand(mac, action, value, buttonElement = null) {
        if (buttonElement) {
            buttonElement.disabled = true;
            buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        }

        fetch(`${BASE_URL}api/update`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mac: mac, action: action, value: value })
        })
        .then(response => response.json())
        .then(data => {
            // Reload halaman untuk melihat perubahan status terbaru
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal mengirim perintah.');
            if (buttonElement) buttonElement.disabled = false;
        });
    }

    // Fungsi helper untuk menampilkan modal konfirmasi
    function showConfirmation(message, onConfirm) {
        const modal = document.getElementById('confirmationModal');
        const msgElement = document.getElementById('confirmationMessage');
        const confirmBtn = document.getElementById('confirmBtn');
        const cancelBtn = document.getElementById('cancelBtn');

        msgElement.textContent = message;
        modal.style.display = 'flex';

        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        const newCancelBtn = cancelBtn.cloneNode(true);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

        newConfirmBtn.addEventListener('click', function() {
            modal.style.display = 'none';
            onConfirm();
        });

        newCancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

document.addEventListener('DOMContentLoaded', function() {
    const gaugeContainer = document.getElementById('gauge-container');
    const activeTemplate = <?php echo json_encode($active_template ?? null); ?>;
    const waterLevel = <?php echo $waterLevel; ?>;
    const fillColor = '<?php echo $fillColor; ?>';

    if (!gaugeContainer || !activeTemplate || !activeTemplate.html) {
        gaugeContainer.innerHTML = '<p>Template tampilan tidak ditemukan atau tidak valid.</p>';
        return;
    }

    // 1. Inject CSS template
    if (activeTemplate.css) {
        const styleEl = document.createElement('style');
        styleEl.textContent = activeTemplate.css;
        document.head.appendChild(styleEl);
    }

    // 2. Inject dan jalankan JS template (jika ada)
    if (activeTemplate.js) {
        const scriptEl = document.createElement('script');
        scriptEl.textContent = activeTemplate.js;
        document.body.appendChild(scriptEl);
    }

    // 3. Buat elemen gauge dari HTML template
    let html = activeTemplate.html.replace(/{{TANK_NAME}}/g, '<?php echo htmlspecialchars($controller['tank_name'] ?? 'N/A'); ?>');
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    const gaugeCard = tempDiv.querySelector('.gauge-card');

    if (gaugeCard) {
        // Panggil fungsi inisialisasi jika ada
        if (typeof window.initGauge === 'function') {
            window.initGauge(gaugeCard);
        }

        // --- TAMBAHAN: Tombol Kontrol di dalam Gauge ---
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'gauge-actions';
        
        const mac = '<?php echo $controller['mac_address']; ?>';
        const mode = '<?php echo $controller['control_mode']; ?>';
        const status = '<?php echo $controller['status']; ?>';
        const isManual = (mode === 'MANUAL');
        
        const newMode = isManual ? 'AUTO' : 'MANUAL';
        const newStatus = (status === 'ON') ? 'OFF' : 'ON';
        
        actionsDiv.innerHTML = `
            <button class="btn-action btn-mode-toggle ${mode === 'AUTO' ? 'btn-blue' : 'btn-gray'}" 
                    data-mac="${mac}" 
                    data-new-mode="${newMode}">
                ${mode === 'AUTO' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-hand-paper"></i>'} ${mode}
            </button>
            <button class="btn-action btn-pump-toggle ${status === 'ON' ? 'btn-green' : 'btn-red'}" 
                    data-mac="${mac}" 
                    data-new-status="${newStatus}"
                    ${!isManual ? 'disabled' : ''}
                    title="${!isManual ? 'Hanya aktif di mode MANUAL' : `Ubah ke ${newStatus}`}">
                <i class="fas fa-power-off"></i> ${status}
            </button>
        `;
        gaugeCard.appendChild(actionsDiv);

        gaugeContainer.appendChild(gaugeCard);

        // 4. Update gauge dengan nilai saat ini
        if (typeof window.updateGauge === 'function') {
            // Jika template menyediakan fungsi update sendiri
            window.updateGauge(gaugeCard, waterLevel, fillColor);
        } else {
            // Gunakan logika universal untuk template sederhana
            const elementsToUpdate = gaugeCard.querySelectorAll('[data-update-style]');
            elementsToUpdate.forEach(el => {
                const styleProp = el.dataset.updateStyle;
                if (styleProp === 'degrees') {
                    const finalValue = (waterLevel / 100) * 270;
                    el.style.setProperty('--percentage', `${finalValue}deg`);
                    el.style.setProperty('--fill-color', fillColor);
                } else if (styleProp === 'percentage') {
                    // PERBAIKAN: Cek juga parent container untuk memastikan ini adalah gauge vertikal
                    if (el.classList.contains('tank-gauge-water') || el.closest('.tank-gauge-container')) {
                        el.style.height = `${waterLevel}%`;
                        el.style.width = '100%';
                    } else if (el.classList.contains('simple-bar-gauge-fill') || el.closest('.simple-bar-gauge-container')) {
                        el.style.width = `${waterLevel}%`;
                        el.style.height = '100%';
                    } else {
                        el.style.width = `${waterLevel}%`;
                        el.style.height = `${waterLevel}%`;
                    }
                    el.style.backgroundColor = fillColor;
                }
            });
            const textElement = gaugeCard.querySelector('.value') || gaugeCard.querySelector('.tank-gauge-text') || gaugeCard.querySelector('.simple-bar-gauge-text');
            if (textElement) {
                textElement.textContent = `${Math.round(waterLevel)}%`;
            }
        }
    }

    // Event Listener untuk Tombol
    gaugeContainer.addEventListener('click', function(event) {
        const btnTarget = event.target.closest('.btn-action');
        if (btnTarget) {
            if (btnTarget.classList.contains('btn-mode-toggle')) {
                const mac = btnTarget.dataset.mac;
                const newMode = btnTarget.dataset.newMode;
                showConfirmation(`Ubah mode ke ${newMode}?`, function() {
                    sendApiCommand(mac, 'set_mode', newMode, btnTarget);
                });
            } else if (btnTarget.classList.contains('btn-pump-toggle') && !btnTarget.disabled) {
                const mac = btnTarget.dataset.mac;
                const newStatus = btnTarget.dataset.newStatus;
                sendApiCommand(mac, 'set_status', newStatus, btnTarget);
            }
        }
    });
});
</script>