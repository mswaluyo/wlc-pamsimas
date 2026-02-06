<div class="container">
    <div class="page-header">
        <h1><?php echo $title ?? 'Detail Kontroler'; ?></h1>
        <a href="<?= base_url('/controllers') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
    </div>

    <?php if ($controller): ?>
        <?php
            $isOnline = (time() - strtotime($controller['last_update'])) < 120; // Toleransi 120 detik
            $waterLevel = $controller['latest_water_level'] ?? 0;
            
            // Ambil pengaturan warna dari database (jika model tersedia, jika tidak gunakan default)
            $fillColor = '#e74c3c'; // Default Low
            if (class_exists('\app\Models\IndicatorSetting')) {
                $settings = \app\Models\IndicatorSetting::getSettings();
                $fillColor = $settings['color_low'];
                if ($waterLevel > $settings['threshold_medium']) {
                    $fillColor = $settings['color_high'];
                } elseif ($waterLevel > $settings['threshold_low']) {
                    $fillColor = $settings['color_medium'];
                }
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
                    <div class="stat-card-value"><?php echo htmlspecialchars($controller['control_mode']); ?></div>
                </div>
            </div>

            <!-- Kartu Level Air -->
            <div class="stat-card">
                <div class="stat-card-icon bg-orange">
                    <i class="fas fa-water"></i>
                </div>
                <div>
                    <div class="stat-card-title">Level Air</div>
                    <div class="stat-card-value"><?php echo round($waterLevel); ?>%</div>
                </div>
            </div>

            <!-- Kartu Status Online -->
            <div class="stat-card">
                <div class="stat-card-icon <?php echo $isOnline ? 'bg-green' : 'bg-red'; ?>">
                    <i class="fas fa-wifi"></i>
                </div>
                <div>
                    <div class="stat-card-title">Koneksi</div>
                    <div class="stat-card-value" style="font-size: 1.5rem;">
                        <?php echo $isOnline ? 'Online' : 'Offline'; ?>
                        <div style="font-size: 0.8rem; color: #666; font-weight: normal;">
                            RSSI: <?php echo htmlspecialchars($controller['rssi'] ?? '-'); ?> dBm
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visualisasi Gauge (Menggunakan Template Aktif) -->
        <div class="card" style="margin-bottom: 30px; text-align: center;">
            <h2>Visualisasi Tangki Real-time</h2>
            
            <?php if (isset($active_template) && $active_template): ?>
                <!-- Render Template Gauge -->
                <style><?php echo $active_template['css']; ?></style>
                <div id="gauge-container-<?php echo $controller['id']; ?>" class="gauge-container-custom" style="max-width: 300px; margin: 0 auto;">
                    <?php 
                        // Replace placeholder dengan ID controller aktual untuk JS selector
                        echo str_replace(
                            ['{{CONTROLLER_ID}}', '{{TANK_NAME}}'], 
                            [$controller['id'], htmlspecialchars($controller['tank_name'] ?? '')], 
                            $active_template['html']
                        ); 
                    ?>
                </div>
                <!-- Inject Data Awal untuk JS Template -->
                <script>
                    // Data awal untuk template gauge
                    window['gauge_data_<?php echo $controller['id']; ?>'] = {
                        id: <?php echo $controller['id']; ?>,
                        water_level: <?php echo $waterLevel; ?>,
                        status: '<?php echo $controller['status']; ?>',
                        is_online: <?php echo $isOnline ? 'true' : 'false'; ?>,
                        tank_name: '<?php echo htmlspecialchars($controller['tank_name'] ?? ''); ?>'
                    };
                </script>
                <script><?php echo $active_template['js']; ?></script>
            <?php else: ?>
                <!-- Fallback Gauge Sederhana jika tidak ada template -->
                <div class="tank-gauge-container">
                    <div class="tank-gauge-water" style="height: <?php echo $waterLevel; ?>%; background-color: <?php echo $fillColor; ?>;"></div>
                    <div class="tank-gauge-text"><?php echo round($waterLevel); ?>%</div>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <a href="<?= base_url('/logs/sensors?device_id=' . $controller['id']) ?>" class="btn btn-info"><i class="fas fa-table"></i> Lihat Data Log (Tabel)</a>
            </div>
        </div>

        <!-- Grafik Riwayat -->
        <div class="card" style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0; border: none;">Grafik Level Air</h2>
                <select id="historyRange" onchange="loadChartData()" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="60">1 Jam Terakhir</option>
                    <option value="360">6 Jam Terakhir</option>
                    <option value="1440">24 Jam Terakhir</option>
                    <option value="10080">7 Hari Terakhir</option>
                </select>
            </div>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="waterLevelChart"></canvas>
            </div>
        </div>

        <div class="controller-detail-grid">
            <!-- Detail Teknis -->
            <div class="card">
                <h2>Detail Konfigurasi</h2>
                <ul style="list-style: none; padding: 0;">
                    <li><strong>Nama Tangki:</strong> <?php echo htmlspecialchars($controller['tank_name'] ?? '-'); ?></li>
                    <li><strong>Nama Pompa:</strong> <?php echo htmlspecialchars($controller['pump_name'] ?? '-'); ?></li>
                    <li><strong>MAC Address:</strong> <?php echo htmlspecialchars($controller['mac_address']); ?></li>
                    <li><strong>Versi Firmware:</strong> <?php echo htmlspecialchars($controller['firmware_version'] ?? 'Belum dilaporkan'); ?></li>
                    <li><strong>Update Terakhir:</strong> <?php echo htmlspecialchars($controller['last_update']); ?></li>
                    <hr>
                    <li><strong>Jarak Sensor (Penuh):</strong> <?php echo htmlspecialchars($controller['full_tank_distance']); ?> cm</li>
                    <li><strong>Jarak Sensor (Kosong):</strong> <?php echo htmlspecialchars($controller['empty_tank_distance']); ?> cm</li>
                    <li><strong>Pemicu Pompa (ON):</strong> <?php echo htmlspecialchars($controller['trigger_percentage']); ?>%</li>
                    <li><strong>Durasi Nyala (Auto):</strong> <?php echo htmlspecialchars($controller['on_duration']); ?> menit</li>
                    <li><strong>Durasi Mati (Auto):</strong> <?php echo htmlspecialchars($controller['off_duration']); ?> menit</li>
                </ul>
                
                <div style="margin-top: 20px;">
                    <form action="<?= base_url('/controllers/sync/' . $controller['id']) ?>" method="POST" style="display: inline;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;" onclick="return confirm('Sinkronisasi akan mengirim pengaturan terbaru ke perangkat. Lanjutkan?')">
                            <i class="fas fa-sync"></i> Sinkronisasi Konfigurasi
                        </button>
                    </form>
                    
                    <form action="<?= base_url('/controllers/apply-settings/' . $controller['id']) ?>" method="POST" style="display: inline;">
                        <button type="submit" class="btn btn-secondary" style="width: 100%;" onclick="return confirm('Kirim perintah update konfigurasi ke perangkat?')">
                            <i class="fas fa-paper-plane"></i> Kirim Ulang Config
                        </button>
                    </form>
                </div>
            </div>

            <!-- Log Peristiwa -->
            <div class="card">
                <h2>Log Peristiwa Terakhir</h2>
                <?php if (!empty($eventLogs)): ?>
                    <ul class="log-list">
                        <?php foreach ($eventLogs as $log): ?>
                            <?php
                                $icon = 'fa-info-circle';
                                $class = '';
                                if (strpos($log['event_type'], 'Power') !== false) { $icon = 'fa-plug'; $class = 'log-power'; }
                                elseif (strpos($log['event_type'], 'Error') !== false) { $icon = 'fa-exclamation-triangle'; $class = 'log-warning'; }
                                elseif (strpos($log['event_type'], 'Recovered') !== false) { $icon = 'fa-check-circle'; $class = 'log-success'; }
                                elseif (strpos($log['event_type'], 'Safety Cutoff') !== false) { $icon = 'fa-shield-alt'; $class = 'log-warning'; }
                            ?>
                            <li class="log-item <?php echo $class; ?>">
                                <div>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                    <span class="log-message"><strong><?php echo htmlspecialchars($log['event_type']); ?>:</strong> <?php echo htmlspecialchars($log['event_description']); ?></span>
                                </div>
                                <span class="log-timestamp"><?php echo $log['event_time']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div style="margin-top: 15px; text-align: right;">
                        <a href="<?= base_url('/logs/events') ?>" class="btn btn-sm btn-secondary">Lihat Semua Log</a>
                    </div>
                <?php else: ?>
                    <p>Belum ada log peristiwa yang tercatat.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Script untuk Grafik -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            let myChart = null;
            const ctx = document.getElementById('waterLevelChart').getContext('2d');
            const controllerId = <?php echo $controller['id']; ?>;

            function loadChartData() {
                const range = document.getElementById('historyRange').value;
                
                // Ambil data dari API
                fetch(`<?= base_url('/api/device/history') ?>?id=${controllerId}&range=${range}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(data.error);
                            return;
                        }
                        renderChart(data.sensors, data.pumps || []);
                    })
                    .catch(err => console.error('Gagal memuat data grafik:', err));
            }

            function renderChart(sensorData, pumpData) {
                // Siapkan data untuk Chart.js
                const labels = sensorData.map(d => {
                    const date = new Date(d.record_time);
                    // Format jam:menit
                    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                });
                
                const dataPoints = sensorData.map(d => d.water_percentage);
                const levelPoints = sensorData.map(d => d.water_level); // Untuk tooltip (cm)

                // Proses Data Status Pompa (Mapping ke Timestamp Sensor)
                let pumpIdx = 0;
                let currentStatus = 0; // Default OFF
                
                // Pastikan data pompa terurut berdasarkan waktu
                if (pumpData.length > 0) {
                    pumpData.sort((a, b) => new Date(a.record_time) - new Date(b.record_time));
                }

                const pumpPoints = sensorData.map(s => {
                    const sTime = new Date(s.record_time).getTime();
                    // Cari status terakhir sebelum waktu sensor saat ini
                    while(pumpIdx < pumpData.length && new Date(pumpData[pumpIdx].record_time).getTime() <= sTime) {
                        const p = pumpData[pumpIdx];
                        // Cek berbagai kemungkinan format status (string 'ON'/'OFF' atau boolean/int)
                        currentStatus = (String(p.status).toUpperCase() === 'ON' || p.status == 1) ? 1 : 0;
                        pumpIdx++;
                    }
                    return currentStatus;
                });

                if (myChart) {
                    myChart.destroy();
                }

                myChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Level Air (%)',
                                data: dataPoints,
                                borderColor: '#3498db',
                                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4, // Membuat garis sedikit melengkung (halus)
                                pointRadius: 2,
                                pointHoverRadius: 5,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Status Pompa',
                                data: pumpPoints,
                                borderColor: '#e74c3c',
                                backgroundColor: 'rgba(231, 76, 60, 0.2)',
                                borderWidth: 2,
                                stepped: true, // Garis patah-patah (kotak) untuk status digital
                                fill: true,
                                pointRadius: 0,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                beginAtZero: true,
                                max: 100,
                                title: { display: true, text: 'Persentase (%)' }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                min: 0,
                                max: 1.2, // Max 1.2 agar grafik ON (1) tidak menempel di atas
                                grid: {
                                    drawOnChartArea: false, // Sembunyikan grid agar tidak tumpang tindih
                                },
                                ticks: {
                                    stepSize: 1,
                                    callback: function(value) {
                                        if (value === 0) return 'OFF';
                                        if (value === 1) return 'ON';
                                        return '';
                                    }
                                },
                                title: { display: true, text: 'Status Pompa' }
                            },
                            x: {
                                ticks: { maxTicksLimit: 10 } // Batasi label sumbu X agar tidak penuh
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    afterLabel: function(context) {
                                        if (context.datasetIndex === 0) {
                                            return `Ketinggian: ${levelPoints[context.dataIndex]} cm`;
                                        }
                                        return null;
                                    },
                                    label: function(context) {
                                        if (context.datasetIndex === 1) {
                                            return 'Pompa: ' + (context.raw === 1 ? 'ON' : 'OFF');
                                        }
                                        return context.dataset.label + ': ' + context.parsed.y + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Load awal saat halaman dibuka
            document.addEventListener('DOMContentLoaded', loadChartData);
        </script>

    <?php else: ?>
        <div class="card">
            <h2>Perangkat Tidak Ditemukan</h2>
            <p>Data kontroler yang Anda cari tidak tersedia atau telah dihapus.</p>
            <a href="<?= base_url('/controllers') ?>" class="btn btn-primary">Kembali ke Daftar</a>
        </div>
    <?php endif; ?>
</div>
