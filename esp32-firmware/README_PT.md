# 🚀 Firmware ESP32 Octus - Guia Completo

## ✅ Firmware está PRONTO e COMPILANDO!

**Status:** ✅ **SUCESSO** - Compilação 100% funcional

```
RAM:   [=         ]  14.7% (48 KB de 328 KB)
Flash: [========  ]  75.5% (990 KB de 1.3 MB)
```

## 📂 Estrutura do Projeto

```
esp32-octus-platformio/
├── src/
│   └── main.cpp              ← ÚNICO arquivo compilado (versão production ativa)
│
├── backup_src/               ← Arquivos alternativos (NÃO versionados no git)
│   ├── main_production.cpp   ← Versão RECOMENDADA para produção
│   ├── main_development.cpp  ← Versão para desenvolvimento local
│   └── main_with_ota.cpp     ← Versão experimental com OTA
│
├── switch_version.sh         ← Script para trocar entre versões
├── platformio.ini            ← Configuração do PlatformIO
└── README_PT.md              ← Este arquivo
```

## 🎯 Como Usar

### 1. Compilar (versão atual)

```bash
cd /home/darley/octus/esp32-octus-platformio
pio run
```

### 2. Trocar de Versão

```bash
# Produção (RECOMENDADO - já está ativo)
./switch_version.sh production

# Desenvolvimento (servidor local)
./switch_version.sh development

# Experimental com OTA
./switch_version.sh ota
```

### 3. Fazer Upload no ESP32

```bash
# Conecte o ESP32 via USB primeiro!
pio run -t upload
```

### 4. Monitorar Serial

```bash
pio device monitor
# Para sair: Ctrl+C
```

## 🔧 Versões Disponíveis

### ⭐ main_production.cpp (RECOMENDADO - ATIVO)

**Características:**
- ✅ Servidor: `145.223.95.178:8000`
- ✅ MQTT: `145.223.95.178:1883`
- ✅ Estável e testado
- ✅ Access Point WiFi (IOT-Zontec)
- ✅ Controle via MQTT
- ❌ Sem OTA (atualização via USB apenas)

**Quando usar:**
- Produção / dispositivos em campo
- Instalações definitivas
- Ambientes críticos

---

### 🔧 main_development.cpp

**Características:**
- Servidor: `10.102.0.115:8000` (local)
- MQTT: `10.102.0.115:1883` (local)
- Mesmas funcionalidades que production

**Quando usar:**
- Desenvolvimento local
- Testes em bancada
- Debug

---

### 🧪 main_with_ota.cpp (Experimental)

**Características:**
- Tudo do production +
- Atualização OTA via MQTT
- Download automático de firmware
- Verificação MD5

**Quando usar:**
- Testes de OTA apenas
- NÃO recomendado para produção

## 📡 Comandos MQTT

**Tópico:** `iot-{MAC_SEM_PONTOS}`

```json
// Ligar LED
{"command": "led_on"}

// Desligar LED
{"command": "led_off"}

// Piscar LED
{"command": "led_blink"}
```

**Exemplo:**
```bash
mosquitto_pub -h 145.223.95.178 -t "iot-AA22BB33CC44" -m '{"command":"led_on"}'
```

## 🎮 Configuração Inicial do Dispositivo

1. **Ligue o ESP32** → Entra automaticamente em modo AP
2. **Conecte ao WiFi:** `IOT-Zontec` (senha: `12345678`)
3. **Acesse:** `http://192.168.4.1`
4. **Preencha:**
   - SSID da rede WiFi
   - Senha WiFi
   - MAC (autodetectado)
5. **Salve** → ESP32 reinicia e conecta automaticamente

## 🎯 Botão Físico (GPIO 0)

| Tempo | Ação |
|-------|------|
| **5 segundos** | Força modo AP (para reconfigurar WiFi) |
| **10 segundos** | Reset completo (apaga todas configurações) |

## 💡 LEDs de Status

| LED (GPIO) | Status | Significado |
|------------|--------|-------------|
| **16** | 🔴 Piscando rápido | Modo AP ativo |
| **16** | 🟡 Piscando lento | Conectando ao WiFi |
| **16** | 🟢 Fixo | WiFi conectado |
| **19** | - | LED controlável via MQTT |
| **18** | - | Reservado para futuro |

## ⚠️ Importante: Estrutura src/

**NUNCA** adicione manualmente os arquivos `main_development.cpp`, `main_production.cpp` ou `main_with_ota.cpp` na pasta `src/`!

- ✅ `src/main.cpp` = arquivo ativo (gerenciado pelo script)
- ❌ `src/main_*.cpp` = causam erro de "multiple definition"
- ✅ `backup_src/main_*.cpp` = local correto para versões alternativas

### Se os arquivos voltarem para src/:

```bash
# Mover para backup_src
cd /home/darley/octus/esp32-octus-platformio
mv src/main_development.cpp src/main_production.cpp src/main_with_ota.cpp backup_src/

# Limpar cache
pio run --target clean

# Compilar
pio run
```

## 🆘 Troubleshooting

### Erro: "multiple definition"

**Causa:** Múltiplos arquivos `.cpp` em `src/`

**Solução:**
```bash
# Mover arquivos extras
mv src/main_*.cpp backup_src/

# Verificar que sobrou apenas main.cpp
ls -la src/

# Limpar e recompilar
pio run --target clean && pio run
```

### Erro: Upload failed

**Causa:** ESP32 não detectado

**Solução:**
1. Verifique cabo USB (precisa ser cabo de dados, não só carga)
2. Verifique permissões: `sudo usermod -a -G dialout $USER`
3. Reconecte o ESP32
4. Tente porta serial diferente

### LED piscando constantemente

**Causa:** Não consegue conectar ao WiFi

**Solução:**
1. Segure botão por 5 segundos (força modo AP)
2. Conecte ao `IOT-Zontec`
3. Reconfigure WiFi em `http://192.168.4.1`

## 📚 Documentação Adicional

- **Guia Completo:** `/home/darley/octus/GUIA_FIRMWARE_PRODUCTION.md`
- **Referência Rápida:** `/home/darley/octus/FIRMWARE_REFERENCIA_RAPIDA.md`
- **Comparação de Versões:** `/home/darley/octus/esp32-octus-platformio/FIRMWARE_INFO.md`

## 🔄 Workflow de Desenvolvimento

```bash
# 1. Escolher versão
./switch_version.sh development  # ou production ou ota

# 2. Editar (se necessário)
# Edite backup_src/main_*.cpp conforme necessário

# 3. Copiar para src/main.cpp
./switch_version.sh production

# 4. Compilar
pio run

# 5. Upload
pio run -t upload

# 6. Monitorar
pio device monitor
```

## ✨ Comandos Úteis

```bash
# Ver informações do projeto
pio project metadata

# Limpar completamente
pio run --target clean

# Ver porta serial
pio device list

# Upload e monitor em um comando
pio run -t upload && pio device monitor

# Ver tamanho do firmware
pio run --target size
```

## 🌐 Servidores

| Ambiente | Backend | MQTT |
|----------|---------|------|
| **Produção** | `145.223.95.178:8000` | `145.223.95.178:1883` |
| **Desenvolvimento** | `10.102.0.115:8000` | `10.102.0.115:1883` |

## 📊 Informações Técnicas

| Item | Valor |
|------|-------|
| **Plataforma** | ESP32 (240MHz) |
| **RAM** | 328 KB (48 KB usado = 14.7%) |
| **Flash** | 1.3 MB (990 KB usado = 75.5%) |
| **Framework** | Arduino for ESP32 |
| **Versão Firmware** | 1.0.0 |
| **Biblioteca MQTT** | PubSubClient 2.8.0 |
| **Biblioteca JSON** | ArduinoJson 6.21.5 |

## 🎓 Para Saber Mais

- **PlatformIO:** https://docs.platformio.org/
- **ESP32 Arduino:** https://docs.espressif.com/projects/arduino-esp32/
- **MQTT:** https://mqtt.org/

---

**Sistema Octus IoT** | Versão 1.0.0 | Última atualização: 24/11/2025

