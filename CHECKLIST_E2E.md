# Checklist de Validação E2E - Track

## Pré-requisitos

- [ ] Track rodando (ex: `http://10.102.0.103:8001`)
- [ ] Broker MQTT acessível (ex: `10.102.0.103:1883`)
- [ ] `.env` do Track com `MQTT_HOST`, `MQTT_PORT`, `MQTT_BROKER_USERNAME`, `MQTT_BROKER_PASSWORD`
- [ ] Firmware ESP32 apontando para `http://TRACK_IP:8001/api/devices/pending`

---

## Fase 1: Cadastros no Track

### 1.1 Organização
- [ ] Login no Track como superadmin
- [ ] Criar nova organização (ex: "Empresa Teste")

### 1.2 Departamento
- [ ] Criar departamento vinculado à organização

### 1.3 Doca Pendente e Ativação
- [ ] ESP32 (doca/fechadura) conecta WiFi e registra em `POST /api/devices/pending`
- [ ] No Track: **Docas Pendentes** → verificar doca na lista
- [ ] Clicar em **Ativar** → selecionar empresa (SuperAdmin) e departamento
- [ ] Confirmar → tópico MQTT e Doca criados
- [ ] Doca sai da lista pendente e aparece em **Gestão de Docas**

---

## Fase 2: App Flutter

### 2.1 Login
- [ ] Abrir app, fazer login (face/QR/senha)
- [ ] Verificar token recebido

### 2.2 Validate User (Reconhecimento Facial)
- [ ] Ir para tela de checkout
- [ ] Capturar rosto (operador cadastrado com face no Track)
- [ ] Verificar resposta: "User validated successfully"

### 2.3 Checkout (Abrir Doca)
- [ ] Após validação, realizar checkout do dispositivo
- [ ] Verificar: comando MQTT "open" publicado pelo Track no broker
- [ ] Se ESP32 conectado: doca deve destravar

### 2.4 Checkin (Fechar Doca)
- [ ] Realizar checkin do dispositivo
- [ ] Verificar: comando MQTT "close" publicado
- [ ] Se ESP32 conectado: doca deve travar

---

## Fase 3: Fluxo Completo

### 3.1 Cadastro de Docas (Fabricante → Cliente)
- [ ] ESP32 (doca) em modo AP: conectar em rede, registrar em `POST /api/devices/pending` (Track)
- [ ] No Track: **Docas Pendentes** → ativar doca (empresa + departamento)
- [ ] Tópico MQTT e Doca criados; doca visível em **Gestão de Docas**
- [ ] MAC etiquetado na doca (ex: `A1:B2:C3:D4:E5:F6`)
- [ ] Tablets/dispositivos: cadastrar em **Gestão de Dispositivos**, associando a uma doca

### 3.2 Tablet Autoatendimento (Self-Service)
- [ ] `GET /api/self-service/docks?department_id=1` retorna lista de docas com mac_address
- [ ] `POST /api/self-service/open` com `{"mac_address":"a1:b2:c3:d4:e5:f6"}` abre a doca
- [ ] `POST /api/self-service/open` com `{"pairing_code":"ABC123"}` também funciona

### 3.3 Integridade
- [ ] Dock sem tópico associado → checkout deve falhar com mensagem clara
- [ ] Doca já ativada → botão "Ativar" não deve aparecer

---

## Tela de Ativação (Docas Pendentes)

| Item | Descrição |
|------|-----------|
| **Rota** | `/SuperAdmin/devices/pending` ou `/Admin/devices/pending` |
| **Menu** | Docas Pendentes (ícone doca) |
| **Listagem** | Apenas pendentes (ativadas saem da lista) |
| **Modal** | Empresa (SuperAdmin) + Departamento + Tipo |
| **Resultado** | Tópico MQTT e Doca criados → aparece em Gestão de Docas |

---

## Comandos Úteis

```bash
# Rodar testes Pest (Track)
cd track && php artisan test

# Verificar rotas
cd track && php artisan route:list --name=devices.pending
```

---

## Resultado Esperado

| Item | Status |
|------|--------|
| Registro ESP32 → Track | ✅ |
| Ativação via tela web | ✅ |
| Tópico MQTT criado | ✅ |
| Dock → Topic link | ✅ |
| App login | ✅ |
| Face validate | ✅ |
| Checkout → MQTT open | ✅ |
| Checkin → MQTT close | ✅ |
