# Diagnóstico: LED não acende no checkout

## Fluxo esperado

1. **Tablet** → FaceValidationScreen → `api.openDock(mac_address, pairing_code, 1)`
2. **API** → `POST /api/self-service/open` com `{ mac_address, slot: 1 }`
3. **Backend** → encontra a doca pelo MAC → publica em `iot-{mac}/cmd` com `{"command":"open","slot":1}`
4. **ESP32** → subscrito em `iot-{mac}/cmd` → recebe → `openSlot(1)` → `digitalWrite(GPIO 18, HIGH)`

## Checklist de verificação

### 1. O app mostrou "Sucesso" ou "Falha ao abrir doca"?

- **Sucesso** → a API retornou OK e o MQTT foi publicado. O problema está entre o broker e o ESP32.
- **Falha** → verifique logs do Laravel (`storage/logs/laravel.log`) e confirme se a doca foi encontrada.

### 2. Doca configurada corretamente no tablet?

- A doca selecionada em **Configurar doca** deve ser a mesma ESP32 física.
- O MAC exibido na doca (ex: `94:54:c5:ed:cb:d0`) deve corresponder ao MAC da ESP32.
- Para ver o MAC da ESP32: Serial Monitor no boot, ou acesse `http://192.168.x.x:5000/info` (IP da doca na rede).

### 3. ESP32 recebe o comando MQTT?

- Com o **Serial Monitor** aberto (`pio device monitor -b 115200`), faça um checkout.
- Se aparecer `📨 MQTT recebido no tópico 'iot-xxx/cmd': {"command":"open","slot":1}` e `🔓 Slot 1 aberto` → o comando chegou; o problema é o LED físico (GPIO 18).
- Se **não** aparecer nada → o ESP32 não está recebendo (tópico errado, broker diferente ou ESP32 desconectado do MQTT).

### 4. Tópico MQTT correto?

- Backend publica em: `{dock.mqttTopic.name}/cmd` (ex: `iot-9454c5edcbd0/cmd`).
- ESP32 escuta em: `iot-{mac_sem_dois_pontos}/cmd` (ex: `iot-9454c5edcbd0/cmd`).
- O `dock_number` no banco deve ser igual ao MAC da ESP32 sem `:` (ex: `9454c5edcbd0`).

### 5. Broker MQTT acessível?

- Backend: `MQTT_HOST` no `.env` (ex: 10.102.0.103).
- ESP32: `MQTT_SERVER` no `main.cpp` (ex: 10.102.0.103).
- Ambos devem usar o **mesmo** broker na mesma rede.

### 6. LED físico (GPIO 18)

- O slot 1 usa GPIO 18 (LED_OTA_PIN).
- Em algumas placas, o LED pode estar em outro pino ou com polaridade invertida.
- **Teste manual:** envie `led_blink` via MQTT no tópico `iot-{mac}/cmd` – isso acende o LED no GPIO 19. Se esse funcionar e o slot 1 não, o problema é o GPIO 18.

## Teste rápido via MQTT

Para verificar se o ESP32 recebe comandos, use um cliente MQTT (ex: `mosquitto_pub`):

```bash
# Publicar comando "open" slot 1 (substitua MAC pelo da sua doca, sem :)
mosquitto_pub -h 10.102.0.103 -t "iot-9454c5edcbd0/cmd" -m '{"command":"open","slot":1}'
```

Se o LED acender com esse comando mas não com o checkout do app, o problema está no app/API (doca errada, MAC incorreto, etc.).

## Logs úteis

- **Laravel:** `storage/logs/laravel.log` – procure por "MqttService: Comando enviado" ou "Erro ao enviar comando".
- **ESP32:** Serial Monitor – procure por "📨 MQTT recebido" e "🔓 Slot 1 aberto".
