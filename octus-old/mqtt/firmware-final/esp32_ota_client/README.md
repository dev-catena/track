# 🔧 ESP32 OTA Client - Sistema MQTT IoT

Cliente ESP32 para receber atualizações de firmware Over-The-Air (OTA) via MQTT.

## 📋 Funcionalidades

- ✅ **Conectividade WiFi** automática com reconexão
- ✅ **Cliente MQTT** com inscrição automática em tópicos OTA
- ✅ **Recebimento de comandos OTA** via MQTT
- ✅ **Download de firmware** via HTTP do servidor nginx
- ✅ **Verificação de integridade** com checksums MD5
- ✅ **Instalação automática** de firmware
- ✅ **Feedback de status** via MQTT
- ✅ **Indicadores LED** para status visual
- ✅ **Heartbeat** periódico com informações do sistema
- ✅ **Configuração flexível** por tipo de dispositivo

## 🔌 Hardware Requerido

### Componentes Básicos
- **ESP32** (qualquer variante)
- **4 LEDs** para indicadores de status
- **Resistores** 220Ω para os LEDs
- **Protoboard** ou PCB personalizada

### Pinos Utilizados (configuráveis)
```
GPIO 2  - LED Status (azul, interno)
GPIO 16 - LED WiFi (verde)
GPIO 17 - LED MQTT (amarelo)  
GPIO 18 - LED OTA (vermelho)
```

### Esquema de Ligação
```
ESP32          LED + Resistor
GPIO 16 ──────┤ LED Verde ├────── GND (WiFi)
GPIO 17 ──────┤ LED Amarelo ├──── GND (MQTT)
GPIO 18 ──────┤ LED Vermelho ├─── GND (OTA)
```

## ⚙️ Configuração

### 1. Configurar Parâmetros

Edite o arquivo `config.h`:

```cpp
// Tipo de dispositivo
#define DEVICE_TYPE_CONFIG "sensor_de_temperatura"

// Departamento
#define DEPARTMENT_CONFIG "producao"

// Versão do firmware
#define FIRMWARE_VERSION_CONFIG "1.0.0"

// WiFi
#define WIFI_SSID_CONFIG "SUA_REDE_WIFI"
#define WIFI_PASSWORD_CONFIG "SUA_SENHA_WIFI"

// MQTT Broker
#define MQTT_SERVER_CONFIG "10.102.0.103"
```

### 2. Compilar e Upload

1. Abra o Arduino IDE
2. Instale as bibliotecas necessárias:
   - **PubSubClient** (para MQTT)
   - **ArduinoJson** (para parsing JSON)
   - **HTTPClient** (inclusa no ESP32)
3. Abra `esp32_ota_client.ino`
4. Compile e faça upload para o ESP32

### 3. Bibliotecas Necessárias

```json
{
  "dependencies": {
    "PubSubClient": "^2.8.0",
    "ArduinoJson": "^6.21.0",
    "ESP32": "^2.0.0"
  }
}
```

## 📡 Protocolo MQTT

### Tópicos Utilizados

O dispositivo utiliza os seguintes tópicos MQTT:

```
Base: iot/{departamento}/{tipo_dispositivo}/{device_id}

Subscrições (recebe):
- iot/producao/sensor_temperatura/A1B2C3D4E5F6/ota

Publicações (envia):
- iot/producao/sensor_temperatura/A1B2C3D4E5F6/status
- iot/producao/sensor_temperatura/A1B2C3D4E5F6/feedback
```

### Formato dos Comandos OTA

**Comando recebido** (tópico `/ota`):
```json
{
  "command": "ota_update",
  "ota_id": "123",
  "firmware_version": "1.1.0",
  "firmware_url": "http://firmware.iot.local/firmware/sensor_temperatura/latest/firmware.bin",
  "checksum_url": "http://firmware.iot.local/firmware/sensor_temperatura/latest/checksum.md5",
  "checksum_md5": "a1b2c3d4e5f6...",
  "size_bytes": 1048576,
  "force_update": false,
  "timeout_minutes": 30,
  "timestamp": "2025-09-14T19:30:00Z"
}
```

**Feedback enviado** (tópico `/feedback`):
```json
{
  "ota_id": "123",
  "device_id": "A1B2C3D4E5F6",
  "status": "success|failed|in_progress",
  "message": "Firmware atualizado com sucesso",
  "progress_percent": 85,
  "firmware_version": "1.1.0",
  "timestamp": 1694723400000
}
```

**Heartbeat** (tópico `/status`):
```json
{
  "device_id": "A1B2C3D4E5F6",
  "device_type": "sensor_de_temperatura",
  "department": "producao",
  "firmware_version": "1.0.0",
  "uptime": 3600000,
  "free_heap": 45000,
  "wifi_rssi": -65,
  "timestamp": 1694723400000
}
```

## 🔄 Fluxo de Atualização OTA

1. **Trigger**: Dashboard envia comando OTA via API
2. **Comando MQTT**: Backend publica comando no tópico `/ota` do dispositivo
3. **Recebimento**: ESP32 recebe e valida o comando
4. **Download**: ESP32 baixa firmware via HTTP do nginx
5. **Verificação**: Checksum MD5 é validado
6. **Instalação**: Firmware é gravado no flash
7. **Feedback**: Status é enviado via MQTT
8. **Reinício**: ESP32 reinicia com novo firmware

## 🚨 Indicadores LED

| LED | Cor | Estado | Significado |
|-----|-----|--------|-------------|
| WiFi | 🟢 Verde | Ligado | WiFi conectado |
| WiFi | 🟢 Verde | Piscando | Tentando conectar |
| WiFi | 🟢 Verde | Apagado | WiFi desconectado |
| MQTT | 🟡 Amarelo | Ligado | MQTT conectado |
| MQTT | 🟡 Amarelo | Apagado | MQTT desconectado |
| OTA | 🔴 Vermelho | Ligado | OTA em progresso |
| OTA | 🔴 Vermelho | Piscando | Download em andamento |
| OTA | 🔴 Vermelho | Piscadas rápidas (5x) | OTA concluído |
| OTA | 🔴 Vermelho | Piscadas rápidas (10x) | OTA falhou |
| Status | 🔵 Azul | 3 piscadas | Sistema inicializado |

## 🐛 Debug e Monitoramento

### Serial Monitor

Conecte-se ao Serial Monitor (115200 baud) para ver logs detalhados:

```
🚀 ESP32 OTA Client - Sistema MQTT IoT
=======================================
🔧 Hardware inicializado
🆔 Device ID: A1B2C3D4E5F6
🌐 Conectando WiFi: MinhaRede........ ✅ Conectado!
📍 IP: 192.168.1.100
📡 Conectando MQTT: 10.102.0.103:1883 ✅ Conectado!
📩 Inscrito em: iot/producao/sensor_temperatura/A1B2C3D4E5F6/ota
✅ Inicialização concluída!
```

### Mensagens de Debug OTA

```
📨 MQTT recebido [iot/producao/sensor_temperatura/A1B2C3D4E5F6/ota]: {"command":"ota_update"...}
🔄 Processando comando OTA...
🆔 OTA ID: 123
🔄 Versão alvo: 1.1.0
📦 URL firmware: http://firmware.iot.local/firmware/sensor_temperatura/latest/firmware.bin
🚀 Processo OTA iniciado!
⬇️ Iniciando download do firmware...
📦 Tamanho do firmware: 1048576 bytes
📥 Fazendo download e instalação...
📊 Progresso: 10%
📊 Progresso: 20%
...
🔐 MD5 calculado: a1b2c3d4e5f6789...
🔐 MD5 esperado: a1b2c3d4e5f6789...
✅ Checksum MD5 verificado!
✅ Firmware instalado com sucesso!
📤 Feedback enviado: success
```

## 🔧 Customização por Tipo de Dispositivo

### Sensor de Temperatura
```cpp
#define DEVICE_TYPE_CONFIG "sensor_de_temperatura"
#define ENABLE_TEMPERATURE_SENSOR true
#define TEMPERATURE_PIN 4
```

### LED de Controle
```cpp
#define DEVICE_TYPE_CONFIG "led_de_controle"
#define ENABLE_LED_CONTROL true
#define LED_CONTROL_PIN 19
```

### Relé de Controle
```cpp
#define DEVICE_TYPE_CONFIG "rele_de_controle"
#define ENABLE_RELAY_CONTROL true
#define RELAY_PIN 21
```

### Sensor de Movimento
```cpp
#define DEVICE_TYPE_CONFIG "sensor_de_movimento"
#define ENABLE_MOTION_SENSOR true
#define MOTION_PIN 4
```

## ⚠️ Solução de Problemas

### WiFi não conecta
1. Verificar SSID e senha
2. Verificar sinal WiFi
3. Verificar se a rede é 2.4GHz

### MQTT não conecta
1. Verificar IP do broker
2. Verificar porta (1883)
3. Verificar firewall

### OTA falha
1. Verificar conectividade com servidor nginx
2. Verificar espaço disponível no flash
3. Verificar integridade do arquivo de firmware

### Logs de Erro Comuns
```
❌ Falha na conexão WiFi! - Verificar credenciais
❌ Falha! Código: -2 - Broker MQTT inacessível
❌ Erro HTTP: 404 - Firmware não encontrado
❌ Checksum MD5 não confere - Arquivo corrompido
❌ Erro ao iniciar update: NO_ERROR - Flash insuficiente
```

## 📚 Referências

- [ESP32 Update Library](https://github.com/espressif/arduino-esp32/tree/master/libraries/Update)
- [PubSubClient MQTT](https://github.com/knolleary/pubsubclient)
- [ArduinoJson](https://arduinojson.org/)
- [ESP32 HTTP Client](https://docs.espressif.com/projects/esp-idf/en/latest/esp32/api-reference/protocols/esp_http_client.html)

## 🚀 Próximas Implementações

- [ ] **Portal Captivo** para configuração WiFi
- [ ] **Backup e Rollback** automático
- [ ] **Compressão** de firmware
- [ ] **Assinatura digital** dos firmwares
- [ ] **Delta updates** (apenas diferenças)
- [ ] **Agendamento** de atualizações
- [ ] **Grupos de dispositivos** para updates em lote 