# 📦 Informações dos Firmwares ESP32 - Octus

## 🎯 Firmware Atual (Ativo)

**✅ main_production.cpp** está ativo desde: 24/11/2025 11:14

## 📊 Comparação de Versões

| Característica | Production ⭐ | Development | With OTA 🧪 |
|----------------|--------------|-------------|-------------|
| **Servidor** | 145.223.95.178 | 10.102.0.115 | 145.223.95.178 |
| **MQTT** | 145.223.95.178:1883 | 10.102.0.115:1883 | 145.223.95.178:1883 |
| **Access Point** | ✅ | ✅ | ✅ |
| **Config Web** | ✅ | ✅ | ✅ |
| **MQTT Control** | ✅ | ✅ | ✅ |
| **LED Status** | ✅ | ✅ | ✅ |
| **EEPROM** | ✅ | ✅ | ✅ |
| **Botão Reset** | ✅ | ✅ | ✅ |
| **OTA Update** | ❌ | ❌ | ✅ |
| **Tamanho** | 32 KB | 32 KB | 33 KB |
| **Status** | **RECOMENDADO** | Para testes | Experimental |

## 📁 Arquivos

```
src/
├── main.cpp                    ← Versão ativa (atualizado automaticamente)
├── main_production.cpp         ← ⭐ PRODUÇÃO (RECOMENDADO)
├── main_development.cpp        ← Desenvolvimento (servidor local)
└── main_with_ota.cpp           ← Experimental (com OTA)
```

## 🔄 Como Trocar de Versão

### Ativar Production (Recomendado)
```bash
./switch_version.sh production
```

### Ativar Development (Testes locais)
```bash
./switch_version.sh development
```

### Ativar OTA (Experimental)
```bash
./switch_version.sh ota
```

## 📝 Detalhes das Versões

### ⭐ main_production.cpp
**Status:** RECOMENDADO PARA USO EM CAMPO

**Características:**
- ✅ Servidor de produção (145.223.95.178)
- ✅ Estável e testado
- ✅ Sem recursos experimentais
- ✅ Ideal para dispositivos instalados
- ✅ Suporte completo a configuração via AP
- ✅ Controle MQTT funcional
- ✅ Gerenciamento de conexão robusto

**Quando usar:**
- Dispositivos em produção
- Instalações em campo
- Ambientes críticos
- Quando estabilidade é prioridade

**Limitações:**
- ❌ Não suporta atualização OTA (precisa USB)

---

### 🔧 main_development.cpp
**Status:** DESENVOLVIMENTO

**Características:**
- ✅ Servidor local (10.102.0.115)
- ✅ Mesmas funcionalidades que production
- ✅ Logs mais verbosos
- ✅ Ideal para testes

**Quando usar:**
- Desenvolvimento local
- Testes de funcionalidades
- Debug de problemas
- Validação antes de produção

**Diferença principal:**
- Aponta para servidor de desenvolvimento (rede local)

---

### 🧪 main_with_ota.cpp
**Status:** EXPERIMENTAL - NÃO RECOMENDADO PARA PRODUÇÃO

**Características:**
- ✅ Tudo do production +
- ✅ Atualização OTA via MQTT
- ✅ Download de firmware via HTTP
- ✅ Verificação MD5
- ✅ LED de status OTA

**Quando usar:**
- Testes de OTA em bancada
- Prototipagem
- Validação de funcionalidade OTA

**Por que não usar em produção:**
- ⚠️ Recurso ainda em desenvolvimento
- ⚠️ Não totalmente testado
- ⚠️ Pode ter bugs
- ⚠️ Aumenta complexidade do firmware
- ⚠️ Requer infraestrutura OTA adicional

**Comandos OTA (quando disponível):**
```json
{
  "command": "ota_update",
  "ota_id": "123",
  "firmware_version": "1.1.0",
  "firmware_url": "http://firmware.iot.local/firmware/sensor/latest/firmware.bin",
  "checksum_md5": "hash_aqui",
  "force_update": false
}
```

## 🎯 Recomendação Oficial

### Para Produção: USE `main_production.cpp`

**Motivos:**
1. **Estável** - Testado em campo
2. **Confiável** - Sem surpresas
3. **Simples** - Fácil de diagnosticar
4. **Suportado** - Documentação completa
5. **Comprovado** - Funcionando em dispositivos reais

### Para Desenvolvimento: USE `main_development.cpp`

**Motivos:**
1. Servidor local (mais rápido)
2. Logs detalhados
3. Ciclo de desenvolvimento ágil

### Para Testes OTA: USE `main_with_ota.cpp` (com cautela)

**Motivos:**
1. Validar conceito OTA
2. Testes controlados
3. Ambiente de laboratório apenas

## 📋 Histórico de Versões

| Data | Versão | Mudanças |
|------|--------|----------|
| 24/11/2025 | 1.0.0 | Versão production ativada como padrão |
| 19/11/2024 | 1.0.0 | Última atualização production e OTA |
| 18/11/2024 | 1.0.0 | Última atualização development |
| 16/09/2025 | 1.0.0 | Versão inicial estável |

## 🔍 Verificar Versão Ativa

```bash
# Ver primeiras linhas do arquivo ativo
head -20 src/main.cpp

# Ver tamanho dos arquivos
ls -lh src/main*.cpp

# Ver última modificação
ls -lt src/main*.cpp | head -1
```

## 📚 Documentação

| Documento | Descrição |
|-----------|-----------|
| `README.md` | Documentação principal do firmware |
| `GUIA_FIRMWARE_PRODUCTION.md` | Guia completo do firmware production |
| `FIRMWARE_REFERENCIA_RAPIDA.md` | Referência rápida de comandos |
| `FIRMWARE_INFO.md` | Este arquivo (comparação de versões) |

## 🆘 Suporte

Para dúvidas ou problemas:
1. Consulte o guia completo: `GUIA_FIRMWARE_PRODUCTION.md`
2. Verifique troubleshooting no README
3. Revise logs do serial monitor
4. Teste em bancada antes de campo

---

**Sistema:** Octus IoT  
**Versão Recomendada:** main_production.cpp  
**Última Atualização:** 24/11/2025

