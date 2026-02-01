document.addEventListener('DOMContentLoaded', function() {
    // 1. Logika Hamburger Menu
    if (!document.getElementById('mobile-menu-toggle')) {
        var btn = document.createElement('button');
        btn.id = 'mobile-menu-toggle';
        btn.innerHTML = '&#9776;'; // Gunakan Unicode (Garis Tiga) agar tombol muncul meski Offline/Tanpa Internet
        btn.style.cssText = 'position:fixed; top:10px; left:10px; z-index:10000; background:#3498db; color:white; border:none; padding:8px 12px; border-radius:4px; font-size:20px; cursor:pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: left 0.3s ease;';
        
        // Hanya tambahkan jika di layar kecil (opsional, CSS sudah menangani display)
        document.body.appendChild(btn);

        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        btn.onclick = function() {
            var sidebar = document.querySelector('.sidebar');
            if(sidebar) sidebar.classList.toggle('show');
            btn.classList.toggle('shifted');
            overlay.classList.toggle('show');
        };

        overlay.onclick = function() { btn.click(); };
    }

    // 2. Sembunyikan kolom tabel tertentu di Mobile
    if (window.innerWidth <= 768) {
        var tables = document.querySelectorAll('table');
        tables.forEach(function(table) {
            var headers = table.querySelectorAll('th');
            var hideIndices = [];
            headers.forEach(function(th, i) {
                var t = th.innerText.toLowerCase();
                if (t.includes('tipe') || t.includes('tanggal') || t.includes('created') || t.includes('type')) {
                    th.style.display = 'none';
                    hideIndices.push(i);
                }
            });
            var rows = table.querySelectorAll('tr');
            rows.forEach(function(tr) {
                hideIndices.forEach(function(i) {
                    if (tr.cells[i]) tr.cells[i].style.display = 'none';
                });
            });
        });
    }

    // 3. Peningkatan Halaman Login (Inject Logo & Placeholder)
    var loginContainer = document.querySelector('.login-container');
    if (loginContainer && !document.querySelector('.banyutech-logo-wrapper')) {
        var logoDiv = document.createElement('div');
        logoDiv.className = 'banyutech-logo-wrapper';
        // Menggunakan BASE_URL yang didefinisikan di main.php
        logoDiv.innerHTML = '<img src="' + BASE_URL + 'img/logo.png" class="banyutech-logo-img" /><span class="banyutech-brand">BanyuTech</span>';
        loginContainer.insertBefore(logoDiv, loginContainer.firstChild);

        var inputs = loginContainer.querySelectorAll('input');
        if(inputs[0]) inputs[0].placeholder = 'Username';
        if(inputs[1]) inputs[1].placeholder = 'Password';

        var copyright = document.createElement('div');
        copyright.innerHTML = '&copy; 2026 BanyuTech';
        copyright.style.cssText = 'position: absolute; bottom: 20px; width: 100%; text-align: center; color: rgba(255,255,255,0.6); font-size: 0.9rem;';
        document.body.appendChild(copyright);
    }

    // 4. Ubah tombol aksi utama (Tambah, Scan, Simpan, dll) menjadi FAB di Mobile
    if (window.innerWidth <= 768) {
        // Seleksi tombol yang potensial: Link atau Button dengan class .btn
        var candidates = document.querySelectorAll('a.btn, button.btn');
        
        candidates.forEach(function(btn) {
            // Abaikan tombol di dalam tabel, modal, navbar, atau sidebar
            // CATATAN: Tombol dalam form (btn.closest('form')) KINI DIIZINKAN agar tombol "Simpan" masuk ke FAB
            if (btn.closest('table') || btn.closest('.modal') || btn.closest('.navbar') || btn.closest('.sidebar')) {
                return;
            }

            var text = btn.innerText.trim();
            var lowerText = text.toLowerCase();
            
            // Filter tambahan: Abaikan tombol yang bersifat navigasi balik, tutup, atau pembatalan
            var excludeKeywords = ['tutup', 'close', 'batal', 'cancel', 'kembali', 'back'];
            if (excludeKeywords.some(k => lowerText.includes(k))) {
                return;
            }
            
            var icon = btn.querySelector('i');

            // Kriteria tombol yang akan dijadikan FAB:
            // Mengandung kata kunci aksi ATAU memiliki class btn-primary/success
            var keywords = ['tambah', 'scan', 'daftar', 'import', 'export', 'simpan', 'new', 'create', 'cari', 'search', 'deteksi'];
            var isKeywordMatch = keywords.some(k => lowerText.includes(k));
            var isStyleMatch = btn.classList.contains('btn-primary') || btn.classList.contains('btn-success');

            if ((isKeywordMatch || isStyleMatch) && text.length > 0) {
                btn.title = text; // Simpan teks asli sebagai label
                
                // Jika belum ada icon, berikan icon default berdasarkan teks
                if (!icon) {
                    if (lowerText.includes('tambah')) btn.innerHTML = '<i class="fas fa-plus"></i>';
                    else if (lowerText.includes('scan') || lowerText.includes('cari') || lowerText.includes('search') || lowerText.includes('deteksi')) btn.innerHTML = '<i class="fas fa-search"></i>';
                    else if (lowerText.includes('simpan')) btn.innerHTML = '<i class="fas fa-save"></i>';
                    else btn.innerHTML = '<i class="fas fa-bolt"></i>';
                } else {
                    // Jika sudah ada icon, pastikan hanya icon yang tampil
                    var iconClone = icon.cloneNode(true);
                    btn.innerHTML = '';
                    btn.appendChild(iconClone);
                }

                // Jika tombol berasal dari form, pastikan tetap bisa submit form tersebut setelah dipindah
                var parentForm = btn.closest('form');
                if (parentForm) {
                    if (!parentForm.id) {
                        parentForm.id = 'form-' + Math.random().toString(36).substr(2, 9);
                    }
                    btn.setAttribute('form', parentForm.id);
                }

                // Tambahkan class khusus agar diproses oleh Logic FAB Group (Poin 6)
                btn.classList.add('mobile-fab-btn');
            }
        });
    }

    // 5. Ubah tombol aksi lain (.btn) menjadi Icon-Only di Mobile untuk menghemat tempat
    if (window.innerWidth <= 768) {
        document.querySelectorAll('.btn').forEach(function(btn) {
            var icon = btn.querySelector('i');
            // Hanya proses jika ada ikon dan ada teks yang terlihat
            if (icon && btn.innerText.trim().length > 0) {
                btn.title = btn.innerText.trim(); // Jadikan teks sebagai tooltip
                
                // Hapus teks node, sisakan ikon
                var cloneIcon = icon.cloneNode(true);
                btn.innerHTML = '';
                btn.appendChild(cloneIcon);
                
                // Style agar rapi
                btn.style.display = 'inline-flex';
                btn.style.justifyContent = 'center';
                btn.style.alignItems = 'center';
                btn.style.width = '40px'; // Lebar tetap agar bulat sempurna
                btn.style.height = '40px'; // Tinggi tetap agar bulat sempurna
                btn.style.padding = '0'; // Reset padding
                btn.style.borderRadius = '50%'; // Buat jadi bulat
            }
        });
    }

    // 6. Sistem FAB Group (Auto Hide / Speed Dial) untuk Mobile
    // Mengumpulkan semua tombol FAB ke dalam satu menu toggle agar rapi
    if (window.innerWidth <= 768) {
        // Cek apakah ada tombol aksi di halaman ini
        var existingFabs = document.querySelectorAll('.mobile-fab-btn');
        // if (existingFabs.length === 0) return; // HAPUS: Menu Sidebar akan selalu ada di FAB

        // Cari atau buat container utama
        var fabContainer = document.getElementById('fab-container');
        if (!fabContainer) {
            fabContainer = document.createElement('div');
            fabContainer.id = 'fab-container';
            // UPDATE: Jarak diperkecil (10px) agar lebih ke pojok, gap diperkecil
            fabContainer.style.cssText = 'position: fixed; bottom: 10px; right: 10px; z-index: 9990; display: flex; flex-direction: column-reverse; align-items: flex-end; gap: 10px;';
            document.body.appendChild(fabContainer);

            // Wrapper untuk tombol-tombol aksi (Hidden by default)
            var actionWrapper = document.createElement('div');
            actionWrapper.id = 'fab-actions';
            // UPDATE: align-items: flex-end agar menu rata kanan
            actionWrapper.style.cssText = 'display: flex; flex-direction: column-reverse; gap: 10px; align-items: flex-end; opacity: 0; transform: translateY(20px); transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55); pointer-events: none; margin-bottom: 10px;';

            // Tombol Toggle Utama (Menu)
            var toggleBtn = document.createElement('button');
            toggleBtn.id = 'fab-toggle';
            toggleBtn.className = 'btn btn-primary';
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            // UPDATE: Ukuran sedikit diperkecil (50px) agar proporsional di pojok
            toggleBtn.style.cssText = 'width: 50px; height: 50px; border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.3); z-index: 9992; font-size: 20px; padding: 0; border: none; transition: transform 0.3s ease;';
            
            // PERBAIKAN URUTAN: Tambahkan tombol dulu, baru wrapper aksi.
            // Karena 'column-reverse', elemen pertama (toggleBtn) akan visualnya di paling bawah.
            // Elemen kedua (actionWrapper) akan visualnya di atas tombol (naik).
            fabContainer.appendChild(toggleBtn);
            fabContainer.appendChild(actionWrapper);

            // --- TAMBAHAN: Masukkan Tombol "Menu Sidebar" ke dalam FAB ---
            var menuContainer = document.createElement('div');
            menuContainer.style.cssText = 'display: flex; align-items: center; gap: 10px; flex-direction: row-reverse;';
            
            var menuBtn = document.createElement('button');
            menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            menuBtn.style.cssText = 'width: 45px; height: 45px; border-radius: 50%; display: flex; justify-content: center; align-items: center; padding: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.2); border: none; background-color: #f39c12; color: white; cursor: pointer;';
            
            menuBtn.onclick = function() {
                var defaultToggle = document.getElementById('mobile-menu-toggle');
                if (defaultToggle) defaultToggle.click(); // Trigger tombol asli untuk buka sidebar
                toggleBtn.click(); // Tutup menu FAB
            };

            var menuLabel = document.createElement('span');
            menuLabel.innerText = 'Menu Utama';
            menuLabel.style.cssText = 'background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; white-space: nowrap;';
            
            menuContainer.appendChild(menuBtn);
            menuContainer.appendChild(menuLabel);
            actionWrapper.appendChild(menuContainer);

            // Sembunyikan tombol hamburger default (kiri atas) karena sudah pindah ke FAB
            var defaultToggle = document.getElementById('mobile-menu-toggle');
            if (defaultToggle) defaultToggle.style.display = 'none';
            // -------------------------------------------------------------

            // Logika Toggle
            var isOpen = false;
            toggleBtn.onclick = function(e) {
                e.stopPropagation();
                isOpen = !isOpen;
                if (isOpen) {
                    toggleBtn.innerHTML = '<i class="fas fa-times"></i>';
                    toggleBtn.style.transform = 'rotate(90deg)';
                    actionWrapper.style.opacity = '1';
                    actionWrapper.style.transform = 'translateY(0)';
                    actionWrapper.style.pointerEvents = 'auto';
                } else {
                    toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                    toggleBtn.style.transform = 'rotate(0deg)';
                    actionWrapper.style.opacity = '0';
                    actionWrapper.style.transform = 'translateY(20px)';
                    actionWrapper.style.pointerEvents = 'none';
                }
            };

            // Tutup menu jika klik di luar
            document.addEventListener('click', function(e) {
                if (isOpen && !fabContainer.contains(e.target)) {
                    toggleBtn.click();
                }
            });
        }

        // Pindahkan tombol FAB "Tambah Perangkat" (dari poin 4) ke dalam actionWrapper
        var actionWrapper = document.getElementById('fab-actions');
        
        existingFabs.forEach(function(btn) {
            // Reset posisi fixed agar mengikuti flow container
            btn.style.position = 'static';
            btn.style.margin = '0';
            btn.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
            btn.style.width = '45px';
            btn.style.height = '45px';
            btn.style.borderRadius = '50%';
            btn.style.display = 'flex';
            btn.style.justifyContent = 'center';
            btn.style.alignItems = 'center';
            btn.style.padding = '0';
            
            // Bungkus dengan label teks
            var itemContainer = document.createElement('div');
            itemContainer.style.cssText = 'display: flex; align-items: center; gap: 10px; flex-direction: row-reverse;';
            
            var label = document.createElement('span');
            label.innerText = btn.title || 'Tambah';
            label.style.cssText = 'background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; white-space: nowrap;';
            
            itemContainer.appendChild(btn);
            itemContainer.appendChild(label);
            
            actionWrapper.appendChild(itemContainer);
        });
    }
});