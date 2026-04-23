# API Octus - Endpoints para Consumidores

API IoT desacoplada. Consumida pelo **Track** e potencialmente por outras aplicações.

## Autenticação

### Login
```
POST /api/login
Content-Type: application/json

{
  "email": "string",
  "password": "string"
}

Response:
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_at": 1234567890
  }
}
```

Use o header em requisições subsequentes:
```
Authorization: Bearer {token}
```

---

## Empresas (Companies)

| Método | Endpoint | Auth | Descrição |
|--------|----------|------|-----------|
| GET | /api/companies | JWT | Listar empresas |
| POST | /api/companies | JWT | Criar empresa |
| GET | /api/companies/{id} | JWT | Obter empresa |
| PUT | /api/companies/{id} | JWT | Atualizar empresa |
| DELETE | /api/companies/{id} | JWT | Excluir empresa |

**POST /api/companies**
```json
{
  "name": "Nome da Empresa"
}
```

---

## Departamentos (Departments)

| Método | Endpoint | Auth | Descrição |
|--------|----------|------|-----------|
| GET | /api/departments | JWT | Listar departamentos |
| POST | /api/departments | JWT | Criar departamento |
| GET | /api/departments/{id} | JWT | Obter departamento |
| PUT | /api/departments/{id} | JWT | Atualizar departamento |
| DELETE | /api/departments/{id} | JWT | Excluir departamento |

**POST /api/departments**
```json
{
  "name": "Nome do Departamento",
  "nivel_hierarquico": 1,
  "id_comp": 1,
  "id_unid_up": null
}
```

---

## Tópicos MQTT

| Método | Endpoint | Auth | Descrição |
|--------|----------|------|-----------|
| GET | /api/mqtt/topics | JWT | Listar tópicos ativos |
| GET | /api/mqtt/topics/{id} | JWT | Obter tópico |
| POST | /api/mqtt/topics | JWT | Criar tópico |
| PUT | /api/mqtt/topics/{id} | JWT | Atualizar tópico |
| DELETE | /api/mqtt/topics/{id} | JWT | Excluir tópico |

### Enviar Comando MQTT
```
POST /api/mqtt/send-command
Authorization: Bearer {token}
Content-Type: application/json

{
  "topic": "iot-aabbccddeeff",
  "command": "open"
}
```

**Comandos suportados pelo firmware:**
- `open` - Abrir/destravamento (doca)
- `close` - Fechar/travamento (doca)
- `led_on`, `led_off`, `led_blink` - Controle de LED (firmware demo)

**Formato MQTT:** O comando é publicado em `{topic}/cmd` com payload `{"command": "valor"}`.

---

## Dispositivos Pendentes (ESP32)

| Método | Endpoint | Auth | Descrição |
|--------|----------|------|-----------|
| POST | /api/devices/pending | Não | Registro de dispositivo ESP32 |
| GET | /api/devices/pending | Não | Listar pendentes |
| GET | /api/devices/pending/{id} | Não | Obter pendente |
| POST | /api/devices/pending/{id}/activate | Não | Ativar dispositivo |

**POST /api/devices/pending** (chamado pelo ESP32)
```json
{
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "device_name": "Doca-Sala-01",
  "ip_address": "192.168.1.100",
  "wifi_ssid": "RedeWiFi"
}
```

---

## Contrato MQTT (Firmware ESP32)

1. **Tópico base:** O nome do tópico no Octus deve corresponder ao que o firmware assina (ex: `iot-aabbccddeeff`).
2. **Assinatura:** O firmware assina `{topic}/cmd`.
3. **Payload:** JSON `{"command": "open"}` ou `{"command": "close"}`.
4. **Resposta:** O firmware pode publicar em `{topic}/status`.

---

## Formato Padrão de Resposta

```json
{
  "success": true,
  "message": "Mensagem opcional",
  "data": { ... }
}
```

Em caso de erro:
```json
{
  "success": false,
  "message": "Descrição do erro"
}
```
