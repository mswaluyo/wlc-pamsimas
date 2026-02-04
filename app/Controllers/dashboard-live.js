/**
 * Script untuk Live Update Dashboard
 * Memperbarui status tombol, indikator online, dan level air secara real-time.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Jalankan polling setiap 2 detik
    setInterval(fetchDashboardData, 2000);
});

function fetchDashboardData() {
    // Pastikan BASE_URL sudah didefinisikan di layout utama
    const baseUrl = (typeof BASE_URL !== 'undefined') ? BASE_URL : '/wlc/public/';
    const apiUrl = baseUrl + 'api/dashboard/data';

    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (data.controllers) {
                updateControllersUI(data.controllers);
            }
            if (data.stats) {
                updateStatsUI(data.stats);
            }
        })
        .catch(error => console.error('Error fetching live data:', error));
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
        const pumpSwitch = document.getElementById(`pump-switch-${device.id}`);
        if (pumpSwitch && document.activeElement !== pumpSwitch) {
            const isOn = device.status === 'ON';
            if (pumpSwitch.checked !== isOn) {
                pumpSwitch.checked = isOn;
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
    });
}

function updateStatsUI(stats) {
    const onlineEl = document.getElementById('online-controllers-count');
    if (onlineEl) onlineEl.textContent = stats.online_controllers;
    
    const totalEl = document.getElementById('total-controllers-count');
    if (totalEl) totalEl.textContent = stats.total_controllers;
}