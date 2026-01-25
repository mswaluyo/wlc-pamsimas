**Terakhir Direvisi: 23 November 2025, 10:22 WIB**

---

# Proyek Water Level Controller (WLC)

Sistem monitoring dan kontrol level air berbasis web dan IoT (ESP8266) yang dirancang untuk Pamsimas.

## Alur Logika Pendaftaran dan Operasi Perangkat (ESP8266)

Sistem ini dirancang agar perangkat baru aman dan tidak melakukan tindakan apapun (selain melaporkan keberadaannya) sampai secara eksplisit didaftarkan dan dikonfigurasi oleh administrator melalui antarmuka web.

---

### Tahap 1: Perangkat Baru Dinyalakan (Status: Belum Terdaftar)

1.  **Boot Awal**: Saat perangkat ESP8266 baru pertama kali dinyalakan, firmware akan mencoba membaca EEPROM.
2.  **Status Awal**: Karena EEPROM masih kosong, fungsi `loadConfigFromEEPROM()` akan menetapkan status `isRegistered` ke `false`. Ini adalah kondisi awal standar untuk semua perangkat baru atau yang EEPROM-nya korup.

### Tahap 2: Mode Menunggu (Waiting Mode)

1.  **Koneksi & Sinkronisasi Awal**: Setelah berhasil terhubung ke WiFi, perangkat akan langsung melakukan serangkaian tugas awal: **menyinkronkan waktu NTP**, mengirimkan "ping deteksi" ke server (`/api/log`), lalu langsung mencoba mengambil status dari server (`fetchControlStatus`) dan mengirimkan log offline jika ada.
2.  **Loop Terbatas**: Setelah ping awal, perangkat masuk ke `loop()` utama. Karena status `isRegistered` adalah `false`, loop ini berjalan dalam mode terbatas.
3.  **Tugas Utama**: Dalam mode ini, perangkat hanya melakukan dua hal secara berulang:
    *   Menjaga koneksi WiFi.
    *   Secara berkala (setiap 2 detik), menghubungi endpoint `/api/status` untuk memeriksa apakah sudah didaftarkan oleh administrator.
4.  **Mode Pasif (Tidak Ada Aksi)**: Untuk memastikan keamanan, perangkat secara sengaja dibuat tidak dapat melakukan operasi apa pun hingga diaktivasi. Selama dalam "Mode Menunggu", perangkat **tidak akan**:
    *   **Menjalankan Pompa**: Fungsi `runUniversalPumpLogic()` tidak akan pernah dipanggil. Relay pompa akan selalu dalam keadaan mati.
    *   **Mengukur Sensor**: Fungsi `measureAndSendData()` tidak akan dipanggil. Perangkat tidak akan membaca sensor level air.
    *   **Merespons Tombol**: Input dari tombol fisik untuk mengubah mode atau menyalakan pompa akan diabaikan.
    Perangkat secara efektif berada dalam mode "hanya-dengar" (*listen-only*).

### Tahap 3: Deteksi oleh Server

1.  **Ping Awal Diterima**: Server menerima "ping deteksi" awal dari perangkat melalui endpoint `/api/log`.
2.  **Pencatatan MAC**: Logika pada sisi server (di `DeviceApiController.php`) akan menangani request ini. Ia akan memeriksa apakah MAC address yang masuk sudah ada di database `controllers`.
3.  **Simpan Jika Baru**: Jika MAC address tersebut belum terdaftar, server akan mencatat MAC address dan *timestamp* ke dalam file `storage/detected_macs.json`.
4.  **Tampil di Dashboard**: Halaman "Pengaturan Perangkat" pada antarmuka web membaca file `storage/detected_macs.json` dan menampilkan daftar MAC address dari perangkat-perangkat aktif yang menunggu untuk didaftarkan.

### Tahap 4: Pendaftaran oleh Administrator

1.  **Proses di Web**: Administrator menggunakan tombol "Tambah Perangkat" di antarmuka web.
2.  **Pemilihan Konfigurasi**: Administrator memilih MAC address dari daftar perangkat yang terdeteksi, lalu mengaitkannya dengan konfigurasi yang ada (Tangki, Pompa, dan Sensor).
3.  **Penyimpanan**: Saat form disimpan, server membuat entri baru di tabel `controllers` dalam database, yang berisi MAC address beserta semua parameter konfigurasinya.

### Tahap 5: Aktivasi Perangkat

1.  **Panggilan Berikutnya**: Pada panggilan berikutnya ke `/api/status`, server kini menemukan MAC address perangkat di dalam tabel `controllers`.
2.  **Kirim Konfigurasi**: Server tidak lagi merespons dengan `{"status": "unregistered"}`, melainkan mengirimkan JSON lengkap yang berisi semua data konfigurasi perangkat (mode kontrol, durasi, jarak sensor, dll).
3.  **Penerimaan oleh Perangkat**:
    *   Firmware menerima JSON konfigurasi ini.
    *   Fungsi `fetchControlStatus()` mendeteksi bahwa perangkat tidak lagi "unregistered".
    *   Status `isRegistered` diubah menjadi `true`.
    *   Status "terdaftar" ini (`1`) dan semua parameter konfigurasi yang diterima akan disimpan ke EEPROM agar tidak hilang saat perangkat di-restart.

### Tahap 6: Mode Operasional Penuh

1.  **Loop Normal**: Pada iterasi `loop()` selanjutnya, kondisi `if (isRegistered)` kini bernilai `true`.
2.  **Fungsi Penuh**: Perangkat keluar dari "Mode Menunggu" dan mulai menjalankan semua fungsi normalnya:
    *   Mengukur level air (`measureAndSendData`).
    *   Menjalankan logika pompa otonom (`runUniversalPumpLogic`).
    *   Merespons input dari tombol fisik.

Alur ini memastikan bahwa setiap perangkat baru bersifat "plug-and-play" dari sisi lapangan, namun tetap memerlukan otorisasi dan konfigurasi terpusat dari administrator sebelum dapat beroperasi.

---

## Alur Operasi Perangkat yang Sudah Terdaftar (Boot ke Mode Penuh)

Berikut adalah alur yang terjadi ketika perangkat yang sebelumnya sudah terdaftar dinyalakan kembali.

### Tahap 1: Boot dan Inisialisasi

1.  **Membaca EEPROM**: Saat `setup()` dimulai, fungsi `loadConfigFromEEPROM()` dipanggil.
2.  **Konfirmasi Status**: Fungsi tersebut membaca alamat `EEPROM_ADDR_IS_REGISTERED` dan menemukan nilai `1`. Ini langsung mengatur status `isRegistered` menjadi `true`.
3.  **Memuat Konfigurasi Lokal**: Semua parameter konfigurasi terakhir yang valid (durasi, jarak sensor, dll.) dimuat dari EEPROM ke dalam memori. Perangkat kini sudah memiliki "ingatan" tentang cara kerjanya.
4.  **Inisialisasi Perangkat Keras**: Pin untuk sensor, relay, tombol, dan LED diinisialisasi. Mode operasi secara *default* diatur ke "AUTO" sebagai tindakan keamanan.

### Tahap 2: Koneksi dan Sinkronisasi Awal

1.  **Koneksi WiFi & NTP**: Perangkat terhubung ke WiFi. Setelah berhasil, ia langsung menyinkronkan waktu melalui server NTP.
2.  **Sinkronisasi Konfigurasi Awal**: Perangkat segera memanggil `fetchControlStatus()` satu kali. Ini adalah langkah krusial:
    *   Perangkat menghubungi server dan mengirimkan MAC address-nya.
    *   Server merespons dengan konfigurasi terbaru yang ada di database.
    *   Firmware membandingkan konfigurasi dari server dengan yang baru saja dimuat dari EEPROM. Jika ada perbedaan (misalnya, administrator mengubah pengaturan saat perangkat mati), firmware akan memperbarui konfigurasinya dan menyimpannya kembali ke EEPROM.
3.  **Mengirim Log Offline**: Jika ada data log yang tersimpan di LittleFS dari sesi sebelumnya (misalnya saat koneksi terputus), perangkat akan mengirimkannya ke server melalui `sendOfflineLogs()`.

### Tahap 3: Loop Operasional Penuh

1.  **Masuk ke Loop Utama**: Fungsi `setup()` selesai dan program masuk ke `loop()`.
2.  **Eksekusi Logika Inti**: Karena `isRegistered` bernilai `true`, blok logika utama langsung dieksekusi secara berulang:
    *   **`measureAndSendData()`**: Mengukur level air secara berkala dan mengirimkan datanya ke server.
    *   **`runUniversalPumpLogic()`**: Menjalankan logika kontrol pompa (mode AUTO, TIMED, atau merespons perintah MANUAL).
    *   **`button.check()`**: Memeriksa input dari tombol fisik.
    *   **`fetchControlStatus()`**: Secara berkala (setiap 2 detik), tetap menghubungi server untuk memeriksa apakah ada perubahan mode atau perintah baru dari dashboard web.

Dengan alur ini, perangkat yang sudah terdaftar dapat segera beroperasi secara mandiri menggunakan konfigurasi terakhir yang disimpannya, sambil secara proaktif menyinkronkan diri dengan server untuk memastikan pengaturannya selalu yang terbaru.

---

## Logging Koneksi WiFi

Untuk mempermudah proses debugging melalui Serial Monitor, firmware membedakan antara dua jenis upaya koneksi WiFi, yang ditandai dengan log yang berbeda:

1.  **Koneksi Awal (Boot)**: Saat perangkat pertama kali dinyalakan, upaya koneksi akan dicatat dengan pesan yang diawali `[BOOT]`.
    ```
    [BOOT] Mencoba menghubungkan ke WiFi untuk pertama kali...
    ```

2.  **Koneksi Ulang (Reconnect)**: Jika koneksi terputus di tengah jalan, perangkat akan mencoba menyambung kembali secara berkala. Upaya ini akan dicatat dengan pesan yang diawali `[RECONNECT]`.
    ```
    [RECONNECT] Mencoba menyambungkan ulang ke WiFi...
    ```

Perbedaan ini membantu administrator untuk dengan cepat mengidentifikasi dari log serial apakah masalah koneksi terjadi saat startup atau selama operasi normal.

---

## Logging Status Perangkat dan Jaringan

Untuk lebih memperjelas konteks saat melakukan *troubleshooting*, firmware juga menggunakan awalan log yang spesifik untuk peristiwa terkait daya dan status jaringan.

1.  **Status Daya Perangkat (`DEVICE:`)**: Log ini hanya muncul satu kali setiap kali perangkat dinyalakan atau di-restart.
    ```
    DEVICE: Perangkat dinyalakan (Booting up)...
    ```

2.  **Status Jaringan (`NETWORK:`)**: Log ini muncul setiap kali koneksi WiFi terputus atau berhasil pulih.
    *   Saat koneksi terputus:
        ```
        NETWORK: Koneksi terputus. Beralih ke mode AUTO sebagai fallback.
        ```
    *   Saat koneksi pulih:
        ```
        NETWORK: Koneksi pulih. Akan menyinkronkan status dengan server.
        ```

Penggunaan awalan `DEVICE:` dan `NETWORK:` ini memudahkan untuk membedakan antara masalah yang disebabkan oleh perangkat yang mati (misalnya, listrik padam) dengan masalah yang hanya terkait dengan gangguan jaringan WiFi.

---

## Pelaporan Peristiwa ke Server

Selain menampilkan log di Serial Monitor, firmware juga secara aktif melaporkan peristiwa-peristiwa penting ke server. Ini memungkinkan pemantauan terpusat dan pencatatan riwayat kejadian untuk setiap perangkat.

Laporan dikirim sebagai perintah dengan `action` berupa `report_event` dan `value` yang menjelaskan peristiwanya.

Peristiwa yang dilaporkan meliputi:

1.  **`boot`**: Dikirim setiap kali perangkat selesai melakukan proses startup dan berhasil terhubung ke WiFi. Ini menandakan perangkat baru saja dinyalakan atau di-restart.
2.  **`network_recovered`**: Dikirim saat perangkat berhasil menyambung kembali ke WiFi setelah sebelumnya koneksi terputus.
3.  **`network_lost`**: Peristiwa ini tidak bisa dikirim secara langsung saat terjadi. Sebagai gantinya, firmware akan mencatatnya ke dalam sebuah file log offline (`event_log.txt`). Saat koneksi pulih, log ini akan dikirim ke server bersama dengan log offline lainnya.

Dengan sistem ini, server akan memiliki catatan yang akurat tentang siklus hidup dan stabilitas koneksi setiap perangkat di lapangan.

---

## Penanganan Kehilangan Daya (Power Loss)

Sistem ini memiliki mekanisme cerdas di sisi server untuk mendeteksi dan mencatat peristiwa kehilangan daya (mati listrik) pada perangkat, bukan hanya restart biasa. Ini memberikan wawasan penting tentang stabilitas catu daya di lokasi perangkat.

### Alur Deteksi

1.  **Laporan `boot` dari Perangkat**: Setiap kali perangkat berhasil dinyalakan dan terhubung ke jaringan, ia akan mengirimkan laporan peristiwa `boot` ke server (seperti yang dijelaskan di bagian sebelumnya).

2.  **Analisis oleh Server**: Ketika server (khususnya `DeviceApiController`) menerima peristiwa `boot` untuk sebuah perangkat, ia tidak hanya mencatatnya begitu saja. Server akan melakukan langkah-langkah berikut:
    *   Mengambil *timestamp* dari `last_update` (kapan terakhir kali perangkat tersebut online) dari database.
    *   Membandingkan *timestamp* tersebut dengan waktu saat ini.
    *   Menghitung selisih waktu (durasi offline).

3.  **Kesimpulan Logis**:
    *   **Jika durasi offline singkat** (misalnya, di bawah 60 detik), server mengasumsikan perangkat hanya melakukan restart normal (misalnya, karena perintah dari server atau fluktuasi jaringan sesaat).
    *   **Jika durasi offline signifikan** (lebih dari 60 detik), server menyimpulkan bahwa perangkat tersebut kemungkinan besar mengalami kehilangan daya.

4.  **Pencatatan Peristiwa Spesifik**: Jika terdeteksi kehilangan daya, server akan membuat entri `EventLog` yang jauh lebih informatif, seperti:
    ```
    "Perangkat pulih dari kehilangan daya setelah mati selama 00 jam, 15 menit, 30 detik"
    ```

Mekanisme ini sejalan dengan prinsip **Logging yang Jelas**, karena memberikan catatan riwayat yang akurat dan terpusat mengenai kondisi fisik catu daya untuk setiap perangkat di lapangan, langsung dari dashboard web.

---

## Struktur Arsitektur Aplikasi (MVC)

Aplikasi ini dibangun menggunakan pola desain **Model-View-Controller (MVC)** murni tanpa menggunakan framework pihak ketiga yang berat, untuk menjaga performa tetap ringan dan kontrol penuh atas kode.

### 1. Model (`app/Models/`)
Bertanggung jawab atas representasi data dan logika bisnis. Model berinteraksi langsung dengan database.
*   **Contoh**: `Controller.php` (Logika perangkat), `Tank.php` (Data tangki), `SensorLog.php` (Pencatatan data sensor).
*   **Fungsi**: Melakukan operasi CRUD (Create, Read, Update, Delete) dan validasi data sebelum disimpan.

### 2. View (`app/Views/`)
Bertanggung jawab atas presentasi data kepada pengguna. File-file ini berisi HTML yang disisipi kode PHP minimal untuk menampilkan data dinamis.
*   **Struktur**:
    *   `layouts/`: Template utama (header, sidebar, footer) untuk menjaga konsistensi tampilan.
    *   `dashboard/`, `settings/`, `users/`: Folder spesifik untuk setiap modul.
*   **Helper**: Menggunakan fungsi helper `view()` untuk merender halaman dengan atau tanpa layout utama.

### 3. Controller (`app/Controllers/`)
Bertindak sebagai penghubung antara Model dan View. Controller menerima input dari Router, memprosesnya (seringkali dengan meminta data dari Model), dan menentukan View mana yang akan ditampilkan.
*   **Web Controller**: Menangani permintaan halaman HTML (misal: `DashboardController`, `SettingController`).
*   **API Controller**: Menangani permintaan data JSON dari perangkat IoT atau AJAX (misal: `Api\DeviceApiController`).

### 4. Core & Routing (`core/`)
*   **`Router.php`**: Memetakan URL yang diminta ke Controller dan Method yang sesuai. Mendukung metode GET dan POST serta parameter dinamis.
*   **`Database.php`**: Mengelola koneksi database menggunakan PDO dengan pola Singleton.
*   **`helpers.php`**: Kumpulan fungsi utilitas global seperti `base_url()`, `view()`, dan `isActive()`.

---

## Alur dan Interaksi Database

Aplikasi menggunakan database MySQL/MariaDB dengan pendekatan yang aman dan efisien.

### 1. Koneksi (Singleton Pattern)
Koneksi database dikelola oleh kelas `core/Database.php`. Pola **Singleton** memastikan bahwa hanya ada satu instans koneksi database yang dibuat selama siklus hidup permintaan (request), mencegah pemborosan resource server.

### 2. Interaksi Model-Database
Setiap Model (misal: `app/Models/Controller.php`) tidak menyimpan koneksi database sebagai properti kelas. Sebaliknya, mereka memanggil instans database saat dibutuhkan:
```php
$pdo = \Database::getInstance()->getConnection();
$stmt = $pdo->prepare("SELECT * FROM controllers WHERE id = :id");
```

### 3. Keamanan Data
Semua interaksi database yang melibatkan input pengguna atau data eksternal menggunakan **Prepared Statements** (PDO). Hal ini secara efektif mencegah serangan **SQL Injection** dengan memisahkan query SQL dari data yang dimasukkan.

---

## Panduan Instalasi & Konfigurasi

### 1. Konfigurasi Database (.env)
Aplikasi ini menggunakan file `.env` untuk menyimpan konfigurasi sensitif agar aman dan fleksibel.

1.  **Buat File .env**: Salin file `.env.example` di root folder menjadi `.env`.
2.  **Edit Kredensial**: Buka file `.env` dan sesuaikan dengan pengaturan database lokal atau hosting Anda:
    ```ini
    DB_HOST=localhost
    DB_NAME=wlc_db
    DB_USER=root
    DB_PASS=
    ```
3.  **Keamanan**: File `.env` berisi password asli, jadi **jangan di-upload** ke repository publik.

### 5. Penjadwalan Backup Otomatis (Cron Job)
Anda dapat mengatur backup otomatis dengan memanggil endpoint API khusus secara berkala.

1.  Pastikan `BACKUP_TOKEN` sudah diatur di file `.env`.
2.  URL Endpoint: `http://localhost/wlc/public/api/system/backup?token=TOKEN_ANDA`

**Windows (Task Scheduler):**
1.  Buka **Task Scheduler** -> **Create Basic Task**.
2.  Nama: "WLC Auto Backup". Trigger: "Daily".
3.  Action: "Start a program".
4.  Program/script: `curl`
5.  Add arguments: `"http://localhost/wlc/public/api/system/backup?token=rahasia_wlc_2025"`

**Linux (Cron):**
Tambahkan baris berikut ke crontab (`crontab -e`) untuk backup setiap jam 12 malam:
```bash
0 0 * * * curl "http://your-domain.com/api/system/backup?token=rahasia_wlc_2025" >/dev/null 2>&1
```

### 2. Deployment ke Hosting
Saat memindahkan aplikasi ke server produksi (hosting):

1.  **Upload File**: Upload semua file proyek **kecuali** file `.env`.
2.  **Buat .env di Hosting**: Buat file `.env` baru di file manager hosting dan isi dengan kredensial database hosting.
3.  **Izin Folder**: Pastikan folder `storage/` memiliki izin tulis (**Write Permissions**, biasanya 755 atau 775) agar fitur deteksi perangkat dan logging berfungsi.
4.  **Database**: Ekspor database lokal Anda ke file `.sql` dan impor ke database hosting melalui phpMyAdmin.

### 3. Persyaratan Server
*   PHP 7.4 atau lebih baru.
*   Ekstensi PHP: PDO, JSON.
*   Database MySQL atau MariaDB.

### 4. Backup Database
Untuk keperluan backup atau migrasi, Anda dapat membuat dump database `.sql`.

**Cara 1: phpMyAdmin**
1.  Buka phpMyAdmin (`http://localhost/phpmyadmin`).
2.  Pilih database `wlc_db`.
3.  Klik tab **Export**.
4.  Pilih format **SQL** dan klik **Go**.

**Cara 2: Command Line (mysqldump)**
```bash
mysqldump -u root -p wlc_db > backup_wlc_db.sql
```
