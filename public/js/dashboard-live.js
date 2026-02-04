/**
 * Script untuk Live Update Dashboard
 * Memperbarui status tombol, indikator online, dan level air secara real-time.
 */

let deviceTimerData = {}; // Penyimpanan lokal untuk data timer
let serverTimeOffset = 0; // Selisih waktu antara Client dan Server

document.addEventListener('DOMContentLoaded', function() {
    // Jalankan polling setiap 2 detik
    setInterval(fetchDashboardData, 2000);

    // Jalankan update timer lokal setiap 1 detik agar terlihat berjalan (ticking)
    setInterval(updateTimersDisplay, 1000);
});

function fetchDashboardData() {
    // Pastikan BASE_URL sudah didefinisikan di layout utama
    let baseUrl = (typeof BASE_URL !== 'undefined') ? BASE_URL : '/wlc/public/';
    // Normalisasi: Pastikan baseUrl diakhiri dengan '/'
    if (!baseUrl.endsWith('/')) baseUrl += '/';
    
    // Tambahkan timestamp (?t=...) untuk mencegah browser/proxy menyimpan cache respons API
    const apiUrl = baseUrl + 'api/dashboard/data?t=' + new Date().getTime();

    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text(); // Ambil text dulu untuk validasi JSON
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.controllers) updateControllersUI(data.controllers);
                if (data.stats) updateStatsUI(data.stats);
                
                // Hitung offset waktu client vs server untuk sinkronisasi timer yang akurat
                if (data.server_timestamp) {
                    serverTimeOffset = Math.floor(Date.now() / 1000) - data.server_timestamp;
                }
                
                if (data.server_time) updateServerTimeUI(data.server_time);
            } catch (e) {
                console.warn('Respons server bukan JSON valid:', text.substring(0, 50) + '...');
            }
        })
        // Gunakan console.debug agar tidak memenuh-menuhi console log jika error berulang
        .catch(error => console.debug('Live update skipped:', error.message));
}

function updateControllersUI(controllers) {
    controllers.forEach(device => {
        // 1. Update Status Online/Offline
        const statusBadge = document.getElementById(`status-badge-${device.id}`);
        if (statusBadge) {
            if (device.is_online) {
                statusBadge.className = 'status-badge status-online';
                statusBadge.textContent = 'Online';
            } else {
                statusBadge.className = 'status-badge status-offline';
                statusBadge.textContent = 'Offline';
            }
        }

        // 2. Update Toggle Mode (Auto/Manual)
        // ID: mode-switch-{id}
        const modeSwitch = document.getElementById(`mode-switch-${device.id}`);
        if (modeSwitch && document.activeElement !== modeSwitch) {
            const isAuto = device.control_mode === 'AUTO';
            if (modeSwitch.checked !== isAuto) {
                modeSwitch.checked = isAuto;
            }
        }

        // 3. Update Toggle Pompa (ON/OFF)
        // ID: pump-switch-{id}
        let pumpSwitch = document.getElementById(`pump-switch-${device.id}`);
        // Fallback: Cari tombol di dalam kartu jika ID tidak ditemukan (untuk template baru)
        if (!pumpSwitch) {
            const card = document.getElementById(`gauge-card-${device.id}`);
            if (card) pumpSwitch = card.querySelector('.btn-pump-toggle');
        }
        
        // --- UPDATE TIMER (Posisi Baru: Atas Tengah) ---
        // Kita pindahkan logika ini keluar dari blok 'if (pumpSwitch)' agar lebih fleksibel
        const card = document.getElementById(`gauge-card-${device.id}`);
        if (card) {
            let durationEl = document.getElementById(`pump-duration-${device.id}`);
            
            // Jika elemen belum ada, buat baru
            if (!durationEl) {
                durationEl = document.createElement('div');
                durationEl.id = `pump-duration-${device.id}`;
                card.appendChild(durationEl);
            } else if (durationEl.parentNode !== card) {
                // Jika sudah ada tapi salah tempat (misal di footer), pindahkan ke card utama
                card.appendChild(durationEl);
            }

            // Reset style lama dan terapkan style posisi absolute di atas tengah
            durationEl.style.cssText = ''; 
            durationEl.style.position = 'absolute';
            durationEl.style.top = '12px'; 
            durationEl.style.left = '50%';
            durationEl.style.transform = 'translateX(-50%)';
            durationEl.style.fontSize = '0.9em';
            durationEl.style.fontWeight = 'bold';
            durationEl.style.zIndex = '10';
            durationEl.style.backgroundColor = 'rgba(255,255,255,0.85)'; // Background putih transparan agar tulisan jelas
            durationEl.style.padding = '2px 8px';
            durationEl.style.borderRadius = '10px';
            durationEl.style.pointerEvents = 'none'; // Agar tidak mengganggu klik pada card

            const isOn = device.status === 'ON';
            // Warna Hijau jika ON, Abu-abu jika OFF
            durationEl.style.color = isOn ? '#27ae60' : '#7f8c8d';

            // Simpan data durasi dari server ke variabel lokal untuk diolah oleh ticker
            deviceTimerData[device.id] = {
                baseDuration: device.current_pump_duration || 0,
                lastChangeTimestamp: device.last_pump_change_timestamp || 0,
                lastSync: Date.now()
            };
        }

        if (pumpSwitch) {
            // Update Posisi Switch (Hanya jika user TIDAK sedang mengklik)
            if (document.activeElement !== pumpSwitch) {
                const isOn = device.status === 'ON';
                if (pumpSwitch.checked !== isOn) {
                    pumpSwitch.checked = isOn;
                }
            }
        }

        // 4. Update Level Air (Visual & Teks)
        const percentage = Math.round(device.latest_water_level || 0);
        
        // Teks
        const textIds = [`tank-text-${device.id}`, `bar-text-${device.id}`, `gauge-value-${device.id}`];
        textIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = percentage + '%';
        });

        // Bar Gauge
        const barFill = document.getElementById(`bar-fill-${device.id}`);
        if (barFill) {
            barFill.style.width = percentage + '%';
            // Update warna sederhana
            if (percentage < 30) barFill.style.backgroundColor = '#e74c3c';
            else if (percentage < 70) barFill.style.backgroundColor = '#f39c12';
            else barFill.style.backgroundColor = '#27ae60';
        }

        // Tank Gauge
        const tankWater = document.getElementById(`tank-water-${device.id}`);
        if (tankWater) {
            tankWater.style.height = percentage + '%';
        }

        // Conic Gauge
        const gaugeBody = document.getElementById(`gauge-body-${device.id}`);
        if (gaugeBody) {
            const degrees = percentage * 2.7;
            gaugeBody.style.setProperty('--percentage', degrees + 'deg');
            let color = '#27ae60';
            if (percentage < 30) color = '#e74c3c';
            else if (percentage < 70) color = '#f39c12';
            gaugeBody.style.setProperty('--fill-color', color);
        }

        // 5. Update Posisi Marker Pemicu (Trigger)
        // Untuk Bar Gauge (Horizontal)
        const barTrigger = document.getElementById(`bar-trigger-${device.id}`);
        if (barTrigger) {
            barTrigger.style.left = device.trigger_percentage + '%';
            barTrigger.title = `Batas Nyala Otomatis: ${device.trigger_percentage}%`;
        }
        // Untuk Tank Gauge (Vertikal)
        const tankTrigger = document.getElementById(`tank-trigger-${device.id}`);
        if (tankTrigger) {
            tankTrigger.style.bottom = device.trigger_percentage + '%';
            tankTrigger.title = `Batas Nyala Otomatis: ${device.trigger_percentage}%`;
        }
    });

    // Update tampilan timer segera setelah data baru diterima
    updateTimersDisplay();
}

function updateStatsUI(stats) {
    const onlineEl = document.getElementById('online-controllers-count');
    if (onlineEl) onlineEl.textContent = stats.online_controllers;
    
    const totalEl = document.getElementById('total-controllers-count');
    if (totalEl) totalEl.textContent = stats.total_controllers;
}

function updateServerTimeUI(timeString) {
    const el = document.getElementById('server-time-clock');
    if (el) {
        el.innerHTML = '<i class="far fa-clock" style="margin-right:5px;"></i> ' + timeString + ' WIB';
    }
}

/**
 * Fungsi untuk mengupdate teks timer secara lokal setiap detik
 * Menghitung selisih waktu sejak data terakhir diterima dari server
 */
function updateTimersDisplay() {
    const now = Date.now();
    const currentClientTimestamp = Math.floor(now / 1000);

    for (const [id, data] of Object.entries(deviceTimerData)) {
        const el = document.getElementById(`pump-duration-${id}`);
        if (el) {
            // METODE: Base Duration + Elapsed Time
            // Ini lebih stabil secara visual dan mencegah lompatan angka (0, 1, 0...)
            const elapsedSeconds = Math.floor((now - data.lastSync) / 1000);
            const totalSeconds = data.baseDuration + elapsedSeconds;
            
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            
            // Format HH:MM:SS atau MM:SS
            const timeString = (hours > 0 ? `${hours}:` : '') + 
                               `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            el.textContent = timeString;
        }
    }
}