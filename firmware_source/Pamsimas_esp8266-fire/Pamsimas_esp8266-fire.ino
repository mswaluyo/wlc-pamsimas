// =================================================================
// == Kode ini diadaptasi untuk terhubung langsung ke Firebase      ==
// =================================================================

// --- Library yang Dibutuhkan ---
#include <ESP8266WiFi.h>
#include <time.h>
#include <LittleFS.h>
#include <EEPROM.h>
#include <AceButton.h>
#include <Firebase_ESP_Client.h>
#include "addons/TokenHelper.h"
#include "addons/RTDBHelper.h"

using namespace ace_button;

// =================================================================
// --- KONFIGURASI (GANTI BAGIAN INI SESUAI KEBUTUHAN ANDA) ---
// =================================================================

// --- Konfigurasi Wi-Fi ---
const char* ssid = "Pamsimas Tirto Argo";  // Ganti dengan SSID Wi-Fi Anda
const char* pass = "p@msim45";             // Ganti dengan Password Wi-Fi Anda

// --- Konfigurasi Firebase ---
#define API_KEY "AIzaSyCb5UgLxtmPtivQ-irfzQu1m7PnsTtNG14" // Dari firebaseConfig Anda
#define PROJECT_ID "wlcpamsimasselur"                   // Dari firebaseConfig Anda
#define USER_EMAIL "mswaluyo@gmail.com"                 // Email user dari database Anda
#define USER_PASSWORD "W@luy0178488"                   // GANTI DENGAN PASSWORD USER ASLI

// --- Konfigurasi Perangkat ---
char deviceMacAddress[18];
const char* deviceName = "ESP8266-Pamsimas";

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

// --- Alamat EEPROM ---
#define EEPROM_ADDR_DEVICE_CONFIG 18
#define EEPROM_ADDR_IS_REGISTERED 40
#define EEPROM_SIZE 64

// =================================================================
// --- VARIABEL GLOBAL ---
// =================================================================

// Variabel Sensor
float currentDistance = 0.0;
int waterLevelPer;

// Variabel untuk Smoothing (Moving Average)
#define SMOOTHING_WINDOW_SIZE 10
float distanceReadingsBuffer[SMOOTHING_WINDOW_SIZE];
int bufferIndex = 0;
bool bufferFilled = false;

// Variabel Konfigurasi
DeviceConfig config;

// Variabel Status & Kontrol (State)
bool relayStatus = false;
bool modeFlag = true;
char currMode[8] = "AUTO";
bool isRegistered = false; // Dianggap selalu terdaftar dalam mode Firebase

// Variabel untuk Buzzer Non-Blocking
bool buzzerActive = false;
unsigned long buzzerStartTime = 0;
unsigned long buzzerDuration = 0;

// Variabel untuk Waktu NTP
bool timeSynchronized = false;
long timeZone = 7 * 3600; // UTC+7 untuk WIB

// Variabel Logika Pompa
bool isCoolingDown = false;
bool wasConnected = true;
unsigned long coolDownStartTime = 0;
bool isResumingFill = false;

// Variabel untuk Timing
unsigned long lastDataSendTime = 0;
unsigned long lastReconnectAttempt = 0;
unsigned long pumpStartTime = 0;
long pumpOnDuration = 5 * 60 * 1000;
long pumpOffDuration = 15 * 60 * 1000;

// Variabel Firebase
FirebaseData fbdo;
FirebaseAuth auth;
FirebaseConfig config_firebase;

// Variabel Tombol
ButtonConfig config1;
AceButton button1(&config1);
ButtonConfig config2;
AceButton button2(&config2);

void button1Handler(AceButton*, uint8_t, uint8_t);
void button2Handler(AceButton*, uint8_t, uint8_t);

// --- DEKLARASI FUNGSI (PROTOTYPES) ---
bool loadConfigFromEEPROM();
void connectToWiFi(bool isInitialBoot);
void syncTime();
void logDataOffline(float percentage, float cm, int rssi);
void sendSensorData(float percentage, float cm);
void sendOfflineLogs();
void measureAndSendData();
void runUniversalPumpLogic();
void controlBuzzer(int duration);
void handlePumpStateChange();
float calculateMovingAverage();
String getISO8601DateTime();

// =================================================================
// --- FUNGSI-FUNGSI UTAMA ---
// =================================================================

void setup() {
  Serial.begin(115200);
  delay(500);
  Serial.println("\n\n===================================");
  Serial.println("==  WLC Firebase Edition - Booting... ==");
  Serial.println("===================================");
  EEPROM.begin(EEPROM_SIZE);

  if (!LittleFS.begin()) {
    Serial.println("Gagal menginisialisasi LittleFS! Log offline tidak akan berfungsi.");
  }

  loadConfigFromEEPROM();
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

  // Pindahkan inisialisasi Firebase setelah WiFi terhubung dan waktu disinkronkan
  if (WiFi.status() == WL_CONNECTED) {
    syncTime();
    config_firebase.api_key = API_KEY;
    auth.user.email = USER_EMAIL;
    auth.user.password = USER_PASSWORD;
    config_firebase.token_status_callback = tokenStatusCallback;
    config_firebase.cert.data = NULL; // Gunakan sertifikat default dari library
    Firebase.begin(&config_firebase, &auth);
    Firebase.reconnectWiFi(true);
  }

  wasConnected = (WiFi.status() == WL_CONNECTED);
}

void loop() {
  unsigned long currentMillis = millis();

  if (WiFi.status() != WL_CONNECTED) {
    digitalWrite(wifiLed, HIGH);
    if (wasConnected) {
      Serial.println("NETWORK: Koneksi terputus. Beralih ke mode AUTO sebagai fallback.");
      strcpy(currMode, "AUTO");
      modeFlag = true;
      wasConnected = false;
    }
    if (currentMillis - lastReconnectAttempt >= 10000) {
      lastReconnectAttempt = currentMillis;
      connectToWiFi(false);
    }
  } else {
    if (!wasConnected) {
      wasConnected = true;
      digitalWrite(wifiLed, LOW);
      Serial.println("NETWORK: Koneksi pulih. Menyinkronkan status...");
      syncTime();
      sendOfflineLogs();
    }
  }

  button1.check();
  if (strcmp(currMode, "MANUAL") == 0) {
    button2.check();
  }

  if (currentMillis - lastDataSendTime >= 500) {
    lastDataSendTime = currentMillis;
    measureAndSendData();
  }

  runUniversalPumpLogic();

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

  // Validasi dan terapkan nilai default jika perlu
  config.on_duration_minutes = (config.on_duration_minutes > 0 && config.on_duration_minutes < 1440) ? config.on_duration_minutes : 5;
  config.off_duration_minutes = (config.off_duration_minutes > 0 && config.off_duration_minutes < 1440) ? config.off_duration_minutes : 15;
  config.full_tank_distance = (config.full_tank_distance > 0 && config.full_tank_distance < 500) ? config.full_tank_distance : 20;
  config.empty_tank_distance = (config.empty_tank_distance > 0 && config.empty_tank_distance < 500) ? config.empty_tank_distance : 180;
  config.trigger_percentage = (config.trigger_percentage > 0 && config.trigger_percentage < 100) ? config.trigger_percentage : 30;
  
  pumpOnDuration = (long)config.on_duration_minutes * 60 * 1000;
  pumpOffDuration = (long)config.off_duration_minutes * 60 * 1000;

  Serial.printf("Konfigurasi dimuat: Nyala=%d min, Mati=%d min, Jarak Penuh=%d cm, Jarak Kosong=%d cm, Pemicu=%d%%\n", 
                config.on_duration_minutes, config.off_duration_minutes, config.full_tank_distance, config.empty_tank_distance, config.trigger_percentage);

  isRegistered = true; // Dalam mode Firebase, kita anggap selalu terdaftar
  return true;
}

void connectToWiFi(bool isInitialBoot) {
  Serial.printf("\n[%s] Mencoba menghubungkan ke WiFi: %s...", isInitialBoot ? "BOOT" : "RECONNECT", ssid);
  WiFi.disconnect();
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, pass);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

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
  int count = bufferFilled ? SMOOTHING_WINDOW_SIZE : bufferIndex;
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
  int retries = 0;
  while (now < 1510644967 && retries < 10) {
    delay(500);
    now = time(nullptr);
    retries++;
  }
  timeSynchronized = (now >= 1510644967);
  if(timeSynchronized) Serial.printf("Waktu berhasil disinkronkan: %s", ctime(&now));
  else Serial.println("Gagal menyinkronkan waktu dari server NTP.");
}

void logDataOffline(float percentage, float cm, int rssi) {
  if (!timeSynchronized) return;
  File dataFile = LittleFS.open("/sensor_log.txt", "a");
  if (dataFile) {
    dataFile.printf("%lu,%.0f,%.2f,%d\n", time(nullptr), percentage, cm, rssi);
    dataFile.close();
  }
}

void sendSensorData(float percentage, float cm) {
  if (WiFi.status() != WL_CONNECTED || !Firebase.ready()) {
    Serial.println("WIFI/FIREBASE: Koneksi terputus. Menyimpan data sensor ke log offline.");
    logDataOffline(percentage, cm, WiFi.RSSI());
    return;
  }
  String documentPath = "sensor_logs/1";
  String content = "{\"fields\":{\"value\":{\"doubleValue\":" + String(percentage) + "},\"created_at\":{\"stringValue\":\"" + getISO8601DateTime() + "\"}}}";
  if (Firebase.Firestore.patchDocument(&fbdo, PROJECT_ID, "", documentPath.c_str(), content.c_str(), "value,created_at")) {
    Serial.printf("FIREBASE: Data berhasil dikirim. (%.0f%%)\n", percentage);
  } else {
    Serial.printf("FIREBASE: Gagal mengirim data. Error: %s\n", fbdo.errorReason().c_str());
    logDataOffline(percentage, cm, WiFi.RSSI());
  }
}

String getISO8601DateTime() {
  time_t now = time(nullptr);
  struct tm* timeinfo = gmtime(&now);
  char buffer[30];
  strftime(buffer, sizeof(buffer), "%Y-%m-%dT%H:%M:%SZ", timeinfo);
  return String(buffer);
}

void sendOfflineLogs() { /* Fungsi ini sengaja dikosongkan untuk saat ini. */ }

void measureAndSendData() {
  digitalWrite(TRIGPIN, LOW);
  delayMicroseconds(2);
  digitalWrite(TRIGPIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIGPIN, LOW);
  float duration = pulseIn(ECHOPIN, HIGH, 35000);
  float singleReading = ((duration / 2) * 0.343) / 10;

  if (singleReading <= 0) {
    singleReading = currentDistance; // Gunakan nilai terakhir jika bacaan gagal
  }

  distanceReadingsBuffer[bufferIndex++] = singleReading;
  if (bufferIndex >= SMOOTHING_WINDOW_SIZE) {
    bufferIndex = 0;
    bufferFilled = true;
  }
  currentDistance = calculateMovingAverage();
  waterLevelPer = map((int)currentDistance, config.empty_tank_distance, config.full_tank_distance, 0, 100);
  waterLevelPer = constrain(waterLevelPer, 0, 100);
  Serial.printf("SENSOR: Jarak: %.2f cm, Level: %d %%\n", currentDistance, waterLevelPer);
  sendSensorData(waterLevelPer, currentDistance);
}

void runUniversalPumpLogic() {
  if (relayStatus && (millis() - pumpStartTime >= pumpOnDuration)) {
    Serial.println("LOGIKA: [KEAMANAN] Durasi nyala maksimum tercapai. Mematikan pompa.");
    relayStatus = false;
    isCoolingDown = true;
    coolDownStartTime = millis();
    if (waterLevelPer < 98) {
      isResumingFill = true;
    }
    controlBuzzer(1000);
    return;
  }

  if (isCoolingDown) {
    if (millis() - coolDownStartTime >= pumpOffDuration) {
      isCoolingDown = false;
    } else {
      return;
    }
  }

  if (strcmp(currMode, "AUTO") == 0) {
    if (isResumingFill && !relayStatus) {
      relayStatus = true;
      isResumingFill = false;
      controlBuzzer(200);
    } else if (waterLevelPer < config.trigger_percentage && !relayStatus) {
      relayStatus = true;
      controlBuzzer(500);
    } else if (waterLevelPer >= 98 && relayStatus) {
      relayStatus = false;
      isCoolingDown = true;
      coolDownStartTime = millis();
      isResumingFill = false;
      controlBuzzer(500);
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

void handlePumpStateChange() {
  bool currentPinState = digitalRead(RelayPin);
  if (relayStatus && !currentPinState) {
    digitalWrite(RelayPin, HIGH);
    pumpStartTime = millis();
    Serial.printf("RELAY: ON (Mode: %s)\n", currMode);
  } else if (!relayStatus && currentPinState) {
    digitalWrite(RelayPin, LOW);
    Serial.printf("RELAY: OFF (Mode: %s)\n", currMode);
  }
}

void button1Handler(AceButton* button, uint8_t eventType, uint8_t buttonState) {
  if (eventType == AceButton::kEventReleased) {
    const char* newModeStr = (strcmp(currMode, "AUTO") == 0) ? "MANUAL" : "AUTO";
    strncpy(currMode, newModeStr, sizeof(currMode) - 1);
    modeFlag = (strcmp(currMode, "AUTO") == 0);
    Serial.printf("TOMBOL: Mode diubah secara lokal menjadi %s\n", currMode);
    controlBuzzer(200);
  }
}

void button2Handler(AceButton* button, uint8_t eventType, uint8_t buttonState) {
  if (eventType == AceButton::kEventReleased) {
    relayStatus = !relayStatus;
    Serial.printf("TOMBOL: Status relay diubah secara lokal menjadi %s\n", relayStatus ? "ON" : "OFF");
    controlBuzzer(500);
  }
}
