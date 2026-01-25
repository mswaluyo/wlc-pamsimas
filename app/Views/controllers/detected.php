<style>
    .container { max-width: 960px; margin: 20px auto; font-family: sans-serif; }
    .detected-container { border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .detected-header { background-color: #f7f7f7; padding: 15px 20px; border-bottom: 1px solid #ddd; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
    .detected-header h2 { margin: 0; font-size: 1.2em; }
    .detected-body { padding: 20px; }
    #device-list { list-style-type: none; padding: 0; }
    #device-list li { background-color: #fff; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
    #device-list .mac-address { font-family: monospace; font-weight: bold; }
    #device-list .register-btn { text-decoration: none; background-color: #28a745; color: white; padding: 8px 12px; border-radius: 5px; font-size: 0.9em; }
    #loading-spinner { text-align: center; padding: 20px; color: #777; }
    .status-indicator { width: 12px; height: 12px; background-color: #6c757d; border-radius: 50%; display: inline-block; animation: blink 1.5s infinite; }
    @keyframes blink { 50% { opacity: 0.5; } }
</style>

<div class="container">
    <h1><?php echo $title ?? 'Deteksi Perangkat'; ?></h1>

    <div class="detected-container">
        <div class="detected-header">
            <h2><span class="status-indicator"></span> Mencari perangkat baru...</h2>
        </div>
        <div class="detected-body">
            <div id="loading-spinner">
                <p>Memuat daftar perangkat yang terdeteksi...</p>
            </div>
            <ul id="device-list">
                <!-- Daftar perangkat akan dimuat di sini oleh JavaScript -->
            </ul>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deviceList = document.getElementById('device-list');
        const loadingSpinner = document.getElementById('loading-spinner');
        const baseUrl = '/wlc'; // Sesuaikan jika path proyek Anda berbeda

        function fetchDetectedDevices() {
            fetch(`${baseUrl}/public/api/detected-devices`)
                .then(response => response.json())
                .then(macAddresses => {
                    loadingSpinner.style.display = 'none';
                    deviceList.innerHTML = ''; // Kosongkan daftar sebelum mengisi ulang

                    if (macAddresses.length === 0) {
                        deviceList.innerHTML = '<li>Tidak ada perangkat baru yang terdeteksi saat ini.</li>';
                    } else {
                        macAddresses.forEach(mac => {
                            const listItem = document.createElement('li');
                            
                            const macSpan = document.createElement('span');
                            macSpan.className = 'mac-address';
                            macSpan.textContent = mac;

                            const registerLink = document.createElement('a');
                            registerLink.className = 'register-btn';
                            registerLink.href = `${baseUrl}/controllers/register?mac=${encodeURIComponent(mac)}`;
                            registerLink.textContent = 'Daftarkan Perangkat Ini';

                            listItem.appendChild(macSpan);
                            listItem.appendChild(registerLink);
                            deviceList.appendChild(listItem);
                        });
                    }
                })
                .catch(error => {
                    console.error('Gagal mengambil data perangkat:', error);
                    loadingSpinner.style.display = 'none';
                    deviceList.innerHTML = '<li>Terjadi kesalahan saat memuat data.</li>';
                });
        }

        // Panggil fungsi pertama kali
        fetchDetectedDevices();

        // Atur interval untuk memanggil fungsi setiap 5 detik
        setInterval(fetchDetectedDevices, 5000);
    });
</script>
```

### **Langkah 3: Tambahkan Rute di `routes/web.php`**

Anda perlu menambahkan rute baru agar URL untuk halaman deteksi dapat diakses. Buka file `routes/web.php` Anda dan tambahkan baris berikut:

```php
// (Contoh isi file routes/web.php)
$router->get('/controllers/detected', 'ControllerController@showDetected');
```

### **Langkah 4: Tambahkan Menu di Sidebar Anda**

Sekarang, Anda hanya perlu menambahkan link ke halaman baru ini di file layout sidebar Anda (kemungkinan besar di `app/Views/layouts/main.php`).

Cari bagian menu "Pengaturan" dan tambahkan item menu baru di paling atas seperti contoh di bawah ini:

```html
<!-- Contoh struktur menu di sidebar Anda -->
<li class="nav-item">
    <a href="/wlc/controllers/detected" class="nav-link">
        <i class="nav-icon fas fa-search"></i>
        <p>Deteksi Perangkat</p>
    </a>
</li>
<li class="nav-item">
    <a href="/wlc/controllers" class="nav-link">
        <i class="nav-icon fas fa-cogs"></i>
        <p>Pengaturan Perangkat</p>
    </a>
</li>
<!-- Item menu lainnya -->
```

Dengan perubahan ini, Anda sekarang memiliki halaman khusus yang didedikasikan untuk mendeteksi perangkat baru secara *real-time*, dan link menuju halaman tersebut sudah tersedia di menu sidebar Anda.