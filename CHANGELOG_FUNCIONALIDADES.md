# Alterações a serem implementdas

---

## 1. Ponto de partida (estado atual)

### 1.1 thalamus-tracking-flutter (App móvel – Flutter)

| Funcionalidade | Descrição |
|----------------|-----------|
| Autenticação | Login por username/senha, QR code e reconhecimento facial (Google ML Kit local) |
| Rastreamento de dispositivos | Checkout, check-in, status de dispositivos |
| Localização | Serviço em foreground com atualizações contínuas de GPS enviadas à API |
| Relatórios | Tela de relatórios de status e atividade |
| Dispositivo perdido/devolvido | Telas para dispositivo perdido e devolvido |
| Terminal self-service | Fluxo de checkout com escaneamento facial |
| Push notifications | Firebase Cloud Messaging (FCM) |
| Localização | Português e inglês (l10n) |

**Telas principais:** Welcome, Login, QRScanner, FaceRecognition, Home, Checkout, Reports, DeviceLost, DeviceReturned

**API base:** `https://phpstack-1495152-5709375.cloudwaysapps.com` (PHP/Cloudways)

---

### 1.2 octus (Infraestrutura IoT)

| Componente | Descrição |
|------------|-----------|
| **ESP32 firmware** | Firmware PlatformIO para dispositivos ESP32 (WiFi AP, MQTT, OTA, slots) |
| **MQTT backend** | Laravel para broker MQTT, tópicos, OTA |
| **iot-config-web-laravel** | Web Laravel para config de empresas, departamentos, usuários, tópicos, OTA |
| **iot-config-app-laravel** | App Laravel para dispositivos pendentes e controle |

**API ESP32:** `POST /api/devices/pending`, `POST /api/devices/checkin`, ativação de pendentes

---

## 2. Web (track) – Funcionalidades a serem implementadas e modificadas

### 2.1 Autenticação e perfis

| Funcionalidade | Status | Descrição |
|----------------|--------|-----------|
| Multi-tenant | a implementar | Organizações e departamentos |
| RBAC (Roles) | a implementar | superadmin, admin, manager com controle de acesso |
| Perfis | a implementar | CRUD de perfis (código gerado automaticamente, atribuível por padrão) |
| Permissões | a implementar | Matriz Perfis × Funcionalidades, middleware CheckPermission |
| Admin acesso total | a implementar | Perfil admin com acesso total por padrão |

### 2.2 Gestão de dados

| Funcionalidade | Status | Descrição |
|----------------|--------|-----------|
| Empresas (Organizations) | a implementar | CRUD, máscara de telefone no cadastro |
| Departamentos | a modificar | Status padrão "Ativo", "Location" → "Localização", índice removido |
| Docas | a modificar | Status padrão "Ativo", "Location" → "Localização", coluna índice removida |
| Usuários | a modificar | Campo "Operation" removido (hidden indoor), departamentos carregam ao abrir formulário |
| Dispositivos | a implementar | CRUD, status (disponível, em uso, offline, atrasado) |

### 2.3 Docas e ESP32

| Funcionalidade | Status | Descrição |
|----------------|--------|-----------|
| Docas pendentes | a implementar | Listagem e ativação de ESP32 (empresa + departamento) |
| Mapa da empresa | a implementar | Grafo Org → Dept → Docas, ping, firmware, OTA |
| OTA (firmware) | a implementar | Upload, disparo, contagem de docas |
| Relatório OTA | a implementar | Histórico de atualizações OTA (tabela ota_activities) |
| Tópicos MQTT | a implementar | Criação automática ao ativar doca pendente |

### 2.4 Interface e UX

| Funcionalidade | Status | Descrição |
|----------------|--------|-----------|
| Sidebar | a modificar | Link "Docas Pendentes" no submenu Docas; "Relatório OTA" adicionado |
| Seletor global de empresa | a modificar | SuperAdmin: listbox com fonte preta, fundo branco (contraste) |
| Navbar | a modificar | Avatar do usuário logado; nome do usuário ao lado do thumbnail |
| Tema Octus | a implementar | CSS octus-theme (paleta #536173, Inter) |
| Tabela Docas Pendentes | a modificar | Fundo escuro (#27293d) para contraste |
| Tabela Permissões | a modificar | Fundo escuro (#27293d) para contraste |
| Google Translate | a modificar | Sidebar com `notranslate`; "Notificações" e "Docas Pendentes" protegidos |
| Notificações | a modificar | Título "Notifications" → "Notificações" |

### 2.5 Configuração e sistema

| Funcionalidade | Status | Descrição |
|----------------|--------|-----------|
| Configuração | a implementar | Bots, Sistema |
| Registros de atividade | a implementar | Logs de ações |
| Notificações | a implementar | Tela de notificações |

### 2.6 Funcionalidades web (lista completa)

| Slug | Nome | Plataforma |
|------|------|------------|
| dashboard | Painel | web |
| department | Departamentos | web |
| dock.management | Gestão de Docas | web |
| dock.panel | Painel de Docas | web |
| devices.pending | Docas Pendentes | web |
| device | Dispositivos | web |
| company-map | Mapa da Empresa | web |
| user | Usuários | web |
| profiles | Perfis | web |
| permissions | Permissões | web |
| configuration | Configuração | web |
| logs | Registros de Atividade | web |
| notification | Notificações | web |

---

## 3. App móvel (track-mobile) – Funcionalidades implementadas e modificadas

### 3.1 Stack tecnológico

| Antes (thalamus) | Depois (track-mobile) |
|------------------|------------------------|
| Flutter | React Native + Expo |
| Provider | AuthContext, DockContext |
| Navegação Flutter | React Navigation (native stack) |

### 3.2 Autenticação

| Funcionalidade | Status | Descrição |
|----------------|--------|-----------|
| Login facial | a implementar | Validação no backend (não mais ML Kit local) |
| Login admin | a implementar | Username/senha para admin/manager |
| Mock tablet | a implementar | Login mock para desenvolvimento |
| QR code login | Removido | Não existe mais no app atual |

### 3.3 Fluxo operador

| Funcionalidade | Status | Descrição |
|----------------|--------|-----------|
| Welcome | a implementar | Tela inicial "Iniciar" ou "Entrar como admin" |
| FaceCamera | a implementar | Câmera facial para login |
| Home | a implementar | Dashboard do operador, checkout |
| FaceValidation | a implementar | Validação facial antes do checkout |
| Checkout | a implementar | Checkout após validação |
| Reports | a implementar | Relatórios |
| Checkin | a implementar | Check-in manual (fechar doca) |

### 3.4 Fluxo admin

| Funcionalidade | Status | Descrição |
|----------------|--------|-----------|
| AdminHome | a implementar | Home do admin |
| SetupDock | a implementar | Seleção de doca para tablet |
| FaceRegister | a implementar | Cadastro de rostos de operadores/usuários |

### 3.5 Self-service e docas

| Funcionalidade | Status | Descrição |
|----------------|--------|-----------|
| DockContext | a implementar | Doca selecionada (mac_address, pairing_code) |
| API open/close | a implementar | Self-service via pairing_code ou mac_address |
| Lista de docas | a implementar | GET /api/self-service/docks |

### 3.6 Funcionalidades app (lista completa)

| Slug | Nome | Plataforma |
|------|------|------------|
| app.checkout | Checkout (App) | app |
| app.setup-dock | Configurar Doca (App) | app |
| app.face-register | Cadastro de Rostos (App) | app |
| app.reports | Relatórios (App) | app |

---

## 4. API (track) – Endpoints a implementar e a modificars

### 4.1 ESP32 / dispositivos pendentes

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| /api/devices/pending | POST | Registro do ESP32 na rede |
| /api/devices/checkin | POST | Check-in do ESP32 (reconexão) |
| /api/devices/pending | GET | Listar pendentes (admin) |
| /api/devices/pending/{id}/activate | POST | Ativar doca pendente |

### 4.2 Self-service (tablet)

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| /api/self-service/docks | GET | Listar docas do departamento |
| /api/self-service/open | POST | Abrir doca (pairing_code ou mac_address) |
| /api/self-service/close | POST | Fechar doca |

### 4.3 Autenticação

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| /api/auth/v2/login | POST | Login operador (username/senha) |
| /api/auth/admin/login | POST | Login admin |
| /api/auth/tablet-mock | POST | Mock login para desenvolvimento |

### 4.4 Operador

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| /api/dashboard | GET | Dashboard do operador |
| /api/reports | GET | Relatórios |
| /api/user/validate | POST | Validação facial antes de acessar dispositivo |
| /api/device/checkout-mock | POST | Mock checkout |
| /api/device/checkin | POST | Check-in de dispositivo |
| /api/device/location/capture | POST | Captura de localização |

### 4.5 Admin

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| /api/admin/operators | GET | Listar operadores |
| /api/admin/operators/{id}/face-register | POST | Registrar rosto do operador |
| /api/admin/users/{id}/face-register | POST | Registrar rosto do usuário |

---

## 5. Resumo comparativo

| Aspecto | Antes (thalamus + octus) | Depois (track + track-mobile) |
|---------|--------------------------|-------------------------------|
| **App móvel** | Flutter | React Native + Expo |
| **Backend** | PHP Cloudways + octus Laravel (MQTT) | Laravel único (track) |
| **Autenticação** | Username, QR, face (ML Kit local) | Username, face (backend), admin |
| **Rastreamento** | Serviço de localização em foreground | Self-service open/close via MQTT |
| **Docas** | Firmware ESP32 (octus) | ESP32 + track (MqttService) |
| **Self-service** | Tela de checkout Flutter | API open/close + tablet |
| **Config IoT** | octus mqtt + iot-config-web | track web (mapa, OTA) |
| **Roles** | Pouco definidos | superadmin, admin, manager |
| **Multi-tenant** | octus companies/departments | track organizations/departments |
| **Face** | On-device (ML Kit) | Server-side (validação no backend) |
| **OTA** | octus MQTT OTA | track OTA (upload, trigger, relatório) |
| **Dispositivos pendentes** | octus mqtt | track (web + API) |
| **Relatórios** | Tela Flutter | track-mobile ReportsScreen + API |
| **Localização** | Foreground contínuo | Opcional (validateUser, deviceCheckin) |
| **FCM** | Flutter | track backend (FCMService) |

---

## 6. Removido / descontinuado

- Serviço de localização em foreground (Flutter)
- Login por QR code
- Reconhecimento facial on-device (ML Kit)
- Apps Laravel separados (octus mqtt, iot-config-web, iot-config-app)
- Telas Device Lost / Device Returned (sem equivalente direto no track-mobile)

---

## 7. Adicionado / melhorado

- Backend Laravel único (track)
- API self-service (pairing_code, mac_address)
- DockContext para tablet
- Fluxo admin (setup de doca, cadastro de rostos)
- Controle de acesso por permissões
- Relatório OTA e Mapa da Empresa
- Modo mock para desenvolvimento
- Máscara de telefone no cadastro de empresa
- Avatar e nome do usuário na navbar
- Tabelas com fundo escuro (Docas Pendentes, Permissões)
- Proteção contra tradução incorreta (Google Translate) na sidebar

---

## 8. Documentos relacionados

| Documento | Descrição |
|-----------|-----------|
| `FLUXO_CADASTRO_DOCAS.md` | Fluxo de cadastro de docas (ESP32 → ativação → checkout) |
| `RECUPERACAO_OTA.md` | Recuperação de dispositivos após OTA (rollback, re-flash) |
| `CHECKLIST_E2E.md` | Checklist de testes end-to-end |

---

*Documento gerado com base no workspace Track. Última atualização: fevereiro 2026.*
