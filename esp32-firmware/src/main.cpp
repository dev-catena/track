/*
 * ESP32 IoT Zontec - Firmware Oficial
 * Versão: 1.0.2
 * Data: 24/02/2026
 *
 * Changelog 1.0.2:
 * - Rollback A/B: novo firmware só é confirmado após WiFi+backend+MQTT; se crashar antes, volta ao anterior
 */

#include <WiFi.h>
#include <WebServer.h>
#include <DNSServer.h>
#include <EEPROM.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <PubSubClient.h>
#include <Update.h>
#include <MD5Builder.h>
#include <esp_ota_ops.h>
#include <time.h>

// ====== CONFIGURAÇÕES FIXAS ======
#define AP_SSID "IOT-Zontec"
#define AP_PASSWORD "12345678"
#define AP_IP IPAddress(192, 168, 4, 1)
#define SERVER_PORT 80  // Porta 80 = http://192.168.4.1 (sem precisar :5000)

#define LED_WIFI_PIN 22     // LED de status de rede (GPIO 22 - ESP32 WROOM-32)
#define LED_MQTT_PIN 19     // LED controlável via MQTT
#define LED_OTA_PIN 18      // LED para status OTA
#define BUTTON_PIN 0        // Botão BOOT/PROG

// 6 GPIO para sensores de presença (chave: HIGH = bateria no slot, LOW = vazio)
#define SENSOR_PINS { 32, 33, 34, 35, 26, 27 }
// 6 GPIO para LEDs dos lockers (um por slot)
#define SLOT_LED_PINS { 18, 5, 13, 14, 15, 16 }
#define NUM_SLOTS 6
#define SENSOR_OCCUPIED_HIGH 1   // 1 = HIGH indica ocupado, 0 = LOW indica ocupado
#define EEPROM_SIZE 512
#define EEPROM_ADDR_DEPLOYED 510  // Flag: doca já ativada (1=sim, 0=não) - no cliente só atualiza rede
#define EEPROM_ADDR_SERVER_IP 400  // IP do servidor (até 19 chars)
#define EEPROM_SERVER_IP_MAX 19

// ====== CONFIGURAÇÕES DO SERVIDOR ======
#define DEFAULT_SERVER_IP "10.102.0.5"
#define BACKEND_PORT 8000
#define MQTT_PORT 1883

// ====== CONFIGURAÇÕES OTA ======
#define OTA_SERVER "http://firmware.iot.local:8080"
#define OTA_TIMEOUT 30000  // 30 segundos
#define OTA_RETRY_COUNT 3

// ====== OBJETOS GLOBAIS ======
WebServer server(SERVER_PORT);
DNSServer dnsServer;
WiFiClient espClient;
PubSubClient mqttClient(espClient);

// ====== VARIÁVEIS GLOBAIS ======
String deviceMAC = "";
String mqttTopic = "";
String savedSSID = "";
String savedPassword = "";
String savedServerIP = DEFAULT_SERVER_IP;
bool isAPMode = false;
bool wifiConnected = false;

// ====== VARIÁVEIS OTA ======
bool otaInProgress = false;
String currentOtaId = "";
String pendingFirmwareUrl = "";
String pendingChecksum = "";
String currentFirmwareVersion = "1.0.2";

// Controle do botão
unsigned long buttonPressStart = 0;
bool buttonPressed = false;
bool button5sTriggered = false;
bool button10sTriggered = false;

// Controle do LED de status
unsigned long lastLedUpdate = 0;
bool ledState = false;
enum LedMode {
    LED_OFF,
    LED_ON,
    LED_FAST_BLINK,  // AP ativo
    LED_SLOW_BLINK,  // Conectando WiFi
    LED_FIXED_ON     // WiFi conectado
};
LedMode currentLedMode = LED_OFF;

// LED dos slots: acende 10s e apaga automaticamente
#define SLOT_LED_DURATION_MS 10000
unsigned long slotLedOnAt = 0;
int slotLedActive = 0;      // qual slot está com LED aceso (1-6)
int slotExpiredNeedsTurnOff = 0;  // slot cujo LED precisa ser apagado (setado no loop, executado no callback)

#define MQTT_RECONNECT_INTERVAL_MS 5000
unsigned long lastMqttReconnectAttempt = 0;

// ====== DECLARAÇÕES DE FUNÇÕES (PROTÓTIPOS) ======
void setLedMode(LedMode mode);
void updateLeds();
void handleButton();
void saveToEEPROM(String ssid, String password, String mac, String topic);
void saveServerIPToEEPROM(String ip);
void loadServerIPFromEEPROM();
void loadFromEEPROM();
void clearEEPROM();
void setDeployedFlag(bool deployed);
bool isDeployed();
bool connectToWiFi(String ssid, String password);
bool registerInBackend();
bool checkinToBackend();
void mqttCallback(char* topic, byte* payload, unsigned int length);
bool setupMQTT();
bool connectMQTT();
void handleRoot();
void handleDeviceInfo();
void handleConfigure();
void startAPMode();
void processOtaCommand(String message);
void processOtaUpdate();
void sendOtaFeedback(String status, String message);
void sendOtaProgress(int progress);
void resetOtaState();
void openSlot(int slot);
void closeSlot(int slot);
void publishSlotsFeedback(String requestId, const int* availableSlots, int count);
void readSlotSensors(int* slotsOccupied);
int postSlotStatusToBackend(const int* slotsOccupied);  // retorna slot (1-6) se backend indicar, 0 caso contrário

// ====== PÁGINA HTML DO FORMULÁRIO ======
const char* htmlForm = R"rawliteral(
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>IOT-Zontec Configuração</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 400px; width: 90%; }
        .logo { text-align: center; margin-bottom: 2rem; }
        .logo h1 { color: #333; font-size: 1.8rem; margin-bottom: 0.5rem; }
        .logo p { color: #666; font-size: 0.9rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s; }
        input[type="text"]:focus, input[type="password"]:focus { outline: none; border-color: #667eea; }
        .btn { width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .status { margin-top: 1rem; padding: 0.75rem; border-radius: 8px; text-align: center; font-weight: bold; }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .device-info { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #e9ecef; }
        .device-info h3 { color: #495057; margin-bottom: 0.5rem; }
        .device-info p { color: #6c757d; font-size: 0.9rem; margin-bottom: 0.25rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🌐 IOT-Zontec</h1>
            <p>Configuração de Dispositivo</p>
        </div>
        
        <div class="device-info">
            <h3>📱 Informações do Dispositivo</h3>
            <p><strong>MAC:</strong> <span id="device-mac">Carregando...</span></p>
            <p><strong>Tópico MQTT:</strong> <span id="device-topic">Carregando...</span></p>
            <p><strong>Firmware:</strong> IOT-Zontec-1.0.2</p>
        </div>

        <form id="wifi-form">
            <div class="form-group">
                <label for="ssid">🌐 Nome da Rede WiFi (SSID):</label>
                <input type="text" id="ssid" name="ssid" required placeholder="Digite o nome da rede">
            </div>
            
            <div class="form-group">
                <label for="password">🔐 Senha da Rede WiFi:</label>
                <input type="password" id="password" name="password" required placeholder="Digite a senha">
            </div>
            
            <div class="form-group">
                <label for="server_ip">🖥️ IP do Servidor (Backend/MQTT):</label>
                <input type="text" id="server_ip" name="server_ip" placeholder="10.102.0.5" value="">
            </div>
            
            <button type="submit" class="btn" id="connect-btn">
                🔗 CONECTAR
            </button>
        </form>
        
        <div id="status" class="status" style="display: none;"></div>
    </div>

    <script>
        // Carregar informações do dispositivo
        fetch('/device-info')
            .then(response => response.json())
            .then(data => {
                document.getElementById('device-mac').textContent = data.mac || 'N/A';
                document.getElementById('device-topic').textContent = data.topic || 'N/A';
                document.getElementById('server_ip').value = data.server_ip || '10.102.0.5';
            })
            .catch(error => {
                console.error('Erro ao carregar informações:', error);
                document.getElementById('device-mac').textContent = 'Erro';
                document.getElementById('device-topic').textContent = 'Erro';
            });

        // Função para mostrar status
        function showStatus(message, type) {
            const statusDiv = document.getElementById('status');
            statusDiv.textContent = message;
            statusDiv.className = 'status ' + type;
            statusDiv.style.display = 'block';
        }

        // Processar formulário
        document.getElementById('wifi-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const ssid = document.getElementById('ssid').value.trim();
            const password = document.getElementById('password').value.trim();
            const server_ip = document.getElementById('server_ip').value.trim() || '10.102.0.5';
            
            if (!ssid || !password) {
                showStatus('❌ Preencha todos os campos!', 'error');
                return;
            }
            
            const btn = document.getElementById('connect-btn');
            btn.disabled = true;
            btn.textContent = '🔄 Conectando...';
            
            showStatus('🔄 Conectando ao WiFi...', 'info');
            
            fetch('/configure', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ssid: ssid, password: password, server_ip: server_ip })
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.textContent = '🔗 CONECTAR';
                
                if (data.success) {
                    showStatus('✅ ' + data.message, 'success');
                    setTimeout(() => {
                        showStatus('🔄 Dispositivo reiniciando...', 'info');
                    }, 2000);
                } else {
                    showStatus('❌ ' + data.message, 'error');
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.textContent = '🔗 CONECTAR';
                showStatus('❌ Erro de conexão', 'error');
                console.error('Erro:', error);
            });
        });
    </script>
</body>
</html>
)rawliteral";

// ====== SETUP ======
void setup() {
    Serial.begin(115200);
    delay(1000);
    
    Serial.println("\n🚀 ESP32 IoT Zontec - Iniciando...");
    Serial.println("📋 Versão: 1.0.2");
    
    // Configurar pinos
    pinMode(LED_WIFI_PIN, OUTPUT);
    pinMode(LED_MQTT_PIN, OUTPUT);
    pinMode(LED_OTA_PIN, OUTPUT);
    pinMode(BUTTON_PIN, INPUT_PULLUP);
    const int sensorPins[NUM_SLOTS] = SENSOR_PINS;
    const int ledPins[NUM_SLOTS] = SLOT_LED_PINS;
    for (int i = 0; i < NUM_SLOTS; i++) {
        pinMode(sensorPins[i], INPUT_PULLUP);
        pinMode(ledPins[i], OUTPUT);
        digitalWrite(ledPins[i], LOW);
    }
    
    // Inicializar EEPROM
    EEPROM.begin(EEPROM_SIZE);
    
    // Inicializar WiFi para obter MAC (sem conectar ainda)
    Serial.println("🔧 Inicializando WiFi para obter MAC...");
    WiFi.mode(WIFI_STA);
    delay(100); // Aguardar inicialização
    
    // Obter MAC address (agora o WiFi já foi inicializado)
    Serial.println("📡 Lendo MAC address...");
    deviceMAC = WiFi.macAddress();
    Serial.printf("🔍 MAC lido: '%s' (tamanho: %d)\n", deviceMAC.c_str(), deviceMAC.length());
    
    if (deviceMAC == "00:00:00:00:00:00" || deviceMAC.length() == 0) {
        Serial.println("⚠️ MAC inválido! Tentando novamente...");
        delay(500);
        deviceMAC = WiFi.macAddress();
        Serial.printf("🔍 Segunda tentativa: '%s'\n", deviceMAC.c_str());
    }
    
    mqttTopic = "iot-" + deviceMAC;
    mqttTopic.replace(":", "");
    mqttTopic.toLowerCase();
    
    Serial.printf("📱 MAC: %s\n", deviceMAC.c_str());
    Serial.printf("📡 Tópico MQTT: %s\n", mqttTopic.c_str());
    
    // Carregar configurações da EEPROM
    loadFromEEPROM();
    
    // Verificar botão pressionado no boot
    if (digitalRead(BUTTON_PIN) == LOW) {
        Serial.println("🔄 Botão pressionado no boot - Modo AP forçado");
        button5sTriggered = true;
    }
    
    // Tentar conectar WiFi se configurado
    if (!button5sTriggered && savedSSID.length() > 0) {
        Serial.printf("🔗 Tentando conectar ao WiFi salvo: %s\n", savedSSID.c_str());
        setLedMode(LED_SLOW_BLINK);
        
            if (connectToWiFi(savedSSID, savedPassword)) {
            Serial.println("✅ Conectado ao WiFi salvo!");
            setLedMode(LED_FIXED_ON);
            configTime(0, 0, "pool.ntp.org");

            // Registrar no backend
            if (registerInBackend()) {
                Serial.println("✅ Registrado no backend com sucesso!");
                
                // Conectar MQTT
                setupMQTT();
                
                // Validar firmware OTA (rollback A/B): se não chamar e o ESP reiniciar, volta ao firmware anterior
                if (esp_ota_mark_app_valid_cancel_rollback() == ESP_OK) {
                    Serial.println("✅ Firmware OTA validado (rollback desativado)");
                }
            } else {
                Serial.println("⚠️ Falha ao registrar no backend, mas WiFi está conectado");
            }
            
            return; // Sair da função setup
        } else {
            Serial.println("❌ Falha ao conectar WiFi salvo");
        }
    }
    
    // Iniciar modo AP
    startAPMode();
}

// ====== LOOP PRINCIPAL ======
void loop() {
    // Ao completar 10s: apagar LED, zerar estado e forçar reconexão MQTT
    // (o timeout pode deixar o MQTT em estado que impede receber novos comandos)
    if (slotLedOnAt > 0 && (millis() - slotLedOnAt) >= SLOT_LED_DURATION_MS) {
        if (slotLedActive >= 1 && slotLedActive <= NUM_SLOTS) {
            const int ledPins[NUM_SLOTS] = SLOT_LED_PINS;
            int pin = ledPins[slotLedActive - 1];
            pinMode(pin, OUTPUT);
            digitalWrite(pin, LOW);
        }
        slotLedOnAt = 0;
        slotLedActive = 0;
        slotExpiredNeedsTurnOff = 0;
        // Forçar reconexão MQTT para restaurar estado de espera de comandos
        if (wifiConnected) {
            mqttClient.disconnect();
            delay(500);  // ESP32: esperar antes de reconectar evita travamento
            connectMQTT();
        }
        yield();
    }
    
    // Processar servidor web
    if (isAPMode) {
        dnsServer.processNextRequest();
        server.handleClient();
    }
    
    // Processar MQTT - reconectar se desconectado (ex: após timeout de 10s que pode afetar a conexão)
    if (wifiConnected) {
        if (mqttClient.connected()) {
            mqttClient.loop();
        } else {
            unsigned long now = millis();
            if (now - lastMqttReconnectAttempt >= MQTT_RECONNECT_INTERVAL_MS) {
                lastMqttReconnectAttempt = now;
                if (connectMQTT()) {
                    lastMqttReconnectAttempt = 0;
                }
            }
        }
    }
    
    // Processar OTA se em andamento
    if (otaInProgress) {
        processOtaUpdate();
    }
    
    // Controlar LEDs
    updateLeds();
    
    // Verificar botão
    handleButton();
    
    delay(10);
}

// ====== FUNÇÕES DE LED ======
void setLedMode(LedMode mode) {
    currentLedMode = mode;
    lastLedUpdate = millis();
    
    switch (mode) {
        case LED_OFF:
            digitalWrite(LED_WIFI_PIN, LOW);
            break;
        case LED_ON:
        case LED_FIXED_ON:
            digitalWrite(LED_WIFI_PIN, HIGH);
            break;
        default:
            break;
    }
}

void updateLeds() {
    unsigned long now = millis();
    
    switch (currentLedMode) {
        case LED_FAST_BLINK:
            if (now - lastLedUpdate > 200) {
                ledState = !ledState;
                digitalWrite(LED_WIFI_PIN, ledState);
                lastLedUpdate = now;
            }
            break;
            
        case LED_SLOW_BLINK:
            if (now - lastLedUpdate > 500) {
                ledState = !ledState;
                digitalWrite(LED_WIFI_PIN, ledState);
                lastLedUpdate = now;
            }
            break;
            
        default:
            break;
    }
}

// ====== FUNÇÕES DE BOTÃO ======
void handleButton() {
    bool currentButtonState = (digitalRead(BUTTON_PIN) == LOW);
    
    if (currentButtonState && !buttonPressed) {
        buttonPressed = true;
        buttonPressStart = millis();
        Serial.println("🔘 Botão pressionado");
        
    } else if (!currentButtonState && buttonPressed) {
        buttonPressed = false;
        unsigned long pressDuration = millis() - buttonPressStart;
        Serial.printf("🔘 Botão liberado após %lu ms\n", pressDuration);
        
        if (pressDuration >= 10000 && !button10sTriggered) {
            Serial.println("🗑️ Botão 10s - Limpando EEPROM...");
            clearEEPROM();
            button10sTriggered = true;
            ESP.restart();
            
        } else if (pressDuration >= 5000 && !button5sTriggered) {
            Serial.println("📡 Botão 5s - Modo AP forçado...");
            button5sTriggered = true;
            startAPMode();
        }
    }
}

// ====== FUNÇÕES EEPROM ======
void saveToEEPROM(String ssid, String password, String mac, String topic) {
    Serial.println("💾 Salvando na EEPROM...");
    
    int addr = 0;
    
    // Salvar SSID
    EEPROM.write(addr++, ssid.length());
    for (int i = 0; i < ssid.length(); i++) {
        EEPROM.write(addr++, ssid[i]);
    }
    
    // Salvar Password
    EEPROM.write(addr++, password.length());
    for (int i = 0; i < password.length(); i++) {
        EEPROM.write(addr++, password[i]);
    }
    
    // Salvar MAC
    EEPROM.write(addr++, mac.length());
    for (int i = 0; i < mac.length(); i++) {
        EEPROM.write(addr++, mac[i]);
    }
    
    // Salvar Topic
    EEPROM.write(addr++, topic.length());
    for (int i = 0; i < topic.length(); i++) {
        EEPROM.write(addr++, topic[i]);
    }
    
    EEPROM.commit();
    Serial.println("✅ Dados salvos na EEPROM");
}

void loadFromEEPROM() {
    Serial.println("📖 Carregando da EEPROM...");
    
    int addr = 0;
    
    // Carregar SSID
    int ssidLen = EEPROM.read(addr++);
    if (ssidLen > 0 && ssidLen < 64) {
        savedSSID = "";
        for (int i = 0; i < ssidLen; i++) {
            savedSSID += (char)EEPROM.read(addr++);
        }
    } else {
        addr += ssidLen; // Pular dados inválidos
    }
    
    // Carregar Password
    int passLen = EEPROM.read(addr++);
    if (passLen > 0 && passLen < 64) {
        savedPassword = "";
        for (int i = 0; i < passLen; i++) {
            savedPassword += (char)EEPROM.read(addr++);
        }
    } else {
        addr += passLen; // Pular dados inválidos
    }
    
    Serial.printf("📖 SSID carregado: %s\n", savedSSID.c_str());
    Serial.printf("📖 Password carregado: %s\n", savedPassword.length() > 0 ? "****" : "(vazio)");

    loadServerIPFromEEPROM();
}

void saveServerIPToEEPROM(String ip) {
    if (ip.length() > EEPROM_SERVER_IP_MAX) return;
    int addr = EEPROM_ADDR_SERVER_IP;
    EEPROM.write(addr++, ip.length());
    for (int i = 0; i < ip.length(); i++) {
        EEPROM.write(addr++, ip[i]);
    }
    EEPROM.commit();
    savedServerIP = ip;
    Serial.printf("💾 IP do servidor salvo: %s\n", ip.c_str());
}

void loadServerIPFromEEPROM() {
    int addr = EEPROM_ADDR_SERVER_IP;
    int len = EEPROM.read(addr++);
    if (len > 0 && len <= EEPROM_SERVER_IP_MAX) {
        savedServerIP = "";
        for (int i = 0; i < len; i++) {
            savedServerIP += (char)EEPROM.read(addr++);
        }
        Serial.printf("📖 IP do servidor carregado: %s\n", savedServerIP.c_str());
    }
}

void clearEEPROM() {
    Serial.println("🗑️ Limpando EEPROM...");
    for (int i = 0; i < EEPROM_SIZE; i++) {
        EEPROM.write(i, 0);
    }
    EEPROM.commit();
    Serial.println("✅ EEPROM limpa");
}

void setDeployedFlag(bool deployed) {
    EEPROM.write(EEPROM_ADDR_DEPLOYED, deployed ? 1 : 0);
    EEPROM.commit();
    Serial.printf("💾 Flag deployed: %s\n", deployed ? "sim" : "não");
}

bool isDeployed() {
    return EEPROM.read(EEPROM_ADDR_DEPLOYED) == 1;
}

// ====== FUNÇÕES WIFI ======
bool connectToWiFi(String ssid, String password) {
    WiFi.mode(WIFI_STA);
    WiFi.begin(ssid.c_str(), password.c_str());
    
    Serial.printf("🔗 Conectando ao WiFi: %s", ssid.c_str());
    
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(500);
        Serial.print(".");
        attempts++;
    }
    
    if (WiFi.status() == WL_CONNECTED) {
        Serial.println(" ✅");
        Serial.printf("📶 IP: %s\n", WiFi.localIP().toString().c_str());
        Serial.printf("📶 RSSI: %d dBm\n", WiFi.RSSI());
        
        wifiConnected = true;
        savedSSID = ssid;
        savedPassword = password;
        
        return true;
    } else {
        Serial.println(" ❌");
        wifiConnected = false;
        return false;
    }
}

// ====== FUNÇÕES BACKEND ======
bool checkinToBackend() {
    if (!wifiConnected) return false;
    Serial.println("📡 Check-in no backend (doca já ativada - só atualiza rede)...");
    DynamicJsonDocument doc(256);
    doc["mac_address"] = deviceMAC;
    doc["ip_address"] = WiFi.localIP().toString();
    doc["wifi_ssid"] = savedSSID;
    doc["firmware_version"] = "1.0.2";
    String jsonString;
    serializeJson(doc, jsonString);
    const int maxRetries = 3;
    for (int attempt = 1; attempt <= maxRetries; attempt++) {
        if (attempt > 1) {
            Serial.printf("🔄 Check-in retry %d/%d em 2s...\n", attempt, maxRetries);
            delay(2000);
        }
        HTTPClient http;
        String url = "http://" + savedServerIP + ":" + String(BACKEND_PORT) + "/api/devices/checkin";
        http.begin(url);
        http.addHeader("Content-Type", "application/json");
        http.setTimeout(15000);
        http.setConnectTimeout(10000);
        int httpCode = http.POST(jsonString);
        String response = http.getString();
        http.end();
        Serial.printf("📥 Check-in resposta (%d): %s\n", httpCode, response.c_str());
        if (httpCode == 200) {
            Serial.println("✅ Rede atualizada com sucesso!");
            return true;
        }
    }
    return false;
}

bool registerInBackend() {
    if (!wifiConnected) {
        Serial.println("❌ WiFi não conectado - não é possível registrar");
        return false;
    }
    if (isDeployed()) {
        return checkinToBackend();
    }
    Serial.println("📡 Registrando dispositivo no backend...");
    DynamicJsonDocument doc(512);
    doc["mac_address"] = deviceMAC;
    doc["device_name"] = "ESP32-Zontec-" + deviceMAC.substring(15);
    doc["ip_address"] = WiFi.localIP().toString();
    doc["wifi_ssid"] = savedSSID;
    doc["registered_at"] = millis();
    doc["device_info"] = "ESP32 IoT Zontec v1.0.2";
    doc["firmware_version"] = "1.0.2";
    doc["esp32_model"] = "ESP32-WROOM";
    doc["free_heap"] = ESP.getFreeHeap();
    String jsonString;
    serializeJson(doc, jsonString);
    Serial.printf("📤 Enviando: %s\n", jsonString.c_str());

    const int maxRetries = 3;
    for (int attempt = 1; attempt <= maxRetries; attempt++) {
        if (attempt > 1) {
            Serial.printf("🔄 Tentativa %d/%d em 2s...\n", attempt, maxRetries);
            delay(2000);
        }
        HTTPClient http;
        String url = "http://" + savedServerIP + ":" + String(BACKEND_PORT) + "/api/devices/pending";
        http.begin(url);
        http.addHeader("Content-Type", "application/json");
        http.setTimeout(15000);
        http.setConnectTimeout(10000);
        int httpCode = http.POST(jsonString);
        String response = http.getString();
        http.end();
        Serial.printf("📥 Resposta (%d): %s\n", httpCode, response.c_str());
        if (httpCode == 200 || httpCode == 201 || httpCode == 409) {
            DynamicJsonDocument respDoc(256);
            if (deserializeJson(respDoc, response) == DeserializationError::Ok && respDoc["deployed"] == true) {
                setDeployedFlag(true);
                Serial.println("✅ Doca já ativada - flag salva para próximas reconfigs");
            }
            Serial.println("✅ Dispositivo registrado com sucesso!");
            return true;
        }
    }
    Serial.printf("❌ Falha ao registrar dispositivo após %d tentativas\n", maxRetries);
    return false;
}

// ====== FUNÇÕES MQTT ======
void mqttCallback(char* topic, byte* payload, unsigned int length) {
    // Apagar LED de slot expirado (se o turn-off no loop não rodou ainda - ex: comando muito rápido)
    if (slotExpiredNeedsTurnOff >= 1 && slotExpiredNeedsTurnOff <= NUM_SLOTS) {
        const int ledPins[NUM_SLOTS] = SLOT_LED_PINS;
        int pin = ledPins[slotExpiredNeedsTurnOff - 1];
        pinMode(pin, OUTPUT);
        digitalWrite(pin, LOW);
        slotExpiredNeedsTurnOff = 0;
    }

    String message = "";
    for (int i = 0; i < length; i++) {
        message += (char)payload[i];
    }
    
    Serial.printf("📨 MQTT recebido no tópico '%s': %s\n", topic, message.c_str());
    
    // Parse JSON para extrair comando
    String command = "";
    DynamicJsonDocument doc(256);
    bool docParsed = false;
    
    if (message.startsWith("{")) {
        DeserializationError error = deserializeJson(doc, message);
        
        if (error) {
            Serial.printf("❌ Erro ao parsear JSON: %s\n", error.c_str());
            return;
        }
        docParsed = true;
        if (doc.containsKey("command")) {
            command = doc["command"].as<String>();
            Serial.printf("🎯 Comando extraído: %s\n", command.c_str());
        } else {
            Serial.println("⚠️ Campo 'command' não encontrado no JSON");
            return;
        }
    } else {
        // Comando em texto simples
        command = message;
        Serial.printf("📝 Comando em texto simples: %s\n", command.c_str());
    }
    
    // Verificar se é comando OTA
    String topicStr = String(topic);
    if (topicStr.endsWith("/ota")) {
        processOtaCommand(message);
        return;
    }
    
    // Processar comandos EXATOS conforme especificação
    if (command == "led_on") {
        digitalWrite(LED_MQTT_PIN, HIGH);
        Serial.println("📡 LED MQTT LIGADO (GPIO19)!");
        
        // Resposta de confirmação
        String responseTopic = mqttTopic + "/status";
        mqttClient.publish(responseTopic.c_str(), "led_ligado");
        
    } else if (command == "led_off") {
        digitalWrite(LED_MQTT_PIN, LOW);
        Serial.println("📡 LED MQTT DESLIGADO (GPIO19)!");
        
        // Resposta de confirmação
        String responseTopic = mqttTopic + "/status";
        mqttClient.publish(responseTopic.c_str(), "led_desligado");
        
    } else if (command == "led_blink") {
        Serial.println("📡 LED MQTT PISCANDO (GPIO19)!");
        
        // Piscar 3 vezes
        for (int i = 0; i < 3; i++) {
            digitalWrite(LED_MQTT_PIN, HIGH);
            delay(300);
            digitalWrite(LED_MQTT_PIN, LOW);
            delay(300);
        }
        
        // Resposta de confirmação
        String responseTopic = mqttTopic + "/status";
        mqttClient.publish(responseTopic.c_str(), "led_piscou");
        
    } else if (command == "slot_status") {
        // Ler sensores, enviar para o endpoint e acionar LED conforme resposta HTTP
        int slotsOccupied[NUM_SLOTS];
        readSlotSensors(slotsOccupied);
        int slot = postSlotStatusToBackend(slotsOccupied);
        if (slot >= 1 && slot <= NUM_SLOTS) {
            openSlot(slot);
            Serial.printf("🔓 Slot %d aberto (LED) - da resposta HTTP\n", slot);
        } else if (slot == 0) {
            Serial.println("⚠️ Backend não retornou slot (nenhum livre?)");
        }
        String responseTopic = mqttTopic + "/status";
        mqttClient.publish(responseTopic.c_str(), "slot_status_sent");

    } else if (command == "open" || command == "close") {
        int slot = 0;
        if (docParsed && doc.containsKey("slot")) {
            slot = doc["slot"].as<int>();
        }
        if (command == "open") {
            if (slot >= 1 && slot <= NUM_SLOTS) {
                openSlot(slot);
                Serial.printf("🔓 Slot %d aberto (LED)\n", slot);
                String responseTopic = mqttTopic + "/status";
                mqttClient.publish(responseTopic.c_str(), ("slot_" + String(slot) + "_opened").c_str());
            } else {
                int slotsOccupied[NUM_SLOTS];
                readSlotSensors(slotsOccupied);
                String jsonString = "[";
                for (int i = 0; i < NUM_SLOTS; i++) {
                    if (i > 0) jsonString += ",";
                    jsonString += String(slotsOccupied[i]);
                }
                jsonString += "]";
                String feedbackTopic = mqttTopic + "/feedback";
                mqttClient.publish(feedbackTopic.c_str(), jsonString.c_str());
                Serial.printf("📤 Status dos slots enviado: %s\n", jsonString.c_str());
                int slotFromBackend = postSlotStatusToBackend(slotsOccupied);
                if (slotFromBackend >= 1 && slotFromBackend <= NUM_SLOTS) {
                    openSlot(slotFromBackend);
                    Serial.printf("🔓 Slot %d aberto (LED) - da resposta HTTP\n", slotFromBackend);
                }
            }
        } else {
            if (slot >= 1 && slot <= NUM_SLOTS) {
                closeSlot(slot);
                Serial.printf("🔒 Slot %d fechado\n", slot);
            } else {
                for (int s = 1; s <= NUM_SLOTS; s++) closeSlot(s);
                Serial.println("🔒 Todos os slots fechados");
            }
            String responseTopic = mqttTopic + "/status";
            String msg = (slot >= 1 && slot <= NUM_SLOTS) ? "slot_" + String(slot) + "_closed" : "all_slots_closed";
            mqttClient.publish(responseTopic.c_str(), msg.c_str());
        }
        
    } else if (command == "no_slots") {
        slotLedOnAt = 0;
        slotLedActive = 0;
        const int ledPins[NUM_SLOTS] = SLOT_LED_PINS;
        // Piscar 3x e garantir que terminam todos apagados
        for (int i = 0; i < 3; i++) {
            for (int s = 0; s < NUM_SLOTS; s++) {
                digitalWrite(ledPins[s], HIGH);
            }
            delay(200);
            for (int s = 0; s < NUM_SLOTS; s++) {
                digitalWrite(ledPins[s], LOW);
            }
            delay(200);
        }
        // Garantia final: todos apagados (um só LED nunca deve ficar aceso)
        for (int s = 0; s < NUM_SLOTS; s++) {
            pinMode(ledPins[s], OUTPUT);
            digitalWrite(ledPins[s], LOW);
        }
        Serial.println("⚠️ Alerta: nenhum slot livre!");
        String responseTopic = mqttTopic + "/status";
        mqttClient.publish(responseTopic.c_str(), "no_slots_alert");
    } else if (command == "get_slots") {
        int slotsOccupied[NUM_SLOTS];
        readSlotSensors(slotsOccupied);
        int freeCount = 0;
        int availableSlots[NUM_SLOTS];
        for (int i = 0; i < NUM_SLOTS; i++) {
            if (slotsOccupied[i] == 0) {
                availableSlots[freeCount++] = i + 1;
            }
        }
        String requestId = String(millis());
        if (docParsed && doc.containsKey("request_id")) {
            requestId = doc["request_id"].as<String>();
        }
        publishSlotsFeedback(requestId, availableSlots, freeCount);
        Serial.printf("📤 Slots livres publicados: %d\n", freeCount);
        
    } else {
        Serial.printf("⚠️ Comando desconhecido: %s\n", command.c_str());
        
        // Resposta de erro
        String responseTopic = mqttTopic + "/status";
        String errorMsg = "comando_desconhecido: " + command;
        mqttClient.publish(responseTopic.c_str(), errorMsg.c_str());
    }
}

bool setupMQTT() {
    if (!wifiConnected) {
        Serial.println("❌ WiFi não conectado - MQTT não pode ser configurado");
        return false;
    }
    
    Serial.printf("📡 Configurando MQTT: %s:%d\n", savedServerIP.c_str(), MQTT_PORT);
    
    mqttClient.setServer(savedServerIP.c_str(), MQTT_PORT);
    mqttClient.setCallback(mqttCallback);
    
    return connectMQTT();
}

bool connectMQTT() {
    String clientId = "ESP32-" + deviceMAC;
    clientId.replace(":", "");
    
    Serial.printf("🔗 Conectando MQTT como: %s\n", clientId.c_str());
    
    if (mqttClient.connect(clientId.c_str())) {
        Serial.println("✅ MQTT conectado!");
        
        // Subscrever ao tópico de comando
        String commandTopic = mqttTopic + "/cmd";
        mqttClient.subscribe(commandTopic.c_str());
        Serial.printf("📩 Subscrito ao tópico: %s\n", commandTopic.c_str());
        
        // Subscrever ao tópico OTA
        String otaTopic = mqttTopic + "/ota";
        mqttClient.subscribe(otaTopic.c_str());
        Serial.printf("📩 Subscrito ao tópico OTA: %s\n", otaTopic.c_str());
        
        // Publicar status de conexão
        String statusTopic = mqttTopic + "/status";
        mqttClient.publish(statusTopic.c_str(), "online");
        Serial.printf("📤 Status publicado em: %s\n", statusTopic.c_str());
        
        return true;
    } else {
        Serial.printf("❌ Falha na conexão MQTT: %d\n", mqttClient.state());
        return false;
    }
}

// ====== FUNÇÕES WEB SERVER ======
void handleRoot() {
    server.send(200, "text/html", htmlForm);
}

void handleDeviceInfo() {
    DynamicJsonDocument doc(256);
    doc["mac"] = deviceMAC;
    doc["topic"] = mqttTopic;
    doc["server_ip"] = savedServerIP.length() > 0 ? savedServerIP : String(DEFAULT_SERVER_IP);
    doc["firmware"] = "IOT-Zontec-1.0.2";
    doc["heap"] = ESP.getFreeHeap();
    
    String response;
    serializeJson(doc, response);
    server.send(200, "application/json", response);
}

void handleConfigure() {
    if (server.method() != HTTP_POST) {
        server.send(405, "application/json", "{\"success\":false,\"message\":\"Method not allowed\"}");
        return;
    }
    
    String body = server.arg("plain");
    DynamicJsonDocument doc(512);
    
    if (deserializeJson(doc, body) != DeserializationError::Ok) {
        server.send(400, "application/json", "{\"success\":false,\"message\":\"JSON inválido\"}");
        return;
    }
    
    String ssid = doc["ssid"];
    String password = doc["password"];
    String serverIp = doc["server_ip"].as<String>();
    if (serverIp.length() == 0) serverIp = DEFAULT_SERVER_IP;

    Serial.printf("🔧 Configurando WiFi: %s\n", ssid.c_str());
    Serial.printf("🖥️ IP do servidor: %s\n", serverIp.c_str());
    Serial.println("🔄 Testando conexão WiFi...");
    
    // Testar conexão WiFi
    if (connectToWiFi(ssid, password)) {
        Serial.println("✅ WiFi conectado com sucesso!");
        
        // Salvar na EEPROM
        Serial.println("💾 Salvando configurações na EEPROM...");
        saveToEEPROM(ssid, password, deviceMAC, mqttTopic);
        saveServerIPToEEPROM(serverIp);
        
        // Registrar no backend
        Serial.println("📡 Iniciando registro no backend...");
        bool registroOk = registerInBackend();
        
        if (registroOk) {
            Serial.println("✅ Registro no backend realizado com sucesso!");
            server.send(200, "application/json", "{\"success\":true,\"message\":\"Dispositivo configurado e registrado com sucesso! Reiniciando...\"}");
        } else {
            Serial.println("⚠️ WiFi OK mas falha no registro do backend");
            server.send(200, "application/json", "{\"success\":true,\"message\":\"WiFi conectado mas erro no registro. Reiniciando...\"}");
        }
        
        delay(3000);
        ESP.restart();
        
    } else {
        Serial.println("❌ Falha ao conectar WiFi!");
        server.send(400, "application/json", "{\"success\":false,\"message\":\"Falha ao conectar WiFi. Verifique SSID e senha.\"}");
    }
}

void startAPMode() {
    Serial.println("📡 Iniciando modo AP: IOT-Zontec");
    
    // Parar WiFi Station se ativo
    WiFi.mode(WIFI_OFF);
    delay(100);
    
    // Configurar AP
    WiFi.mode(WIFI_AP);
    WiFi.softAPConfig(AP_IP, AP_IP, IPAddress(255, 255, 255, 0));
    WiFi.softAP(AP_SSID, AP_PASSWORD);
    
    Serial.printf("✅ AP criado: %s\n", AP_SSID);
    Serial.printf("🌐 IP: %s\n", WiFi.softAPIP().toString().c_str());
    Serial.printf("🔐 Senha: %s\n", AP_PASSWORD);
    
    // Configurar DNS server (captive portal)
    dnsServer.start(53, "*", AP_IP);
    
    // Configurar rotas do servidor web
    server.on("/", handleRoot);
    server.on("/device-info", HTTP_GET, handleDeviceInfo);
    server.on("/configure", HTTP_POST, handleConfigure);
    // Evita "request handler not found" - requisições do navegador e captive portal
    auto send204 = []() { server.send(204, "text/plain", ""); };
    server.on("/favicon.ico", send204);
    server.on("/apple-touch-icon.png", send204);
    server.on("/generate_204", send204);           // Android connectivity check
    server.on("/hotspot-detect.html", handleRoot); // iOS captive portal
    server.on("/connecttest.txt", send204);       // Windows
    server.on("/success.html", handleRoot);
    server.on("/ncsi.txt", send204);              // Windows Network Connectivity Status
    // Captive portal - redirecionar todas as requisições desconhecidas
    server.onNotFound(handleRoot);
    
    server.begin();
    Serial.printf("🌐 Servidor web: http://%s\n", WiFi.softAPIP().toString().c_str());
    
    isAPMode = true;
    setLedMode(LED_FAST_BLINK);
}

// ====== FUNÇÕES OTA ======
void processOtaCommand(String message) {
    Serial.println("🔄 Processando comando OTA...");
    
    DynamicJsonDocument doc(1024);
    DeserializationError error = deserializeJson(doc, message);
    
    if (error) {
        Serial.printf("❌ Erro ao parsear JSON OTA: %s\n", error.c_str());
        sendOtaFeedback("error", "JSON inválido");
        return;
    }
    
    String firmwareUrl = doc["firmware_url"];
    String checksumMd5 = doc["checksum_md5"];
    String version = doc["version"];
    String otaId = doc["ota_id"];
    bool forceUpdate = doc["force_update"] | false;
    
    Serial.printf("📦 OTA - URL: %s\n", firmwareUrl.c_str());
    Serial.printf("📦 OTA - Versão: %s\n", version.c_str());
    Serial.printf("📦 OTA - ID: %s\n", otaId.c_str());
    Serial.printf("📦 OTA - Forçar: %s\n", forceUpdate ? "Sim" : "Não");
    
    // Verificar se já está na versão correta
    if (!forceUpdate && version == currentFirmwareVersion) {
        Serial.println("✅ Já está na versão correta");
        sendOtaFeedback("already_updated", "Dispositivo já está na versão " + version);
        return;
    }
    
    // Configurar OTA
    currentOtaId = otaId;
    pendingFirmwareUrl = firmwareUrl;
    pendingChecksum = checksumMd5;
    otaInProgress = true;
    
    // Acender LED OTA
    digitalWrite(LED_OTA_PIN, HIGH);
    
    Serial.println("🚀 Iniciando atualização OTA...");
    sendOtaFeedback("in_progress", "Iniciando download do firmware");
}

void processOtaUpdate() {
    if (!otaInProgress || pendingFirmwareUrl.length() == 0) {
        return;
    }
    
    Serial.println("📥 Baixando firmware...");
    
    HTTPClient http;
    http.begin(pendingFirmwareUrl);
    http.setTimeout(OTA_TIMEOUT);
    
    int httpCode = http.GET();
    if (httpCode != HTTP_CODE_OK) {
        Serial.printf("❌ Erro HTTP: %d\n", httpCode);
        sendOtaFeedback("error", "Erro HTTP: " + String(httpCode));
        resetOtaState();
        return;
    }
    
    int contentLength = http.getSize();
    if (contentLength <= 0) {
        Serial.println("❌ Tamanho do arquivo inválido");
        sendOtaFeedback("error", "Tamanho do arquivo inválido");
        resetOtaState();
        return;
    }
    
    Serial.printf("📦 Tamanho do firmware: %d bytes\n", contentLength);
    
    // Verificar se há espaço suficiente
    if (contentLength > UPDATE_SIZE_UNKNOWN) {
        Serial.println("❌ Firmware muito grande");
        sendOtaFeedback("error", "Firmware muito grande");
        resetOtaState();
        return;
    }
    
    // Iniciar atualização
    if (!Update.begin(contentLength)) {
        Serial.printf("❌ Erro ao iniciar OTA: %s\n", Update.errorString());
        sendOtaFeedback("error", "Erro ao iniciar OTA: " + String(Update.errorString()));
        resetOtaState();
        return;
    }
    
    // Baixar e escrever firmware
    WiFiClient* stream = http.getStreamPtr();
    uint8_t buff[512] = { 0 };
    int downloadedBytes = 0;
    MD5Builder md5;
    md5.begin();
    
    while (http.connected() && downloadedBytes < contentLength) {
        size_t size = stream->available();
        if (size) {
            int c = stream->readBytes(buff, ((size > sizeof(buff)) ? sizeof(buff) : size));
            md5.add(buff, c);
            
            if (Update.write(buff, c) != c) {
                Serial.println("❌ Erro ao escrever firmware");
                sendOtaFeedback("error", "Erro ao escrever firmware");
                resetOtaState();
                return;
            }
            
            downloadedBytes += c;
            
            // Enviar progresso
            int progress = (downloadedBytes * 100) / contentLength;
            sendOtaProgress(progress);
            
            // Piscar LED durante download
            digitalWrite(LED_OTA_PIN, !digitalRead(LED_OTA_PIN));
        }
        delay(1);
    }
    
    http.end();
    
    // Verificar checksum
    md5.calculate();
    String calculatedChecksum = md5.toString();
    
    if (pendingChecksum.length() > 0 && calculatedChecksum != pendingChecksum) {
        Serial.printf("❌ Checksum inválido! Esperado: %s, Calculado: %s\n", 
                     pendingChecksum.c_str(), calculatedChecksum.c_str());
        sendOtaFeedback("error", "Checksum inválido");
        resetOtaState();
        return;
    }
    
    // Finalizar atualização
    if (Update.end()) {
        Serial.println("✅ Firmware instalado com sucesso!");
        sendOtaFeedback("success", "Firmware instalado com sucesso");
        
        // Atualizar versão
        currentFirmwareVersion = pendingFirmwareUrl.substring(pendingFirmwareUrl.lastIndexOf('/') + 1);
        
        delay(2000);
        ESP.restart();
    } else {
        Serial.printf("❌ Erro ao finalizar OTA: %s\n", Update.errorString());
        sendOtaFeedback("error", "Erro ao finalizar OTA: " + String(Update.errorString()));
        resetOtaState();
    }
}

void sendOtaFeedback(String status, String message) {
    if (!mqttClient.connected()) {
        Serial.println("❌ MQTT não conectado - não é possível enviar feedback");
        return;
    }
    
    DynamicJsonDocument doc(512);
    doc["ota_id"] = currentOtaId;
    doc["device_id"] = deviceMAC;
    doc["status"] = status;
    doc["message"] = message;
    doc["firmware_version"] = currentFirmwareVersion;
    doc["timestamp"] = millis();
    
    String jsonString;
    serializeJson(doc, jsonString);
    
    String feedbackTopic = mqttTopic + "/feedback";
    mqttClient.publish(feedbackTopic.c_str(), jsonString.c_str());
    
    Serial.printf("📤 Feedback OTA enviado: %s\n", jsonString.c_str());
}

void sendOtaProgress(int progress) {
    if (!mqttClient.connected()) {
        return;
    }
    
    DynamicJsonDocument doc(256);
    doc["ota_id"] = currentOtaId;
    doc["device_id"] = deviceMAC;
    doc["status"] = "in_progress";
    doc["progress"] = progress;
    doc["timestamp"] = millis();
    
    String jsonString;
    serializeJson(doc, jsonString);
    
    String feedbackTopic = mqttTopic + "/feedback";
    mqttClient.publish(feedbackTopic.c_str(), jsonString.c_str());
}

void resetOtaState() {
    otaInProgress = false;
    currentOtaId = "";
    pendingFirmwareUrl = "";
    pendingChecksum = "";
    
    // Apagar LED OTA e piscar para indicar erro
    digitalWrite(LED_OTA_PIN, LOW);
    for (int i = 0; i < 3; i++) {
        digitalWrite(LED_OTA_PIN, HIGH);
        delay(200);
        digitalWrite(LED_OTA_PIN, LOW);
        delay(200);
    }
}

// ====== FUNÇÕES DE SLOT (DOCA) ======
void readSlotSensors(int* slotsOccupied) {
    const int sensorPins[NUM_SLOTS] = SENSOR_PINS;
    for (int i = 0; i < NUM_SLOTS; i++) {
#if SENSOR_OCCUPIED_HIGH
        slotsOccupied[i] = (digitalRead(sensorPins[i]) == HIGH) ? 1 : 0;
#else
        slotsOccupied[i] = (digitalRead(sensorPins[i]) == LOW) ? 1 : 0;
#endif
    }
}

int postSlotStatusToBackend(const int* slotsOccupied) {
    if (!wifiConnected) return 0;
    DynamicJsonDocument doc(768);
    doc["id_doca"] = mqttTopic.c_str();

    time_t now;
    char isoBuf[32];
    if (time(&now) > 0) {
        struct tm* t = gmtime(&now);
        strftime(isoBuf, sizeof(isoBuf), "%Y-%m-%dT%H:%M:%SZ", t);
        doc["ultima_atualizacao"] = isoBuf;
    } else {
        doc["ultima_atualizacao"] = "1970-01-01T00:00:00Z";
    }

    JsonArray arr = doc.createNestedArray("slots");
    for (int i = 0; i < NUM_SLOTS; i++) {
        JsonObject slot = arr.createNestedObject();
        slot["id_slot"] = i;
        slot["status"] = (slotsOccupied[i] == 1) ? "fechado" : "aberto";
        slot["nivel_bateria"] = (slotsOccupied[i] == 1) ? 100 : 0;
    }
    String jsonString;
    serializeJson(doc, jsonString);
    const int maxRetries = 3;
    for (int attempt = 1; attempt <= maxRetries; attempt++) {
        if (attempt > 1) {
            Serial.printf("🔄 Slot-status retry %d/%d em 1s...\n", attempt, maxRetries);
            for (int i = 0; i < 100; i++) {
                if (wifiConnected && mqttClient.connected()) mqttClient.loop();
                delay(10);
            }
        }
        HTTPClient http;
        String url = "http://" + savedServerIP + ":" + String(BACKEND_PORT) + "/api/docks/slot-status";
        http.begin(url);
        http.addHeader("Content-Type", "application/json");
        http.setTimeout(8000);
        http.setConnectTimeout(5000);
        int httpCode = http.POST(jsonString);
        String response = http.getString();
        http.end();
        Serial.printf("📤 Slot-status POST (%d): %s\n", httpCode, response.c_str());
        if (httpCode == 200 || httpCode == 201) {
            DynamicJsonDocument respDoc(128);
            if (deserializeJson(respDoc, response) == DeserializationError::Ok && respDoc["success"] == true) {
                int slot = respDoc["slot"] | 0;
                if (slot >= 1 && slot <= NUM_SLOTS) {
                    return slot;
                }
            }
            break;
        }
    }
    return 0;
}

void openSlot(int slot) {
    const int ledPins[NUM_SLOTS] = SLOT_LED_PINS;
    if (slot >= 1 && slot <= NUM_SLOTS) {
        // SEMPRE apagar todos os LEDs antes de acender um (garante: máximo 1 aceso)
        for (int s = 0; s < NUM_SLOTS; s++) {
            pinMode(ledPins[s], OUTPUT);
            digitalWrite(ledPins[s], LOW);
        }
        // Breve pausa para GPIO estabilizar após desligar (evita LED não acender em chamadas seguintes)
        delay(5);
        // Acender só o solicitado
        int pin = ledPins[slot - 1];
        digitalWrite(pin, HIGH);
        slotLedActive = slot;
        slotLedOnAt = millis();
    }
}

void closeSlot(int slot) {
    const int ledPins[NUM_SLOTS] = SLOT_LED_PINS;
    if (slot >= 1 && slot <= NUM_SLOTS) {
        digitalWrite(ledPins[slot - 1], LOW);
        if (slotLedActive == slot) {
            slotLedOnAt = 0;
            slotLedActive = 0;
        }
    }
}

void publishSlotsFeedback(String requestId, const int* availableSlots, int count) {
    if (!mqttClient.connected()) return;
    
    DynamicJsonDocument doc(256);
    doc["request_id"] = requestId;
    doc["type"] = "slots_response";
    JsonArray arr = doc.createNestedArray("available_slots");
    for (int i = 0; i < count; i++) {
        arr.add(availableSlots[i]);
    }
    
    String jsonString;
    serializeJson(doc, jsonString);
    
    String feedbackTopic = mqttTopic + "/feedback";
    mqttClient.publish(feedbackTopic.c_str(), jsonString.c_str());
} 