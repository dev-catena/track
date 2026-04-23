/*
 * ESP32 IoT Zontec - Firmware Oficial
 * Versão: 1.0.0
 * Data: 16/09/2025
 * 
 * ESPECIFICAÇÕES:
 * - AP: IOT-Zontec
 * - Servidor: 192.168.4.1:5000
 * - EEPROM: MAC + tópico "iot-MAC"
 * - Botão: 5s=AP forçado, 10s=limpar EEPROM
 * - MQTT: comandos {"command": "led_on"}, {"command": "led_off"}, {"command": "led_blink"}
 */

#include <WiFi.h>
#include <WebServer.h>
#include <DNSServer.h>
#include <EEPROM.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <PubSubClient.h>

// ====== CONFIGURAÇÕES FIXAS ======
#define AP_SSID "IOT-Zontec"
#define AP_PASSWORD "12345678"
#define AP_IP IPAddress(192, 168, 4, 1)
#define SERVER_PORT 5000

#define LED_WIFI_PIN 16     // LED de status WiFi (piscar/fixo)
#define LED_MQTT_PIN 19     // LED controlável via MQTT
#define BUTTON_PIN 0        // Botão BOOT/PROG
#define EEPROM_SIZE 512

// ====== CONFIGURAÇÕES DO SERVIDOR (CORRIGIDO PARA LOCAL) ======
#define BACKEND_SERVER "10.102.0.115"
#define BACKEND_PORT 8000
#define MQTT_SERVER "10.102.0.115"
#define MQTT_PORT 1883

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
bool isAPMode = false;
bool wifiConnected = false;

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
            <p><strong>Firmware:</strong> IOT-Zontec-1.0.0</p>
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
                body: JSON.stringify({ ssid: ssid, password: password })
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
    Serial.println("📋 Versão: 1.0.0");
    
    // Configurar pinos
    pinMode(LED_WIFI_PIN, OUTPUT);
    pinMode(LED_MQTT_PIN, OUTPUT);
    pinMode(BUTTON_PIN, INPUT_PULLUP);
    
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
            
            // Registrar no backend
            if (registerInBackend()) {
                Serial.println("✅ Registrado no backend com sucesso!");
                
                // Conectar MQTT
                setupMQTT();
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
    // Processar servidor web
    if (isAPMode) {
        dnsServer.processNextRequest();
        server.handleClient();
    }
    
    // Processar MQTT se conectado
    if (wifiConnected && mqttClient.connected()) {
        mqttClient.loop();
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
}

void clearEEPROM() {
    Serial.println("🗑️ Limpando EEPROM...");
    for (int i = 0; i < EEPROM_SIZE; i++) {
        EEPROM.write(i, 0);
    }
    EEPROM.commit();
    Serial.println("✅ EEPROM limpa");
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
bool registerInBackend() {
    if (!wifiConnected) {
        Serial.println("❌ WiFi não conectado - não é possível registrar");
        return false;
    }
    
    Serial.println("📡 Registrando dispositivo no backend...");
    
    HTTPClient http;
    String url = "http://" + String(BACKEND_SERVER) + ":" + String(BACKEND_PORT) + "/api/devices/pending";
    
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    http.setTimeout(10000);
    
    DynamicJsonDocument doc(512);
    doc["mac_address"] = deviceMAC;
    doc["device_name"] = "ESP32-Zontec-" + deviceMAC.substring(15);
    doc["ip_address"] = WiFi.localIP().toString();
    doc["wifi_ssid"] = savedSSID;
    doc["registered_at"] = millis();
    doc["device_info"] = "ESP32 IoT Zontec v1.0.0";
    doc["firmware_version"] = "1.0.0";
    doc["esp32_model"] = "ESP32-WROOM";
    doc["free_heap"] = ESP.getFreeHeap();
    
    String jsonString;
    serializeJson(doc, jsonString);
    
    Serial.printf("📤 Enviando: %s\n", jsonString.c_str());
    
    int httpCode = http.POST(jsonString);
    String response = http.getString();
    http.end();
    
    Serial.printf("📥 Resposta (%d): %s\n", httpCode, response.c_str());
    
    if (httpCode == 200 || httpCode == 201 || httpCode == 409) {
        Serial.println("✅ Dispositivo registrado com sucesso!");
        return true;
    } else {
        Serial.println("❌ Falha ao registrar dispositivo");
        return false;
    }
}

// ====== FUNÇÕES MQTT ======
void mqttCallback(char* topic, byte* payload, unsigned int length) {
    String message = "";
    for (int i = 0; i < length; i++) {
        message += (char)payload[i];
    }
    
    Serial.printf("📨 MQTT recebido no tópico '%s': %s\n", topic, message.c_str());
    
    // Parse JSON para extrair comando
    String command = "";
    
    if (message.startsWith("{")) {
        DynamicJsonDocument doc(256);
        DeserializationError error = deserializeJson(doc, message);
        
        if (error) {
            Serial.printf("❌ Erro ao parsear JSON: %s\n", error.c_str());
            return;
        }
        
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
    
    Serial.printf("📡 Configurando MQTT: %s:%d\n", MQTT_SERVER, MQTT_PORT);
    
    mqttClient.setServer(MQTT_SERVER, MQTT_PORT);
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
    doc["firmware"] = "IOT-Zontec-1.0.0";
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
    
    Serial.printf("🔧 Configurando WiFi: %s\n", ssid.c_str());
    Serial.println("🔄 Testando conexão WiFi...");
    
    // Testar conexão WiFi
    if (connectToWiFi(ssid, password)) {
        Serial.println("✅ WiFi conectado com sucesso!");
        
        // Salvar na EEPROM
        Serial.println("💾 Salvando configurações na EEPROM...");
        saveToEEPROM(ssid, password, deviceMAC, mqttTopic);
        
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
    
    // Captive portal - redirecionar todas as requisições
    server.onNotFound(handleRoot);
    
    server.begin();
    Serial.printf("🌐 Servidor web iniciado na porta %d\n", SERVER_PORT);
    
    isAPMode = true;
    setLedMode(LED_FAST_BLINK);
} 