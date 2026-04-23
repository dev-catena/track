/*
 * ESP32 OTA Client - Sistema MQTT IoT
 * ===================================
 * 
 * Funcionalidades:
 * - Conectividade WiFi e MQTT
 * - Recebimento de comandos OTA via MQTT
 * - Download e instalação de firmware via HTTP
 * - Verificação de integridade (MD5)
 * - Feedback de status via MQTT
 * - Controle de LEDs para indicação visual
 * 
 * Autor: Sistema MQTT IoT
 * Data: 2025-09-14
 */

#include <WiFi.h>
#include <PubSubClient.h>
#include <HTTPClient.h>
#include <Update.h>
#include <ArduinoJson.h>
#include <MD5Builder.h>
#include <EEPROM.h>

// ========================================
// CONFIGURAÇÕES DO DISPOSITIVO
// ========================================

// Informações do dispositivo (devem ser únicas por ESP32)
String DEVICE_ID = ""; // Será preenchido com MAC Address
String DEVICE_TYPE = "sensor_de_temperatura"; // Alterar conforme o tipo
String DEPARTMENT = "producao"; // Alterar conforme departamento

// Versão atual do firmware
const String FIRMWARE_VERSION = "1.0.0";

// Configurações WiFi (podem ser definidas via portal captivo)
const char* WIFI_SSID = "SUA_REDE_WIFI";
const char* WIFI_PASSWORD = "SUA_SENHA_WIFI";

// Configurações MQTT
const char* MQTT_SERVER = "10.102.0.103";
const int MQTT_PORT = 1883;
const char* MQTT_USER = ""; // Se necessário
const char* MQTT_PASSWORD = ""; // Se necessário

// Configurações OTA
const char* OTA_SERVER = "http://firmware.iot.local";
const int OTA_TIMEOUT = 30000; // 30 segundos
const int OTA_RETRY_COUNT = 3;

// Pinos dos LEDs (ajustar conforme hardware)
const int LED_STATUS = 2;    // LED azul interno
const int LED_WIFI = 16;     // LED verde - WiFi
const int LED_MQTT = 17;     // LED amarelo - MQTT  
const int LED_OTA = 18;      // LED vermelho - OTA

// ========================================
// VARIÁVEIS GLOBAIS
// ========================================

WiFiClient wifiClient;
PubSubClient mqttClient(wifiClient);
HTTPClient http;

// Tópicos MQTT
String topicBase = "";
String topicOta = "";
String topicStatus = "";
String topicFeedback = "";

// Estado do sistema
bool wifiConnected = false;
bool mqttConnected = false;
bool otaInProgress = false;
unsigned long lastHeartbeat = 0;
unsigned long lastWifiCheck = 0;
unsigned long lastMqttCheck = 0;

// Informações OTA
String currentOtaId = "";
String pendingFirmwareUrl = "";
String pendingChecksum = "";

// ========================================
// SETUP INICIAL
// ========================================

void setup() {
    Serial.begin(115200);
    Serial.println();
    Serial.println("🚀 ESP32 OTA Client - Sistema MQTT IoT");
    Serial.println("=======================================");
    
    // Inicializar hardware
    initializeHardware();
    
    // Gerar ID único baseado no MAC
    generateDeviceId();
    
    // Configurar tópicos MQTT
    setupMqttTopics();
    
    // Conectar WiFi
    connectWiFi();
    
    // Configurar MQTT
    setupMqtt();
    
    Serial.println("✅ Inicialização concluída!");
    Serial.printf("🆔 Device ID: %s\n", DEVICE_ID.c_str());
    Serial.printf("🔄 Firmware: %s\n", FIRMWARE_VERSION.c_str());
    Serial.printf("📡 Tópico base: %s\n", topicBase.c_str());
    
    // LED de status piscando = sistema pronto
    blinkLed(LED_STATUS, 3, 200);
}

// ========================================
// LOOP PRINCIPAL
// ========================================

void loop() {
    unsigned long now = millis();
    
    // Verificar conexões
    checkConnections(now);
    
    // Processar MQTT
    if (mqttConnected) {
        mqttClient.loop();
        
        // Enviar heartbeat a cada 30 segundos
        if (now - lastHeartbeat > 30000) {
            sendHeartbeat();
            lastHeartbeat = now;
        }
    }
    
    // Processar OTA se pendente
    if (otaInProgress) {
        processOtaUpdate();
    }
    
    // Delay pequeno para não sobrecarregar
    delay(100);
}

// ========================================
// INICIALIZAÇÃO DO HARDWARE
// ========================================

void initializeHardware() {
    // Configurar pinos dos LEDs
    pinMode(LED_STATUS, OUTPUT);
    pinMode(LED_WIFI, OUTPUT);
    pinMode(LED_MQTT, OUTPUT);
    pinMode(LED_OTA, OUTPUT);
    
    // Todos os LEDs apagados inicialmente
    digitalWrite(LED_STATUS, LOW);
    digitalWrite(LED_WIFI, LOW);
    digitalWrite(LED_MQTT, LOW);
    digitalWrite(LED_OTA, LOW);
    
    // Inicializar EEPROM para configurações
    EEPROM.begin(512);
    
    Serial.println("🔧 Hardware inicializado");
}

// ========================================
// GERAÇÃO DE ID ÚNICO
// ========================================

void generateDeviceId() {
    uint8_t mac[6];
    WiFi.macAddress(mac);
    DEVICE_ID = String(mac[0], HEX) + String(mac[1], HEX) + 
                String(mac[2], HEX) + String(mac[3], HEX) + 
                String(mac[4], HEX) + String(mac[5], HEX);
    DEVICE_ID.toUpperCase();
}

// ========================================
// CONFIGURAÇÃO DOS TÓPICOS MQTT
// ========================================

void setupMqttTopics() {
    // Formato: iot/{departamento}/{tipo_dispositivo}/{device_id}
    topicBase = "iot/" + DEPARTMENT + "/" + DEVICE_TYPE + "/" + DEVICE_ID;
    topicOta = topicBase + "/ota";
    topicStatus = topicBase + "/status";
    topicFeedback = topicBase + "/feedback";
    
    Serial.printf("📡 Tópicos configurados:\n");
    Serial.printf("   Base: %s\n", topicBase.c_str());
    Serial.printf("   OTA: %s\n", topicOta.c_str());
    Serial.printf("   Status: %s\n", topicStatus.c_str());
    Serial.printf("   Feedback: %s\n", topicFeedback.c_str());
}

// ========================================
// CONECTIVIDADE WIFI
// ========================================

void connectWiFi() {
    Serial.printf("🌐 Conectando WiFi: %s", WIFI_SSID);
    
    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 30) {
        delay(500);
        Serial.print(".");
        attempts++;
        
        // LED WiFi piscando durante conexão
        digitalWrite(LED_WIFI, !digitalRead(LED_WIFI));
    }
    
    if (WiFi.status() == WL_CONNECTED) {
        wifiConnected = true;
        digitalWrite(LED_WIFI, HIGH);
        
        Serial.println(" ✅ Conectado!");
        Serial.printf("📍 IP: %s\n", WiFi.localIP().toString().c_str());
        Serial.printf("📶 RSSI: %d dBm\n", WiFi.RSSI());
    } else {
        wifiConnected = false;
        digitalWrite(LED_WIFI, LOW);
        Serial.println(" ❌ Falha na conexão WiFi!");
    }
}

// ========================================
// CONFIGURAÇÃO MQTT
// ========================================

void setupMqtt() {
    mqttClient.setServer(MQTT_SERVER, MQTT_PORT);
    mqttClient.setCallback(onMqttMessage);
    mqttClient.setBufferSize(2048); // Buffer maior para mensagens OTA
    
    connectMqtt();
}

void connectMqtt() {
    if (!wifiConnected) return;
    
    Serial.printf("📡 Conectando MQTT: %s:%d", MQTT_SERVER, MQTT_PORT);
    
    int attempts = 0;
    while (!mqttClient.connected() && attempts < 5) {
        String clientId = "ESP32_" + DEVICE_ID;
        
        if (mqttClient.connect(clientId.c_str(), MQTT_USER, MQTT_PASSWORD)) {
            mqttConnected = true;
            digitalWrite(LED_MQTT, HIGH);
            
            Serial.println(" ✅ Conectado!");
            
            // Inscrever-se no tópico OTA
            if (mqttClient.subscribe(topicOta.c_str())) {
                Serial.printf("📩 Inscrito em: %s\n", topicOta.c_str());
            }
            
            // Enviar status inicial
            sendDeviceStatus("online");
            
        } else {
            mqttConnected = false;
            digitalWrite(LED_MQTT, LOW);
            
            Serial.printf(" ❌ Falha! Código: %d\n", mqttClient.state());
            attempts++;
            delay(2000);
        }
    }
}

// ========================================
// CALLBACK MQTT
// ========================================

void onMqttMessage(char* topic, byte* payload, unsigned int length) {
    String message = "";
    for (int i = 0; i < length; i++) {
        message += (char)payload[i];
    }
    
    Serial.printf("📨 MQTT recebido [%s]: %s\n", topic, message.c_str());
    
    // Verificar se é comando OTA
    if (String(topic) == topicOta) {
        processOtaCommand(message);
    }
}

// ========================================
// PROCESSAMENTO DE COMANDOS OTA
// ========================================

void processOtaCommand(String message) {
    Serial.println("🔄 Processando comando OTA...");
    
    // Parse do JSON
    DynamicJsonDocument doc(1024);
    DeserializationError error = deserializeJson(doc, message);
    
    if (error) {
        Serial.printf("❌ Erro ao parse JSON: %s\n", error.c_str());
        sendOtaFeedback("failed", "Erro no formato JSON do comando OTA");
        return;
    }
    
    // Verificar se é comando de update
    if (doc["command"] != "ota_update") {
        Serial.println("⚠️ Comando não reconhecido");
        return;
    }
    
    // Extrair informações do comando
    currentOtaId = doc["ota_id"].as<String>();
    pendingFirmwareUrl = doc["firmware_url"].as<String>();
    String checksumUrl = doc["checksum_url"].as<String>();
    pendingChecksum = doc["checksum_md5"].as<String>();
    String targetVersion = doc["firmware_version"].as<String>();
    bool forceUpdate = doc["force_update"] | false;
    
    Serial.printf("🆔 OTA ID: %s\n", currentOtaId.c_str());
    Serial.printf("🔄 Versão alvo: %s\n", targetVersion.c_str());
    Serial.printf("📦 URL firmware: %s\n", pendingFirmwareUrl.c_str());
    
    // Verificar se já temos essa versão (a menos que seja forçado)
    if (!forceUpdate && targetVersion == FIRMWARE_VERSION) {
        Serial.println("ℹ️ Já temos esta versão do firmware");
        sendOtaFeedback("success", "Versão já instalada: " + FIRMWARE_VERSION);
        return;
    }
    
    // Baixar checksum se não foi fornecido
    if (pendingChecksum.length() == 0 && checksumUrl.length() > 0) {
        pendingChecksum = downloadChecksum(checksumUrl);
    }
    
    // Iniciar processo OTA
    otaInProgress = true;
    digitalWrite(LED_OTA, HIGH);
    
    sendOtaFeedback("in_progress", "Iniciando download do firmware...");
    
    Serial.println("🚀 Processo OTA iniciado!");
}

// ========================================
// PROCESSAMENTO DO UPDATE OTA
// ========================================

void processOtaUpdate() {
    Serial.println("⬇️ Iniciando download do firmware...");
    
    // Configurar HTTP
    http.begin(pendingFirmwareUrl);
    http.setTimeout(OTA_TIMEOUT);
    
    int httpCode = http.GET();
    
    if (httpCode != HTTP_CODE_OK) {
        String error = "Erro HTTP: " + String(httpCode);
        Serial.printf("❌ %s\n", error.c_str());
        sendOtaFeedback("failed", error);
        resetOtaState();
        return;
    }
    
    int contentLength = http.getSize();
    Serial.printf("📦 Tamanho do firmware: %d bytes\n", contentLength);
    
    if (contentLength <= 0) {
        sendOtaFeedback("failed", "Tamanho do firmware inválido");
        resetOtaState();
        return;
    }
    
    // Verificar se há espaço suficiente
    if (!Update.begin(contentLength)) {
        String error = "Erro ao iniciar update: " + String(Update.errorString());
        Serial.printf("❌ %s\n", error.c_str());
        sendOtaFeedback("failed", error);
        resetOtaState();
        return;
    }
    
    // Download e instalação
    WiFiClient* client = http.getStreamPtr();
    size_t written = 0;
    uint8_t buffer[1024];
    MD5Builder md5;
    md5.begin();
    
    Serial.println("📥 Fazendo download e instalação...");
    
    while (http.connected() && written < contentLength) {
        size_t available = client->available();
        if (available > 0) {
            size_t readBytes = client->readBytes(buffer, min(available, sizeof(buffer)));
            
            // Escrever no flash
            size_t writtenBytes = Update.write(buffer, readBytes);
            if (writtenBytes != readBytes) {
                sendOtaFeedback("failed", "Erro na escrita do firmware");
                resetOtaState();
                return;
            }
            
            // Atualizar MD5
            md5.add(buffer, readBytes);
            written += readBytes;
            
            // Progresso
            int progress = (written * 100) / contentLength;
            if (progress % 10 == 0) {
                Serial.printf("📊 Progresso: %d%%\n", progress);
                sendOtaProgress(progress);
                
                // LED OTA piscando durante download
                digitalWrite(LED_OTA, !digitalRead(LED_OTA));
            }
        }
        delay(1);
    }
    
    http.end();
    
    // Verificar integridade se temos checksum
    if (pendingChecksum.length() > 0) {
        md5.calculate();
        String calculatedMd5 = md5.toString();
        
        Serial.printf("🔐 MD5 calculado: %s\n", calculatedMd5.c_str());
        Serial.printf("🔐 MD5 esperado: %s\n", pendingChecksum.c_str());
        
        if (calculatedMd5 != pendingChecksum) {
            sendOtaFeedback("failed", "Checksum MD5 não confere");
            resetOtaState();
            return;
        }
        
        Serial.println("✅ Checksum MD5 verificado!");
    }
    
    // Finalizar update
    if (Update.end(true)) {
        Serial.println("✅ Firmware instalado com sucesso!");
        sendOtaFeedback("success", "Firmware atualizado. Reiniciando...");
        
        // LED de sucesso
        blinkLed(LED_OTA, 5, 100);
        
        delay(2000);
        ESP.restart();
    } else {
        String error = "Erro ao finalizar update: " + String(Update.errorString());
        Serial.printf("❌ %s\n", error.c_str());
        sendOtaFeedback("failed", error);
        resetOtaState();
    }
}

// ========================================
// FUNÇÕES DE FEEDBACK MQTT
// ========================================

void sendOtaFeedback(String status, String message) {
    if (!mqttConnected) return;
    
    DynamicJsonDocument doc(512);
    doc["ota_id"] = currentOtaId;
    doc["device_id"] = DEVICE_ID;
    doc["status"] = status;
    doc["message"] = message;
    doc["firmware_version"] = FIRMWARE_VERSION;
    doc["timestamp"] = millis();
    
    String jsonString;
    serializeJson(doc, jsonString);
    
    if (mqttClient.publish(topicFeedback.c_str(), jsonString.c_str())) {
        Serial.printf("📤 Feedback enviado: %s\n", status.c_str());
    }
}

void sendOtaProgress(int progress) {
    if (!mqttConnected) return;
    
    DynamicJsonDocument doc(256);
    doc["ota_id"] = currentOtaId;
    doc["device_id"] = DEVICE_ID;
    doc["status"] = "in_progress";
    doc["progress_percent"] = progress;
    doc["timestamp"] = millis();
    
    String jsonString;
    serializeJson(doc, jsonString);
    
    mqttClient.publish(topicFeedback.c_str(), jsonString.c_str());
}

void sendHeartbeat() {
    if (!mqttConnected) return;
    
    DynamicJsonDocument doc(512);
    doc["device_id"] = DEVICE_ID;
    doc["device_type"] = DEVICE_TYPE;
    doc["department"] = DEPARTMENT;
    doc["firmware_version"] = FIRMWARE_VERSION;
    doc["uptime"] = millis();
    doc["free_heap"] = ESP.getFreeHeap();
    doc["wifi_rssi"] = WiFi.RSSI();
    doc["timestamp"] = millis();
    
    String jsonString;
    serializeJson(doc, jsonString);
    
    mqttClient.publish(topicStatus.c_str(), jsonString.c_str());
}

void sendDeviceStatus(String status) {
    if (!mqttConnected) return;
    
    DynamicJsonDocument doc(256);
    doc["device_id"] = DEVICE_ID;
    doc["status"] = status;
    doc["firmware_version"] = FIRMWARE_VERSION;
    doc["timestamp"] = millis();
    
    String jsonString;
    serializeJson(doc, jsonString);
    
    mqttClient.publish(topicStatus.c_str(), jsonString.c_str());
}

// ========================================
// FUNÇÕES AUXILIARES
// ========================================

String downloadChecksum(String checksumUrl) {
    Serial.printf("🔐 Baixando checksum: %s\n", checksumUrl.c_str());
    
    HTTPClient httpChecksum;
    httpChecksum.begin(checksumUrl);
    
    int httpCode = httpChecksum.GET();
    String checksum = "";
    
    if (httpCode == HTTP_CODE_OK) {
        checksum = httpChecksum.getString();
        checksum.trim();
        Serial.printf("🔐 Checksum obtido: %s\n", checksum.c_str());
    } else {
        Serial.printf("❌ Erro ao baixar checksum: %d\n", httpCode);
    }
    
    httpChecksum.end();
    return checksum;
}

void resetOtaState() {
    otaInProgress = false;
    currentOtaId = "";
    pendingFirmwareUrl = "";
    pendingChecksum = "";
    digitalWrite(LED_OTA, LOW);
    
    // LED de erro
    blinkLed(LED_OTA, 10, 50);
}

void checkConnections(unsigned long now) {
    // Verificar WiFi a cada 10 segundos
    if (now - lastWifiCheck > 10000) {
        if (WiFi.status() != WL_CONNECTED) {
            if (wifiConnected) {
                Serial.println("⚠️ WiFi desconectado!");
                wifiConnected = false;
                mqttConnected = false;
                digitalWrite(LED_WIFI, LOW);
                digitalWrite(LED_MQTT, LOW);
            }
            connectWiFi();
        } else if (!wifiConnected) {
            wifiConnected = true;
            digitalWrite(LED_WIFI, HIGH);
        }
        lastWifiCheck = now;
    }
    
    // Verificar MQTT a cada 5 segundos
    if (now - lastMqttCheck > 5000) {
        if (wifiConnected && !mqttClient.connected()) {
            if (mqttConnected) {
                Serial.println("⚠️ MQTT desconectado!");
                mqttConnected = false;
                digitalWrite(LED_MQTT, LOW);
            }
            connectMqtt();
        }
        lastMqttCheck = now;
    }
}

void blinkLed(int pin, int times, int delayMs) {
    for (int i = 0; i < times; i++) {
        digitalWrite(pin, HIGH);
        delay(delayMs);
        digitalWrite(pin, LOW);
        delay(delayMs);
    }
} 