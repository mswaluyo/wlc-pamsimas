// =================================================================
// == Kode Firmware WLC Pamsimas (Versi HTTP/HTTPS) ==
// =================================================================
// Uncomment baris di bawah ini untuk melakukan factory reset EEPROM pada flash berikutnya.
// #define FORCE_FACTORY_RESET  // <--- FITUR RESET DINONAKTIFKAN

// --- Library yang Dibutuhkan ---
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecure.h>
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

// --- Konfigurasi Server (Cloudflare Tunnel) ---
const char* server_domain = "pamsimas.selur.my.id"; // Domain Anda
const int   server_port   = 443; // HTTPS Port
const char* api_key       = "P4mS1m4s-T1rt0-Arg0-2025"; // Wajib sama dengan di .env Server

// --- Konfigurasi Perangkat ---
char deviceMacAddress[18];                    // MAC Address (Otomatis)
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
  int min_run_time; // Tambahan: Waktu tunda/nyala minimal dalam detik
};

// --- Alamat EEPROM untuk menyimpan durasi ---
#define EEPROM_ADDR_ON_DURATION 0     // long (4 bytes)
#define EEPROM_ADDR_OFF_DURATION 4    // long (4 bytes)
#define EEPROM_ADDR_FULL_DIST 8       // int (4 bytes)
#define EEPROM_ADDR_TRIGGER_PER 12    // int (4 bytes)
#define EEPROM_ADDR_MODE 16           // byte (1 byte) - Saat ini tidak digunakan
#define EEPROM_ADDR_DEVICE_CONFIG 18  // Alamat untuk struct DeviceConfig
#define EEPROM_ADDR_IS_REGISTERED 50  // GESER ALAMAT: Dari 40 ke 50 agar muat untuk struct baru
#define EEPROM_SIZE 512               // Perbesar ukuran EEPROM

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
bool lastRelayStatus = false;  // Status relay sebelumnya untuk deteksi perubahan
bool modeFlag = true;          // true = AUTO, false = MANUAL/TIMED
char currMode[8] = "AUTO";     // Cukup untuk "MANUAL" atau "TIMED"
bool isRegistered = false;     // Status pendaftaran perangkat
bool versionReported = false;  // Flag untuk menandai apakah versi sudah dilaporkan

// --- TAMBAHAN UNTUK DEBOUNCE SENSOR ---
unsigned long sensorDebounceStartTime = 0;
bool isDebouncing = false;
int sensorDebounceDelay = 5; // Default 5 detik (nanti akan diupdate dari server)

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
unsigned long lastStatusFetchTime = 0; // Khusus HTTP: Polling status
unsigned long lastReconnectAttempt = 0;  // Untuk reconnect non-blocking
unsigned long lastOfflineLogSync = 0;    // Untuk sinkronisasi log offline
unsigned long pumpStartTime = 0;
long pumpOnDuration = 5 * 60 * 1000;    // Default 5 menit (dalam milidetik)
long pumpOffDuration = 15 * 60 * 1000;  // Default 15 menit (dalam milidetik)
bool pumpTimedState = false;            // Status pompa untuk mode TIMED

int serverConnectionFailures = 0;
const int MAX_SERVER_FAILURES = 5; // Beralih ke AUTO setelah 5 kali gagal berturut-turut

const long dataSendInterval = 5000;       // Kirim data setiap 5 detik (HTTP lebih berat dari MQTT)
const long statusFetchInterval = 3000;    // Cek status/perintah setiap 3 detik

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
bool loadConfigFromEEPROM();
void connectToWiFi(bool isInitialBoot);
float calculateMovingAverage();
void syncTime();
void logDataOffline(float percentage, float cm, int rssi);
void logPumpStatusOffline(bool status);
void logEventOffline(const char* eventName);
void sendSensorData(float percentage, float cm);
void sendControlCommand(const char* action, const char* value);
void fetchControlStatus();
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

  // Ambil MAC Address
  strncpy(deviceMacAddress, WiFi.macAddress().c_str(), sizeof(deviceMacAddress));
  Serial.printf("MAC Address: %s\n", deviceMacAddress);

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
    sendControlCommand("report_event", "boot");
    fetchControlStatus(); // Ambil config awal
  }

  // PERBAIKAN BUG: Baca sensor satu kali SEBELUM masuk loop utama.
  // Ini mencegah pompa menyala otomatis karena variabel waterLevelPer masih 0 (dianggap kosong).
  Serial.println("Inisialisasi pembacaan sensor awal...");
  measureAndSendData();

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
      sendOfflineLogs(); // 4. Kirim semua log yang tersimpan saat offline.
    }
  }

  // --- TAHAP 2: LOGIKA OPERASIONAL INTI ---
  // Logika ini harus tetap berjalan baik online maupun offline, selama perangkat sudah terdaftar.
  if (!isRegistered) {
    // Jika belum terdaftar, polling status lebih jarang
    if (WiFi.status() == WL_CONNECTED && currentMillis - lastStatusFetchTime >= 5000) {
      lastStatusFetchTime = currentMillis;
      fetchControlStatus();
    }
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

  // Polling Status/Perintah dari Server (Pengganti Subscribe MQTT)
  if (WiFi.status() == WL_CONNECTED && currentMillis - lastStatusFetchTime >= statusFetchInterval) {
    lastStatusFetchTime = currentMillis;
    fetchControlStatus();
  }

  runUniversalPumpLogic();

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

  // PERBAIKAN: Set default aman jika EEPROM kosong/korup agar logika tidak macet
  if (config.trigger_percentage <= 0 || config.trigger_percentage > 100) config.trigger_percentage = 70;
  if (config.full_tank_distance <= 0) config.full_tank_distance = 30;
  if (config.empty_tank_distance <= 0) config.empty_tank_distance = 100;
  
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

// Helper untuk request HTTP
void sendHttpRequest(const char* endpoint, const char* method, String payload, bool isStatusCheck) {
  if (WiFi.status() != WL_CONNECTED) return;

  WiFiClientSecure client;
  client.setInsecure(); // Penting untuk Cloudflare SSL
  HTTPClient http;

  String url = "https://" + String(server_domain) + endpoint;
  
  // Khusus GET status, tambahkan parameter MAC
  if (strcmp(method, "GET") == 0) {
    url += "?mac=" + String(deviceMacAddress);
  }
  
  Serial.print("Requesting: "); Serial.println(url); // Debug URL aktif

  if (http.begin(client, url)) {
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-Key", api_key);
    // Tambahkan User-Agent agar tidak diblokir oleh Cloudflare Bot Protection
    http.setUserAgent("Mozilla/5.0 (Compatible; ESP8266; WLC-Pamsimas)");

    int httpCode;
    if (strcmp(method, "POST") == 0) {
      httpCode = http.POST(payload);
    } else {
      httpCode = http.GET();
    }

    if (httpCode > 0) {
      serverConnectionFailures = 0;
      if (isStatusCheck && httpCode == 200) {
        // Parse respons status
        String response = http.getString();
        DynamicJsonDocument doc(1024);
        deserializeJson(doc, response);

        if (doc["status"] == "unregistered") {
          isRegistered = false;
        } else {
          if (!isRegistered) {
             isRegistered = true;
             EEPROM.write(EEPROM_ADDR_IS_REGISTERED, 1); EEPROM.commit();
          }
          
          // Update Mode & Status
          const char* sMode = doc["control_mode"];
          const char* sStatus = doc["status"];
          if (sMode) {
             strncpy(currMode, sMode, sizeof(currMode)-1);
             modeFlag = (strcmp(currMode, "AUTO") == 0);
          }
          if (!modeFlag && sStatus) {
             relayStatus = (strcmp(sStatus, "ON") == 0);
          }

          // Update Config
          DeviceConfig newConfig;
          newConfig.on_duration_minutes = doc["on_duration"];
          newConfig.off_duration_minutes = doc["off_duration"];
          newConfig.full_tank_distance = doc["full_tank_distance"];
          newConfig.empty_tank_distance = doc["empty_tank_distance"];
          newConfig.trigger_percentage = doc["trigger_percentage"];
          newConfig.min_run_time = doc["min_run_time"] | 60;
          
          if (memcmp(&newConfig, &config, sizeof(DeviceConfig)) != 0) {
             config = newConfig;
             pumpOnDuration = (long)config.on_duration_minutes * 60000;
             pumpOffDuration = (long)config.off_duration_minutes * 60000;
             EEPROM.put(EEPROM_ADDR_DEVICE_CONFIG, config); EEPROM.commit();
          }

          // Perintah Khusus
          if (doc["restart_command"] == 1) {
             sendControlCommand("reset_restart", "0");
             delay(1000); ESP.restart();
          }
          if (doc["mode_update_command"] == 1) sendControlCommand("reset_mode_update", "0");
        }
      }
    } else {
      Serial.printf("HTTP Error: %d\n", httpCode);
      serverConnectionFailures++;
    }
    http.end();
  }
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

  // Kirim via HTTP
  DynamicJsonDocument doc(256);
  doc["mac_address"] = deviceMacAddress;
  doc["water_level_cm"] = cm;
  doc["water_percentage"] = percentage;
  doc["rssi"] = WiFi.RSSI();
  
  String json; serializeJson(doc, json);
  sendHttpRequest("/api/log", "POST", json, false);
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

  // Kirim via HTTP
  DynamicJsonDocument doc(256);
  doc["mac"] = deviceMacAddress;
  doc["action"] = action;
  doc["value"] = value;
  
  String json; serializeJson(doc, json);
  sendHttpRequest("/api/update", "POST", json, false);
}

void fetchControlStatus() {
  sendHttpRequest("/api/status", "GET", "", true);
}

void sendOfflineLogs() {
  // Kirim log offline jika ada (Implementasi sederhana)
  if (LittleFS.exists("/sensor_log.txt")) {
     // Baca file dan kirim ke /api/log-offline (Perlu implementasi di PHP jika belum ada)
     // Untuk saat ini dikosongkan agar fokus ke koneksi utama
  }
}

void measureAndSendData() {
  const int NUM_READINGS = 8; // Sedikit diperbanyak untuk stabilitas
  float totalDistance = 0;
  int validReadings = 0;

  for (int i = 0; i < NUM_READINGS; i++) {
    digitalWrite(TRIGPIN, LOW);
    delayMicroseconds(5);
    digitalWrite(TRIGPIN, HIGH);
    delayMicroseconds(15); // Trigger sedikit lebih lama
    digitalWrite(TRIGPIN, LOW);

    float duration = pulseIn(ECHOPIN, HIGH, 30000); // Timeout 30ms (~5 meter)
    float singleReading = (duration / 2.0) * 0.0343;

    // Filter: Abaikan 0 dan nilai tidak masuk akal (> 500cm)
    if (singleReading > 2.0 && singleReading < 500.0) {
      totalDistance += singleReading;
      validReadings++;
    }
    delay(50); // Jeda antar ping diperlama untuk mengurangi interferensi gema
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
    Serial.printf("LOGIKA: [KEAMANAN] Durasi nyala maksimum tercapai. (Elapsed: %lu ms, Limit: %ld ms)\n", millis() - pumpStartTime, pumpOnDuration);
    sendControlCommand("report_event", "Pompa Mati: Durasi Maksimum Tercapai");
    sendControlCommand("set_status", "OFF");
    relayStatus = false;
    isCoolingDown = true;
    coolDownStartTime = millis();
    if (waterLevelPer < 98) {
      isResumingFill = true;
      Serial.println("LOGIKA: [KEAMANAN] Menandai untuk melanjutkan pengisian setelah istirahat.");
    }
    controlBuzzer(1000);
    handlePumpStateChange(); // PERBAIKAN: Pastikan status relay diperbarui dan disinkronkan sebelum return
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

  // DEBUGGING: Cetak status logika AUTO setiap 5 detik jika pompa mati tapi level rendah
  static unsigned long lastDebugLog = 0;
  if (millis() - lastDebugLog > 5000) {
    lastDebugLog = millis();
    if (strcmp(currMode, "AUTO") == 0 && !relayStatus && waterLevelPer < config.trigger_percentage) {
       Serial.printf("DEBUG AUTO: Level=%d%%, Trigger=%d%%, Relay=%d (OFF). Menunggu siklus berikutnya...\n", waterLevelPer, config.trigger_percentage, relayStatus);
    }
  }

  if (strcmp(currMode, "AUTO") == 0) {
    // Reset debounce jika level air turun di bawah ambang batas penuh (misal < 98%)
    if (waterLevelPer < 98 && isDebouncing) {
      Serial.println("LOGIKA: [AUTO] Level air turun (bukan penuh). Debounce di-reset.");
      isDebouncing = false;
    }

    if (isResumingFill && !relayStatus) {
      Serial.println("LOGIKA: [AUTO] Melanjutkan pengisian setelah istirahat...");
      relayStatus = true;
      pumpStartTime = millis(); // PERBAIKAN: Reset timer secara eksplisit saat menyalakan pompa
      sendControlCommand("set_status", "ON");
      isResumingFill = false;
      controlBuzzer(200);
    }
    else if (waterLevelPer < config.trigger_percentage && !relayStatus) {
      Serial.println("LOGIKA: [AUTO] Level air rendah, menyalakan pompa.");
      relayStatus = true;
      pumpStartTime = millis(); // PERBAIKAN: Reset timer secara eksplisit saat menyalakan pompa
      sendControlCommand("set_status", "ON");
      controlBuzzer(500);
    } else if (waterLevelPer >= 98 && relayStatus) {
      
      // --- LOGIKA BARU: Minimum Run Time (Anti-Short Cycle) ---
      // Menggunakan nilai dari konfigurasi EEPROM/Server
      long minRunTimeMs = (long)config.min_run_time * 1000;
      
      if (millis() - pumpStartTime < minRunTimeMs) {
        Serial.printf("LOGIKA: [AUTO] Sensor penuh, tapi diabaikan (Minimum Run Time %d detik belum tercapai).\n", config.min_run_time);
        isDebouncing = false; // Reset debounce agar tidak menumpuk
        return; // Keluar dari fungsi, biarkan pompa tetap nyala
      }

      // Implementasi Debounce
      if (!isDebouncing) {
        isDebouncing = true;
        sensorDebounceStartTime = millis();
        Serial.println("LOGIKA: [AUTO] Terdeteksi penuh, memverifikasi gelombang...");
      } else {
        if (millis() - sensorDebounceStartTime > (sensorDebounceDelay * 1000)) {
          Serial.println("AUTO: Tangki penuh (Stabil), mematikan pompa.");
          relayStatus = false;
          isCoolingDown = true;
          coolDownStartTime = millis();
          char eventMsg[64];
          // Sertakan level air yang terdeteksi saat mematikan pompa untuk bukti
          snprintf(eventMsg, sizeof(eventMsg), "Pompa Mati: Tangki Penuh (Auto) - Terdeteksi %d%%", waterLevelPer);
          sendControlCommand("report_event", eventMsg);
          sendControlCommand("set_status", "OFF");
          controlBuzzer(500);
          isResumingFill = false;
        }
      }
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
        pumpStartTime = millis(); // PERBAIKAN: Reset timer secara eksplisit saat menyalakan pompa
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
  if (relayStatus != lastRelayStatus) {
    if (relayStatus) {
      digitalWrite(RelayPin, HIGH);
      pumpStartTime = millis();
      Serial.println("RELAY: Pin relay diaktifkan (HIGH), timer keamanan dimulai.");
      Serial.printf("  -> DEBUG: Mode Saat Ini = %s, Status Pompa = ON\n", currMode);
    } else {
      digitalWrite(RelayPin, LOW);
      Serial.println("RELAY: Pin relay dinonaktifkan (LOW).");
      Serial.printf("  -> DEBUG: Mode Saat Ini = %s, Status Pompa = OFF\n", currMode);
    }
    lastRelayStatus = relayStatus;
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
