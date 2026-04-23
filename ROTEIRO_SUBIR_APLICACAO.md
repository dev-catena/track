# Roteiro para Subir a Aplicação

## Pré-requisitos

- PHP 8.2+
- Composer
- MySQL (com bancos `track`, `track_test`, `octus` e usuário `octus`)
- Node.js 18+ e npm
- Broker MQTT (Mosquitto ou similar) – para comandos aos ESP32

---

## 1. Banco de Dados

```bash
mysql -u root -p -e "
CREATE DATABASE IF NOT EXISTS track CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS track_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS octus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'octus'@'localhost' IDENTIFIED BY 'octus()123';
GRANT ALL PRIVILEGES ON track.* TO 'octus'@'localhost';
GRANT ALL PRIVILEGES ON track_test.* TO 'octus'@'localhost';
GRANT ALL PRIVILEGES ON octus.* TO 'octus'@'localhost';
FLUSH PRIVILEGES;
"
```

---

## 2. Octus (Backend IoT)

```bash
cd /home/darley/track/octus/mqtt

# Dependências
composer install
npm install

# Chave e migrations
php artisan key:generate
php artisan migrate

# Subir servidor (porta 8000)
php artisan serve --host=0.0.0.0 --port=8000
```

Deixe rodando em um terminal.

---

## 3. Track (Backend Principal)

Em outro terminal:

```bash
cd /home/darley/track/track

# Dependências
composer install
npm install

# Chave e migrations (se ainda não fez)
php artisan key:generate
php artisan migrate

# Subir servidor (porta 8000)
php artisan serve --host=0.0.0.0 --port=8000
```

Deixe rodando.

---

## 4. Broker MQTT (opcional, para ESP32)

```bash
# Ubuntu/Debian
sudo apt install mosquitto mosquitto-clients
sudo systemctl start mosquitto
```

---

## 5. App móvel (track-mobile)

```bash
cd /home/darley/track/track-mobile

# Dependências
npm install

# Rodar em dispositivo/emulador
npm run android
# ou: npm run ios
# ou: npx expo start
```

Antes de rodar, ajuste o `BASE_URL` em `src/config/api.js` para o IP da máquina onde o Track está rodando (ex: `http://10.100.0.94:8000`).

---

## 6. Resumo – Ordem de Inicialização

| # | Serviço      | Comando                                      | Porta |
|---|--------------|----------------------------------------------|-------|
| 1 | MySQL        | `sudo systemctl start mysql`                 | 3306  |
| 2 | MQTT Broker  | `sudo systemctl start mosquitto`             | 1883  |
| 3 | Octus        | `cd octus/mqtt && php artisan serve --port=8000` | 8000  |
| 4 | Track        | `cd track && php artisan serve --port=8000`   | 8000  |
| 5 | App móvel    | `cd track-mobile && npm run android`          | -     |

---

## 7. Variáveis de Ambiente

**Track (.env):**
- `DB_DATABASE=track`
- `DB_USERNAME=octus`
- `DB_PASSWORD=octus()123`
- `MQTT_BASE_URL=http://10.102.0.103:8000/api` (IP da máquina onde o Octus roda)
- `MQTT_USERNAME=roboflex@octus.com`
   - `MQTT_PASSWORD=Roboflex()123`

**Octus (.env):**
- `DB_DATABASE=octus`
- `DB_USERNAME=octus`
- `DB_PASSWORD=octus()123`
- `MQTT_HOST=127.0.0.1` (ou IP do broker)

---

## 8. Script Único (opcional)

Crie `start-all.sh` na raiz do projeto:

```bash
#!/bin/bash
cd "$(dirname "$0")"

echo "Iniciando Octus..."
cd octus/mqtt && php artisan serve --host=0.0.0.0 --port=8000 &
OCTUS_PID=$!

sleep 2
echo "Iniciando Track..."
cd ../../track && php artisan serve --host=0.0.0.0 --port=8000 &
TRACK_PID=$!

echo "Octus: http://localhost:8000 (PID $OCTUS_PID)"
echo "Track: http://localhost:8000 (PID $TRACK_PID)"
echo "Pressione Ctrl+C para encerrar"
wait
```
