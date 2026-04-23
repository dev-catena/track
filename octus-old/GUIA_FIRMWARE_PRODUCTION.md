# 🚀 Guia de Uso - Firmware Production (main_production.cpp)

## 📌 Visão Geral

Este é o **firmware oficial recomendado** para dispositivos ESP32 do sistema Octus IoT em ambiente de produção.

- **Versão:** 1.0.0
- **Data:** 16/09/2025
- **Localização:** `/home/darley/octus/esp32-octus-platformio/src/main_production.cpp`

## ✨ Características

### ✅ O que TEM nesta versão:
- Access Point WiFi integrado (IOT-Zontec)
- Servidor web de configuração (192.168.4.1:5000)
- Conexão MQTT estável com reconexão automática
- Armazenamento de configurações em EEPROM
- Controle de LEDs via MQTT
- Botão físico com múltiplas funções
- Servidor de produção (145.223.95.178)

### ❌ O que NÃO TEM nesta versão:
- OTA (Over-The-Air updates) - Atualização remota
- Recursos experimentais
- Logs verbosos de debug

## 🎯 Por que usar esta versão?

1. **Estabilidade** - Testada e aprovada para produção
2. **Confiabilidade** - Sem recursos experimentais que possam causar problemas
3. **Simplicidade** - Foco nas funcionalidades essenciais
4. **Manutenção** - Fácil de gerenciar e diagnosticar problemas

## 🛠️ Instalação e Uso

### Passo 1: Ativar a Versão

```bash
cd /home/darley/octus/esp32-octus-platformio
./switch_version.sh production
```

**Saída esperada:**
```
✅ Versão de produção ativada
📁 Arquivo ativo: src/main.cpp
🔧 Para compilar: pio run
📤 Para fazer upload: pio run -t upload
```

### Passo 2: Compilar

```bash
pio run
```

**Isso irá:**
- Compilar o código C++
- Gerar o firmware binário (.bin)
- Verificar erros de compilação

### Passo 3: Upload para ESP32

```bash
# Conecte o ESP32 via USB primeiro!
pio run -t upload
```

**Durante o upload:**
- Aguarde a barra de progresso completar
- Não desconecte o cabo USB
- O ESP32 reiniciará automaticamente após o upload

### Passo 4: Monitorar (Opcional)

```bash
pio device monitor
```

**Para sair:** Pressione `Ctrl + C`

## 🔧 Configuração Inicial do Dispositivo

### 1️⃣ Primeira Conexão

1. **Ligue o ESP32** - Ele iniciará automaticamente em modo AP
2. **Conecte-se ao WiFi:**
   - **Rede:** `IOT-Zontec`
   - **Senha:** `12345678`
3. **Abra o navegador e acesse:** `http://192.168.4.1:5000`

### 2️⃣ Preencher o Formulário

No formulário web, você verá:

| Campo | Descrição | Exemplo |
|-------|-----------|---------|
| **SSID WiFi** | Nome da rede WiFi do local | `MinhaRedeWiFi` |
| **Senha WiFi** | Senha da rede WiFi | `senha123` |
| **MAC do Dispositivo** | Identificador único (auto-detectado) | `AA:BB:CC:DD:EE:FF` |

> 💡 **Dica:** O MAC é detectado automaticamente, mas você pode alterá-lo se necessário.

### 3️⃣ Salvar e Conectar

1. Clique em **"Salvar Configurações"**
2. O ESP32 reiniciará automaticamente
3. Aguarde 10-15 segundos
4. O dispositivo se conectará ao WiFi configurado
5. Aparecerá no sistema Octus automaticamente

### 4️⃣ Verificar Conexão

**LED WiFi (GPIO 16):**
- 🔴 **Piscando rápido** → Modo AP ativo (aguardando configuração)
- 🟡 **Piscando lento** → Tentando conectar ao WiFi
- 🟢 **Fixo aceso** → Conectado com sucesso!

## 🎮 Usando o Botão Físico

O botão no **GPIO 0** (geralmente marcado como BOOT/PROG) tem duas funções:

### ⏱️ Segurar por 5 segundos
**Ativa o Modo AP forçado**
- Use quando: Precisar reconfigurar o WiFi
- O que acontece: Dispositivo volta ao modo Access Point
- LED: Começa a piscar rapidamente

### ⏱️ Segurar por 10 segundos
**Reset completo (limpa EEPROM)**
- Use quando: Precisar resetar completamente o dispositivo
- O que acontece: Apaga todas as configurações salvas
- LED: Pisca muito rápido (confirmação de reset)

## 📡 Controlando via MQTT

### Tópico do Dispositivo

Cada ESP32 tem seu tópico único baseado no MAC:
```
iot-AABBCCDDEEFF
```
(onde `AABBCCDDEEFF` é o MAC do dispositivo sem os `:`)

### Comandos Disponíveis

#### 1. Ligar LED (GPIO 19)
```json
{
  "command": "led_on"
}
```

#### 2. Desligar LED (GPIO 19)
```json
{
  "command": "led_off"
}

```

#### 3. Piscar LED (GPIO 19)
```json
{
  "command": "led_blink"
}
```

### Exemplo Prático com MQTT

Usando o mosquitto_pub:
```bash
# Ligar LED do dispositivo com MAC 112233445566
mosquitto_pub -h 145.223.95.178 -t "iot-112233445566" -m '{"command":"led_on"}'

# Desligar LED
mosquitto_pub -h 145.223.95.178 -t "iot-112233445566" -m '{"command":"led_off"}'

# Piscar LED
mosquitto_pub -h 145.223.95.178 -t "iot-112233445566" -m '{"command":"led_blink"}'
```

## 🔌 Pinagem do ESP32

### LEDs
| GPIO | Função | Comportamento |
|------|--------|---------------|
| 16 | LED WiFi | Indica status da conexão WiFi |
| 19 | LED MQTT | Controlável remotamente via MQTT |
| 18 | Reservado | Para uso futuro |

### Botão
| GPIO | Função | Uso |
|------|--------|-----|
| 0 | BOOT/PROG | 5s=AP forçado, 10s=Reset completo |

## 🌐 Servidores

### Produção (Atual)
- **Backend API:** `http://145.223.95.178:8000`
- **MQTT Broker:** `145.223.95.178:1883`
- **Web Interface:** `http://145.223.95.178:8001`

### Como o dispositivo se conecta

1. **Lê configurações da EEPROM** (WiFi + MAC + Tópico)
2. **Conecta ao WiFi** configurado
3. **Registra no Backend** via HTTP POST
4. **Conecta ao MQTT Broker**
5. **Subscreve ao tópico** `iot-{MAC}`
6. **Envia status inicial** (online)
7. **Aguarda comandos** via MQTT

## 🆘 Solução de Problemas

### Problema: LED WiFi piscando constantemente (lento)

**Causa:** Não consegue conectar ao WiFi

**Solução:**
1. Verifique se a rede WiFi está disponível
2. Confirme a senha do WiFi
3. Segure o botão por 5s para forçar modo AP
4. Reconfigure as credenciais

---

### Problema: Dispositivo não aparece no sistema

**Causa:** Falha na comunicação com o backend

**Solução:**
1. Verifique se o servidor está online: `http://145.223.95.178:8000`
2. Teste conexão MQTT: `telnet 145.223.95.178 1883`
3. Verifique logs do backend
4. Force reconexão (reinicie o ESP32)

---

### Problema: Comandos MQTT não funcionam

**Causa:** Tópico incorreto ou formato JSON inválido

**Solução:**
1. Verifique o tópico: deve ser `iot-{MAC sem :}`
2. Confirme formato JSON válido
3. Use aspas duplas no JSON
4. Teste com mosquitto_pub primeiro

---

### Problema: Preciso trocar de rede WiFi

**Causa:** Mudança de local ou rede

**Solução:**
1. Segure o botão por 5 segundos
2. ESP32 entrará em modo AP
3. Conecte ao `IOT-Zontec`
4. Acesse `http://192.168.4.1:5000`
5. Configure a nova rede

---

### Problema: Tudo parou de funcionar

**Causa:** Configurações corrompidas

**Solução:**
1. **Reset completo:** Segure botão por 10 segundos
2. Aguarde LEDs piscarem rapidamente
3. Configure do zero

---

## 📊 Monitoramento e Debug

### Via Serial Monitor

```bash
pio device monitor
```

**O que você verá:**
```
=== ESP32 IoT Zontec - Firmware v1.0.0 ===
MAC: AA:BB:CC:DD:EE:FF
EEPROM: Lendo configurações...
WiFi: Conectando a 'MinhaRede'...
WiFi: Conectado! IP: 192.168.1.100
Backend: Registrando dispositivo...
Backend: Registrado com sucesso!
MQTT: Conectando a 145.223.95.178:1883...
MQTT: Conectado!
MQTT: Subscrito ao tópico: iot-AABBCCDDEEFF
=== Sistema pronto ===
```

### Via Sistema Web

Acesse o painel Octus:
- **URL:** `http://145.223.95.178:8001`
- **Login:** Use suas credenciais
- **Menu:** Dispositivos → Ver dispositivos pendentes/ativos

## 🔄 Atualização do Firmware

### Método Atual (USB)

1. Conecte o ESP32 via USB
2. Compile a nova versão: `pio run`
3. Faça upload: `pio run -t upload`
4. Desconecte e teste

> ⚠️ **Nota:** Esta versão NÃO suporta OTA (atualização remota). Para OTA, use `main_with_ota.cpp` (ainda experimental).

## 📈 Próximos Passos

Após configurar o dispositivo:

1. ✅ Verifique se aparece no painel web
2. ✅ Teste comandos MQTT
3. ✅ Associe a uma empresa/departamento
4. ✅ Configure regras de automação (se disponível)
5. ✅ Monitore status periodicamente

## 🎓 Recursos Adicionais

- **Documentação completa:** `/home/darley/octus/README.md`
- **Código fonte:** `/home/darley/octus/esp32-octus-platformio/src/main_production.cpp`
- **Outras versões:** `/home/darley/octus/esp32-octus-platformio/README.md`
- **API Backend:** `http://145.223.95.178:8000/api/documentation`

## 💡 Dicas Finais

1. **Mantenha backup das configurações** de rede WiFi
2. **Anote o MAC** de cada dispositivo para rastreamento
3. **Teste em bancada** antes de instalar em campo
4. **Use fonte de alimentação estável** (5V, mínimo 1A)
5. **Evite interferências** WiFi (micro-ondas, etc)

---

**Versão deste guia:** 1.0.0  
**Última atualização:** 24/11/2025  
**Sistema:** Octus IoT

