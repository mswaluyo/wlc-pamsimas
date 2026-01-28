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

    // 4. Ubah tombol "Tambah Perangkat" menjadi FAB (Floating Action Button) di Mobile
    if (window.innerWidth <= 768) {
        var links = document.querySelectorAll('a');
        links.forEach(function(link) {
            var text = link.innerText.trim();
            if (text.includes('Tambah Perangkat') || (link.href && link.href.includes('controllers/register'))) {
                link.title = text; // Tambahkan Tooltip
                link.innerHTML = '<i class="fas fa-plus"></i>';
                link.className = 'mobile-fab-btn'; // Ganti class sepenuhnya menjadi FAB
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
                btn.style.padding = '8px'; // Padding seimbang
            }
        });
    }
});