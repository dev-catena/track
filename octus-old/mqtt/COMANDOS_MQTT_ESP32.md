# 🔌 Comandos MQTT para ESP32

## 📋 Visão Geral
O ESP32 agora possui funcionalidade MQTT completa, permitindo controle remoto de LEDs e monitoramento via mensagens MQTT.

---

## 🔧 **Configuração dos LEDs**

### 📍 **Pinos Configurados:**
- **GPIO 19**: LED de notificações MQTT (controlável via MQTT)
- **GPIO 16**: LED externo de status de conexão
- **GPIO 48**: LED interno ESP32-S3

### 🎯 **LED Principal (GPIO 19):**
Este é o LED que pode ser controlado via comandos MQTT.

---

## 📡 **Tópico MQTT**

### 🔸 **Formato do Tópico:**
```
iot/<mac_address>
```

### 🔸 **Exemplo:**
```
MAC: 3C:84:27:C8:49:F0
Tópico: iot/3c8427c849f0
```

---

## 💬 **Comandos Disponíveis**

### 1. **Ligar LED** 💡

#### **Formato Texto Simples:**
```bash
# Comando 1
mosquitto_pub -h 10.102.0.103 -t "iot/3c8427c849f0" -m "ligar_led"

# Comando 2 (alternativo)
mosquitto_pub -h 10.102.0.103 -t "iot/3c8427c849f0" -m "1"
```

#### **Formato JSON:**
```bash
# Comando JSON
mosquitto_pub -h 10.102.0.103 -t "iot/3c8427c849f0" -m '{"command":"led_on"}'
```

**Resultado:**
- LED GPIO 19 liga
- ESP32 responde no tópico `iot/3c8427c849f0/status` com `led_ligado`

### 2. **Desligar LED** 🌑

#### **Formato Texto Simples:**
```bash
# Comando 1
mosquitto_pub -h 10.102.0.103 -t "iot/3c8427c849f0" -m "desligar_led"

# Comando 2 (alternativo)
mosquitto_pub -h 10.102.0.103 -t "iot/3c8427c849f0" -m "0"
```

#### **Formato JSON:**
```bash
# Comando JSON
mosquitto_pub -h 10.102.0.103 -t "iot/3c8427c849f0" -m '{"command":"led_off"}'
```

**Resultado:**
- LED GPIO 19 desliga
- ESP32 responde no tópico `iot/3c8427c849f0/status` com `led_desligado`

### 3. **Consultar Status** 📊
```bash
# Texto simples
mosquitto_pub -h 10.102.0.103 -t "iot/3c8427c849f0" -m "status"

# Formato JSON
mosquitto_pub -h 10.102.0.103 -t "iot/3c8427c849f0" -m '{"command":"status"}'
```

**Resultado:**
- ESP32 responde no tópico `iot/3c8427c849f0/status` com:
  - `led_ligado` (se LED estiver ligado)
  - `led_desligado` (se LED estiver desligado)

### 4. **Teste do LED** 🧪
```bash
# Texto simples
mosquitto_pub -h 10.102.0.103 -t "iot/3c8427c849f0" -m "teste_led"

# Formato JSON
mosquitto_pub -h 10.102.0.103 -t "iot/3c8427c849f0" -m '{"command":"test"}'
```

**Resultado:**
- LED GPIO 19 pisca 5 vezes para teste
- ESP32 responde com `teste_led_concluido`

---

## 📺 **Monitorar Respostas**

### **Escutar Status do Dispositivo:**
```bash
mosquitto_sub -h 10.102.0.103 -t "iot/3c8427c849f0/status"
```

### **Escutar Todos os Tópicos do Dispositivo:**
```bash
mosquitto_sub -h 10.102.0.103 -t "iot/3c8427c849f0/+"
```

### **Escutar Todos os Dispositivos IoT:**
```bash
mosquitto_sub -h 10.102.0.103 -t "iot/+/+"
```

---

## 🔍 **Logs no Serial Monitor**

### **Conexão MQTT:**
```
🔌 Conectando ao MQTT broker 10.102.0.103:1883...
✅ Conectado ao MQTT!
📺 Subscrito ao tópico: iot/3c8427c849f0
```

### **Comando Recebido:**
```
📨 MQTT recebido no tópico 'iot/3c8427c849f0': ligar_led
💡 LED MQTT ligado!
```

### **Comando Desconhecido:**
```
📨 MQTT recebido no tópico 'iot/3c8427c849f0': comando_invalido
⚠️ Comando MQTT não reconhecido: comando_invalido
```

---

## 🔄 **Comportamento do Sistema**

### **Ao Receber Mensagem MQTT:**
1. **Processa** o comando
2. **Executa** a ação (liga/desliga LED)
3. **Envia confirmação** no tópico `/status`
4. **LED de notificação** (GPIO 16) pisca 3 vezes rapidamente
5. **Log no Serial** Monitor

### **Status de Conexão:**
- **Online**: Envia `online` no tópico `/status` ao conectar
- **Reconexão automática**: Se perder conexão MQTT, tenta reconectar
- **Heartbeat**: Mantém conexão ativa com `mqttClient.loop()`

---

## 🧪 **Teste Completo**

### **1. Verificar MAC do Dispositivo:**
```bash
# Acessar Serial Monitor ou interface web para ver o MAC
```

### **2. Ligar LED:**
```bash
mosquitto_pub -h 10.102.0.103 -t "iot/SEU_MAC_AQUI" -m "ligar_led"
```

### **3. Monitorar Resposta:**
```bash
mosquitto_sub -h 10.102.0.103 -t "iot/SEU_MAC_AQUI/status"
```

### **4. Verificar LED:**
- LED GPIO 19 deve estar ligado
- LED GPIO 16 deve piscar 3 vezes (notificação)

### **5. Desligar LED:**
```bash
mosquitto_pub -h 10.102.0.103 -t "iot/SEU_MAC_AQUI" -m "desligar_led"
```

---

## ⚠️ **Solução de Problemas**

### **LED Não Responde:**
1. **Verificar conexão MQTT** no Serial Monitor
2. **Confirmar tópico correto** (usar MAC sem dois pontos, em minúsculas)
3. **Verificar broker MQTT** está acessível
4. **Conferir pino GPIO 19** está conectado corretamente

### **Não Conecta ao MQTT:**
1. **Verificar IP do broker** (10.102.0.103)
2. **Confirmar porta 1883** está aberta
3. **Verificar WiFi** está conectado
4. **Recarregar firmware** se necessário

### **Tópico Incorreto:**
- Usar o formato exato: `iot/3c8427c849f0` (sem dois pontos no MAC)
- MAC deve estar em **minúsculas**
- Verificar o MAC real do dispositivo

---

## 📚 **Bibliotecas Necessárias**

Para usar este firmware, instale no Arduino IDE:

```
PubSubClient (by Nick O'Leary)
ArduinoJson (by Benoit Blanchon)
```

---

## 🎯 **Sistema Funcional!**

Agora o ESP32 possui:
- ✅ **Conexão WiFi automática**
- ✅ **Registro no backend**
- ✅ **Tópico MQTT automático**
- ✅ **Controle de LED via MQTT**
- ✅ **Feedback visual e de status**
- ✅ **Reconexão automática**

**🚀 O sistema IoT está completamente funcional e pronto para produção!** 