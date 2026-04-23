# App Tablet - Autoatendimento

## Objetivo

O tablet instalado na doca funciona como autoatendimento. Ao ser acionado pelo usuário, envia o comando para abrir a doca. O tablet precisa saber **qual doca** deve acionar.

> **Importante:** O checkout **não** pede número de série do dispositivo. O usuário apenas valida a identidade por face match; a doca associada ao tablet é acionada automaticamente. Ver `FLUXO_TABLET_CORRETO.md` para os dois fluxos (configuração única + checkout diário).

## Amarração: MAC Address (recomendado)

O **MAC address** é o identificador único da doca (ESP32). Ele é etiquetado na doca na fábrica e permite rastrear do firmware até o servidor MQTT.

### Fluxo recomendado

**Configuração (uma vez) – Colaborador do setor:**
1. **Fábrica** → MAC etiquetado na doca (ex: `A1:B2:C3:D4:E5:F6`)
2. **Admin** ativa a doca no Track → tópico MQTT `iot-{mac}` criado
3. **Tablet** configurado com `department_id` do local
4. **Colaborador do setor** (quem trabalha no local) acessa a tela de configuração do tablet
5. Tablet lista docas do departamento (nome + MAC)
6. Colaborador seleciona a doca que corresponde à etiqueta na doca física
7. App salva a associação localmente → tablet passa a saber qual doca acionar

**Uso diário – Usuário final:**
- **Não** informa número de série nem identificação de dispositivo
- Valida identidade por **face match**
- Após validação, o tablet envia `mac_address` (da doca associada) para a API → doca abre

### Alternativa: Código de Pareamento

Também é possível usar o **pairing_code** (6 caracteres, ex: `ABC123`) – visível em Gestão de Docas ao editar a doca. Útil para digitação rápida.

---

## API para o Tablet

### Listar Docas (para seleção)

```
GET http://TRACK_IP:8001/api/self-service/docks?department_id=1
```

**Resposta sucesso (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Doca-Sala-01",
      "mac_address": "a1:b2:c3:d4:e5:f6",
      "location": "Sala 01"
    }
  ]
}
```

### Abrir Doca

Aceita **mac_address** (recomendado) ou **pairing_code**:

```
POST http://TRACK_IP:8001/api/self-service/open
Content-Type: application/json

# Por MAC (etiquetado na doca)
{ "mac_address": "a1:b2:c3:d4:e5:f6" }

# Ou por código de pareamento
{ "pairing_code": "ABC123" }
```

**Resposta sucesso (200):**
```json
{
  "success": true,
  "message": "Doca aberta.",
  "data": {
    "dock_name": "Doca-Sala-01",
    "mac_address": "a1:b2:c3:d4:e5:f6"
  }
}
```

**Resposta erro (404):**
```json
{
  "success": false,
  "message": "Doca não encontrada ou identificador inválido."
}
```

**Resposta erro (422):**
```json
{
  "success": false,
  "message": "Informe pairing_code ou mac_address."
}
```

---

## Onde ver o MAC no Track

1. Acesse **Docas** → **Gestão de Docas**
2. O **dock_number** armazena o MAC sem separadores (ex: `a1b2c3d4e5f6`)
3. Formato exibido na API: `a1:b2:c3:d4:e5:f6`
4. O **pairing_code** (6 caracteres) também aparece ao editar a doca

---

## Implementação no App do Tablet

```dart
// Exemplo Flutter - fluxo por seleção de MAC
class SelfServiceService {
  final String baseUrl = 'http://10.102.0.103:8001';
  final int departmentId; // configurado para o local

  Future<List<Dock>> listDocks() async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/self-service/docks?department_id=$departmentId'),
    );
    final data = jsonDecode(response.body);
    return (data['data'] as List).map((d) => Dock.fromJson(d)).toList();
  }

  Future<bool> openDock(String macAddress) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/self-service/open'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'mac_address': macAddress}),
    );
    final data = jsonDecode(response.body);
    return data['success'] == true;
  }
}
```

---

## Segurança

- A API é **pública** (sem autenticação)
- Recomenda-se **rate limiting** no servidor para evitar abuso
- O tablet deve ser configurado com `department_id` correto para listar apenas docas do local
