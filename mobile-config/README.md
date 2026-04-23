# IotZontecConfig (mobile-config)

Aplicativo **opcional** em React Native para **parear** com a doca (ESP32) via **Bluetooth LE** e enviar a mesma configuração do portal `http://192.168.4.1`: **SSID**, **senha** da rede e **IP do backend**. O **modo AP e o portal web** continuam existindo; este app **não substitui** esse fluxo.

## Requisitos

- Node 18+
- **Android** ou **iOS** (Bluetooth LE)
- Firmware ESP32 a partir do commit que adiciona **NimBLE** (`ble_provisioning.cpp`); anúncio: nome `IOT-` + 4 hex finais do MAC (ex.: `IOT-80BC`).

## Instalar e rodar

```bash
cd mobile-config
npm install
# Android
npx react-native run-android
# iOS (macOS)
npx pod-install
npx react-native run-ios
```

## Permissões (Android 12+)

- Bluetooth, localização (necessária para *scan* BLE e leitura do SSID conectado em muitos aparelhos)
- Ajuste a mensagem no `AndroidManifest` se a Play Store exigir política

## Uso

1. Ligue a doca. No celular, ative Bluetooth.
2. **Parear: buscar IOT-…** — lista dispositivos cujo **nome** começa com `IOT-` (o firmware usa `IOT-` + 4 caracteres do MAC).
3. Toque no dispositivo para **conectar**.
4. Ajuste **IP do backend**, **SSID** e **senha** (o SSID costuma preencher no Android; no iOS pode ser preciso digitar).
5. **Enviar para gravar no ESP32** — o ESP aplica a mesma lógica de `/configure` (conecta WiFi, grava EEPROM, chama o backend) e o app mostra a resposta JSON do characteristic de *status*.

## Limites

- O firmware usa **NimBLE**; espaço de flash fica apertado (~>90%). Se faltar memória, avalie tamanho de partição OTA ou otimizar binário.
- **iOS** restringe leitura de SSID; preencha manualmente se o campo estiver vazio.
- A senha do WiFi **não** pode ser lida de forma fiable em todos os aparelhos; o usuário insere o que a rede exige (como no portal web).

## UUIDs BLE (espelho do `ble_provisioning.cpp`)

- Serviço: `a1b2c3d4-0001-4000-8000-00805f9b34fb`
- Config (write): `a1b2c3d4-0002-4000-8000-00805f9b34fb`
- Status (read/notify): `a1b2c3d4-0003-4000-8000-00805f9b34fb`

Payload JSON: `{"ssid","password","server_ip"}` (igual ao `POST` do portal).
