# Track — ambiente de desenvolvimento

Monorepô com backend Laravel, apps mobile (Expo e React Native), firmware ESP32 e integração **Thalamus** (face) + **MQTT** (docas).

## Estrutura do repositório

| Pasta | Descrição |
|-------|-----------|
| `track/` | API e painel web **Laravel 12**, PHP 8.2+ |
| `track-mobile/` | App **Expo** (operador: login, face, checkout de doca) |
| `mobile-config/` | App **React Native** (config da doca via **BLE**; build com EAS) — **não** roda no Expo Go |
| `esp32-firmware/` | Firmware **PlatformIO** da doca (AP `IOT-Zontec`, portal `192.168.4.1`) |

---

## 1. Requisitos

- **PHP** 8.2+ e **Composer**
- **Node.js** 18+ e **npm** (front Laravel, Expo, React Native)
- **MySQL** (ou MariaDB) compatível com o Laravel
- **Broker MQTT** (ex.: Mosquitto) na rede — o Laravel publica em `{tópico_da_doca}/cmd`
- (Opcional firmware) **PlatformIO** / `pio` para o ESP32

---

## 2. Backend Laravel (`track/`)

### 2.1 Instalação

```bash
cd track
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

Ajuste **`.env`** (veja seção 4). Depois:

```bash
php artisan config:clear
php artisan serve --host 0.0.0.0 --port 8000
```

O `--host 0.0.0.0` permite acessar o API pelo IP da máquina na LAN (celular, tablets).

### 2.2 Usuários iniciais (seed)

O seeder cria superadmins (ajuste após o primeiro acesso se necessário):

- `admin@track.com` / `admin123`
- `darlley@gmail.com` / `11111111`

A listagem de **Usuários** no painel **não** exibe contas `superadmin` (filtro de negócio). Superadmin no app tablet escolhe **unidade** após o login; admin de empresa usa `organization_id` no perfil.

---

## 3. App `track-mobile/` (Expo)

### 3.1 Instalação

```bash
cd track-mobile
npm install
```

### 3.2 URL da API

O app chama o **mesmo host** do Laravel, porta **8000** (ou a que você usar).

- Padrão em `track-mobile/src/config/api.js`: `http://10.102.0.103:8000`
- Para sobrescrever, crie **`.env`** na raiz do `track-mobile`:

```env
EXPO_PUBLIC_API_BASE_URL=http://SEU_IP:8000
```

Reinicie o bundler com cache limpo:

```bash
npx expo start -c
```

**Login facial (match):** `POST /api/auth/v2/login` com `type=face_login` e imagem. O Thalamus é chamado **só no servidor** (variáveis `THALAMUS_*` no `.env` do Laravel).

**Checkout:** após o rosto, o app chama `POST /api/self-service/open` → o Laravel envia **MQTT** (`slot_status` etc.). O broker precisa ser o **mesmo** usado pelo ESP32.

---

## 4. Configuração importante do `.env` (Laravel em `track/.env`)

Valores alinhados ao ambiente **atual** de referência; **altere** IPs e senhas para o seu ambiente.

| Variável | Função / exemplo |
|----------|------------------|
| `APP_URL` | URL pública do app, ex. `http://10.102.0.103:8000` |
| `DB_*` | Banco MySQL (host, base, usuário, senha) |
| `MQTT_HOST` | IP/hostname do broker **alcançável pelo processo PHP** (não use `localhost` se o PHP e o MQTT estiverem em máquinas diferentes). Ex.: `10.102.0.103` |
| `MQTT_PORT` | Geralmente `1883` |
| `MQTT_CONNECT_TIMEOUT` | Segundos para conectar (ex.: `15`) |
| `MQTT_BROKER_USERNAME` / `MQTT_BROKER_PASSWORD` | Se o broker exigir autenticação |
| `THALAMUS_FACE_BASE_URL` | Base da API Thalamus, ex. `https://face.thalamus.ind.br` |
| `THALAMUS_FACE_BANCO_IMAGENS` | Ex.: `thalamus` |
| `THALAMUS_FACE_RECOGNIZE_PATH` | Padrão: `/face/api/recognize/image` |
| `THALAMUS_FACE_USER_AGENT` | Padrão: `Flutter-App/1.0` (compatível com o cliente de referência) |

Após qualquer mudança no `.env`:

```bash
cd track
php artisan config:clear
```

### 4.1 Diagnóstico MQTT (mesmo processo do PHP)

```bash
cd track
php artisan track:mqtt-test
# opcional: --topic=iot-xxxxx
```

Teste TCP e publish. Se o terminal `nc` ao broker funciona mas o checkout não, o `MQTT_HOST` no `.env` costumava estar errado ou o cache de config desatualizado.

---

## 5. App `mobile-config/` (React Native + BLE)

Não use **Expo Go** — depende de módulos nativos (`react-native-ble-plx`).

```bash
cd mobile-config
npm install
npx react-native run-android
# ou iOS: npx react-native run-ios
```

Build na nuvem (APK) com EAS (ver `package.json` script `build:android:apk` e `eas.json`).

---

## 6. Firmware ESP32 (`esp32-firmware/`)

- **AP de configuração:** SSID `IOT-Zontec`, senha padrão `12345678`, portal `http://192.168.4.1`
- **PlatformIO:** `pio run`, `pio run -t upload`, `pio device monitor`
- **IP padrão do backend** no portal/ EEPROM segue o definido no firmware (ex. `10.102.0.5` no código — ajuste conforme o servidor de API usado em produção)

Detalhes adicionais: `esp32-firmware/README.md` (se existir) ou comentários em `src/main.cpp`.

---

## 7. Fluxo resumido (checkout com doca)

1. Operador passa no **reconhecimento facial** (Thalamus, via Laravel).
2. App chama **`POST /api/self-service/open`** com `mac_address` ou `pairing_code` da doca e, quando aplicável, `operator_id` / `fcm_token`.
3. Laravel publica no MQTT (ex. comando `slot_status`); o **ESP32** reage, envia telemetria, e o backend pode enviar `open` com o slot livre.
4. O **MQTT_HOST** do Laravel **precisa** ser o IP do broker que o **ESP32** e o **servidor PHP** enxergam (mesma lógica de rede).

---

## 8. Licença

Componentes herdam as licenças dos respectivos frameworks (Laravel, Expo, etc.). Ajuste esta secção se o projeto tiver licença própria unificada.
