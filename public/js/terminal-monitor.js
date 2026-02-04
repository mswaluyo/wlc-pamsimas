/**
 * Script untuk Terminal Monitoring Live
 * Mengambil data event log terbaru dan menampilkannya seperti console.
 */

document.addEventListener('DOMContentLoaded', function() {
    const terminalWindow = document.getElementById('terminal-window');
    const statusText = document.getElementById('terminal-status-text');
    const statusDot = document.getElementById('terminal-status-dot');
    const autoScrollToggle = document.getElementById('auto-scroll-toggle');
    const clearBtn = document.getElementById('clear-terminal-btn');
    
    // Hanya jalankan jika elemen terminal ada di halaman
    if (!terminalWindow) return;

    // Pastikan BASE_URL tersedia
    let baseUrl = (typeof BASE_URL !== 'undefined') ? BASE_URL : '/wlc/public/';
    if (!baseUrl.endsWith('/')) baseUrl += '/';

    let isAutoScroll = true; // Default aktif
    let minDisplayId = 0;    // ID minimum untuk ditampilkan (filter Clear)
    let maxSeenId = 0;       // ID maksimum yang pernah diterima

    if (autoScrollToggle) {
        isAutoScroll = autoScrollToggle.checked;
        autoScrollToggle.addEventListener('change', function() {
            isAutoScroll = this.checked;
            if (isAutoScroll) {
                terminalWindow.scrollTop = terminalWindow.scrollHeight;
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            // Set batas ID minimum ke ID terakhir yang kita lihat
            minDisplayId = maxSeenId;
            terminalWindow.innerHTML = '<div style="padding:10px; color:#555; font-style:italic; text-align:center;">Terminal dibersihkan. Menunggu data baru...</div>';
        });
    }

    function fetchEvents() {
        // Gunakan timestamp untuk cache busting
        const apiUrl = baseUrl + 'api/terminal/events?limit=50&t=' + new Date().getTime();

        fetch(apiUrl)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                updateTerminal(data);
                
                // Update status indikator
                if (statusText) statusText.textContent = 'Live Monitoring';
                if (statusDot) statusDot.classList.add('active');
            })
            .catch(err => {
                console.error('Terminal fetch error:', err);
                if (statusText) statusText.textContent = 'Disconnected';
                if (statusDot) statusDot.classList.remove('active');
            });
    }

    function updateTerminal(events) {
        // Simpan posisi scroll saat ini untuk mode non-aktif
        const previousScrollTop = terminalWindow.scrollTop;

        // Kosongkan terminal untuk render ulang (sederhana & mencegah duplikasi)
        terminalWindow.innerHTML = '';
        
        if (events.length === 0) {
            terminalWindow.innerHTML = '<div style="padding:20px; text-align:center; color:#555;">Belum ada data log kejadian.</div>';
            return;
        }

        // Balik urutan (reverse) agar data terbaru ada di BAWAH (seperti terminal asli)
        // events dari server biasanya DESC (terbaru di index 0), kita butuh ASC untuk append ke bawah
        events.slice().reverse().forEach(event => {
            // Update maxSeenId untuk referensi Clear berikutnya
            if (event.id && event.id > maxSeenId) {
                maxSeenId = event.id;
            }

            // Filter: Jangan tampilkan log yang ID-nya lebih kecil atau sama dengan saat tombol Clear ditekan
            if (event.id && event.id <= minDisplayId) return;

            const line = document.createElement('div');
            line.className = 'terminal-line';
            
            const time = document.createElement('div');
            time.className = 'terminal-time';
            // Tampilkan waktu apa adanya dari server (Raw) untuk menghindari kesalahan konversi ganda
            time.textContent = event.event_time || event.timestamp; 
            
            const source = document.createElement('div');
            source.className = 'terminal-source';
            source.textContent = `[${event.event_type || 'SYSTEM'}]`;
            
            const msg = document.createElement('div');
            msg.className = 'terminal-message';
            msg.textContent = event.description || event.message;
            
            line.appendChild(time);
            line.appendChild(source);
            line.appendChild(msg);
            
            terminalWindow.appendChild(line);
        });

        // Logika Auto Scroll
        if (isAutoScroll) {
            terminalWindow.scrollTop = terminalWindow.scrollHeight;
        } else {
            // Kembalikan posisi scroll agar user tidak terganggu saat membaca log lama
            terminalWindow.scrollTop = previousScrollTop;
        }
    }

    // Jalankan polling setiap 3 detik
    setInterval(fetchEvents, 3000);
    
    // Panggilan pertama langsung
    fetchEvents();
});