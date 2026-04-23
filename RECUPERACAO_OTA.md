# Recuperação após OTA – dispositivos offline

Se os dispositivos ficaram offline após atualização OTA e não voltaram, siga estes passos.

## Rollback A/B (a partir da v1.0.2)

O firmware passou a usar **rollback automático**: o novo firmware é gravado na partição B; só é confirmado após conectar WiFi + backend + MQTT. Se o ESP32 reiniciar ou travar antes disso, o bootloader volta ao firmware anterior (partição A).

## 1. Verificar se o ESP32 está em boot loop

Conecte o ESP32 ao PC via USB e abra o Monitor Serial (115200 baud):

```bash
cd /home/darley/track/octus/esp32-octus-platformio
pio device monitor -b 115200
```

- Se aparecer mensagens repetidas de boot/crash → boot loop (firmware corrompido ou com bug).
- Se aparecer "Modo AP" ou "IOT-Zontec" → ESP32 está no AP, aguardando configuração WiFi.

## 2. Re-flash via USB (recuperar dispositivo)

Se o OTA corrompeu o firmware, grave novamente via USB:

```bash
cd /home/darley/track/octus/esp32-octus-platformio
pio run -t upload
```

Ou use o .bin que está em `track/storage/app/firmware/`:

```bash
pio run -t upload --upload-port /dev/ttyACM1
```

## 3. Possíveis causas do OTA falhar

| Causa | Solução |
|-------|---------|
| **URL de download inacessível** | O ESP32 precisa acessar a URL do firmware. Verifique `FIRMWARE_BASE_URL` no `.env` – deve ser um IP/domínio que o ESP32 alcance na rede (ex: `http://10.102.0.103:8001`). |
| **Arquivo .bin incorreto** | Confirme que o arquivo enviado é o firmware correto para ESP32 (não outro modelo). |
| **Download interrompido** | Rede instável durante o download pode corromper o firmware. |
| **Espaço insuficiente** | O ESP32 precisa de flash livre para o novo firmware. |

## 4. Configurar URL do firmware

No `track/.env`:

```env
FIRMWARE_BASE_URL=http://SEU_IP:PORTA
```

Exemplo: se o Track roda em `http://10.102.0.103:8001`, use:

```env
FIRMWARE_BASE_URL=http://10.102.0.103:8001
```

O ESP32 na rede do cliente precisa conseguir acessar esse endereço.

## 5. Reconfigurar WiFi após recuperação

Depois de re-flash via USB:

1. Conecte ao AP "IOT-Zontec" (senha: 12345678).
2. Acesse http://192.168.4.1.
3. Informe SSID e senha da rede.
4. O dispositivo fará check-in e voltará a aparecer online.
