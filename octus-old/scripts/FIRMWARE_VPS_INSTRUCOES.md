# 🚀 Firmware ESP32 Atualizado para VPS

## 📋 Resumo das Alterações

O firmware foi **atualizado** para conectar diretamente à VPS em `181.215.135.118` ao invés do IP local `10.102.0.101`.

### 🔄 Principais Mudanças

| Configuração | Valor Anterior | Valor Novo (VPS) |
|--------------|---------------|------------------|
| **Backend Server** | `10.102.0.101` | `181.215.135.118` |
| **Backend Port** | `8000` | `8000` |
| **MQTT Broker** | `10.102.0.101` | `181.215.135.118` |
| **MQTT Port** | `1883` | `1883` |

## 📁 Arquivos

- **📄 `esp32_wifi_mqtt_manager_vps.ino`** - Firmware atualizado para VPS
- **📂 Original:** `/mqtt/firmware-final/esp32_wifi_mqtt_manager_final/`

## 🔧 Como Usar

### 1. **Download do Firmware**
```bash
# Da VPS, baixe o arquivo:
wget http://181.215.135.118:8000/esp32_wifi_mqtt_manager_vps.ino
# ou acesse via navegador
```

### 2. **Arduino IDE**
1. Abra o Arduino IDE
2. Carregue o arquivo `esp32_wifi_mqtt_manager_vps.ino`
3. Configure para ESP32-S3:
   - **Board:** ESP32S3 Dev Module
   - **CPU Frequency:** 240MHz
   - **Flash Size:** 4MB
   - **Partition Scheme:** Default 4MB with spiffs

### 3. **Bibliotecas Necessárias**
Instale via Library Manager:
- `WiFi` (built-in)
- `WebServer` (built-in)  
- `DNSServer` (built-in)
- `EEPROM` (built-in)
- `ArduinoJson` by Benoit Blanchon
- `PubSubClient` by Nick O'Leary
- `HTTPClient` (built-in)

### 4. **Upload para ESP32**
1. Conecte o ESP32 via USB
2. Selecione a porta COM correta
3. Clique em **Upload** (Ctrl+U)

## 🌐 URLs de Teste

Após configurar o dispositivo, teste os endpoints:

### 📡 **Backend (API)**
- **URL:** http://181.215.135.118:8000
- **Endpoint Registro:** http://181.215.135.118:8000/api/devices/pending

### 🖥️ **Dashboard Web**  
- **URL:** http://181.215.135.118:8001
- **Login:** admin@sistema.com / admin123

### 📱 **App Configuração**
- **URL:** http://181.215.135.118:8002

### 📡 **MQTT Broker**
- **Host:** 181.215.135.118
- **Port:** 1883
- **Tópico Exemplo:** `iot/3c8427c849f0`

## 🧪 Teste de Comandos MQTT

### Via Mosquitto Cliente (Linux/Mac):
```bash
# Publicar comando ligar LED
mosquitto_pub -h 181.215.135.118 -t "iot/SEU_MAC_SEM_DOIS_PONTOS" -m "led_on"

# Publicar comando desligar LED  
mosquitto_pub -h 181.215.135.118 -t "iot/SEU_MAC_SEM_DOIS_PONTOS" -m "led_off"

# Comando JSON
mosquitto_pub -h 181.215.135.118 -t "iot/SEU_MAC_SEM_DOIS_PONTOS" -m '{"command":"led_on"}'

# Verificar status
mosquitto_pub -h 181.215.135.118 -t "iot/SEU_MAC_SEM_DOIS_PONTOS" -m "status"
```

### Via Dashboard Web:
1. Acesse http://181.215.135.118:8001
2. Faça login com admin@sistema.com / admin123
3. Vá em **Tópicos** 
4. Clique em **🎮 Testar MQTT** no tópico do seu dispositivo

## 🔍 Diagnóstico

### LEDs de Status:
- **LED Interno + GPIO16:** Status WiFi (piscar = conectando, fixo = conectado)
- **GPIO19:** Notificações MQTT (piscar ao receber comandos)

### Serial Monitor (115200 baud):
- Mensagens detalhadas de conexão
- Status MQTT em tempo real  
- Confirmação de comandos recebidos

### Comandos de Teste via MQTT:
- `encontrar_led` - Testa todos os GPIOs para encontrar LED
- `diagnostico` - Executa diagnóstico completo
- `testar_gpio_19` - Testa GPIO específico

## ❗ Solução de Problemas

### Problema: Device não aparece no dashboard
**Solução:** 
1. Verifique se registrou corretamente via Serial Monitor
2. Acesse http://181.215.135.118:8001 e procure em dispositivos pendentes
3. Ative o dispositivo se necessário

### Problema: MQTT não conecta
**Solução:**
1. Verificar se WiFi está conectado (LED fixo)
2. Confirmar que MQTT broker está funcionando
3. Testar comando: `telnet 181.215.135.118 1883`

### Problema: Comandos não funcionam
**Solução:**
1. Verificar tópico correto (MAC sem dois-pontos)
2. Usar comandos: `led_on`, `led_off`, `status`
3. Observar Serial Monitor para debug

## 🎯 Próximos Passos

1. ✅ **Upload do firmware atualizado**
2. ✅ **Configuração WiFi via AP (IOT-Zontec / 12345678)**
3. ✅ **Registro automático na VPS**
4. ✅ **Teste comandos MQTT**
5. ✅ **Monitoramento via Dashboard**

---

🎉 **Sistema IoT completamente funcional com VPS!** 