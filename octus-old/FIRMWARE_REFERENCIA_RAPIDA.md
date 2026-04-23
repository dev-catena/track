# ⚡ Firmware Production - Referência Rápida

## 🚀 Comandos Essenciais

```bash
# 1. Ativar versão de produção
cd /home/darley/octus/esp32-octus-platformio
./switch_version.sh production

# 2. Compilar
pio run

# 3. Upload (com ESP32 conectado via USB)
pio run -t upload

# 4. Monitorar serial
pio device monitor
```

## 📋 Especificações Técnicas

| Item | Valor |
|------|-------|
| **Versão** | 1.0.0 |
| **Firmware** | main_production.cpp |
| **Backend** | 145.223.95.178:8000 |
| **MQTT** | 145.223.95.178:1883 |
| **AP SSID** | IOT-Zontec |
| **AP Password** | 12345678 |
| **AP IP** | 192.168.4.1:5000 |

## 🔌 Pinagem

| GPIO | Função |
|------|--------|
| 16 | LED WiFi (status) |
| 19 | LED MQTT (controlável) |
| 18 | Reservado |
| 0 | Botão (5s=AP, 10s=Reset) |

## 💡 LEDs

| LED WiFi (GPIO 16) | Significado |
|-------------------|-------------|
| 🔴 Piscando rápido | Modo AP ativo |
| 🟡 Piscando lento | Conectando WiFi |
| 🟢 Fixo | Conectado |

## 📡 Comandos MQTT

**Tópico:** `iot-{MAC sem :}`

```json
// Ligar LED
{"command": "led_on"}

// Desligar LED
{"command": "led_off"}

// Piscar LED
{"command": "led_blink"}
```

## 🔧 Configuração Inicial

1. Ligue o ESP32
2. Conecte WiFi: `IOT-Zontec` (senha: `12345678`)
3. Acesse: `http://192.168.4.1:5000`
4. Preencha formulário (SSID, senha, MAC)
5. Salve e aguarde conexão

## 🎮 Botão Físico

| Tempo | Ação |
|-------|------|
| 5 segundos | Força modo AP (reconfigurar) |
| 10 segundos | Reset completo (limpa EEPROM) |

## 🆘 Troubleshooting Rápido

| Problema | Solução |
|----------|---------|
| LED piscando sempre | Verificar WiFi → Segurar botão 5s |
| Não aparece no sistema | Verificar servidor → Reiniciar ESP32 |
| MQTT não funciona | Verificar tópico e formato JSON |
| Reset necessário | Segurar botão 10s |

## 🌐 Acesso ao Sistema

- **Web:** http://145.223.95.178:8001
- **API:** http://145.223.95.178:8000/api
- **MQTT:** telnet 145.223.95.178 1883

## 📚 Documentação Completa

- **Guia Completo:** `/home/darley/octus/GUIA_FIRMWARE_PRODUCTION.md`
- **README Firmware:** `/home/darley/octus/esp32-octus-platformio/README.md`
- **Código Fonte:** `/home/darley/octus/esp32-octus-platformio/src/main_production.cpp`

## 🔄 Outras Versões

```bash
# Development (servidor local)
./switch_version.sh development

# Production (recomendado)
./switch_version.sh production

# OTA (experimental)
./switch_version.sh ota
```

---

**Sistema Octus IoT** | Versão 1.0.0 | 24/11/2025

