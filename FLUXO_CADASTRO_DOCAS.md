# Fluxo de Cadastro de Docas

## Visão Geral

```
[Fabricante/Instalação]              [Track]
     |                                   |
     | 1. ESP32 conecta WiFi              |
     |    e registra                      |
     |---------------------------------->|
     |    POST /api/devices/pending       |
     |    (porta 8001)                    |
     |                                    |
     | 2. Admin ativa (Empresa + Dept)    |
     |    -> Cria tópico MQTT e Doca      |
     |       (aparece em Gestão de Docas) |
     |                                    |
     | 3. App: checkout ->                |
     |    Track publica em {topic}/cmd     |
     |---------------------------------->| Broker MQTT
     |                                    |---------> ESP32
     |                                    |           destrava
```

---

## Passo a Passo

### 1. Registro da Doca (ESP32)

A doca é feita com ESP32. Ao conectar o ESP32 na rede (modo AP ou WiFi), ele se auto-registra:

```
POST http://TRACK_IP:8001/api/devices/pending
Content-Type: application/json

{
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "device_name": "Doca-Sala-01",
  "ip_address": "192.168.1.100",
  "wifi_ssid": "RedeWiFi"
}
```
`device_name` = nome da doca (o ESP32 é a doca).

A doca (ESP32) fica com status `pending` no Track.

### 2. Ativação no Track

**Opção A – Tela Web (recomendado)**

1. Acesse o Track (ex: `http://10.102.0.103:8001`)
2. Faça login como **SuperAdmin** ou **Admin**
3. No menu lateral: **Docas Pendentes**
4. Localize a doca na lista (nome, MAC, IP, WiFi)
5. Clique em **Ativar**
6. No modal, preencha:
   - **Empresa** (SuperAdmin só) – selecione a empresa
   - **Departamento / Local** – onde a doca ficará
   - **Tipo** (ESP32/IoT ou Outro)
7. Confirme → é criado o tópico MQTT e a **Doca**; ela sai da lista pendente e passa a aparecer em **Gestão de Docas** (tablets/dispositivos são cadastrados separadamente)

**Opção B – API**

```
# 1. Obter token (login no app ou via API)
# 2. Listar pendentes
curl -H "Authorization: Bearer SEU_TOKEN" \
  http://TRACK_IP:8001/api/devices/pending

# 3. Ativar (cria tópico MQTT e Doca)
# SuperAdmin: incluir "organization"
curl -X POST -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"device_type":1,"department":1,"organization":1}' \
  http://TRACK_IP:8001/api/devices/pending/1/activate

# Admin: organization vem do usuário
curl -X POST -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"device_type":1,"department":1}' \
  http://TRACK_IP:8001/api/devices/pending/1/activate
```

### 3. Gestão de Docas

Após a ativação, a doca aparece em **Gestão de Docas**, associada ao departamento escolhido. Tablets/dispositivos (para checkout) são cadastrados separadamente em **Gestão de Dispositivos**, associando cada um a uma doca.

### 4. Tablet de Autoatendimento

**Configuração (uma vez) – Colaborador do setor:**
1. O app do tablet é configurado com `department_id` do local
2. O tablet lista docas: `GET /api/self-service/docks?department_id=1`
3. O colaborador vê o **MAC** na etiqueta da doca física (ex: `A1:B2:C3:D4:E5:F6`)
4. Na tela do tablet, seleciona a doca que corresponde à etiqueta (ou digita o pairing_code)
5. O app salva a associação localmente → **tablet passa a saber qual doca acionar**

**Uso diário – Usuário final:**
- Ao fazer checkout, o tablet já sabe qual doca abrir
- Aciona o botão → `POST /api/self-service/open` com `mac_address` ou `pairing_code` → doca abre

**Alternativa (pairing_code):**
- Cada doca tem um **código de pareamento** (ex: `ABC123`) – visível em Gestão de Docas ao editar
- O colaborador pode digitar o código em vez de selecionar na lista

### 5. Doca no cliente (reconfiguração de rede)

Quando a doca já foi ativada na fábrica e chega ao cliente, ela precisa ser reconfigurada para a rede local do cliente (novo SSID e senha). O firmware:

1. **Primeira vez no cliente:** Conecta ao AP da doca, usuário informa SSID/senha do cliente
2. **Firmware:** Se tiver flag "deployed" (já ativada) → chama `POST /api/devices/checkin` (só atualiza rede)
3. **Firmware:** Se não tiver flag → chama `POST /api/devices/pending` (registro normal)
4. **Backend:** Se doca já ativada, aceita check-in, atualiza IP/WiFi, retorna `deployed: true`
5. **Firmware:** Guarda flag "deployed" na EEPROM para próximas reconfigs
6. **Docas Pendentes:** Doca ativada **nunca** reaparece (filtro por status=pending)

O endpoint `/api/devices/checkin` existe apenas para docas com status `activated`. Se a doca não estiver ativada, retorna 404 e o firmware pode tentar o registro normal.

### 6. Uso no App (operadores)

1. Operador faz login no app (face/QR/senha)
2. **Validate User** (reconhecimento facial)
3. **Checkout** do dispositivo → Track publica `open` em `{topic}/cmd` no broker MQTT
4. ESP32 recebe no tópico e destrava a doca
5. **Checkin** → comando `close` → doca trava

---

## Fluxo do ponto de vista do usuário

### 1. Admin / SuperAdmin (gestão no Track)

1. Acessa o Track e faz login
2. Vai em **Docas Pendentes** e vê as docas recém-registradas (ESP32 conectados)
3. Clica em **Ativar** na doca desejada
4. No modal, escolhe **Empresa** (SuperAdmin) e **Departamento** (local onde a doca ficará)
5. Confirma → a doca sai da lista pendente e passa a aparecer em **Gestão de Docas**
6. Em **Gestão de Docas**, pode editar a doca e ver o **MAC** e o **código de pareamento** (6 caracteres)

---

### 2. Colaborador do setor (configuração do tablet – uma vez)

**Quem:** Pessoa que trabalha no setor onde a doca está (não é o usuário final).

**Objetivo:** Associar o tablet à doca física para que, quando o usuário final vier fazer checkout, o dispositivo já saiba qual doca acionar.

1. Chega à doca física (fechadura/armário)
2. Vê o **MAC** na etiqueta da doca (ex: `A1:B2:C3:D4:E5:F6`)
3. No tablet, acessa a tela de **configuração** (ou primeira utilização)
4. O tablet exibe a **lista de docas** do local (nome + MAC)
5. Seleciona a doca que corresponde à etiqueta na doca física
6. O app salva a associação localmente (ex: SharedPreferences, AsyncStorage)
7. **Pronto** – essa configuração é feita **uma vez**. O tablet passa a saber qual doca abrir

**Alternativa:** Se o tablet pedir código em vez de lista, digita o código de 6 caracteres (ex: `ABC123`) que aparece em Gestão de Docas.

---

### 3. Usuário final (checkout no tablet)

**Quem:** Cliente/colaborador que vai retirar ou devolver um dispositivo.

1. Chega ao tablet de autoatendimento
2. Faz o fluxo de checkout (login, validação facial etc.)
3. Aciona o botão para abrir a doca
4. A doca abre – **o tablet já sabe qual doca acionar** (configurado pelo colaborador do setor)

---

### 4. Operador (app com checkout)

1. Faz login no app (face, QR ou senha)
2. Passa pela validação de usuário (reconhecimento facial)
3. Faz o checkout do dispositivo
4. O sistema envia o comando para abrir a doca associada
5. A doca abre
6. Ao devolver o dispositivo, faz o checkin → a doca fecha

---

### 5. Instalador / técnico na fábrica

1. Conecta o ESP32 (doca) ao WiFi
2. O ESP32 se registra sozinho no Track
3. Coloca uma **etiqueta com o MAC** na doca física
4. A doca fica pronta para ser ativada pelo Admin

---

### 6. Instalador no cliente

1. Recebe a doca já ativada (com MAC etiquetado)
2. Conecta a doca ao WiFi do cliente
3. A doca faz check-in no servidor (atualiza IP/SSID)
4. Configura o tablet com o `department_id` do local
5. O tablet passa a listar as docas daquele departamento
6. O **colaborador do setor** fará a associação tablet↔doca (item 2 acima)

---

## Pré-requisitos

- Track rodando (ex: porta 8001)
- Broker MQTT acessível (configurado em `config/mqtt.php` e `.env`)
- `.env` do Track com `MQTT_HOST`, `MQTT_PORT`, `MQTT_BROKER_USERNAME`, `MQTT_BROKER_PASSWORD`
- Firmware ESP32 configurado para `http://TRACK_IP:8001/api/devices/pending`

---

## Formato do Tópico MQTT

O firmware espera tópicos no formato `iot-{mac}` (ex: `iot-aabbccddeeff`).

O Track cria esse tópico na ativação da doca pendente. O nome é usado ao enviar comandos `open`/`close` em `{topic}/cmd`.
