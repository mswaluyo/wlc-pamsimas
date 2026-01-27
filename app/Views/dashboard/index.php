<div class="container">
    <h1><?php echo $title ?? 'Dashboard'; ?></h1>

    <!-- Kartu Statistik -->
    <div class="stat-cards-container">
        <a href="<?= base_url('/controllers') ?>" class="stat-card">
            <div class="stat-card-icon bg-blue">
                <i class="fas fa-microchip"></i>
            </div>
            <div>
                <div class="stat-card-title">Total Perangkat</div>
                <div class="stat-card-value" id="stat-total-controllers"><?php echo $stats['total_controllers'] ?? 0; ?></div>
            </div>
        </a>
        <a href="<?= base_url('/controllers?status=online') ?>" class="stat-card">
            <div class="stat-card-icon bg-green">
                <i class="fas fa-wifi"></i>
            </div>
            <div>
                <div class="stat-card-title">Perangkat Online</div>
                <div class="stat-card-value" id="stat-online-controllers"><?php echo $stats['online_controllers'] ?? 0; ?></div>
            </div>
        </a>
        <a href="<?= base_url('/settings/tanks') ?>" class="stat-card">
            <div class="stat-card-icon bg-orange">
                <i class="fas fa-database"></i>
            </div>
            <div>
                <div class="stat-card-title">Total Tangki</div>
                <div class="stat-card-value" id="stat-total-tanks"><?php echo $stats['total_tanks'] ?? 0; ?></div>
            </div>
        </a>
        <a href="<?= base_url('/users') ?>" class="stat-card">
            <div class="stat-card-icon bg-red">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div class="stat-card-title">Total Pengguna</div>
                <div class="stat-card-value" id="stat-total-users"><?php echo $stats['total_users'] ?? 0; ?></div>
            </div>
        </a>
    </div>

    <!-- Gauge Level Air -->
    <h2 style="margin-top: 30px;">Status Level Air Tangki</h2>
    <!-- PERBAIKAN: Kontainer ini sekarang menjadi grid utama untuk kartu-kartu gauge -->
    <div id="gauge-grid-container" class="gauge-container"></div>
    <p id="no-device-message" style="display: none;">Tidak ada perangkat yang terdaftar untuk ditampilkan.</p>
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

<!-- PERBAIKAN: Muat library DevExtreme yang diperlukan untuk template dx-gauge -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn3.devexpress.com/jslib/17.1.6/js/dx.all.js"></script>
<!-- PERBAIKAN: Muat library JSCharting yang diperlukan untuk template baru -->
<script src="https://code.jscharting.com/latest/jscharting.js"></script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Ambil data template aktif yang sudah disiapkan oleh PHP (dari backup)
    const activeTemplate = <?php echo json_encode($active_template ?? null); ?>;

    // Jika ada template aktif dan memiliki CSS, inject langsung saat halaman dimuat
    if (activeTemplate && activeTemplate.css) {
        const styleEl = document.createElement('style');
        styleEl.id = `template-style-${activeTemplate.id}`;
        styleEl.textContent = activeTemplate.css;
        document.head.appendChild(styleEl);
    }

    // Jika ada template aktif dan memiliki JavaScript, inject dan jalankan
    if (activeTemplate && activeTemplate.js) {
        const scriptEl = document.createElement('script');
        scriptEl.id = `template-script-${activeTemplate.id}`;
        scriptEl.textContent = activeTemplate.js;
        document.body.appendChild(scriptEl);
    }

    const gaugeContainer = document.getElementById('gauge-grid-container');

    function updateDashboard() {
        fetch(`${BASE_URL}api/dashboard-data`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // 1. Perbarui Kartu Statistik
                document.getElementById('stat-total-controllers').textContent = data.stats.total_controllers;
                document.getElementById('stat-online-controllers').textContent = data.stats.online_controllers;
                document.getElementById('stat-total-tanks').textContent = data.stats.total_tanks;
                document.getElementById('stat-total-users').textContent = data.stats.total_users;

                // Ambil semua data yang diperlukan dari API
                const settings = data.indicator_settings;

                // PERBAIKAN: Logika untuk menghapus kartu yang sudah tidak aktif
                const existingCards = new Set(Array.from(gaugeContainer.querySelectorAll('.gauge-card:not(.gauge-card-placeholder)')).map(el => el.id));

                // 2. Render dan Perbarui Gauge secara dinamis
                if (data.controllers && data.controllers.length > 0) {
                    document.getElementById('no-device-message').style.display = 'none';

                    data.controllers.forEach(controller => {
                        const cardId = `gauge-card-${controller.id}`;
                        existingCards.delete(cardId); // Hapus dari daftar yang akan dihapus karena masih aktif

                        // Cek apakah gauge sudah ada di DOM, jika belum, buat dari template
                        let gaugeCard = document.getElementById(cardId);
                        if (!gaugeCard && activeTemplate && activeTemplate.html) {
                            let html = activeTemplate.html
                                .replace(/{{CONTROLLER_ID}}/g, controller.id)
                                .replace(/{{TANK_NAME}}/g, controller.tank_name || 'N/A');
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = html;
                            gaugeCard = tempDiv.querySelector('.gauge-card');
                            if (!gaugeCard) return; // Lewati jika template tidak valid
                            gaugeCard.id = `gauge-card-${controller.id}`;

                            // Jika ada fungsi inisialisasi, panggil
                            if (typeof window.initGauge === 'function') {
                                window.initGauge(gaugeCard);
                            }
                            
                            // PERBAIKAN: Tambahkan div untuk tombol di dalam kartu
                            const actionsDiv = document.createElement('div');
                            actionsDiv.className = 'gauge-actions';
                            gaugeCard.appendChild(actionsDiv);

                            gaugeContainer.appendChild(gaugeCard);
                        }

                        // --- LOGIKA BARU: Update tombol aksi di dalam kartu ---
                        if (gaugeCard) {
                            const actionsDiv = gaugeCard.querySelector('.gauge-actions');
                            if (actionsDiv) {
                                const isManualMode = (controller.control_mode === 'MANUAL');
                                const currentMode = controller.control_mode;
                                const currentStatus = controller.status;

                                const newMode = isManualMode ? 'AUTO' : 'MANUAL';
                                const newStatus = (currentStatus === 'ON') ? 'OFF' : 'ON';
                                
                                // Tentukan warna tombol berdasarkan STATUS SAAT INI
                                const statusBtnClass = (currentStatus === 'ON') ? 'btn-green' : 'btn-red';
                                
                                // Tentukan warna tombol mode berdasarkan MODE SAAT INI
                                const modeBtnClass = (currentMode === 'AUTO') ? 'btn-blue' : 'btn-gray';
                                
                                // Tentukan ikon
                                const modeIcon = (currentMode === 'AUTO') ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-hand-paper"></i>';
                                const statusIcon = '<i class="fas fa-power-off"></i>';

                                actionsDiv.innerHTML = `
                                    <button class="btn-action btn-mode-toggle ${modeBtnClass}" 
                                            data-mac="${controller.mac_address}" 
                                            data-new-mode="${newMode}">
                                        ${modeIcon} ${currentMode}
                                    </button>
                                    <button class="btn-action btn-pump-toggle ${statusBtnClass}" 
                                            data-mac="${controller.mac_address}" 
                                            data-new-status="${newStatus}"
                                            ${!isManualMode ? 'disabled' : ''}
                                            title="${!isManualMode ? 'Hanya aktif di mode MANUAL' : `Ubah ke ${newStatus}`}">
                                        ${statusIcon} ${currentStatus}
                                    </button>
                                `;
                            }
                        }

                        // Pastikan gaugeCard ada sebelum melanjutkan
                        if (!gaugeCard) return;

                        // Tambahkan link detail ke dataset kartu agar bisa diklik
                        gaugeCard.dataset.detailUrl = `${BASE_URL}controllers/${controller.id}`;
                        gaugeCard.style.cursor = 'pointer';

                        // --- LOGIKA BARU: Update atau buat indikator sinyal ---
                        let signalIndicator = gaugeCard.querySelector('.signal-indicator');
                        if (!signalIndicator) {
                            signalIndicator = document.createElement('div');
                            signalIndicator.className = 'signal-indicator';
                            gaugeCard.appendChild(signalIndicator);
                        }

                        const rssi = controller.rssi;
                        const isOnline = controller.is_online; // Menggunakan flag dari API
                        let signalColor = '#bdc3c7'; // Default abu-abu (Offline/No Signal)
                        let signalTitle = 'Offline / Tidak ada sinyal';

                        if (isOnline && rssi && rssi != 0) {
                            if (rssi > -67) {
                                signalColor = '#27ae60'; // Hijau (Kuat)
                                signalTitle = `Sinyal Kuat (${rssi} dBm)`;
                            } else if (rssi > -80) {
                                signalColor = '#f39c12'; // Kuning/Oranye (Sedang)
                                signalTitle = `Sinyal Sedang (${rssi} dBm)`;
                            } else {
                                signalColor = '#e74c3c'; // Merah (Lemah)
                                signalTitle = `Sinyal Lemah (${rssi} dBm)`;
                            }
                        }

                        // Gunakan ikon fa-wifi dengan warna dinamis
                        signalIndicator.innerHTML = `
                            <i class="fas fa-wifi" style="color: ${signalColor}; font-size: 1.2em;" title="${signalTitle}"></i>
                        `;

                        // --- LOGIKA BARU: Update atau buat indikator status pompa ---
                        let pumpIndicator = gaugeCard.querySelector('.pump-indicator');
                        if (!pumpIndicator) {
                            pumpIndicator = document.createElement('div');
                            pumpIndicator.className = 'pump-indicator';
                            gaugeCard.appendChild(pumpIndicator);
                        }

                        const pumpStatus = controller.status; // Sekarang bisa 'ON', 'OFF', atau 'RESTING'
                        
                        let pumpClass = 'pump-off'; // Default
                        let pumpText = pumpStatus; // Teks sama dengan status

                        if (pumpStatus === 'ON') {
                            pumpClass = 'pump-on';
                        } else if (pumpStatus === 'RESTING') {
                            pumpClass = 'pump-resting';
                        } else {
                            // Untuk status 'OFF' atau status lain yang tidak dikenal
                            pumpClass = 'pump-off';
                            pumpText = 'OFF';
                        }

                        // Hapus kelas lama dan tambahkan yang baru
                        pumpIndicator.classList.remove('pump-on', 'pump-off', 'pump-resting');
                        pumpIndicator.classList.add(pumpClass);

                        // Update ikon dan teks
                        pumpIndicator.innerHTML = `
                            <i class="fas fa-power-off"></i> 
                            <span style="margin-left: 4px;">${pumpText}</span>
                        `;

                        // --- LOGIKA JAVASCRIPT UNIVERSAL ---
                        const waterLevel = controller.latest_water_level || 0;
                        let fillColor;

                        if (!isOnline) {
                            fillColor = '#95a5a6'; // Abu-abu jika offline
                        } else if (parseFloat(waterLevel) > parseFloat(settings.threshold_medium)) {
                            fillColor = settings.color_high;
                        } else if (parseFloat(waterLevel) > parseFloat(settings.threshold_low)) {
                            fillColor = settings.color_medium;
                        } else {
                            fillColor = settings.color_low;
                        }

                        // Pastikan gaugeCard ada sebelum mencoba mengupdatenya
                        if (gaugeCard) {
                            // Panggil fungsi update universal yang disediakan oleh script template
                            if (typeof window.updateGauge === 'function') {
                                // Jika template menyediakan fungsi update sendiri, gunakan itu.
                                window.updateGauge(gaugeCard, waterLevel, fillColor);
                            } else {
                                // JIKA TIDAK: Gunakan logika universal untuk template sederhana.
                                const elementsToUpdate = gaugeCard.querySelectorAll('[data-update-style]');
                                elementsToUpdate.forEach(el => {
                                    const styleProp = el.dataset.updateStyle;

                                    // PERBAIKAN: Cek 'styleProp', bukan 'transformType'
                                    if (styleProp === 'degrees') {
                                        const finalValue = (waterLevel / 100) * 270;
                                        // PERBAIKAN: Set properti kustom '--percentage', bukan 'styleProp'
                                        el.style.setProperty('--percentage', `${finalValue}deg`);
                                        el.style.setProperty('--fill-color', fillColor);
                                    } else if (styleProp === 'percentage') {
                                        // PERBAIKAN: Cek juga parent container untuk memastikan ini adalah gauge vertikal
                                        if (el.classList.contains('tank-gauge-water') || el.closest('.tank-gauge-container')) {
                                            el.style.height = `${waterLevel}%`;
                                            el.style.width = '100%'; // Pastikan lebar tetap penuh untuk tangki vertikal
                                        } else if (el.classList.contains('simple-bar-gauge-fill') || el.closest('.simple-bar-gauge-container')) {
                                            el.style.width = `${waterLevel}%`;
                                            el.style.height = '100%'; // Pastikan tinggi tetap penuh untuk bar horizontal
                                        } else {
                                            el.style.width = `${waterLevel}%`;
                                            el.style.height = `${waterLevel}%`;
                                        }
                                        el.style.backgroundColor = fillColor;
                                    }
                                });

                                // PERBAIKAN: Tambahkan kembali logika untuk update teks
                                const textElement = gaugeCard.querySelector('.value') || gaugeCard.querySelector('.tank-gauge-text') || gaugeCard.querySelector('.simple-bar-gauge-text');
                                if (textElement) {
                                    if (!isOnline) {
                                        textElement.textContent = 'OFFLINE';
                                        textElement.style.fontSize = '1.2em'; // Perkecil font agar teks OFFLINE muat
                                    } else {
                                        textElement.textContent = `${Math.round(waterLevel)}%`;
                                        textElement.style.fontSize = ''; // Reset ke ukuran default CSS
                                    }
                                }
                            }
                        }
                    });
                } else {
                    document.getElementById('no-device-message').style.display = 'block';
                    gaugeContainer.innerHTML = ''; // Kosongkan jika tidak ada perangkat
                }

                // Hapus kartu dari perangkat yang sudah tidak ada
                existingCards.forEach(cardId => {
                    document.getElementById(cardId)?.remove();
                });
            })
            .catch(error => console.error('Gagal memperbarui dashboard:', error));
    }

    // --- LOGIKA BARU: Menangani klik tombol aksi ---

    // Fungsi untuk mengirim perintah ke API
    function sendApiCommand(mac, action, value, buttonElement = null) {
        // Visual Feedback: Ubah tombol menjadi loading
        if (buttonElement) {
            buttonElement.disabled = true;
            buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        }

        console.log(`Sending command: MAC=${mac}, Action=${action}, Value=${value}`);
        fetch(`${BASE_URL}api/update`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mac: mac, action: action, value: value })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Server response:', data);
            updateDashboard(); // Memicu pembaruan data dashboard segera
        })
        .catch(error => {
            console.error('Error sending command:', error);
            if (buttonElement) {
                buttonElement.disabled = false;
                buttonElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
            }
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

        // Clone button untuk menghapus event listener lama agar tidak menumpuk
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
        
        // Tutup jika klik di luar area konten modal
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    }

    // Gunakan event delegation pada kontainer utama
    gaugeContainer.addEventListener('click', function(event) {
        // 1. Cek apakah yang diklik adalah tombol aksi (Mode/Power)
        const btnTarget = event.target.closest('.btn-action');
        if (btnTarget) {
            if (btnTarget.classList.contains('btn-mode-toggle')) {
                const mac = btnTarget.dataset.mac;
                const newMode = btnTarget.dataset.newMode;
                
                showConfirmation(`Anda yakin ingin mengubah mode perangkat ${mac} menjadi ${newMode}?`, function() {
                    sendApiCommand(mac, 'set_mode', newMode, btnTarget);
                });
            } else if (btnTarget.classList.contains('btn-pump-toggle') && !btnTarget.disabled) { 
                const mac = btnTarget.dataset.mac;
                const newStatus = btnTarget.dataset.newStatus;
                sendApiCommand(mac, 'set_status', newStatus, btnTarget);
            }
            return; // Hentikan eksekusi agar tidak memicu klik kartu
        }

        // 2. Cek apakah yang diklik adalah kartu gauge (untuk navigasi ke detail)
        const cardTarget = event.target.closest('.gauge-card');
        if (cardTarget && cardTarget.dataset.detailUrl) {
            window.location.href = cardTarget.dataset.detailUrl;
        }
    });
    
    // Inisialisasi dan pembaruan periodik (diubah ke 5 detik)
    updateDashboard();
    setInterval(updateDashboard, 5000);
});
</script>