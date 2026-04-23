# Plano de Integração Octus + Track

## 1. Análise do Estado Atual

### 1.1 Visão Geral dos Projetos

| Projeto | Tecnologia | Propósito |
|---------|------------|-----------|
| **Track** | Laravel 12 + Blade + Vite | Aplicação principal: gestão multitenant (empresas, departamentos, docas, dispositivos), reconhecimento facial (Luxand), checkout/checkin de dispositivos, Firebase |
| **Octus (mqtt)** | Laravel 12 + Blade + Vite | Backend IoT: empresas, departamentos, tópicos MQTT, dispositivos pendentes, OTA, envio de comandos para ESP32 |
| **thalamus-tracking-flutter** | Flutter 3.8 | App mobile: login (face/QR/senha), reconhecimento facial, checkout/checkin, localização, notificações |

### 1.2 Fluxo Atual do Sistema

```
[App Flutter] --face/QR/login--> [Track API]
       |                              |
       |--validateUser (face)-------->|--Luxand--> valida operador
       |                              |
       |--deviceCheckin-------------->|--checkout--> [Octus API /mqtt/send-command]
       |                              |                    |
       |                              |                    v
       |                              |              [Broker MQTT]
       |                              |                    |
       |                              |                    v
       |                              |              [ESP32/Doca] --destrava slot
```

### 1.3 Integração Existente Track ↔ Octus

O **Track já consome a API do Octus** via `MQTTApiService`:

- **Organização** → cria `Company` no Octus (`POST /companies`), armazena `mqtt_company_id`
- **Departamento** → cria `Department` no Octus (`POST /departments`), armazena `mqtt_department_id`
- **Dock** → associa `mqtt_topic_id` (tópico do Octus)
- **Checkout** → chama `POST /mqtt/send-command` com `topic` + `command: "open"`
- **Checkin** → chama `POST /mqtt/send-command` com `command: "close"`

### 1.4 Duplicações Identificadas

| Entidade | Track | Octus | Problema |
|----------|-------|-------|----------|
| Empresa | `organizations` | `companies` | Dois CRUDs; Track sincroniza via API |
| Departamento | `departments` | `departments` | Schemas diferentes (Track: flat, Octus: hierárquico) |
| Dock/Doca | `docks` (Track) | `topics` (Octus) | Dock referencia `mqtt_topic_id` |
| Dispositivo | `devices` (Track) | `pending_devices`, `device_types` (Octus) | Conceitos distintos |

### 1.5 Schemas de Departamento

**Track:** `organization_id`, `name`, `internal_id`, `location`, `operating_start`, `operating_end`, `description`, `mqtt_department_id`

**Octus:** `id_comp`, `name`, `nivel_hierarquico`, `id_unid_up` (hierarquia)

O Track envia `nivel_hierarquico: 1` e `id_comp` ao criar departamento no Octus.

### 1.6 Identidade Visual

- **Octus:** Navbar "🔌 Octus", font Inter, Tailwind, tema limpo
- **Track:** Black Dashboard, font Nunito, layout diferente
- **App Flutter:** Estilo próprio

---

## 2. Objetivos da Integração

1. **Centralizar** cadastros em um único backend, eliminando duplicações
2. **Desacoplar Octus** para ser consumido por qualquer aplicação (Track é um consumidor)
3. **Garantir integridade** do fluxo: App → Track → Octus/MQTT → Firmware
4. **Unificar identidade visual** conforme modelo do Octus

---

## 3. Arquitetura Proposta

### 3.1 Modelo de Desacoplamento

```
                    +------------------+
                    |   Octus (API)    |
                    |  - Companies     |
                    |  - Departments   |
                    |  - Topics        |
                    |  - MQTT send     |
                    |  - Pending Dev.  |
                    +--------+--------+
                             |
         +-------------------+-------------------+
         |                   |                   |
         v                   v                   v
   [Track Web]          [Outro App]         [Firmware ESP32]
   (consumidor)        (futuro)              (tópico MQTT)
```

- **Octus** = API IoT desacoplada; expõe endpoints REST
- **Track** = consumidor principal; gerencia negócio (operadores, dispositivos, Luxand, Firebase)
- **Firmware** = assinante MQTT; recebe comandos via tópico

### 3.2 Fonte Única de Verdade

- **Track** é a única interface para criar/editar empresas, departamentos e docas
- **Octus** armazena companies/departments/topics para MQTT e dispositivos
- Sincronização: Track cria/atualiza no Octus via API (já implementado)

### 3.3 Eliminação de Duplicações

1. **Remover CRUD de Companies e Departments da interface web do Octus** (ou torná-lo somente leitura/admin)
2. **Manter** Companies e Departments no Octus apenas para:
   - Organização de tópicos e dispositivos
   - Consumo via API por Track e outros clientes
3. **Track** continua como única UI de gestão de organizações/departamentos/docas

---

## 4. Plano de Execução

### Fase 1: Consolidação da API Octus (Desacoplamento)

**Objetivo:** Octus como API pura, documentada e estável.

| # | Tarefa | Detalhes |
|---|--------|----------|
| 1.1 | Documentar API Octus | OpenAPI/Swagger com todos os endpoints consumidos pelo Track |
| 1.2 | Padronizar respostas | Formato único `{ success, message, data }` |
| 1.3 | Remover rotas duplicadas | `/api/mqtt/companies` e `/api/mqtt/departments` duplicados em `api.php` |
| 1.4 | Autenticação API | Garantir que rotas usadas pelo Track tenham auth adequada (JWT ou token de serviço) |
| 1.5 | Endpoints públicos para firmware | `/api/devices/pending` (registro ESP32) sem conflito com auth do Track |

### Fase 2: Integridade Track ↔ Octus

**Objetivo:** Garantir que a sincronização e os comandos MQTT funcionem corretamente.

| # | Tarefa | Detalhes |
|---|--------|----------|
| 2.1 | Validar mapeamento Organization ↔ Company | Campos enviados no `POST /companies` |
| 2.2 | Validar mapeamento Department | `nivel_hierarquico: 1`, `id_comp` corretos |
| 2.3 | Validar fluxo send-command | `topic` + `command` ("open"/"close"); formato esperado pelo firmware |
| 2.4 | Tratamento de falhas | Se Octus indisponível, Track deve logar e informar sem quebrar o fluxo principal |
| 2.5 | Testes E2E | Login app → validateUser → checkout → verificar MQTT → firmware |

### Fase 3: Identidade Visual Unificada

**Objetivo:** Track e Octus web seguirem o modelo visual do Octus.

| # | Tarefa | Detalhes |
|---|--------|----------|
| 3.1 | Extrair tema Octus | CSS/Tailwind, navbar, cores, tipografia (Inter) |
| 3.2 | Aplicar ao Track | Substituir Black Dashboard pelo tema Octus |
| 3.3 | Aplicar ao app Flutter | Cores, ícones e layout alinhados ao Octus |
| 3.4 | Branding | Logo/nome "Octus" ou "Track by Octus" conforme definição |

### Fase 4: Cadastro de Dispositivos (Doca)

**Objetivo:** Fluxo claro para registrar docas no fabricante e associar ao cliente.

| # | Tarefa | Detalhes |
|---|--------|----------|
| 4.1 | Cadastro prévio no fabricante | PendingDevice ou identificador único (MAC/serial) |
| 4.2 | Associação doca ↔ departamento | No Track, ao criar/editar Dock, selecionar tópico (dispositivo) do Octus |
| 4.3 | Lista de dispositivos disponíveis | Track consome `GET /mqtt/topics` do Octus para popular dropdown |
| 4.4 | Fluxo de ativação | ESP32 registra → Octus cria PendingDevice → ativação manual ou automática → Topic criado |

### Fase 5: Validação End-to-End

| # | Tarefa | Detalhes |
|---|--------|----------|
| 5.1 | Criar organização no Track | Verificar criação em Octus |
| 5.2 | Criar departamento | Verificar sincronização |
| 5.3 | Criar dock + associar tópico | Verificar mqtt_topic_id |
| 5.4 | Login no app | Face/QR/senha |
| 5.5 | ValidateUser + checkout | Verificar comando MQTT e abertura da doca |
| 5.6 | Checkin | Verificar fechamento |

---

## 5. Riscos e Mitigações

| Risco | Mitigação |
|-------|-----------|
| Octus indisponível | Track deve funcionar; sync pode ser retentada em background |
| Incompatibilidade de schemas Department | Manter `nivel_hierarquico: 1` e `id_unid_up: null` para departamentos flat do Track |
| Formato de comando MQTT diferente no firmware | Documentar contrato topic/cmd e payload; validar com firmware atual |
| Duas bases de dados | Manter por enquanto; migração futura para DB única se necessário |

---

## 6. Ordem Sugerida de Execução

1. **Fase 1** – Estabilizar e documentar API Octus
2. **Fase 2** – Garantir integridade da integração Track ↔ Octus
3. **Fase 4** – Refinar fluxo de cadastro de docas (se necessário)
4. **Fase 5** – Testes E2E
5. **Fase 3** – Identidade visual (pode ser feita em paralelo ou após validação funcional)

---

## 7. Próximos Passos Imediatos

1. ~~Remover duplicação de rotas em `octus/mqtt/routes/api.php` (companies/departments sob `/mqtt`)~~ ✅ Concluído
2. ~~Verificar se `TopicController::sendCommand` usa `topic` + `command` no formato esperado pelo firmware (`topic/cmd`, payload `{"command":"open"}`)~~ ✅ Compatível
3. ~~Revisar `DeviceRepository::checkout` e `checkin` para garantir que `dock_mqtt_topic_id` e chamada à API estão corretos~~ ✅ Corrigido bug com `$response` indefinido
4. ~~Criar documentação da API Octus~~ ✅ `octus/mqtt/API_ENDPOINTS.md`
5. Rotas `/api/mqtt/*` protegidas com `auth:api` para segurança

---

## 8. Resumo

- **Track** = aplicação principal de negócio (multitenant, operadores, dispositivos, reconhecimento facial)
- **Octus** = API IoT desacoplada (companies, departments, topics, MQTT, dispositivos)
- **Integração** = Track consome Octus via API; sem duplicação de CRUD de empresas/departamentos na UI do Octus
- **Fluxo** = App → Track (validateUser, checkout) → Octus (send-command) → MQTT → ESP32/Doca
- **Visual** = Unificar Track e app no estilo Octus
