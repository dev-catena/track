# Comando MQTT slot_status - Fluxo completo

## Comando MQTT

**Tópico:** `{id_doca}/cmd`  
Exemplo: `iot-b0cbd88b80bc/cmd` (MAC sem dois-pontos, minúsculo)

**Payload (JSON):**
```json
{"command": "slot_status"}
```

## Fluxo completo

1. **Você** publica `slot_status` no tópico
2. **ESP32** lê os 6 sensores e POSTa o array no endpoint
3. **Backend** grava na tabela `dock_slot_status`
4. **Backend** localiza o primeiro slot livre (status=aberto)
5. **Se houver slot livre:** Backend envia MQTT `open` com `slot` → **ESP32 acende o LED correspondente**
6. **Se não houver:** Backend envia MQTT `no_slots` → ESP32 pisca todos os LEDs 3x

---

## 2. Endpoint para Postman

**URL:** `POST http://10.102.0.103:8000/api/docks/slot-status`

(Use `http://localhost:8000` ou o IP/porta do seu backend se diferente)

**Headers:**
- `Content-Type: application/json`

**Body (raw JSON):**
```json
{
  "id_doca": "iot-b0cbd88b80bc",
  "ultima_atualizacao": "2026-03-12T14:30:00Z",
  "slots": [
    {"id_slot": 0, "status": "fechado", "nivel_bateria": 100},
    {"id_slot": 1, "status": "aberto", "nivel_bateria": 0},
    {"id_slot": 2, "status": "fechado", "nivel_bateria": 100},
    {"id_slot": 3, "status": "fechado", "nivel_bateria": 100},
    {"id_slot": 4, "status": "fechado", "nivel_bateria": 100},
    {"id_slot": 5, "status": "aberto", "nivel_bateria": 0}
  ]
}
```

O endpoint grava na tabela `dock_slot_status`.

---

## 3. Conferir no banco

```sql
SELECT * FROM dock_slot_status ORDER BY id DESC LIMIT 5;
```
