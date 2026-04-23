# Slots da Doca via MQTT

Cada doca possui 6 slots. O comando de abrir/fechar pode especificar qual slot acionar.

## Comandos

### 1. Abrir/Fechar com slot (direto)

**Tópico:** `{topic}/cmd`

```json
{"command": "open", "slot": 3}
{"command": "close", "slot": 3}
```

- Se `slot` não for informado, usa slot 1 por padrão.
- `slot` válido: 1 a 6.

### 2. Handshake: get_slots

Quando o cliente não sabe qual slot usar, pode pedir à doca quais estão disponíveis:

**Enviar em** `{topic}/cmd`:
```json
{"command": "get_slots", "request_id": "abc123"}
```

**Resposta em** `{topic}/feedback`:
```json
{
  "request_id": "abc123",
  "type": "slots_response",
  "available_slots": [1, 2, 3, 4, 5, 6]
}
```

O cliente que enviou `get_slots` deve estar inscrito em `{topic}/feedback` para receber a resposta. Depois, envia `open` com o slot escolhido.

## Fluxos

### Fluxo A: Slot conhecido (Device com slot_id)

- Device cadastrado com `slot_id` (1-6).
- Checkout/check-in envia `open`/`close` com `slot` do device.
- Self-service: pode enviar `slot` no body da requisição.

### Fluxo B: Handshake (tablet/app)

1. App envia `get_slots` via MQTT (ou chama API que publica).
2. App inscrito em `{topic}/feedback` recebe `available_slots`.
3. Usuário escolhe slot (ou app escolhe o primeiro).
4. App envia `open` com `slot` via API ou MQTT.

## API

### POST /api/self-service/open

```json
{
  "mac_address": "a1:b2:c3:d4:e5:f6",
  "slot": 3
}
```

`slot` opcional (1-6). Se omitido, a doca usa slot 1.

### Device (checkout/check-in)

- Se o device tem `slot_id` preenchido, o comando MQTT inclui `slot`.
- Caso contrário, envia sem slot (doca usa slot 1).

## Firmware (ESP32)

- **GPIO dos slots:** 18 (slot 1 = LED), 5, 13, 14, 15, 16 (ajustar em `SLOT_PINS` conforme hardware).
- **Slot 1 (LED):** Ao abrir, o LED acende por 10 segundos e apaga automaticamente.
- Sem sensores de ocupação: `get_slots` retorna sempre [1,2,3,4,5,6].
- Com sensores: alterar `publishSlotsFeedback` para ler os sensores e retornar apenas slots livres.
