/*
 * ESP32 WiFi Manager - Versão Simplificada
 * Conecta ao WiFi e registra no backend MQTT
 */

#include <WiFi.h>
#include <WebServer.h>
#include <DNSServer.h>
#include <EEPROM.h>
#include <ArduinoJson.h>
#include <esp_wifi.h>
#include <HTTPClient.h>
#include <PubSubClient.h>

// ====== CONFIGURAÇÕES ======
#define LED_PIN 48          // LED interno ESP32-S3-WROOM
#define LED_EXTERNAL_PIN 16 // LED externo GPIO16 - STATUS DE CONEXÃO
#define LED_MQTT_PIN 19     // LED GPIO19 - NOTIFICAÇÕES MQTT
#define BUTTON_PIN 0        // Botão PROG

/*
 * INDICAÇÕES DOS LEDs:
 * 
 * LED_PIN + LED_EXTERNAL_PIN (Status de Conexão):
 * - DESLIGADO: Dispositivo iniciando
 * - PISCAR RÁPIDO: Modo AP ativo aguardando configuração OU erro de conexão
 * - PISCAR LENTO: Tentando conectar ao WiFi
 * - LIGADO FIXO: Conectado ao WiFi com sucesso
 * 
 * LED_MQTT_PIN (Notificações MQTT):
 * - DESLIGADO: Normal (sem atividade MQTT)
 * - PISCAR 3X LENTO: Dispositivo registrado com sucesso (novo)
 * - PISCAR 2X LENTO: Dispositivo já registrado e ativado
 * - PISCAR 1X LONGO: Dispositivo registrado mas aguardando ativação
 * - PISCAR RÁPIDO 5X: Erro HTTP no registro
 * - PISCAR MUITO RÁPIDO 10X: Erro de conexão de rede
 * - PISCAR CONTÍNUO: Mensagem MQTT recebida (implementação futura)
 */
#define EEPROM_SIZE 512     
#define AP_SSID "IOT-Zontec"
#define AP_PASSWORD "12345678"

// Backend Configuration
#define BACKEND_SERVER "10.102.0.103"
#define BACKEND_PORT 8001
#define BACKEND_ENDPOINT "/api/devices/pending"

// Estados do LED
#define LED_OFF 0
#define LED_ON 1
#define LED_FAST_BLINK 2
#define LED_SLOW_BLINK 3

// ====== VARIÁVEIS GLOBAIS ======
WebServer server(5000);
DNSServer dnsServer;
WiFiClient espClient;
PubSubClient mqttClient(espClient);

int led_state = LED_OFF;
unsigned long last_led_update = 0;
bool led_on = false;
bool wifi_configured = false;
String saved_ssid = "";
String saved_password = "";
String mqtt_topic = "";
String mqtt_broker = "";
int mqtt_port = 1883;
bool wifi_connected = false; // Nova variável para controlar a conexão WiFi

// ====== HTML SIMPLIFICADO ======
const char* html_page = R"rawliteral(<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ESP32 WiFi Config</title>
<style>
body { font-family: Arial; background: #f0f0f0; padding: 20px; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 500px; margin: 0 auto; }
.form-group { margin-bottom: 20px; }
label { display: block; margin-bottom: 5px; font-weight: bold; }
input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
.btn { width: 100%; padding: 15px; background: #007bff; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
.btn:hover { background: #0056b3; }
.status { margin-top: 20px; padding: 15px; border-radius: 5px; text-align: center; display: none; }
.success { background: #d4edda; color: #155724; }
.error { background: #f8d7da; color: #721c24; }
.info { background: #d1ecf1; color: #0c5460; }
</style>
</head>
<body>
<div class="container">
<h1>🌐 ESP32 WiFi Setup</h1>
<p><strong>MAC:</strong> <span id="mac-display">--:--:--:--:--:--</span></p>

<form id="wifi-form">
<div class="form-group">
<label>📱 Nome do Dispositivo</label>
<input type="text" id="device-name" required placeholder="Ex: Sensor Temperatura">
</div>

<div class="form-group">
<label>📶 SSID da Rede</label>
<input type="text" id="ssid" required placeholder="Nome da rede WiFi">
</div>

<div class="form-group">
<label>🔐 Senha da Rede</label>
<div style="position: relative;">
<input type="password" id="password" required placeholder="Senha da rede" style="padding-right: 45px;">
<span id="toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 18px; color: #666;">👁️</span>
</div>
</div>

<button type="submit" class="btn" id="connect-btn">🔗 CONECTAR</button>
<button type="button" class="btn" id="test-btn" style="background: #28a745; margin-top: 10px;">🧪 TESTAR WiFi APENAS</button>
<button type="button" class="btn" id="scan-btn" style="background: #17a2b8; margin-top: 10px;">🔍 APENAS ESCANEAR REDES</button>
</form>

<div id="status" class="status"></div>
</div>

<script>
// Obter MAC address
window.addEventListener('load', function() {
  fetch('/api/device-info')
    .then(response => response.json())
    .then(data => {
      document.getElementById('mac-display').textContent = data.mac_address || 'N/A';
    })
    .catch(err => console.log('Erro ao obter MAC:', err));
});

function showStatus(msg, type) {
  var s = document.getElementById('status');
  s.className = 'status ' + type;
  s.innerHTML = msg;
  s.style.display = 'block';
}

function testWiFiConnection() {
  var ssid = document.getElementById('ssid').value.trim();
  var pass = document.getElementById('password').value.trim();
  
  if (!ssid || !pass) {
    showStatus('❌ Preencha SSID e senha para testar!', 'error');
    return;
  }
  
  var btn = document.getElementById('test-btn');
  btn.disabled = true;
  btn.textContent = '🧪 Testando...';
  
  showStatus('🧪 Testando conexão WiFi (apenas teste)...', 'info');
  
  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/api/test-wifi');
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.timeout = 30000;
  
  xhr.onload = function() {
    btn.disabled = false;
    btn.textContent = '🧪 TESTAR WiFi APENAS';
    
    if (xhr.status === 200) {
      var result = JSON.parse(xhr.responseText);
      if (result.success) {
        showStatus('✅ ' + result.message, 'success');
      } else {
        showStatus('❌ ' + result.message, 'error');
      }
    } else {
      showStatus('❌ Erro HTTP: ' + xhr.status, 'error');
    }
  };
  
  xhr.ontimeout = function() {
    btn.disabled = false;
    btn.textContent = '🧪 TESTAR WiFi APENAS';
    showStatus('⏰ Timeout - Teste demorou muito', 'error');
  };
  
  xhr.onerror = function() {
    btn.disabled = false;
    btn.textContent = '🧪 TESTAR WiFi APENAS';
    showStatus('❌ Erro de conexão', 'error');
  };
  
  xhr.send(JSON.stringify({
    ssid: ssid, 
    password: pass
  }));
}

document.getElementById('test-btn').addEventListener('click', testWiFiConnection);

function scanOnlyNetworks() {
  var btn = document.getElementById('scan-btn');
  btn.disabled = true;
  btn.textContent = '🔍 Escaneando...';
  
  showStatus('🔍 Escaneando redes WiFi disponíveis...', 'info');
  
  var xhr = new XMLHttpRequest();
  xhr.open('GET', '/api/scan-only');
  xhr.timeout = 15000;
  
  xhr.onload = function() {
    btn.disabled = false;
    btn.textContent = '🔍 APENAS ESCANEAR REDES';
    
    if (xhr.status === 200) {
      var result = JSON.parse(xhr.responseText);
      if (result.success) {
        showStatus('✅ Scan concluído! Verifique o Serial Monitor para ver todas as redes encontradas.', 'success');
      } else {
        showStatus('❌ ' + result.message, 'error');
      }
    } else {
      showStatus('❌ Erro HTTP: ' + xhr.status, 'error');
    }
  };
  
  xhr.ontimeout = function() {
    btn.disabled = false;
    btn.textContent = '🔍 APENAS ESCANEAR REDES';
    showStatus('⏰ Timeout - Scan demorou muito', 'error');
  };
  
  xhr.onerror = function() {
    btn.disabled = false;
    btn.textContent = '🔍 APENAS ESCANEAR REDES';
    showStatus('❌ Erro de conexão', 'error');
  };
  
  xhr.send();
}

document.getElementById('scan-btn').addEventListener('click', scanOnlyNetworks);

document.getElementById('wifi-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  var deviceName = document.getElementById('device-name').value.trim();
  var ssid = document.getElementById('ssid').value.trim();
  var pass = document.getElementById('password').value.trim();
  
  if (!deviceName || !ssid || !pass) {
    showStatus('❌ Preencha todos os campos!', 'error');
    return;
  }
  
  var btn = document.getElementById('connect-btn');
  btn.disabled = true;
  btn.textContent = '🔄 Conectando...';
  
  showStatus('🔄 Conectando ao WiFi...', 'info');
  
  var xhr = new XMLHttpRequest();
  xhr.open('POST', '/api/configure');
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.timeout = 30000;
  
  xhr.onload = function() {
    btn.disabled = false;
    btn.textContent = '🔗 CONECTAR';
    
    if (xhr.status === 200) {
      var result = JSON.parse(xhr.responseText);
      if (result.success) {
        showStatus('✅ ' + result.message, 'success');
        setTimeout(function() {
          showStatus('🔄 Dispositivo reiniciando...', 'info');
        }, 2000);
      } else {
        showStatus('❌ ' + result.message, 'error');
      }
    } else {
      showStatus('❌ Erro HTTP: ' + xhr.status, 'error');
    }
  };
  
  xhr.ontimeout = function() {
    btn.disabled = false;
    btn.textContent = '🔗 CONECTAR';
    showStatus('⏰ Timeout - Conexão demorou muito', 'error');
  };
  
  xhr.onerror = function() {
    btn.disabled = false;
    btn.textContent = '🔗 CONECTAR';
    showStatus('❌ Erro de conexão', 'error');
  };
  
  xhr.send(JSON.stringify({
    device_name: deviceName,
    ssid: ssid, 
    password: pass
  }));
});

// Funcionalidade do ícone do olho para mostrar/ocultar senha
document.getElementById('toggle-password').addEventListener('click', function() {
  var passwordField = document.getElementById('password');
  var toggleIcon = document.getElementById('toggle-password');
  
  if (passwordField.type === 'password') {
    passwordField.type = 'text';
    toggleIcon.textContent = '🙈';
  } else {
    passwordField.type = 'password';
    toggleIcon.textContent = '👁️';
  }
});
</script>
</body>
</html>)rawliteral";

// ====== FUNÇÕES DE LED ======
void setLedState(int state) {
  led_state = state;
  last_led_update = millis();
  
  switch(state) {
    case LED_OFF:
      digitalWrite(LED_PIN, LOW);
      digitalWrite(LED_EXTERNAL_PIN, LOW);
      led_on = false;
      break;
    case LED_ON:
      digitalWrite(LED_PIN, HIGH);
      digitalWrite(LED_EXTERNAL_PIN, HIGH);
      led_on = true;
      break;
    case LED_FAST_BLINK:
    case LED_SLOW_BLINK:
      break;
  }
}

void updateLed() {
  unsigned long now = millis();
  unsigned long interval = (led_state == LED_FAST_BLINK) ? 200 : 1000;
  
  if (led_state == LED_FAST_BLINK || led_state == LED_SLOW_BLINK) {
    if (now - last_led_update >= interval) {
      led_on = !led_on;
      digitalWrite(LED_PIN, led_on ? HIGH : LOW);
      digitalWrite(LED_EXTERNAL_PIN, led_on ? HIGH : LOW);
      last_led_update = now;
    }
  }
}

// ====== EEPROM FUNCTIONS ======
void saveCredentials(String ssid, String password) {
  EEPROM.write(0, 1); // Marca como configurado
  
  // Salvar SSID
  int addr = 1;
  EEPROM.write(addr++, ssid.length());
  for (int i = 0; i < ssid.length(); i++) {
    EEPROM.write(addr++, ssid[i]);
  }
  
  // Salvar Password
  EEPROM.write(addr++, password.length());
  for (int i = 0; i < password.length(); i++) {
    EEPROM.write(addr++, password[i]);
  }
  
  EEPROM.commit();
  Serial.println("💾 Credenciais salvas na EEPROM");
}

void saveMqttConfig(String topic, String broker, int port) {
  // 🔄 PASSO 9: Salvar configuração MQTT na EEPROM
  int addr = 200; // Usar área diferente da EEPROM
  
  EEPROM.write(addr++, 2); // Marca MQTT configurado
  
  // Salvar tópico
  EEPROM.write(addr++, topic.length());
  for (int i = 0; i < topic.length(); i++) {
    EEPROM.write(addr++, topic[i]);
  }
  
  // Salvar broker
  EEPROM.write(addr++, broker.length());
  for (int i = 0; i < broker.length(); i++) {
    EEPROM.write(addr++, broker[i]);
  }
  
  // Salvar porta (2 bytes)
  EEPROM.write(addr++, port & 0xFF);
  EEPROM.write(addr++, (port >> 8) & 0xFF);
  
  EEPROM.commit();
  Serial.printf("💾 Configuração MQTT salva: %s @ %s:%d\n", topic.c_str(), broker.c_str(), port);
}

bool loadMqttConfig() {
  int addr = 200;
  if (EEPROM.read(addr++) != 2) {
    return false; // MQTT não configurado
  }
  
  // Carregar tópico
  int topic_len = EEPROM.read(addr++);
  if (topic_len > 100 || topic_len < 1) return false;
  
  mqtt_topic = "";
  for (int i = 0; i < topic_len; i++) {
    mqtt_topic += char(EEPROM.read(addr++));
  }
  
  // Carregar broker
  int broker_len = EEPROM.read(addr++);
  if (broker_len > 50 || broker_len < 1) return false;
  
  mqtt_broker = "";
  for (int i = 0; i < broker_len; i++) {
    mqtt_broker += char(EEPROM.read(addr++));
  }
  
  // Carregar porta
  mqtt_port = EEPROM.read(addr++) | (EEPROM.read(addr++) << 8);
  
  Serial.printf("📚 Config MQTT carregada: %s @ %s:%d\n", mqtt_topic.c_str(), mqtt_broker.c_str(), mqtt_port);
  return true;
}

bool loadCredentials() {
  if (EEPROM.read(0) != 1) {
    return false; // Não configurado
  }
  
  int addr = 1;
  
  // Carregar SSID
  int ssid_len = EEPROM.read(addr++);
  if (ssid_len > 32 || ssid_len < 1) return false;
  
  saved_ssid = "";
  for (int i = 0; i < ssid_len; i++) {
    saved_ssid += char(EEPROM.read(addr++));
  }
  
  // Carregar Password
  int pass_len = EEPROM.read(addr++);
  if (pass_len > 63 || pass_len < 1) return false;
  
  saved_password = "";
  for (int i = 0; i < pass_len; i++) {
    saved_password += char(EEPROM.read(addr++));
  }
  
  wifi_configured = true;
  Serial.printf("📚 Credenciais carregadas: SSID='%s'\n", saved_ssid.c_str());
  return true;
}

// ====== WIFI FUNCTIONS ======
bool connectToWiFi(String ssid, String password) {
  Serial.printf("\n📶 Conectando ao WiFi: %s\n", ssid.c_str());
  
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid.c_str(), password.c_str());
  
  setLedState(LED_SLOW_BLINK);
  
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 15000) {
    delay(500);
    Serial.printf("[%d] ", WiFi.status());
    updateLed();
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    wifi_connected = true;
    setLedState(LED_ON);
    
    Serial.println();
    Serial.printf("✅ WiFi conectado! IP: %s\n", WiFi.localIP().toString().c_str());
    
    // Testar conectividade
    Serial.println("🌐 Testando conectividade...");
    HTTPClient http;
    http.begin("http://8.8.8.8");
    http.setTimeout(5000);
    int httpCode = http.GET();
    http.end();
    
    if (httpCode > 0) {
      Serial.println("✅ Internet OK");
      return true;
    } else {
      Serial.println("⚠️ WiFi conectado mas sem internet");
      return true; // Ainda considerar sucesso
    }
  } else {
    Serial.printf("\n❌ WiFi falhou! Status: %d\n", WiFi.status());
    
    // SOMENTE agora fazer scan para debug
    Serial.println("\n🔍 === DEBUG: Redes disponíveis ===");
    int networks = WiFi.scanNetworks();
    Serial.printf("📡 Total encontradas: %d\n", networks);
    
    if (networks > 0) {
      Serial.println("📋 Lista das redes:");
      for (int i = 0; i < networks; i++) {
        String foundSSID = WiFi.SSID(i);
        int32_t rssi = WiFi.RSSI(i);
        Serial.printf("  %d: '%s' (RSSI: %d)\n", i, foundSSID.c_str(), rssi);
        
        // Verificar se é similar ao que procuramos (case-insensitive)
        String foundLower = foundSSID;
        foundLower.toLowerCase();
        String targetLower = ssid;
        targetLower.toLowerCase();
        
        if (foundLower.indexOf(targetLower) >= 0 || targetLower.indexOf(foundLower) >= 0) {
          Serial.printf("    ⚠️ SIMILAR a '%s' - pode ser a rede procurada?\n", ssid.c_str());
        }
      }
    }
    
    Serial.println("\n🔧 Possíveis causas:");
    Serial.println("  1. Rede está em 5GHz apenas (ESP32 só vê 2.4GHz)");
    Serial.println("  2. Nome da rede está incorreto");
    Serial.println("  3. Rede está oculta");
    Serial.println("  4. Roteador não está funcionando");
    
    setLedState(LED_FAST_BLINK);
    return false;
  }
}

// ====== BACKEND FUNCTIONS ======
bool registerDevice(String deviceName) {
  Serial.println("📡 Registrando dispositivo no backend...");
  
  String macAddress = WiFi.macAddress();
  
  HTTPClient http;
  http.begin("http://" + String(BACKEND_SERVER) + ":" + String(BACKEND_PORT) + String(BACKEND_ENDPOINT));
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(10000);
  
  DynamicJsonDocument doc(512);
  doc["mac_address"] = macAddress;
  doc["device_name"] = deviceName;
  doc["ip_address"] = WiFi.localIP().toString();
  doc["wifi_ssid"] = WiFi.SSID();
  doc["registered_at"] = millis();
  doc["status"] = "pending";
  
  String jsonString;
  serializeJson(doc, jsonString);
  
  Serial.printf("📤 Enviando: %s\n", jsonString.c_str());
  
  int httpCode = http.POST(jsonString);
  String response = http.getString();
  http.end();
  
  Serial.printf("📥 Resposta (%d): %s\n", httpCode, response.c_str());
  
  // Analisar resposta do servidor
  if (httpCode == 200 || httpCode == 201) {
    Serial.println("✅ Dispositivo registrado com sucesso!");
    
    // Gerar e salvar tópico MQTT automaticamente: iot/<mac_address>
    String macForTopic = macAddress;
    macForTopic.replace(":", "");
    macForTopic.toLowerCase();
    String autoTopic = "iot/" + macForTopic;
    
    // Salvar configuração MQTT na EEPROM
    saveMqttConfig(autoTopic, String(BACKEND_SERVER), 1883);
    Serial.printf("💾 Tópico MQTT salvo automaticamente: %s\n", autoTopic.c_str());
    
    // LED de sucesso - piscar 3 vezes
    for (int i = 0; i < 3; i++) {
      digitalWrite(LED_MQTT_PIN, HIGH);
      delay(200);
      digitalWrite(LED_MQTT_PIN, LOW);
      delay(200);
    }
    
    // Garantir que LED MQTT esteja desligado após o feedback
    digitalWrite(LED_MQTT_PIN, LOW);
    
    return true;
    
  } else if (httpCode == 409) {
    // Dispositivo já registrado - não é erro
    Serial.println("ℹ️ Dispositivo já registrado no sistema");
    
    // Gerar e verificar tópico MQTT (sempre, independente do status)
    String macForTopic = macAddress;
    macForTopic.replace(":", "");
    macForTopic.toLowerCase();
    String autoTopic = "iot/" + macForTopic;
    
    // Verificar se tópico já está salvo na EEPROM
    if (!loadMqttConfig() || mqtt_topic != autoTopic) {
      saveMqttConfig(autoTopic, String(BACKEND_SERVER), 1883);
      Serial.printf("💾 Tópico MQTT atualizado: %s\n", autoTopic.c_str());
    }
    
    // Verificar se já está ativado
    if (response.indexOf("Ativado") >= 0) {
      Serial.println("✅ Dispositivo já está ativado!");
      
      // LED de sucesso - piscar 2 vezes (diferente do registro novo)
      for (int i = 0; i < 2; i++) {
        digitalWrite(LED_MQTT_PIN, HIGH);
        delay(300);
        digitalWrite(LED_MQTT_PIN, LOW);
        delay(300);
      }
    } else {
      Serial.println("⏳ Dispositivo registrado mas aguardando ativação");
      
      // LED de aviso - 1 piscada longa
      digitalWrite(LED_MQTT_PIN, HIGH);
      delay(1000);
      digitalWrite(LED_MQTT_PIN, LOW);
    }
    
    digitalWrite(LED_MQTT_PIN, LOW);
    return true;
    
  } else if (httpCode == -1) {
    Serial.println("❌ Falha de conexão HTTP - Verificar rede");
    Serial.printf("🔧 Tentativa: http://%s:%d%s\n", BACKEND_SERVER, BACKEND_PORT, BACKEND_ENDPOINT);
    
    // LED de erro de rede - piscar muito rápido 10 vezes
    for (int i = 0; i < 10; i++) {
      digitalWrite(LED_MQTT_PIN, HIGH);
      delay(50);
      digitalWrite(LED_MQTT_PIN, LOW);
      delay(50);
    }
    
    return false;
    
  } else {
    Serial.printf("❌ Erro HTTP (%d): %s\n", httpCode, response.c_str());
    
    // LED de erro - piscar rápido 5 vezes
    for (int i = 0; i < 5; i++) {
      digitalWrite(LED_MQTT_PIN, HIGH);
      delay(100);
      digitalWrite(LED_MQTT_PIN, LOW);
      delay(100);
    }
    
    return false;
  }
}

// ====== WEB HANDLERS ======
void handleRoot() {
  server.send(200, "text/html", html_page);
}

void handleDeviceInfo() {
  String macAddress = WiFi.macAddress();
  
  DynamicJsonDocument doc(256);
  doc["mac_address"] = macAddress;
  doc["device_name"] = AP_SSID;
  doc["firmware_version"] = "WiFi-Only-1.0";
  doc["free_heap"] = ESP.getFreeHeap();
  doc["wifi_mode"] = WiFi.getMode() == WIFI_AP ? "AP" : "STA";
  doc["wifi_configured"] = wifi_configured;
  
  String response;
  serializeJson(doc, response);
  server.send(200, "application/json", response);
}

void handleTestWiFi() {
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
  
  Serial.printf("\n🧪 === TESTE DIRETO (sem scan) ===\n");
  Serial.printf("🎯 SSID: '%s'\n", ssid.c_str());
  Serial.printf("🔐 Senha: '%s'\n", password.c_str());
  
  // Usar a função simplificada que funciona
  if (connectToWiFi(ssid, password)) {
    server.send(200, "application/json", "{\"success\":true,\"message\":\"✅ WiFi conectado com sucesso! Credenciais corretas.\"}");
    
    // Desconectar e voltar para AP
    delay(3000);
    WiFi.disconnect();
    delay(1000);
    WiFi.mode(WIFI_AP);
    WiFi.softAP(AP_SSID, AP_PASSWORD);
    setLedState(LED_FAST_BLINK); // Piscar rápido = modo AP ativo
    
  } else {
    server.send(400, "application/json", "{\"success\":false,\"message\":\"❌ Falha ao conectar. Verifique SSID e senha.\"}");
    
    // Voltar para modo AP
    WiFi.mode(WIFI_AP);
    WiFi.softAP(AP_SSID, AP_PASSWORD);
    setLedState(LED_FAST_BLINK); // Piscar rápido = modo AP ativo
  }
}

void handleScanOnly() {
  Serial.println("\n🔍 === SCAN PURO DE REDES ===");
  
  WiFi.mode(WIFI_STA);
  delay(100);
  
  int networks = WiFi.scanNetworks();
  Serial.printf("📡 Total de redes encontradas: %d\n", networks);
  
  if (networks == 0) {
    Serial.println("❌ Nenhuma rede encontrada!");
    server.send(200, "application/json", "{\"success\":false,\"message\":\"Nenhuma rede encontrada\"}");
    return;
  }
  
  Serial.println("\n📋 === LISTA COMPLETA ===");
  for (int i = 0; i < networks; i++) {
    String ssid = WiFi.SSID(i);
    int32_t rssi = WiFi.RSSI(i);
    wifi_auth_mode_t encType = WiFi.encryptionType(i);
    
    Serial.printf("  %d: '%s' (RSSI: %d, Seg: %d)\n", i, ssid.c_str(), rssi, encType);
    
    // Procurar por "catena" ou similar
    String ssidLower = ssid;
    ssidLower.toLowerCase();
    
    if (ssidLower.indexOf("catena") >= 0) {
      Serial.printf("    🎯 CONTÉM 'catena'!\n");
    }
    if (ssidLower.indexOf("cat") >= 0) {
      Serial.printf("    🐱 CONTÉM 'cat'!\n");
    }
  }
  
  // Voltar para modo AP
  WiFi.mode(WIFI_AP);
  WiFi.softAP(AP_SSID, AP_PASSWORD);
  
  server.send(200, "application/json", "{\"success\":true,\"message\":\"Scan concluído - veja Serial Monitor\"}");
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
  
  String deviceName = doc["device_name"];
  String ssid = doc["ssid"];
  String password = doc["password"];
  
  Serial.printf("🔧 Configurando dispositivo: %s\n", deviceName.c_str());
  Serial.printf("📶 SSID: %s\n", ssid.c_str());
  
  setLedState(LED_SLOW_BLINK); // Piscar lento = tentando conectar WiFi
  
  // Tentar conectar WiFi
  if (connectToWiFi(ssid, password)) {
    // Salvar credenciais
    saveCredentials(ssid, password);
    
    // Registrar no backend
    if (registerDevice(deviceName)) {
      server.send(200, "application/json", "{\"success\":true,\"message\":\"Dispositivo configurado e registrado com sucesso! Reiniciando...\"}");
      
      // Reiniciar após 3 segundos
      delay(3000);
      ESP.restart();
    } else {
      server.send(500, "application/json", "{\"success\":false,\"message\":\"WiFi OK mas falha ao registrar no backend\"}");
    }
  } else {
    server.send(400, "application/json", "{\"success\":false,\"message\":\"Falha ao conectar WiFi. Verifique SSID e senha.\"}");
    
    // Voltar para modo AP
    WiFi.mode(WIFI_AP);
    WiFi.softAP(AP_SSID, AP_PASSWORD);
    setLedState(LED_FAST_BLINK); // Piscar rápido = modo AP ativo
    digitalWrite(LED_MQTT_PIN, LOW); // Garantir LED MQTT desligado
  }
}

// ====== FUNÇÕES MQTT ======
void mqttCallback(char* topic, byte* payload, unsigned int length) {
  // Converter payload para string
  String message = "";
  for (int i = 0; i < length; i++) {
    message += (char)payload[i];
  }
  
  Serial.printf("📨 MQTT recebido no tópico '%s': %s\n", topic, message.c_str());
  
  // Processar comando (JSON ou texto simples)
  String command = "";
  
  // Verificar se é JSON
  if (message.startsWith("{") && message.endsWith("}")) {
    Serial.println("🔍 Detectado formato JSON, processando...");
    
    // Parse JSON simples para extrair comando
    DynamicJsonDocument doc(256);
    DeserializationError error = deserializeJson(doc, message);
    
    if (error) {
      Serial.printf("❌ Erro ao parsear JSON: %s\n", error.c_str());
      command = message; // Fallback para texto simples
    } else {
      // Extrair comando do JSON
      if (doc.containsKey("command")) {
        command = doc["command"].as<String>();
        Serial.printf("📋 Comando extraído do JSON: %s\n", command.c_str());
      } else {
        Serial.println("⚠️ Campo 'command' não encontrado no JSON");
        command = message; // Fallback
      }
    }
  } else {
    // Comando em texto simples
    command = message;
    Serial.printf("📝 Comando em texto simples: %s\n", command.c_str());
  }
  
  // Processar comandos unificados
  if (command == "ligar_led" || command == "led_on" || command == "1") {
    Serial.printf("🔧 Executando: digitalWrite(GPIO %d, HIGH)\n", LED_MQTT_PIN);
    digitalWrite(LED_MQTT_PIN, HIGH);
    
    // Verificar se o comando foi executado
    int estado = digitalRead(LED_MQTT_PIN);
    Serial.printf("🔍 Estado atual do GPIO %d após comando: %d\n", LED_MQTT_PIN, estado);
    
    if (estado == HIGH) {
      Serial.println("✅ GPIO está HIGH - comando executado corretamente!");
    } else {
      Serial.println("❌ GPIO NÃO está HIGH - possível problema de hardware!");
    }
    
    Serial.println("💡 LED MQTT ligado!");
    
    // Enviar confirmação
    String confirmTopic = String(topic) + "/status";
    mqttClient.publish(confirmTopic.c_str(), "led_ligado");
    
  } else if (command == "desligar_led" || command == "led_off" || command == "0") {
    Serial.printf("🔧 Executando: digitalWrite(GPIO %d, LOW)\n", LED_MQTT_PIN);
    digitalWrite(LED_MQTT_PIN, LOW);
    
    // Verificar se o comando foi executado
    int estado = digitalRead(LED_MQTT_PIN);
    Serial.printf("🔍 Estado atual do GPIO %d após comando: %d\n", LED_MQTT_PIN, estado);
    
    if (estado == LOW) {
      Serial.println("✅ GPIO está LOW - comando executado corretamente!");
    } else {
      Serial.println("❌ GPIO NÃO está LOW - possível problema de hardware!");
    }
    
    Serial.println("💡 LED MQTT desligado!");
    
    // Enviar confirmação
    String confirmTopic = String(topic) + "/status";
    mqttClient.publish(confirmTopic.c_str(), "led_desligado");
    
  } else if (command == "status") {
    // Responder com status atual
    String confirmTopic = String(topic) + "/status";
    bool ledState = digitalRead(LED_MQTT_PIN);
    mqttClient.publish(confirmTopic.c_str(), ledState ? "led_ligado" : "led_desligado");
    
  } else if (command == "teste_led" || command == "test") {
    // Teste específico do LED MQTT
    Serial.println("🧪 Executando teste do LED MQTT...");
    for (int i = 0; i < 5; i++) {
      Serial.printf("🔄 Teste LED %d/5\n", i+1);
      digitalWrite(LED_MQTT_PIN, HIGH);
      delay(200);
      digitalWrite(LED_MQTT_PIN, LOW);
      delay(200);
    }
    
    String confirmTopic = String(topic) + "/status";
    mqttClient.publish(confirmTopic.c_str(), "teste_led_concluido");
    
  } else if (command == "diagnostico" || command == "diag") {
    // Diagnóstico completo de hardware
    Serial.println("🔧 === DIAGNÓSTICO DE HARDWARE ===");
    
    // Testar todos os GPIOs
    Serial.printf("📍 Testando GPIO %d (LED_PIN)...\n", LED_PIN);
    digitalWrite(LED_PIN, HIGH);
    delay(500);
    digitalWrite(LED_PIN, LOW);
    
    Serial.printf("📍 Testando GPIO %d (LED_EXTERNAL_PIN)...\n", LED_EXTERNAL_PIN);
    digitalWrite(LED_EXTERNAL_PIN, HIGH);
    delay(500);
    digitalWrite(LED_EXTERNAL_PIN, LOW);
    
    Serial.printf("📍 Testando GPIO %d (LED_MQTT_PIN)...\n", LED_MQTT_PIN);
    
    // Teste detalhado do GPIO 19
    for (int i = 0; i < 3; i++) {
      Serial.printf("🔧 GPIO 19 - Teste %d/3\n", i+1);
      Serial.printf("   -> digitalWrite(19, HIGH)\n");
      digitalWrite(LED_MQTT_PIN, HIGH);
      Serial.printf("   -> Estado lido: %d\n", digitalRead(LED_MQTT_PIN));
      delay(1000);
      
      Serial.printf("   -> digitalWrite(19, LOW)\n");
      digitalWrite(LED_MQTT_PIN, LOW);
      Serial.printf("   -> Estado lido: %d\n", digitalRead(LED_MQTT_PIN));
      delay(1000);
    }
    
    // Teste com outros pinos para comparar
    Serial.println("🔧 Teste comparativo com outros pinos:");
    int testPins[] = {2, 4, 5, 18, 19, 21, 22, 23};
    for (int pin : testPins) {
      Serial.printf("   GPIO %d: ", pin);
      pinMode(pin, OUTPUT);
      digitalWrite(pin, HIGH);
      delay(100);
      Serial.printf("HIGH=%d ", digitalRead(pin));
      digitalWrite(pin, LOW);
      delay(100);
      Serial.printf("LOW=%d\n", digitalRead(pin));
    }
    
    String confirmTopic = String(topic) + "/status";
    mqttClient.publish(confirmTopic.c_str(), "diagnostico_concluido");
    
  } else if (command == "encontrar_led" || command == "find_led") {
    // Teste sequencial para encontrar onde o LED está conectado
    Serial.println("🔍 === ENCONTRANDO LED ===");
    Serial.println("⚠️ Observe qual GPIO acende o LED!");
    
    // Lista de GPIOs comuns do ESP32
    int testPins[] = {2, 4, 5, 12, 13, 14, 15, 16, 17, 18, 19, 21, 22, 23, 25, 26, 27, 32, 33};
    int numPins = sizeof(testPins) / sizeof(testPins[0]);
    
    for (int i = 0; i < numPins; i++) {
      int pin = testPins[i];
      
      Serial.printf("🔧 Testando GPIO %d...\n", pin);
      Serial.printf("   ➡️ Configurando como OUTPUT\n");
      
      pinMode(pin, OUTPUT);
      
      Serial.printf("   ➡️ Enviando HIGH para GPIO %d\n", pin);
      digitalWrite(pin, HIGH);
      
      Serial.printf("   ⏳ GPIO %d está HIGH por 3 segundos - OBSERVE O LED!\n", pin);
      delay(3000);  // 3 segundos para observar
      
      Serial.printf("   ➡️ Enviando LOW para GPIO %d\n", pin);
      digitalWrite(pin, LOW);
      
      Serial.printf("   ⏳ GPIO %d está LOW por 1 segundo\n", pin);
      delay(1000);   // 1 segundo de pausa
      
      Serial.printf("✅ Teste do GPIO %d concluído\n\n", pin);
    }
    
    Serial.println("🎉 Teste completo!");
    Serial.println("📝 Anote qual GPIO acendeu o LED e me informe!");
    
    String confirmTopic = String(topic) + "/status";
    mqttClient.publish(confirmTopic.c_str(), "encontrar_led_concluido");
    
  } else if (command == "detectar_gpio" || command == "detect_gpio") {
    // COMANDO DESABILITADO - causava reset no ESP32 S3
    Serial.println("❌ === COMANDO DESABILITADO ===");
    Serial.println("🚫 Este comando causa reset no ESP32 S3");
    Serial.println("✅ Use o comando seguro: 'encontrar_led'");
    Serial.println("📝 Exemplo: mosquitto_pub -h 10.102.0.103 -t iot/3c8427c849f0 -m encontrar_led");
    
    String confirmTopic = String(topic) + "/status";
    mqttClient.publish(confirmTopic.c_str(), "comando_desabilitado_use_encontrar_led");
    
  } else if (command.startsWith("testar_gpio_")) {
    // Comando para testar um GPIO específico: testar_gpio_2, testar_gpio_16, etc.
    String pinStr = command.substring(12); // Remove "testar_gpio_"
    int pin = pinStr.toInt();
    
    if (pin >= 0 && pin <= 39) {
      Serial.printf("🔧 Testando especificamente GPIO %d\n", pin);
      
      pinMode(pin, OUTPUT);
      
      for (int i = 0; i < 5; i++) {
        Serial.printf("   🔄 Ciclo %d/5 - GPIO %d HIGH\n", i+1, pin);
        digitalWrite(pin, HIGH);
        delay(500);
        
        Serial.printf("   🔄 Ciclo %d/5 - GPIO %d LOW\n", i+1, pin);
        digitalWrite(pin, LOW);
        delay(500);
      }
      
      Serial.printf("✅ Teste do GPIO %d concluído\n", pin);
      
      String confirmTopic = String(topic) + "/status";
      String response = "gpio_" + String(pin) + "_testado";
      mqttClient.publish(confirmTopic.c_str(), response.c_str());
    } else {
      Serial.printf("❌ GPIO inválido: %d (deve ser 0-39)\n", pin);
    }
    
  } else {
    Serial.printf("⚠️ Comando não reconhecido: '%s'\n", command.c_str());
    Serial.println("📚 Comandos válidos:");
    Serial.println("   Controle: ligar_led, desligar_led, status, 1, 0");
    Serial.println("   Teste: teste_led, diagnostico, encontrar_led, detectar_gpio");
    Serial.println("   GPIO específico: testar_gpio_2, testar_gpio_16, testar_gpio_19");
    Serial.println("   JSON: {\"command\":\"led_on\"}, {\"command\":\"detect_gpio\"}");
  }
  
  // LED de notificação - piscar LED_MQTT_PIN para indicar mensagem recebida
  Serial.println("📳 Notificação: mensagem MQTT recebida!");
  bool originalState = digitalRead(LED_MQTT_PIN); // Salvar estado original
  
  for (int i = 0; i < 3; i++) {
    digitalWrite(LED_MQTT_PIN, HIGH);
    delay(150);
    digitalWrite(LED_MQTT_PIN, LOW);
    delay(150);
  }
  
  // Restaurar estado original do LED
  digitalWrite(LED_MQTT_PIN, originalState);
}

void connectMQTT() {
  if (!mqtt_topic.length() || !mqtt_broker.length()) {
    Serial.println("⚠️ Configuração MQTT não disponível");
    return;
  }
  
  Serial.printf("🔧 Configurando servidor MQTT: %s:%d\n", mqtt_broker.c_str(), mqtt_port);
  mqttClient.setServer(mqtt_broker.c_str(), mqtt_port);
  mqttClient.setCallback(mqttCallback);
  
  // Tentar conectar
  String clientId = "ESP32-" + WiFi.macAddress();
  Serial.printf("🆔 Client ID: %s\n", clientId.c_str());
  
  Serial.printf("🔌 Conectando ao MQTT broker %s:%d...\n", mqtt_broker.c_str(), mqtt_port);
  Serial.println("⏳ Aguardando conexão MQTT...");
  
  if (mqttClient.connect(clientId.c_str())) {
    Serial.println("✅ CONECTADO AO MQTT COM SUCESSO!");
    Serial.printf("📡 Status da conexão: %d\n", mqttClient.state());
    
    // Subscrever ao tópico
    bool subscribed = mqttClient.subscribe(mqtt_topic.c_str());
    if (subscribed) {
      Serial.printf("✅ Subscrito ao tópico: %s\n", mqtt_topic.c_str());
    } else {
      Serial.printf("❌ FALHA ao subscrever tópico: %s\n", mqtt_topic.c_str());
    }
    
    // Enviar mensagem de status
    String statusTopic = mqtt_topic + "/status";
    bool published = mqttClient.publish(statusTopic.c_str(), "online");
    if (published) {
      Serial.printf("✅ Status 'online' enviado para: %s\n", statusTopic.c_str());
    } else {
      Serial.printf("❌ FALHA ao enviar status para: %s\n", statusTopic.c_str());
    }
    
    Serial.println("🎉 MQTT TOTALMENTE CONFIGURADO E PRONTO!");
    
  } else {
    int state = mqttClient.state();
    Serial.printf("❌ FALHA NA CONEXÃO MQTT!\n");
    Serial.printf("🔍 Código de erro: %d\n", state);
    Serial.println("📚 Códigos de erro MQTT:");
    Serial.println("   -4: MQTT_CONNECTION_TIMEOUT");
    Serial.println("   -3: MQTT_CONNECTION_LOST");
    Serial.println("   -2: MQTT_CONNECT_FAILED");
    Serial.println("   -1: MQTT_DISCONNECTED");
    Serial.println("    0: MQTT_CONNECTED");
    Serial.println("    1: MQTT_CONNECT_BAD_PROTOCOL");
    Serial.println("    2: MQTT_CONNECT_BAD_CLIENT_ID");
    Serial.println("    3: MQTT_CONNECT_UNAVAILABLE");
    Serial.println("    4: MQTT_CONNECT_BAD_CREDENTIALS");
    Serial.println("    5: MQTT_CONNECT_UNAUTHORIZED");
    
    // Tentar diagnóstico de rede
    Serial.printf("🌐 Testando conectividade com %s...\n", mqtt_broker.c_str());
  }
}

void maintainMQTT() {
  if (WiFi.status() == WL_CONNECTED && mqtt_topic.length() > 0) {
    if (!mqttClient.connected()) {
      Serial.println("⚠️ MQTT desconectado! Tentando reconectar...");
      connectMQTT();
    } else {
      // MQTT conectado - manter ativo
      mqttClient.loop();
      
      // Log periódico de status (a cada 30 segundos)
      static unsigned long lastStatusLog = 0;
      if (millis() - lastStatusLog > 30000) {
        Serial.printf("✅ MQTT ativo - Estado: %d, Tópico: %s\n", mqttClient.state(), mqtt_topic.c_str());
        lastStatusLog = millis();
      }
    }
  } else {
    if (WiFi.status() != WL_CONNECTED) {
      static unsigned long lastWifiLog = 0;
      if (millis() - lastWifiLog > 5000) {
        Serial.println("⚠️ WiFi desconectado - MQTT indisponível");
        lastWifiLog = millis();
      }
    }
  }
}

// ====== SETUP E LOOP ======
void setup() {
  Serial.begin(115200);
  Serial.println();
  Serial.println("🌐 ESP32 WiFi Manager - Versão Simplificada");
  Serial.println("==========================================");
  
  // Configurar LEDs e botão
  pinMode(LED_PIN, OUTPUT);
  pinMode(LED_EXTERNAL_PIN, OUTPUT);
  pinMode(LED_MQTT_PIN, OUTPUT);
  pinMode(BUTTON_PIN, INPUT_PULLUP);
  
  // Teste LED
  Serial.println("🧪 Testando LEDs...");
  Serial.printf("📍 LED_PIN (GPIO %d)\n", LED_PIN);
  Serial.printf("📍 LED_EXTERNAL_PIN (GPIO %d)\n", LED_EXTERNAL_PIN);
  Serial.printf("📍 LED_MQTT_PIN (GPIO %d)\n", LED_MQTT_PIN);
  
  for (int i = 1; i <= 3; i++) {
    Serial.printf("🔄 Teste %d/3 - Ligando LEDs...\n", i);
    digitalWrite(LED_PIN, HIGH);
    digitalWrite(LED_EXTERNAL_PIN, HIGH);
    digitalWrite(LED_MQTT_PIN, HIGH);
    delay(300);
    
    Serial.printf("🔄 Teste %d/3 - Desligando LEDs...\n", i);
    digitalWrite(LED_PIN, LOW);
    digitalWrite(LED_EXTERNAL_PIN, LOW);
    digitalWrite(LED_MQTT_PIN, LOW);
    delay(300);
  }
  Serial.println("✅ Teste de LEDs concluído!");
  
  // Inicializar EEPROM
  EEPROM.begin(EEPROM_SIZE);
  
  // Tentar carregar credenciais salvas
  if (loadCredentials()) {
    Serial.println("🔄 Tentando conectar com credenciais salvas...");
    setLedState(LED_SLOW_BLINK); // Indicar tentativa de conexão
    
    if (connectToWiFi(saved_ssid, saved_password)) {
      Serial.println("✅ Conectado com credenciais salvas!");
      setLedState(LED_ON);  // LED fixo = conectado
      digitalWrite(LED_MQTT_PIN, LOW); // Garantir que LED MQTT esteja desligado
      
      // Carregar configuração MQTT e conectar
      if (loadMqttConfig()) {
        Serial.println("🔌 Configuração MQTT encontrada, conectando...");
        delay(2000); // Aguardar estabilização da conexão WiFi
        connectMQTT();
      } else {
        Serial.println("⚠️ Nenhuma configuração MQTT encontrada");
      }
      
      // Modo normal - apenas piscar para indicar que está funcionando
      return;
    } else {
      Serial.println("❌ Falha com credenciais salvas, iniciando modo AP");
      setLedState(LED_FAST_BLINK); // Piscar rápido = erro de conexão
      delay(2000); // Mostrar erro por 2 segundos
    }
  }
  
  // Iniciar modo AP para configuração
  Serial.println("📡 Iniciando modo AP...");
  WiFi.mode(WIFI_AP);
  WiFi.softAP(AP_SSID, AP_PASSWORD);
  
  setLedState(LED_FAST_BLINK); // Piscar rápido = modo AP ativo
  digitalWrite(LED_MQTT_PIN, LOW); // Garantir que LED MQTT esteja desligado
  
  IPAddress apIP = WiFi.softAPIP();
  Serial.printf("✅ AP ativo: %s\n", AP_SSID);
  Serial.printf("🌐 Acesse: http://%s:5000\n", apIP.toString().c_str());
  
  // Configurar DNS Server
  dnsServer.start(53, "*", apIP);
  
  // Configurar rotas
  server.on("/", handleRoot);
  server.on("/api/device-info", HTTP_GET, handleDeviceInfo);
  server.on("/api/configure", HTTP_POST, handleConfigure);
  server.on("/api/test-wifi", HTTP_POST, handleTestWiFi);  // 🧪 Rota de teste
  server.on("/api/scan-only", HTTP_GET, handleScanOnly);   // 🔍 Rota scan
  server.onNotFound(handleRoot);
  
  server.begin();
  Serial.println("🚀 Servidor web iniciado!");
  Serial.println("🔗 Conecte ao WiFi " + String(AP_SSID) + " e acesse http://192.168.4.1:5000");
}

void loop() {
  if (WiFi.getMode() == WIFI_AP) {
    // Modo AP - gerenciar servidor web
    dnsServer.processNextRequest();
    server.handleClient();
  } else if (WiFi.getMode() == WIFI_STA) {
    // Modo STA - verificar se ainda está conectado
    if (WiFi.status() == WL_CONNECTED) {
      // Conectado - LED fixo ligado
      if (led_state != LED_ON) {
        setLedState(LED_ON);
        digitalWrite(LED_MQTT_PIN, LOW); // Garantir LED MQTT desligado
      }
      
      // Manter conexão MQTT
      maintainMQTT();
      
    } else {
      // Perdeu conexão - piscar rápido
      if (led_state != LED_FAST_BLINK) {
        setLedState(LED_FAST_BLINK);
        Serial.println("⚠️ Conexão WiFi perdida!");
      }
    }
  }
  
  updateLed();
  
  // Reset com botão (5 segundos)
  static unsigned long buttonStart = 0;
  static bool buttonPressed = false;
  
  if (digitalRead(BUTTON_PIN) == LOW) {
    if (!buttonPressed) {
      buttonPressed = true;
      buttonStart = millis();
    } else if (millis() - buttonStart >= 5000) {
      Serial.println("🔄 Reset via botão - Limpando EEPROM");
      
      // Limpar EEPROM
      for (int i = 0; i < EEPROM_SIZE; i++) {
        EEPROM.write(i, 0);
      }
      EEPROM.commit();
      
      ESP.restart();
    }
  } else {
    buttonPressed = false;
  }
  
  delay(10);
} 