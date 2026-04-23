# GPIO - Sensores e LEDs dos Slots

## Configuração

### 6 GPIO para sensores de presença (chaves)
| Slot | GPIO | Função |
|------|------|--------|
| 1 | 32 | Chave: HIGH = bateria no slot, LOW = vazio |
| 2 | 33 | Chave: HIGH = bateria no slot, LOW = vazio |
| 3 | 34 | Chave: HIGH = bateria no slot, LOW = vazio |
| 4 | 35 | Chave: HIGH = bateria no slot, LOW = vazio |
| 5 | 36 | Chave: HIGH = bateria no slot, LOW = vazio |
| 6 | 39 | Chave: HIGH = bateria no slot, LOW = vazio |

**Nota:** GPIO 34-39 são input-only no ESP32. Para inverter a lógica (LOW = ocupado), altere `SENSOR_OCCUPIED_HIGH` para `0` em `main.cpp`.

### 6 GPIO para LEDs dos lockers
| Slot | GPIO | Função |
|------|------|--------|
| 1 | 18 | LED do locker (acende quando slot liberado) |
| 2 | 5 | LED do locker |
| 3 | 13 | LED do locker |
| 4 | 14 | LED do locker |
| 5 | 15 | LED do locker |
| 6 | 16 | LED do locker |

## Fluxo

1. **Comando `open` sem slot:** ESP lê os 6 sensores, publica array JSON em `{topic}/feedback`, envia POST para `/api/docks/slot-status`.
2. **Backend:** Recebe array `[1,0,1,0,0,1]` (1=ocupado, 0=livre), encontra primeiro slot livre, envia MQTT `open` com `slot`.
3. **Comando `open` com slot:** ESP acende o LED do slot indicado (10s).
4. **Sem slot livre:** Backend envia MQTT `no_slots`; ESP pisca todos os LEDs 3x; FCM push para o operador (se `fcm_token` foi enviado).
