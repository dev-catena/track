# ESP32 Octus Firmware - PlatformIO

Este projeto contém o firmware ESP32 para o sistema Octus IoT.

## 🎯 Firmware Recomendado: `main_production.cpp`

**Versão Oficial para Produção**
- Versão: 1.0.0
- Servidor: 145.223.95.178:8000
- MQTT: 145.223.95.178:1883
- Estável e testado para uso em campo

## 📁 Arquivos Disponíveis

- `src/main.cpp` - Versão ativa (gerada pelo script)
- `src/main_production.cpp` - ⭐ **RECOMENDADO** - Versão para produção
- `src/main_development.cpp` - Versão para desenvolvimento (servidor local)
- `src/main_with_ota.cpp` - Versão experimental com OTA

## ✨ Funcionalidades (Versão Production)

### 🔧 **Conectividade**
- Access Point WiFi integrado (`IOT-Zontec`)
- Servidor web de configuração (192.168.4.1)
- Conexão MQTT estável
- Gerenciamento automático de reconexão

### 💾 **Armazenamento**
- EEPROM para configurações persistentes
- Salva: MAC, tópico MQTT, WiFi credenciais
- Backup automático de configurações

### 🎮 **Controles**
- Botão físico (GPIO 0) com múltiplas funções:
  - 5 segundos: Força modo AP
  - 10 segundos: Limpa EEPROM (reset completo)

### 💡 **LEDs de Status**
- **GPIO 22**: LED WiFi
  - Piscando rápido: Modo AP ativo
  - Piscando lento: Conectando ao WiFi
  - Fixo: WiFi conectado
- **GPIO 19**: LED MQTT (controlável remotamente)
- **GPIO 18**: LED OTA / Slot 1

### 📌 **Diagrama de conexão (Sensores e Lockers)**

| GPIO | Função |
|------|--------|
| 32 | Sensor slot 1 |
| 33 | Sensor slot 2 |
| 34 | Sensor slot 3 |
| 35 | Sensor slot 4 |
| 26 | Sensor slot 5 |
| 27 | Sensor slot 6 |
| 18 | LED locker 1 |
| 5 | LED locker 2 |
| 13 | LED locker 3 |
| 14 | LED locker 4 |
| 15 | LED locker 5 |
| 16 | LED locker 6 |

### 📡 **Comandos MQTT**
```json
// Ligar LED
{"command": "led_on"}

// Desligar LED
{"command": "led_off"}

// Piscar LED
{"command": "led_blink"}
```

## 🚀 Como Usar (Passo a Passo)

### 1. **Ativar Versão de Produção**
```bash
cd /home/darley/track/esp32-firmware
./switch_version.sh production
```

### 2. **Compilar o Firmware**
```bash
pio run
```

### 3. **Fazer Upload para o ESP32**
```bash
# Conecte o ESP32 via USB
pio run -t upload
```

### 4. **Monitorar em Tempo Real**
```bash
pio device monitor
```

### 5. **Firmware de teste (sensores/LEDs)**
LED N acende quando o sensor N está HIGH. Útil para validar conexões físicas.
```bash
pio run -e esp32dev_test -t upload
pio device monitor
```

## 🔧 Configuração do Dispositivo

### Primeira Configuração

1. O ESP32 inicia automaticamente em **Modo AP**
2. Conecte-se ao WiFi: `IOT-Zontec` (senha: `12345678`)
3. Acesse no navegador: `http://192.168.4.1`
4. Preencha o formulário:
   - SSID da rede WiFi
   - Senha do WiFi
   - MAC do dispositivo (ou deixe detectar automaticamente)
5. Clique em "Salvar Configurações"
6. O dispositivo reiniciará e conectará automaticamente

### Reconfiguração

- **Forçar Modo AP**: Segure o botão por 5 segundos
- **Reset Total**: Segure o botão por 10 segundos (apaga todas configurações)

## 🌐 Servidores Configurados

| Ambiente | Backend | MQTT |
|----------|---------|------|
| **Produção** | `145.223.95.178:8000` | `145.223.95.178:1883` |
| Desenvolvimento | `10.102.0.115:8000` | `10.102.0.115:1883` |

## 📋 Versões Disponíveis

### ⭐ `main_production.cpp` (RECOMENDADO)
- ✅ Servidor de produção configurado
- ✅ Estável e testado
- ✅ Ideal para dispositivos em campo
- ✅ Configuração via Access Point
- ✅ Controle MQTT completo

### 🔧 `main_development.cpp`
- Servidor local (10.102.0.115)
- Para testes e desenvolvimento
- Mesmas funcionalidades que production

### 🧪 `main_with_ota.cpp` (Experimental)
- Inclui atualização OTA (Over-The-Air)
- Ainda em desenvolvimento
- Não recomendado para produção

## 🔄 Alternar Entre Versões

```bash
# Produção (recomendado)
./switch_version.sh production

# Desenvolvimento
./switch_version.sh development

# Experimental com OTA
./switch_version.sh ota
```

## 📦 Estrutura do Projeto

```
esp32-octus-platformio/
├── src/
│   ├── main.cpp                  ← Arquivo ativo
│   ├── main_production.cpp       ← ⭐ Versão produção
│   ├── main_development.cpp      ← Versão desenvolvimento
│   └── main_with_ota.cpp         ← Versão experimental
├── platformio.ini                ← Configurações PlatformIO
├── switch_version.sh             ← Script para trocar versões
└── README.md                     ← Este arquivo
```

## 🆘 Solução de Problemas

### ESP32 não conecta ao WiFi
1. Segure o botão por 5s para forçar modo AP
2. Reconecte ao `IOT-Zontec`
3. Verifique as credenciais WiFi

### LED WiFi piscando constantemente
- Dispositivo está tentando conectar
- Verifique se o servidor está online
- Verifique as credenciais WiFi

### Reset completo necessário
1. Segure o botão por 10 segundos
2. Aguarde o LED piscar rapidamente
3. Reconfigure o dispositivo do zero

## 📞 Suporte

Para mais informações sobre o sistema Track, consulte a documentação principal em `/home/darley/track/track/README.md`