document.addEventListener('DOMContentLoaded', function() {
    const scanButton = document.getElementById('scan-button');
    const scanStatus = document.getElementById('scan-status');
    const loadingSpinner = document.getElementById('loading-spinner');
    const deviceList = document.getElementById('device-list');
    const scanWrapper = document.querySelector('.scan-icon-wrapper');

    scanButton.addEventListener('click', function() {
        // 1. Update UI ke status scanning
        scanButton.disabled = true;
        
        // Cek apakah tombol ada di dalam wrapper FAB (mobile) agar labelnya juga disembunyikan
        if (window.innerWidth <= 768 && scanButton.parentNode.tagName === 'DIV' && scanButton.parentNode.parentNode.id === 'fab-actions') {
             scanButton.parentNode.style.display = 'none'; 
        } else {
             scanButton.style.display = 'none';
        }
        
        loadingSpinner.style.display = 'block';
        scanStatus.textContent = 'Sedang memindai jaringan untuk perangkat aktif...';
        scanWrapper.classList.add('scanning');
        deviceList.innerHTML = ''; // Bersihkan hasil sebelumnya

        // 2. Panggil API (Simulasi delay agar terlihat seperti scanning sungguhan)
        setTimeout(() => {
            fetch(BASE_URL + 'api/detected-devices')
                .then(response => response.json())
                .then(data => {
                    renderDevices(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    scanStatus.innerHTML = '<span style="color: red;">Terjadi kesalahan saat memindai.</span>';
                })
                .finally(() => {
                    // 3. Kembalikan UI ke status normal
                    scanButton.disabled = false;
                    if (window.innerWidth <= 768) {
                        scanButton.innerHTML = '<i class="fas fa-redo"></i>';
                        // Style Floating Button (Konsisten dengan inisialisasi)
                        if (scanButton.parentNode.tagName === 'DIV' && scanButton.parentNode.parentNode.id === 'fab-actions') {
                             scanButton.parentNode.style.display = 'flex';
                             scanButton.style.display = 'flex';
                        } else {
                             scanButton.style.display = 'flex';
                        }
                    } else {
                        scanButton.innerHTML = '<i class="fas fa-redo"></i> Scan Ulang';
                        scanButton.style.display = 'inline-block';
                    }
                    loadingSpinner.style.display = 'none';
                    scanWrapper.classList.remove('scanning');
                });
        }, 1500); // Delay 1.5 detik untuk efek visual
    });

    function renderDevices(devices) {
        if (devices.length === 0) {
            scanStatus.textContent = 'Tidak ditemukan perangkat baru yang belum terdaftar.';
            return;
        }

        scanStatus.textContent = `Ditemukan ${devices.length} perangkat baru!`;

        devices.forEach(mac => {
            const deviceCard = document.createElement('div');
            deviceCard.className = 'device-item';
            
            // URL untuk mendaftarkan perangkat
            const registerUrl = `${BASE_URL}controllers/register?mac=${encodeURIComponent(mac)}`;

            // Deteksi Mobile untuk styling tombol bulat (Konsisten dengan mobile-interact.js)
            let btnContent = '<i class="fas fa-plus"></i> Daftar';
            let btnStyle = '';
            if (window.innerWidth <= 768) {
                btnContent = '<i class="fas fa-plus"></i>';
                btnStyle = 'width: 40px; height: 40px; padding: 0; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center;';
            }

            deviceCard.innerHTML = `
                <div>
                    <h3 style="margin: 0 0 5px 0; color: var(--secondary-color);">WLC Controller</h3>
                    <div style="font-family: monospace; color: var(--dark-gray); font-size: 1.1em;">
                        <i class="fas fa-microchip"></i> ${mac}
                    </div>
                    <small style="color: var(--success-color);"><i class="fas fa-circle" style="font-size: 8px;"></i> Online</small>
                </div>
                <a href="${registerUrl}" class="btn btn-success btn-sm" style="${btnStyle}" title="Daftar">
                    ${btnContent}
                </a>
            `;
            deviceList.appendChild(deviceCard);
        });
    }
});