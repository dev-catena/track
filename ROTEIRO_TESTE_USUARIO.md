# Roteiro de Teste Real como Usuário

## Cenário

Operador usa o app para fazer checkout de um dispositivo. O reconhecimento facial valida a identidade e a doca é destravada.

---

## Pré-requisitos

- [ ] Octus rodando (ex: http://10.102.0.103:8000)
- [ ] Track rodando (ex: http://10.102.0.103:8001)
- [ ] App Flutter instalado no celular
- [ ] Celular e PC na mesma rede (ou IP acessível)

---

## Parte 1: Configuração Inicial (Admin no Track)

### 1.1 Login no Track

1. Acesse: `http://10.102.0.103:8001` (ou IP do seu servidor)
2. Faça login como **superadmin** ou **admin**
3. Confirme que o dashboard carrega

### 1.2 Criar Organização

1. Menu: **Company** (ou Organization)
2. Clique em **Add** / **Nova**
3. Preencha:
   - Nome: `Empresa Teste`
   - Email, telefone (opcional)
4. Salve
5. Verifique se a organização aparece na lista

### 1.3 Criar Departamento

1. Menu: **Department**
2. Clique em **Add**
3. Preencha:
   - Nome: `TI`
   - Organização: `Empresa Teste`
4. Salve

### 1.4 Cadastrar Operador

1. Menu: **Users** ou **Operators**
2. Adicione um operador:
   - Nome: `João Silva`
   - Email: `joao@teste.com`
   - Username: `joao`
   - Senha: `123456`
   - Departamento: `TI`
   - Tipo: Operador
3. Salve

### 1.5 Cadastrar Face do Operador

1. Abra o detalhe do operador `João Silva`
2. Vá em **Face Register** / **Registrar Rosto**
3. Envie uma foto do rosto (ou use a câmera)
4. Aguarde confirmação de registro

### 1.6 Criar Tópico no Octus (se ainda não existir)

1. Acesse o Octus: `http://10.102.0.103:8000`
2. Login com `roboflex@octus.com` / `Roboflex()123`
3. Vá em **Tópicos MQTT**
4. Crie um tópico, ex: `iot-doca01` (ou use um PendingDevice ativado)
5. Anote o **ID** do tópico

### 1.7 Criar Dock

1. No Track: menu **Docks** → **Docks Management**
2. Clique em **Add Dock**
3. Preencha:
   - Nome: `Doca TI`
   - Departamento: `TI`
   - Status: `Active`
   - **Active Devices (MQTT Topic)**: selecione o tópico criado no Octus
4. Salve

### 1.8 Cadastrar Dispositivo

1. Menu: **Device**
2. Adicione um dispositivo:
   - Nome: `Tablet 01`
   - Dock: `Doca TI`
   - Serial/Build number: anote (ex: `TB001`)
   - Status: `Active`
3. Salve

---

## Parte 2: Teste no App (Operador)

### 2.1 Configurar URL da API no App

No código do Flutter (`ApiService.dart`), confirme:

```dart
static const String BASE_URL = "http://10.102.0.103:8001";
```

(Use o IP da máquina onde o Track está rodando.)

### 2.2 Abrir o App

1. Abra o app no celular
2. Tela inicial: escolha **Login com senha** (ou QR/Face, conforme disponível)

### 2.3 Login

1. Username: `joao`
2. Senha: `123456`
3. Toque em **Entrar**
4. Confirme que entra no dashboard

### 2.4 Checkout (Reconhecimento Facial + Abrir Doca)

1. Toque em **Checkout** ou **Pegar dispositivo**
2. O app pede **validação facial**
3. Posicione o rosto na câmera
4. Aguarde o reconhecimento
5. Se aprovado: mensagem tipo "User validated successfully"
6. O app envia comando para abrir a doca
7. Se houver ESP32 conectado ao tópico MQTT, a doca deve destravar

### 2.5 Checkin (Devolver Dispositivo)

1. Toque em **Checkin** ou **Devolver dispositivo`
2. Selecione o dispositivo (ou confirme o que está devolvendo)
3. Confirme a devolução
4. O app envia comando para fechar a doca
5. A doca deve travar novamente

---

## Parte 3: Verificações

### No Track (Web)

- [ ] **Activity Logs**: aparecem as ações de checkout/checkin
- [ ] **Device**: status do dispositivo muda (inuse → available)
- [ ] **Docks Panel**: uso da doca refletido

### No Octus (se tiver acesso)

- [ ] Logs do MQTT: comando `open`/`close` enviado ao tópico

### No App

- [ ] Login funciona
- [ ] Reconhecimento facial identifica o operador
- [ ] Checkout concluído sem erro
- [ ] Checkin concluído sem erro

---

## Fluxo Resumido

```
[App] Login (joao/123456)
  → [Track] Valida credenciais
  → [App] Tela principal

[App] Checkout → Validação facial
  → [App] Envia foto
  → [Track] Luxand reconhece → joao
  → [Track] Checkout → chama Octus send-command (open)
  → [Octus] Publica em topic/cmd
  → [ESP32] Recebe → destrava doca

[App] Checkin
  → [Track] Checkin → chama Octus send-command (close)
  → [ESP32] Trava doca
```

---

## Problemas Comuns

| Problema | Verificar |
|----------|-----------|
| "No match found" no face | Face do operador cadastrada no Track? |
| "Device not found" | Serial do dispositivo correto no cadastro? |
| "MQTT command failed" | Octus rodando? MQTT_BASE_URL correto no .env do Track? |
| Doca não destrava | ESP32 conectado ao broker? Tópico correto? |
| App não conecta | IP do Track acessível do celular? Mesma rede? |
