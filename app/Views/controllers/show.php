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
            <div class="card stat-card">
                <!-- PERBAIKAN: Kontainer untuk gauge dinamis -->
                <div id="gauge-container"></div>
            </div>

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
            <h2>Riwayat Peristiwa Terbaru</h2>
            <ul class="log-list" style="max-height: 300px;">
                <?php if (!empty($eventLogs)): ?>
                    <?php foreach ($eventLogs as $log): ?>
                        <?php
                            $icon = 'fas fa-info-circle'; // Default icon
                            $color = 'log-info'; // Default color class
                            if (strpos(strtolower($log['event_type']), 'reconnected') !== false || strpos(strtolower($log['event_type']), 'online') !== false) {
                                $icon = 'fas fa-check-circle';
                                $color = 'log-success';
                            } elseif (strpos(strtolower($log['event_type']), 'offline') !== false || strpos(strtolower($log['event_type']), 'lost') !== false) {
                                $icon = 'fas fa-exclamation-triangle';
                                $color = 'log-warning';
                            }
                        ?>
                        <li class="log-item <?php echo $color; ?>">
                            <i class="<?php echo $icon; ?>"></i>
                            <span class="log-message"><?php echo htmlspecialchars($log['message'] ?? $log['event_type']); ?></span>
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

<!-- Muat semua library yang mungkin diperlukan oleh template -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn3.devexpress.com/jslib/17.1.6/js/dx.all.js"></script>
<script src="https://code.jscharting.com/latest/jscharting.js"></script>

<script>
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
});
</script>