# Fluxo correto do tablet de autoatendimento

## Tela inicial (sem login)

O app **não** exige login para todos. A tela inicial tem:
- **Iniciar** – abre a câmera e tenta identificar o rosto; se reconhecer, vai direto para a tela de checkout
- **Entrar como admin** – abre o login para administradores

## ⚠️ O que NÃO é o fluxo

- **Não** pedir número de série do dispositivo no checkout
- **Não** informar identificação de dispositivo ao usuário final
- O checkout **não** envia localização + serial para o backend

---

## As duas rotinas corretas

### 1. Configuração única (tablet instalado na doca)

**Quando:** Uma única vez, quando o tablet de autoatendimento é instalado no local onde está a doca.

**Quem:** Usuário **admin da empresa**.

**O que acontece:**
1. Um link aparece para o admin da empresa
2. Esse link lista **todas as docas ativadas pelo superadmin na fábrica** (quando o superadmin informou que aquela doca pertencerá à empresa X)
3. O admin indica **qual doca** das ativas é aquela que está sendo instalada ali
4. Isso é possível pelo **número impresso na etiqueta da doca** (MAC ou código de pareamento)
5. Depois disso, **todo checkout** feito pelos usuários comuns terá essa associação no sistema: o MQTT saberá qual doca acionar quando aquele tablet estiver sendo usado

**Resultado:** Associação tablet ↔ doca salva localmente (ex: AsyncStorage). O tablet passa a saber qual doca abrir/fechar.

---

### 2. Checkout no dia a dia

**Quando:** Uso diário do tablet.

**Quem:** Usuário comum (operador).

**O que acontece:**
1. Usuário **não informa** nenhum dado de identificação de dispositivo
2. Usuário apenas **valida quem ele é por face match**
3. Após validação, a doca associada ao tablet é **acionada automaticamente** para liberar o slot
4. O sistema usa a associação tablet↔doca feita na configuração única

**Resultado:** Doca abre (MQTT open, LED acende etc.). Usuário retira o dispositivo.

---

## Resumo

| Etapa | Quem | O que faz |
|-------|------|-----------|
| **Configuração (uma vez)** | Admin da empresa | Seleciona a doca da lista (pelo MAC/código na etiqueta) → associação tablet↔doca |
| **Checkout diário** | Usuário comum | Valida identidade por face match → doca abre automaticamente |
| **Checkin (devolução)** | Automático | Usuário coloca dispositivo no slot → doca identifica e envia ao sistema → processo concluído (sem ação na tela) |

---

## Implementação atual (track-mobile)

### Fluxo usuário comum
- **WelcomeScreen:** "Iniciar" (face) + "Entrar como admin"
- **FaceCameraScreen:** Câmera frontal → captura → `api.faceLogin(uri)` → se reconhecido, login como operador → Home
- **HomeScreen:** Checkout (FaceValidation) e Checkin
- **FaceValidationScreen:** Usa doca associada; chama `api.openDock(...)` – **sem** pedir número de série
- **Checkin:** Automático – doca detecta dispositivo no slot e envia identificação ao backend; tela do usuário comum **só tem checkout**

### Fluxo admin
- **AdminLoginScreen:** Login com usuário/senha (admin, manager, superadmin)
- **AdminHomeScreen:** Configurar doca + Gravar rostos
- **SetupDockScreen:** Lista docas por `organization_id` ou `department_id`; admin seleciona e salva em `DockContext`
- **FaceRegisterScreen:** Lista operadores; admin seleciona e captura rosto; envia para `api.registerOperatorFace` (Luxand)

### Backend
- `POST /api/auth/v2/login` com `type=face_login` + `image` → login por rosto
- `POST /api/auth/admin/login` → login admin (username/email + password)
- `GET /api/admin/operators` → lista operadores (admin)
- `POST /api/admin/operators/{id}/face-register` → registra rosto (admin)
