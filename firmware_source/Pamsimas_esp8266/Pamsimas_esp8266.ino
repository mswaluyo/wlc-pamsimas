// =================================================================
// == Kode ini diadaptasi dari kode Blynk untuk Server Web Pribadi ==
// =================================================================
#include <WiFiClientSecure.h> // Tambahkan library untuk HTTPS
// Uncomment baris di bawah ini untuk melakukan factory reset EEPROM pada flash berikutnya.
// #define FORCE_FACTORY_RESET  // <--- FITUR RESET DINONAKTIFKAN

// --- Library yang Dibutuhkan ---
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ESP8266httpUpdate.h>  // Library baru untuk HTTP Update
#include <ArduinoJson.h>        // FIX: Tambahkan kembali library ArduinoJson
#include <time.h>               // Library untuk manajemen waktu
#include <LittleFS.h>           // Library untuk sistem file
#include <EEPROM.h>
#include <AceButton.h>
using namespace ace_button;

// =================================================================
// --- KONFIGURASI (GANTI BAGIAN INI SESUAI KEBUTUHAN ANDA) ---
// =================================================================

// --- Konfigurasi Wi-Fi ---
const char* ssid = "Pamsimas Tirto Argo";  // Ganti dengan SSID Wi-Fi Anda
const char* pass = "p@msim45";             // Ganti dengan Password Wi-Fi Anda

// --- Konfigurasi Server API ---
const char* server_domain = "pamsimas.selur.my.id"; // PERBAIKAN: Domain server yang benar
const int server_port = 443; // Port standar untuk HTTPS
const char* api_key = "P4mS1m4s-T1rt0-Arg0-2025";                      // API Key harus SAMA PERSIS dengan yang di PHP
const char* api_log_endpoint = "/public/api/log";                  // Sesuaikan dengan struktur URL di server live
const char* api_status_endpoint = "/public/api/status";            // Sesuaikan dengan struktur URL di server live
const char* api_offline_log_endpoint = "/public/api/log-offline";  // Sesuaikan dengan struktur URL di server live

// Fingerprint SHA1 dari sertifikat SSL/TLS server Anda.
// Anda bisa mendapatkannya dengan membuka https://pamsimas.selur.my.id/ di browser, klik ikon gembok, lihat detail sertifikat.
const char* fingerprint = "XX XX XX XX XX XX XX XX XX XX XX XX XX XX XX XX XX XX XX XX"; // GANTI DENGAN FINGERPRINT ASLI SERVER ANDA

// --- Konfigurasi Perangkat ---
char deviceMacAddress[18];                    // Akan diisi secara otomatis (17 karakter + null terminator)
const char* deviceName = "ESP8266-Pamsimas";  // Nama default perangkat

// --- Konfigurasi Sensor & Aktuator ---
const int TRIGPIN = D6;
const int ECHOPIN = D5;
const int wifiLed = D7;
const int BuzzerPin = D8;
const int RelayPin = D0;
const int ButtonPin1 = D3;  // Mode AUTO/MANUAL
const int ButtonPin2 = D4;  // ON/OFF Manual

// --- Struct untuk Mengelompokkan Konfigurasi ---
struct DeviceConfig {
  int on_duration_minutes;
  int off_duration_minutes;
  int full_tank_distance;
  int empty_tank_distance;
  int trigger_percentage;
};

// --- Alamat EEPROM untuk menyimpan durasi ---
#define EEPROM_ADDR_ON_DURATION 0     // long (4 bytes)
#define EEPROM_ADDR_OFF_DURATION 4    // long (4 bytes)
#define EEPROM_ADDR_FULL_DIST 8       // int (4 bytes)
#define EEPROM_ADDR_TRIGGER_PER 12    // int (4 bytes)
#define EEPROM_ADDR_MODE 16           // byte (1 byte) - Saat ini tidak digunakan
#define EEPROM_ADDR_DEVICE_CONFIG 18  // Alamat untuk struct DeviceConfig
#define EEPROM_ADDR_IS_REGISTERED 40  // Alamat untuk status pendaftaran (1 byte)
#define EEPROM_SIZE 64                // Perbesar ukuran EEPROM untuk mengakomodasi struct
// =================================================================
// --- VARIABEL GLOBAL (JANGAN DIUBAH KECUALI ANDA TAHU APA YANG DILAKUKAN) ---
// =================================================================

// Variabel Sensor
float currentDistance = 0.0;  // Menyimpan jarak terakhir yang valid
int waterLevelPer;

// Variabel untuk Smoothing (Moving Average)
#define SMOOTHING_WINDOW_SIZE 10  // Jumlah pembacaan yang akan dirata-ratakan
float distanceReadingsBuffer[SMOOTHING_WINDOW_SIZE];
int bufferIndex = 0;
bool bufferFilled = false;  // Menandakan apakah buffer sudah terisi penuh setidaknya sekali

// Variabel Konfigurasi yang Dikelompokkan
DeviceConfig config;

// Variabel Status & Kontrol (State)
bool relayStatus = false;      // Status relay terkini (dibaca dari pin)
bool modeFlag = true;          // true = AUTO, false = MANUAL/TIMED
char currMode[8] = "AUTO";     // Cukup untuk "MANUAL" atau "TIMED"
bool isRegistered = false;     // Status pendaftaran perangkat
bool versionReported = false;  // Flag untuk menandai apakah versi sudah dilaporkan

// Variabel untuk Buzzer Non-Blocking
bool buzzerActive = false;
unsigned long buzzerStartTime = 0;
unsigned long buzzerDuration = 0;

// Variabel untuk Waktu NTP
bool timeSynchronized = false;  // Flag untuk menandai apakah waktu sudah sinkron
long timeZone = 7 * 3600;       // Zona waktu dalam detik (UTC+7 untuk WIB)

bool isCoolingDown = false;           // Flag untuk menandai masa tenang setelah safety-off
bool wasConnected = true;             // Flag untuk mendeteksi perubahan status koneksi
unsigned long coolDownStartTime = 0;  // Waktu dimulainya masa tenang
bool isResumingFill = false;          // Flag untuk menandai jika harus melanjutkan pengisian setelah istirahat

// Variabel untuk Timing
unsigned long lastDataSendTime = 0;
unsigned long lastReconnectAttempt = 0;  // Untuk reconnect non-blocking
unsigned long lastOfflineLogSync = 0;    // Untuk sinkronisasi log offline
unsigned long lastStatusFetchTime = 0;
unsigned long pumpStartTime = 0;
long pumpOnDuration = 5 * 60 * 1000;    // Default 5 menit (dalam milidetik)
long pumpOffDuration = 15 * 60 * 1000;  // Default 15 menit (dalam milidetik)
bool pumpTimedState = false;            // Status pompa untuk mode TIMED

// PERBAIKAN: Tambahkan variabel untuk mendeteksi kegagalan koneksi ke server
int serverConnectionFailures = 0;
const int MAX_SERVER_FAILURES = 5; // Beralih ke AUTO setelah 5 kali gagal berturut-turut

const long dataSendInterval = 500;        // Kirim data sensor setiap 0.5 detik
const long STATUS_FETCH_NORMAL = 2000;    // Interval normal: 2 detik
const long STATUS_FETCH_MAX = 60000;      // Interval maksimum saat error: 60 detik
long currentStatusFetchInterval = STATUS_FETCH_NORMAL; // Interval yang bisa berubah-ubah

const long reconnectInterval = 10000;     // Coba sambung ulang setiap 10 detik
const long offlineSyncInterval = 300000;  // Coba kirim log offline setiap 5 menit

// Variabel Tombol
ButtonConfig config1;
AceButton button1(&config1);
ButtonConfig config2;
AceButton button2(&config2);

void button1Handler(AceButton*, uint8_t, uint8_t);  // Forward declaration
void button2Handler(AceButton*, uint8_t, uint8_t);  // Forward declaration

// --- DEKLARASI FUNGSI (PROTOTYPES) ---
// Ini memberitahu kompiler tentang semua fungsi yang ada sebelum digunakan.
bool loadConfigFromEEPROM();
void connectToWiFi(bool isInitialBoot);
float calculateMovingAverage();
void syncTime();
void fetchQuickStatus(); // <<< FUNGSI BARU YANG RINGAN
void fetchControlStatus();
void logDataOffline(float percentage, float cm, int rssi);
void logPumpStatusOffline(bool status);
void logEventOffline(const char* eventName);
void sendSensorData(float percentage, float cm);
void sendControlCommand(const char* action, const char* value);
void sendOfflineLogs();
void measureAndSendData();
void runUniversalPumpLogic();
void controlBuzzer(int duration);
void sendCommandAndBuzz(const char* action, const char* value, int buzzerDuration);
void handlePumpStateChange();

// =================================================================
// --- FUNGSI-FUNGSI UTAMA ---
// =================================================================

void setup() {
  Serial.begin(115200);
  delay(500);  // Beri jeda singkat agar Serial Monitor stabil

  Serial.println("DEVICE: Perangkat dinyalakan (Booting up)...");
  Serial.println("\n\n===================================");
  Serial.println("==      Mulai Aplikasi IoT Pompa     ==");
  Serial.println("===================================");
  EEPROM.begin(EEPROM_SIZE);

  // Inisialisasi LittleFS
  if (!LittleFS.begin()) {
    Serial.println("Gagal menginisialisasi LittleFS! Log offline tidak akan berfungsi.");
  }

  loadConfigFromEEPROM();

  Serial.println("INFO: Mengatur mode awal ke 'AUTO' sebagai standar keamanan.");
  strcpy(currMode, "AUTO");
  modeFlag = true;

  strncpy(deviceMacAddress, WiFi.macAddress().c_str(), sizeof(deviceMacAddress));
  Serial.printf("ID Perangkat (MAC Address): %s\n", deviceMacAddress);

  pinMode(ECHOPIN, INPUT);
  pinMode(TRIGPIN, OUTPUT);
  pinMode(wifiLed, OUTPUT);
  pinMode(RelayPin, OUTPUT);
  pinMode(BuzzerPin, OUTPUT);
  pinMode(ButtonPin1, INPUT_PULLUP);
  pinMode(ButtonPin2, INPUT_PULLUP);

  digitalWrite(wifiLed, HIGH);
  digitalWrite(RelayPin, LOW);
  digitalWrite(BuzzerPin, LOW);

  config1.setEventHandler(button1Handler);
  config2.setEventHandler(button2Handler);
  button1.init(ButtonPin1);
  button2.init(ButtonPin2);

  connectToWiFi(true);

  if (WiFi.status() == WL_CONNECTED) {
    syncTime();
    Serial.println("Mengirim laporan 'boot' ke server...");
    // PERBAIKAN: Kirim status mode 'AUTO' ke server saat boot untuk memastikan sinkronisasi.
    sendControlCommand("set_mode", "AUTO");
    sendControlCommand("report_event", "boot");
    Serial.println("Mengirim ping deteksi awal ke server (persen=0, cm=0)...");
    // PERBAIKAN: Sesuaikan panggilan fungsi dengan dua argumen (persen dan cm).
    sendSensorData(0.0, 0.0);
    Serial.println("Melakukan sinkronisasi status awal dengan server...");
    fetchControlStatus();
    sendOfflineLogs();
  }

  wasConnected = (WiFi.status() == WL_CONNECTED);
}

void loop() {
  unsigned long currentMillis = millis();

  // --- TAHAP 1: MANAJEMEN KONEKSI DAN SINKRONISASI ---
  if (WiFi.status() != WL_CONNECTED) {
    digitalWrite(wifiLed, HIGH);
    if (wasConnected) {
      Serial.println("NETWORK: Koneksi terputus. Beralih ke mode AUTO sebagai fallback.");
      strcpy(currMode, "AUTO");
      logEventOffline("network_lost");
      modeFlag = true;
      wasConnected = false;
    }
    if (currentMillis - lastReconnectAttempt >= reconnectInterval) {
      lastReconnectAttempt = currentMillis;
      connectToWiFi(false);
    }
  } else { // Jika WiFi TERHUBUNG
    if (!wasConnected) {
      // Blok ini hanya berjalan satu kali tepat setelah koneksi berhasil pulih.
      wasConnected = true;
      digitalWrite(wifiLed, LOW);
      Serial.println("NETWORK: Koneksi pulih. Akan menyinkronkan status dengan server.");
      
      // SINKRONISASI SETELAH KONEKSI PULIH
      syncTime(); // 1. Sinkronkan waktu terlebih dahulu.
      sendControlCommand("report_event", "network_recovered"); // 2. Laporkan bahwa koneksi telah pulih.
      // 3. PERBAIKAN LOGIKA: Laporkan mode 'AUTO' yang menjadi fallback ke server.
      sendControlCommand("set_mode", "AUTO"); 
      sendOfflineLogs(); // 4. Kirim semua log yang tersimpan saat offline.
      fetchControlStatus(); // 5. Ambil konfigurasi penuh dari server (mode sekarang sudah sinkron).
    }
  }

  // --- TAHAP 2: LOGIKA OPERASIONAL INTI ---
  // Logika ini harus tetap berjalan baik online maupun offline, selama perangkat sudah terdaftar.
  if (!isRegistered) {
    // Jika belum terdaftar, hanya cek status ke server jika sedang online.
    if (wasConnected && (currentMillis - lastStatusFetchTime >= currentStatusFetchInterval)) {
      lastStatusFetchTime = currentMillis;
      fetchQuickStatus();
    }
    // Hentikan eksekusi di sini jika belum terdaftar.
    return; 
  }

  // --- BLOK OPERASIONAL PENUH (JIKA SUDAH TERDAFTAR) ---
  // Kode di bawah ini akan selalu berjalan, tidak peduli status koneksi.
  button1.check();
  if (strcmp(currMode, "MANUAL") == 0) {
    button2.check();
  }

  if (currentMillis - lastDataSendTime >= dataSendInterval) {
    lastDataSendTime = currentMillis;
    measureAndSendData();
  }

  runUniversalPumpLogic();

  // Hanya coba ambil status dari server jika sedang online.
  if (wasConnected) {
    if (currentMillis - lastStatusFetchTime >= currentStatusFetchInterval) {
      lastStatusFetchTime = currentMillis;
      fetchQuickStatus();
    }
  }

  // PERBAIKAN: Logika fallback baru jika server tidak dapat dihubungi
  if (serverConnectionFailures >= MAX_SERVER_FAILURES && strcmp(currMode, "AUTO") != 0) {
    Serial.println("SERVER: Tidak dapat dihubungi berulang kali. Memaksa beralih ke mode AUTO.");
    strcpy(currMode, "AUTO");
    modeFlag = true;
    logEventOffline("server_unreachable_fallback");
    serverConnectionFailures = 0; // Reset setelah beralih
  }

  // Logika buzzer non-blocking
  if (buzzerActive && (currentMillis - buzzerStartTime >= buzzerDuration)) {
    digitalWrite(BuzzerPin, LOW);
    buzzerActive = false;
  }
}

// =================================================================
// --- FUNGSI-FUNGSI PENDUKUNG ---
// =================================================================

bool loadConfigFromEEPROM() {
  Serial.println("Mencoba memuat konfigurasi dari EEPROM...");
  EEPROM.get(EEPROM_ADDR_DEVICE_CONFIG, config);

  if (config.on_duration_minutes > 0 && config.on_duration_minutes < 1440) {
    pumpOnDuration = (long)config.on_duration_minutes * 60 * 1000;
  } else {
    pumpOnDuration = 5 * 60 * 1000;
  }
  if (config.off_duration_minutes > 0 && config.off_duration_minutes < 1440) {
    pumpOffDuration = (long)config.off_duration_minutes * 60 * 1000;
  } else {
    pumpOffDuration = 15 * 60 * 1000;
  }

  Serial.printf("Durasi dari EEPROM: Nyala=%ld ms, Mati=%ld ms\n", pumpOnDuration, pumpOffDuration);

  if (EEPROM.read(EEPROM_ADDR_IS_REGISTERED) == 1) {
    isRegistered = true;
    Serial.println("Status Pendaftaran dari EEPROM: Perangkat sudah terdaftar sebelumnya.");
  } else {
    isRegistered = false;
    Serial.println("Status Pendaftaran dari EEPROM: Perangkat belum terdaftar.");
  }
  return true;
}

void connectToWiFi(bool isInitialBoot) {
  if (isInitialBoot) {
    Serial.println("\n[BOOT] Mencoba menghubungkan ke WiFi untuk pertama kali...");
  } else {
    Serial.println("\n[RECONNECT] Mencoba menyambungkan ulang ke WiFi...");
  }

  WiFi.disconnect();
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, pass);

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi Terhubung!");
    Serial.print("Alamat IP: ");
    Serial.println(WiFi.localIP());
    digitalWrite(wifiLed, LOW);
  } else {
    Serial.println("\nGagal terhubung ke WiFi.");
  }
}

float calculateMovingAverage() {
  float sum = 0;
  int count = 0;
  if (bufferFilled) {
    count = SMOOTHING_WINDOW_SIZE;
  } else {
    count = bufferIndex;
  }

  if (count == 0) return 0;

  for (int i = 0; i < count; i++) {
    sum += distanceReadingsBuffer[i];
  }
  return sum / count;
}

void syncTime() {
  if (WiFi.status() != WL_CONNECTED) return;

  Serial.println("Menyinkronkan waktu dari server NTP...");
  configTime(timeZone, 0, "pool.ntp.org", "time.nist.gov");

  time_t now = time(nullptr);
  while (now < 1510644967) {
    delay(500);
    now = time(nullptr);
  }
  timeSynchronized = true;
  Serial.printf("Waktu berhasil disinkronkan: %s", ctime(&now));
}

/**
 * Fungsi ringan untuk mengambil status singkat (mode & status pompa) dari server.
 * Juga memeriksa apakah ada perintah untuk mengambil konfigurasi penuh.
 * Menerapkan strategi backoff jika gagal.
 */
void fetchQuickStatus() {
  if (WiFi.status() != WL_CONNECTED) return;

  WiFiClient client;
  WiFiClientSecure secureClient; // Gunakan secureClient untuk HTTPS
  HTTPClient http; 
  char serverPath[128];
  snprintf(serverPath, sizeof(serverPath), "https://%s%s?mac=%s", server_domain, api_status_endpoint, deviceMacAddress); // Gunakan https dan server_domain

  if (http.begin(secureClient, server_domain, server_port, serverPath, true, fingerprint)) { // Gunakan secureClient dan fingerprint
    http.addHeader("X-API-Key", api_key);
    int httpResponseCode = http.GET();

    if (httpResponseCode == 200) {
      StaticJsonDocument<512> doc;
      deserializeJson(doc, http.getStream());

      // Cek jika ada perintah untuk mengambil konfigurasi penuh
      if (doc.containsKey("config_update_command") && doc["config_update_command"] == 1) {
        Serial.println("PERINTAH: Menerima perintah untuk mengambil konfigurasi penuh dari server...");
        fetchControlStatus(); // Panggil fungsi lengkap
        sendControlCommand("reset_config_update", "0");
        http.end();
        return; 
      }

      // PERBAIKAN: Logika baru untuk mengubah mode hanya jika ada perintah
      if (doc.containsKey("mode_update_command") && doc["mode_update_command"] == 1) {
        Serial.println("PERINTAH: Menerima perintah perubahan mode dari server.");
        const char* serverMode = doc["control_mode"];
        if (serverMode) {
          strncpy(currMode, serverMode, sizeof(currMode) - 1);
          modeFlag = (strcmp(currMode, "AUTO") == 0);
          Serial.printf("  -> Mode diubah menjadi: %s\n", currMode);
          // Kirim konfirmasi kembali ke server untuk mereset bendera
          sendControlCommand("reset_mode_update", "0");
        }
      }
      // Selalu sinkronkan status pompa jika dalam mode selain AUTO
      if (strcmp(currMode, "AUTO") != 0) {
        const char* serverStatus = doc["status"];
        if (serverStatus) {
          relayStatus = (strcmp(serverStatus, "ON") == 0);
        }
      }

      // Jika berhasil, kembalikan interval ke normal
      if (currentStatusFetchInterval != STATUS_FETCH_NORMAL) {
        Serial.println("NETWORK: Komunikasi dengan server pulih. Interval status kembali normal.");
        currentStatusFetchInterval = STATUS_FETCH_NORMAL;
      }
      serverConnectionFailures = 0; // Reset penghitung kegagalan jika berhasil

    } else {
      Serial.printf("KESALAHAN: [QuickStatus] GET... gagal, kode error: %d\n", httpResponseCode);
      currentStatusFetchInterval = min(currentStatusFetchInterval * 2, STATUS_FETCH_MAX);
      Serial.printf("NETWORK: Gagal menghubungi server. Interval status diperpanjang menjadi %ld ms.\n", currentStatusFetchInterval);
      serverConnectionFailures++; // Tambah penghitung kegagalan
    }
    http.end();
  } else {
    Serial.printf("KESALAHAN: [QuickStatus] Tidak bisa terhubung ke server.\n");
    currentStatusFetchInterval = min(currentStatusFetchInterval * 2, STATUS_FETCH_MAX);
    Serial.printf("NETWORK: Gagal menghubungi server. Interval status diperpanjang menjadi %ld ms.\n", currentStatusFetchInterval);
    serverConnectionFailures++; // Tambah penghitung kegagalan
  }
}

void fetchControlStatus() {
  if (WiFi.status() != WL_CONNECTED) return;

  WiFiClient client;
  HTTPClient http;
  char serverPath[128];
  snprintf(serverPath, sizeof(serverPath), "http://%s%s?mac=%s", server_ip, api_status_endpoint, deviceMacAddress);

  if (http.begin(client, serverPath)) {
    Serial.printf("Fetching FULL CONFIG from: %s\n", serverPath);
    http.addHeader("X-API-Key", api_key);

    int httpResponseCode = http.GET();
    if (httpResponseCode == 200) {
      StaticJsonDocument<512> doc;
      DeserializationError error = deserializeJson(doc, http.getStream());

      if (error) {
        Serial.println("--- Respons Gagal Parsing ---");
        Serial.println(http.getString());
        Serial.println("---------------------------");
        Serial.print("deserializeJson() gagal: ");
        Serial.println(error.c_str());
        http.end();
        return;
      }

      if (doc.containsKey("status") && doc["status"] == "unregistered") {
        Serial.println("STATUS: Perangkat belum terdaftar. Menunggu pendaftaran di server...");
        isRegistered = false;
        digitalWrite(wifiLed, !digitalRead(wifiLed));
        http.end();
        return;
      }
      
      // FIX: Gunakan nama kunci yang benar ('control_mode') dan tambahkan pemeriksaan NULL.
      const char* serverMode = doc["control_mode"];
      const char* serverStatus = doc["status"];

      // Pemeriksaan keamanan untuk mencegah crash jika kunci tidak ada dalam JSON.
      if (serverMode == nullptr || serverStatus == nullptr) {
        Serial.println("KESALAHAN: Respons JSON dari server tidak lengkap (mode/status tidak ada).");
        http.end();
        return;
      }

      Serial.println("RESPONS SERVER: Diterima data dari server.");
      Serial.printf("  - Mode Kontrol: %s\n", serverMode ? serverMode : "NULL");
      Serial.printf("  - Status Pompa: %s\n", serverStatus ? serverStatus : "NULL");

      strncpy(currMode, serverMode, sizeof(currMode) - 1);
      modeFlag = (strcmp(currMode, "AUTO") == 0);

      if (strcmp(currMode, "AUTO") != 0) {
        relayStatus = (strcmp(serverStatus, "ON") == 0);
      }

      if (!isRegistered) {
        Serial.println("Pendaftaran berhasil! Perangkat sekarang aktif.");
        EEPROM.write(EEPROM_ADDR_IS_REGISTERED, 1);
        EEPROM.commit();
        Serial.println("Status 'Terdaftar' telah disimpan ke EEPROM.");
      }
      isRegistered = true;
      digitalWrite(wifiLed, LOW);

      Serial.println("\n--- PROSES PENGECEKAN KONFIGURASI ---");

      DeviceConfig newConfig;
      newConfig.on_duration_minutes = doc["on_duration"];
      newConfig.off_duration_minutes = doc["off_duration"].as<int>();
      newConfig.full_tank_distance = doc["full_tank_distance"];
      newConfig.trigger_percentage = doc["trigger_percentage"];
      newConfig.empty_tank_distance = doc["empty_tank_distance"];

      Serial.println("  [KONFIGURASI DARI SERVER]");
      Serial.printf("    - Durasi Nyala: %d menit\n", newConfig.on_duration_minutes);
      Serial.printf("    - Durasi Istirahat: %d menit\n", newConfig.off_duration_minutes);
      Serial.printf("    - Jarak Penuh: %d cm\n", newConfig.full_tank_distance);
      Serial.printf("    - Jarak Kosong: %d cm\n", newConfig.empty_tank_distance);
      Serial.printf("    - Pemicu Pompa: %d %%\n", newConfig.trigger_percentage);

      Serial.println("\n  [KONFIGURASI LOKAL SAAT INI]");
      Serial.printf("    - Durasi Nyala: %d menit\n", config.on_duration_minutes);
      Serial.printf("    - Durasi Istirahat: %d menit\n", config.off_duration_minutes);
      Serial.printf("    - Jarak Penuh: %d cm\n", config.full_tank_distance);
      Serial.printf("    - Jarak Kosong: %d cm\n", config.empty_tank_distance);
      Serial.printf("    - Pemicu Pompa: %d %%\n", config.trigger_percentage);

      bool configHasChanged = (memcmp(&newConfig, &config, sizeof(DeviceConfig)) != 0);

      Serial.println("\n  [HASIL PERBANDINGAN]");
      Serial.println("\n--- KESIMPULAN ---");
      if (configHasChanged) {
        Serial.println("-> Ditemukan perubahan nilai konfigurasi yang signifikan.");
        Serial.println("-> Memperbarui variabel dan menyimpan ke EEPROM...");
        memcpy(&config, &newConfig, sizeof(DeviceConfig));
        pumpOnDuration = (long)config.on_duration_minutes * 60 * 1000;
        pumpOffDuration = (long)config.off_duration_minutes * 60 * 1000;
        EEPROM.put(EEPROM_ADDR_DEVICE_CONFIG, config);
        if (EEPROM.commit()) {
          Serial.println("Penyimpanan ke EEPROM selesai.");
        }
      } else {
        Serial.println("-> Tidak ada perubahan nilai konfigurasi yang signifikan.");
        Serial.println("-> Penulisan ke EEPROM dilewati untuk menjaga umur memori.");
      }
      Serial.println("-------------------------------------\n");

      if (doc.containsKey("restart_command") && doc["restart_command"] == 1) {
        Serial.println("PERINTAH: Menerima perintah restart dari server. Merestart dalam 3 detik...");
        sendControlCommand("reset_restart", "0");
        delay(3000);
        ESP.restart();
      }

      if (!versionReported) {
        const char* currentVersion = __DATE__ " " __TIME__;
        sendControlCommand("report_version", currentVersion);
        versionReported = true;
      }

    } else {
      Serial.printf("KESALAHAN: [HTTP] GET... gagal, kode error: %d\n", httpResponseCode);
    }
    http.end();
  } else {
    Serial.printf("KESALAHAN: [HTTP] Tidak bisa terhubung ke server.\n");
  }
}

void logDataOffline(float percentage, float cm, int rssi) {
  if (!timeSynchronized) {
    Serial.println("Waktu belum sinkron, tidak bisa mencatat log offline dengan timestamp.");
    return;
  }
  time_t now = time(nullptr);

  File dataFile = LittleFS.open("/sensor_log.txt", "a");
  if (!dataFile) {
    Serial.println("Gagal membuka file log untuk ditulis.");
    return;
  }
  dataFile.printf("%lu,%.0f,%.2f,%d\n", now, percentage, cm, rssi);
  dataFile.close();
}

void logPumpStatusOffline(bool status) {
  if (!timeSynchronized) {
    Serial.println("Waktu belum sinkron, tidak bisa mencatat log offline dengan timestamp.");
    return;
  }
  time_t now = time(nullptr);

  File dataFile = LittleFS.open("/pump_log.txt", "a");
  if (!dataFile) {
    Serial.println("Gagal membuka file log status pompa.");
    return;
  }
  dataFile.printf("%lu,%d\n", now, status ? 1 : 0);
  dataFile.close();
}

void logEventOffline(const char* eventName) {
  if (!timeSynchronized) {
    Serial.println("Waktu belum sinkron, tidak bisa mencatat log event offline.");
    return;
  }
  time_t now = time(nullptr);

  File dataFile = LittleFS.open("/event_log.txt", "a");
  if (!dataFile) {
    Serial.println("Gagal membuka file log event.");
    return;
  }
  dataFile.printf("%lu,%s\n", now, eventName);
  dataFile.close();
}

void sendSensorData(float percentage, float cm) {
  if (WiFi.status() != WL_CONNECTED) {
    // PERBAIKAN: Pesan log yang lebih bersih saat WiFi putus
    Serial.println("WIFI: Koneksi terputus. Menyimpan data sensor ke log offline.");
    logDataOffline(percentage, cm, WiFi.RSSI());
    return;
  }

  WiFiClient client;
  HTTPClient http;
  char serverPath[128];
  snprintf(serverPath, sizeof(serverPath), "http://%s%s", server_ip, api_log_endpoint);

  if (http.begin(client, serverPath)) {
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-Key", api_key);

    StaticJsonDocument<200> doc;
    doc["mac_address"] = deviceMacAddress;
    // PERBAIKAN: Ganti nama kunci agar sesuai dengan yang diharapkan server.
    doc["water_percentage"] = percentage;
    doc["water_level_cm"] = cm; // PERBAIKAN: Tambahkan nilai CM
    int currentRssi = WiFi.RSSI();
    doc["rssi"] = currentRssi;

    String requestBody;
    serializeJson(doc, requestBody);
    int httpResponseCode = http.POST(requestBody);

    if (httpResponseCode < 200 || httpResponseCode >= 300) {
      Serial.printf("SERVER: Gagal mengirim data sensor (HTTP Code: %d), menyimpan ke log offline.\n", httpResponseCode);
      logDataOffline(percentage, cm, currentRssi);
    } else {
      Serial.println("SERVER: Data sensor berhasil dikirim.");
      // Jika pengiriman data berhasil, berarti koneksi ke server baik-baik saja.
      // Reset penghitung kegagalan.
      serverConnectionFailures = 0;
    }
    http.end();
  }
}

void sendControlCommand(const char* action, const char* value) {
  if (WiFi.status() != WL_CONNECTED) {
    if (strcmp(action, "set_status") == 0) {
      if (timeSynchronized)
        logPumpStatusOffline(strcmp(value, "ON") == 0);
    } else if (strcmp(action, "report_event") == 0) {
      logEventOffline(value);
    }
    return;
  }

  WiFiClient client;
  HTTPClient http;
  char serverPath[128];
  snprintf(serverPath, sizeof(serverPath), "http://%s/wlc/public/api/update", server_ip);

  if (http.begin(client, serverPath)) {
    http.addHeader("Content-Type", "application/json");
    StaticJsonDocument<200> doc;
    doc["mac"] = deviceMacAddress;
    doc["action"] = action;
    doc["value"] = value;

    String requestBody;
    serializeJson(doc, requestBody);
    int httpResponseCode = http.POST(requestBody);
    if (httpResponseCode > 0 && httpResponseCode < 400) {
      Serial.printf("[HTTP] Perintah '%s' -> '%s' dikirim, kode: %d\n", action, value, httpResponseCode);
    } else {
      Serial.printf("[HTTP] Gagal mengirim perintah '%s'\n", action);
      if (strcmp(action, "set_status") == 0) {
        logPumpStatusOffline(strcmp(value, "ON") == 0);
      }
    }
    http.end();
  }
}

void sendOfflineLogs() {
  bool hasSensorLogs = LittleFS.exists("/sensor_log.txt") && LittleFS.open("/sensor_log.txt", "r").size() > 0;
  bool hasPumpLogs = LittleFS.exists("/pump_log.txt") && LittleFS.open("/pump_log.txt", "r").size() > 0;
  bool hasEventLogs = LittleFS.exists("/event_log.txt") && LittleFS.open("/event_log.txt", "r").size() > 0;

  if (!hasSensorLogs && !hasPumpLogs && !hasEventLogs) {
    return;
  }

  Serial.println("Membaca log offline untuk dikirim ke server...");

  WiFiClient client;
  HTTPClient http;
  char serverPath[128];
  snprintf(serverPath, sizeof(serverPath), "http://%s%s", server_ip, api_offline_log_endpoint);

  // PERBAIKAN: Gunakan metode streaming untuk mengirim data besar tanpa menghabiskan memori.
  if (http.begin(client, serverPath)) {
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-Key", api_key);

    // Mulai mengirim request dengan payload chunked
    int httpResponseCode = http.sendRequest("POST", (uint8_t*)"", 0);
    if (httpResponseCode != HTTP_CODE_CONTINUE) {
        http.end();
        Serial.printf("[HTTP] Gagal memulai streaming, kode: %d\n", httpResponseCode);
        return;
    }

    // Buat JSON secara bertahap dan kirim langsung ke client
    client.print("{\"mac_address\":\"");
    client.print(deviceMacAddress);
    client.print("\"");

    if (hasSensorLogs) {
        client.print(",\"sensor_logs\":[");
        File sensorFile = LittleFS.open("/sensor_log.txt", "r");
        bool first = true;
        while(sensorFile.available()){
            String line = sensorFile.readStringUntil('\n');
            line.trim();
            if(line.length() > 0){
                if(!first) client.print(",");
                client.print("[");
                client.print(line);
                client.print("]");
                first = false;
            }
        }
        client.print("]");
        sensorFile.close();
    }

    if (hasPumpLogs) {
        client.print(",\"pump_logs\":[");
        File pumpFile = LittleFS.open("/pump_log.txt", "r");
        bool first = true;
        while(pumpFile.available()){
            String line = pumpFile.readStringUntil('\n');
            line.trim();
            if(line.length() > 0){
                if(!first) client.print(",");
                client.print("[");
                client.print(line);
                client.print("]");
                first = false;
            }
        }
        client.print("]");
        pumpFile.close();
    }

    if (hasEventLogs) {
        client.print(",\"event_logs\":[");
        File eventFile = LittleFS.open("/event_log.txt", "r");
        bool first = true;
        while(eventFile.available()){
            String line = eventFile.readStringUntil('\n');
            line.trim();
            if(line.length() > 0){
                if(!first) client.print(",");
                client.print("[\"");
                client.print(line);
                client.print("\"]");
                first = false;
            }
        }
        client.print("]");
        eventFile.close();
    }

    client.print("}");
    
    // PERBAIKAN KRUSIAL: Cara yang benar untuk menyelesaikan request streaming.
    // Hentikan penulisan ke client, yang akan mengirimkan chunk terakhir.
    client.stop();

    // Sekarang, dapatkan kode status HTTP yang sebenarnya dari server.
    httpResponseCode = http.getStream().read(); // Ini akan menunggu respons dan mendapatkan kodenya.

    if (httpResponseCode == 200) {
      Serial.println("Log offline berhasil dikirim. Menghapus file log lokal.");
      if (hasSensorLogs) LittleFS.remove("/sensor_log.txt");
      if (hasPumpLogs) LittleFS.remove("/pump_log.txt");
      if (hasEventLogs) LittleFS.remove("/event_log.txt");
    } else {
      Serial.printf("[HTTP] Gagal mengirim log offline, kode: %d\n", httpResponseCode);
    }
    http.end();
  }
}

void measureAndSendData() {
  const int NUM_READINGS = 5;
  float totalDistance = 0;
  int validReadings = 0;

  for (int i = 0; i < NUM_READINGS; i++) {
    digitalWrite(TRIGPIN, LOW);
    delayMicroseconds(2);
    digitalWrite(TRIGPIN, HIGH);
    delayMicroseconds(10);
    digitalWrite(TRIGPIN, LOW);

    float duration = pulseIn(ECHOPIN, HIGH, 35000);
    float singleReading = ((duration / 2) * 0.343) / 10;

    if (singleReading > 0) {
      totalDistance += singleReading;
      validReadings++;
    }
    delay(30);
  }

  float oversampledDistance;
  if (validReadings > 0) {
    oversampledDistance = totalDistance / validReadings;
  } else {
    Serial.println("Gagal mengambil semua bacaan sensor, menggunakan nilai terakhir.");
    oversampledDistance = currentDistance;
  }

  distanceReadingsBuffer[bufferIndex] = oversampledDistance;
  bufferIndex++;
  if (bufferIndex >= SMOOTHING_WINDOW_SIZE) {
    bufferIndex = 0;
    bufferFilled = true;
  }

  currentDistance = calculateMovingAverage();

  waterLevelPer = map((int)currentDistance, config.empty_tank_distance, config.full_tank_distance, 0, 100);
  if (waterLevelPer < 0) waterLevelPer = 0;
  if (waterLevelPer > 100) waterLevelPer = 100;

  Serial.printf("SENSOR: Jarak Oversampled: %.2f cm, Jarak Final: %.2f cm, Level: %d %%, RSSI: %d dBm\n", oversampledDistance, currentDistance, waterLevelPer, (WiFi.status() == WL_CONNECTED) ? WiFi.RSSI() : 0);

  // PERBAIKAN FINAL: Kirim nilai persentase (waterLevelPer), bukan jarak (currentDistance).
  sendSensorData(waterLevelPer, currentDistance);
}

void runUniversalPumpLogic() {
  if (relayStatus && (millis() - pumpStartTime >= pumpOnDuration)) {
    Serial.println("LOGIKA: [KEAMANAN] Durasi nyala maksimum tercapai. Mematikan pompa.");
    sendControlCommand("set_status", "OFF");
    relayStatus = false;
    isCoolingDown = true;
    coolDownStartTime = millis();
    if (waterLevelPer < 98) {
      isResumingFill = true;
      Serial.println("LOGIKA: [KEAMANAN] Menandai untuk melanjutkan pengisian setelah istirahat.");
    }
    controlBuzzer(1000);
    return;
  }

  if (isCoolingDown) {
    if (millis() - coolDownStartTime >= pumpOffDuration) {
      Serial.println("LOGIKA: Masa tenang selesai. Logika AUTO kembali aktif.");
      isCoolingDown = false;
    } else {
      return;
    }
  }

  if (strcmp(currMode, "AUTO") == 0) {
    if (isResumingFill && !relayStatus) {
      Serial.println("LOGIKA: [AUTO] Melanjutkan pengisian setelah istirahat...");
      relayStatus = true;
      sendControlCommand("set_status", "ON");
      isResumingFill = false;
      controlBuzzer(200);
    }
    else if (waterLevelPer < config.trigger_percentage && !relayStatus) {
      Serial.println("LOGIKA: [AUTO] Level air rendah, menyalakan pompa.");
      relayStatus = true;
      sendControlCommand("set_status", "ON");
      controlBuzzer(500);
    } else if (waterLevelPer >= 98 && relayStatus) {
      Serial.println("AUTO: Tangki penuh, mematikan pompa.");
      relayStatus = false;
      isCoolingDown = true;
      coolDownStartTime = millis();
      sendControlCommand("set_status", "OFF");
      controlBuzzer(500);
      isResumingFill = false;
    }
  } else if (strcmp(currMode, "TIMED") == 0) {
    if (relayStatus) {
      if (millis() - pumpStartTime >= pumpOnDuration) {
        Serial.println("LOGIKA: [TIMED] Durasi nyala selesai, mematikan pompa.");
        relayStatus = false;
        sendControlCommand("set_status", "OFF");
      }
    } else {
      if (millis() - pumpStartTime >= pumpOffDuration) {
        Serial.println("LOGIKA: [TIMED] Durasi mati selesai, menyalakan pompa.");
        relayStatus = true;
        sendControlCommand("set_status", "ON");
      }
    }
  }

  handlePumpStateChange();
}

void controlBuzzer(int duration) {
  buzzerDuration = duration;
  buzzerStartTime = millis();
  digitalWrite(BuzzerPin, HIGH);
  buzzerActive = true;
}

void sendCommandAndBuzz(const char* action, const char* value, int buzzerDuration) {
  sendControlCommand(action, value);
  controlBuzzer(buzzerDuration);
}

void handlePumpStateChange() {
  bool currentPinState = digitalRead(RelayPin);

  if (relayStatus && !currentPinState) {
    digitalWrite(RelayPin, HIGH);
    pumpStartTime = millis();
    Serial.println("RELAY: Pin relay diaktifkan (HIGH), timer keamanan dimulai.");
    // DEBUGGING: Tampilkan status saat ini
    Serial.printf("  -> DEBUG: Mode Saat Ini = %s, Status Pompa = ON\n", currMode);
  }
  else if (!relayStatus && currentPinState) {
    digitalWrite(RelayPin, LOW);
    Serial.println("RELAY: Pin relay dinonaktifkan (LOW).");
    // DEBUGGING: Tampilkan status saat ini
    Serial.printf("  -> DEBUG: Mode Saat Ini = %s, Status Pompa = OFF\n", currMode);
  }
}

void button1Handler(AceButton* button, uint8_t eventType, uint8_t buttonState) {
  if (eventType == AceButton::kEventReleased) {
    Serial.println("TOMBOL: Tombol 1 (Mode) ditekan.");
    const char* newModeStr; // Logika baru: Hanya beralih antara AUTO dan MANUAL
    if (strcmp(currMode, "AUTO") == 0) {
      newModeStr = "MANUAL";
    } else {
      newModeStr = "AUTO";
    }
    sendCommandAndBuzz("set_mode", newModeStr, 200);
  }
}

void button2Handler(AceButton* button, uint8_t eventType, uint8_t buttonState) {
  if (eventType == AceButton::kEventReleased) {
    Serial.println("TOMBOL: Tombol 2 (ON/OFF) ditekan.");
    const char* newStatus = relayStatus ? "OFF" : "ON";
    sendCommandAndBuzz("set_status", newStatus, 500);
  }
}
