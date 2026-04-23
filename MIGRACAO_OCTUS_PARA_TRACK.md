# Migração: Funcionalidades do Octus para o Track

## Objetivo

Todas as funcionalidades necessárias ao ciclo completo foram integradas ao **Track**, eliminando a dependência do **Octus**.

---

## O que foi feito

### 1. Banco de Dados (Track)

- **`mqtt_topics`** – Tópicos MQTT (nome, descrição, is_active)
- **`pending_devices`** – Dispositivos ESP32 aguardando ativação

### 2. MQTT direto no Track

- **`php-mqtt/client`** – Publicação de comandos no broker
- **`config/mqtt.php`** – MQTT_HOST, MQTT_PORT, MQTT_BROKER_USERNAME, MQTT_BROKER_PASSWORD
- **`MqttService`** – Envio de comandos `open`/`close` para `{topic}/cmd`

### 3. Remoção da dependência do Octus

- **OrganizationRepository** – Sync com Octus removido
- **DepartmentRepository** – Sync com Octus removido
- **DeviceRepository** – Passa a usar `MqttService` local em vez da API Octus
- **DockRepository** – Lista tópicos de `mqtt_topics` local em vez da API Octus

### 4. Novos endpoints (Track API)

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| POST | `/api/devices/pending` | Público | Registro ESP32 |
| GET | `/api/devices/pending` | Sanctum | Listar pendentes |
| POST | `/api/devices/pending/{id}/activate` | Sanctum | Ativar e criar tópico MQTT |

### 5. Fluxo de cadastro (sem Octus)

1. **ESP32** → `POST /api/devices/pending` (Track) com mac_address, device_name, etc.
2. **Admin** → Ativa via API ou interface futura → cria tópico em `mqtt_topics` (formato `iot-{mac}`)
3. **Admin** → Cria Dock no Track e associa o tópico (lista vem de `mqtt_topics`)
4. **Checkout/Checkin** → Track publica no broker MQTT diretamente

---

## Configuração (.env do Track)

```env
# Broker MQTT (para comandos open/close nas docas)
MQTT_HOST=localhost
MQTT_PORT=1883
MQTT_BROKER_USERNAME=
MQTT_BROKER_PASSWORD=
MQTT_CLIENT_ID=track_mqtt
```

**Removido:** `MQTT_BASE_URL`, `MQTT_USERNAME`, `MQTT_PASSWORD` (credenciais Octus)

---

## Migração de dados existentes

Se você já usava Octus e tem docks com `mqtt_topic_id` apontando para tópicos do Octus:

1. Criar tópicos equivalentes em `mqtt_topics` no Track (mesmo `name`)
2. Atualizar `docks.mqtt_topic_id` para os novos IDs do Track

Ou recadastrar as docas associando aos tópicos criados no Track.

---

## Formato do tópico MQTT

O firmware ESP32 espera:
- **Nome:** `iot-{mac_sem_dois_pontos}` (ex: `iot-aabbccddeeff`)
- **Comando:** publicado em `{topic}/cmd` com payload `{"command":"open"}` ou `{"command":"close"}`

---

## O que NÃO foi migrado (opcional)

- **OTA Updates** – Atualização de firmware via Octus (se usado)
- **Device Types** – Tipos de dispositivo do Octus (activate usa device_type/department apenas para compatibilidade)
- **Interface web** – Telas de Dispositivos Pendentes e Tópicos no painel Track (podem ser adicionadas depois)

---

## Resumo

O **Track** passa a ser autônomo. O **Octus** deixa de ser necessário para o ciclo:

```
ESP32 → Track (registro) → Admin ativa no Track → Tópico criado
       → Dock associada ao tópico
       → Checkout → Track publica MQTT → ESP32 destrava
```
