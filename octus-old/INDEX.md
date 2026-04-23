# 📚 Índice Completo - Octus IOT

## 🚀 **Scripts de Deploy e Gerenciamento**

### 🔄 **Deploy**
| Script | Descrição | Uso |
|--------|-----------|-----|
| `deploy-code-only.sh` | Deploy seletivo (preserva .env) | **DIÁRIO** ⭐ |
| `deploy-production-auto.sh` | Deploy automático completo | Setup inicial |
| `rsync-complete.sh` | Sincronização completa de arquivos | Sincronização manual |

### 💾 **Backup e Restauração**
| Script | Descrição | Uso |
|--------|-----------|-----|
| `backup-production.sh` | Backup completo (.env + DB + logs) | Antes de mudanças importantes |
| `restore-backup.sh [timestamp]` | Restaurar backup específico | Emergência |

### 📊 **Monitoramento**
| Script | Descrição | Uso |
|--------|-----------|-----|
| `health-check.sh` | Verificação completa do sistema | Verificar saúde do sistema |
| `view-production-logs.sh` | Menu interativo de logs | Troubleshooting |
| `compare-local-prod.sh` | Comparar local vs produção | Ver diferenças entre ambientes |

### ⚙️ **Configuração do Servidor**
| Script | Descrição | Uso |
|--------|-----------|-----|
| `setup-server-complete.sh` | Setup completo do servidor | Setup inicial |
| `setup-complete.sh` | Configuração Laravel | Após transferir arquivos |
| `create-systemd-services.sh` | Criar serviços systemd | Auto-restart ⭐ |
| `install-server-requirements.sh` | Instalar PHP/Composer | Preparar servidor |

### 🌐 **Configuração de Domínio e HTTPS**
| Script | Descrição | Uso |
|--------|-----------|-----|
| `setup-nginx-domain.sh` | Configurar Nginx + domínio | Setup SSL |
| `update-env-domain.sh` | Atualizar .env para domínio | Após configurar DNS |
| `fix-https-proxy.sh` | Corrigir HTTPS no Laravel | Problemas com SSL |
| `force-https-complete.sh` | Forçar HTTPS em tudo | Garantir HTTPS |
| `update-nginx-headers.sh` | Atualizar headers Nginx | Proxy reverso |

### 🔧 **Configuração de Arquivos**
| Script | Descrição | Uso |
|--------|-----------|-----|
| `create-env-files.sh` | Criar .env para cada projeto | Setup inicial |
| `configure-laravel.sh` | Configurar Laravel apps | Após instalar |
| `install-dependencies.sh` | Instalar dependências | Após clone |

### 🔍 **Utilitários**
| Script | Descrição | Uso |
|--------|-----------|-----|
| `check-hardcoded-ips.sh` | Procurar IPs hardcoded | Auditoria de código |
| `fix-hardcoded-ips.sh` | Corrigir IPs hardcoded | Atualização de IPs |
| `init-git.sh` | Inicializar repositório Git | Versionar código |

---

## 📖 **Documentação**

### 📘 **Guias Completos**
| Documento | Conteúdo | Quando Ler |
|-----------|----------|------------|
| `WORKFLOW_DESENVOLVIMENTO.md` | Workflow local → produção | **ESSENCIAL** ⭐ |
| `GUIA_SERVICOS_SYSTEMD.md` | Gerenciar serviços systemd | Gerenciamento de serviços |
| `GUIA_DOMINIO.md` | Configurar domínio e SSL | Setup de domínio |
| `GUIA_DEPLOY_MANUAL.md` | Deploy manual passo a passo | Referência de deploy |
| `RESUMO_DEPLOY.md` | Resumo do processo de deploy | Visão geral |

### ⚡ **Guias Rápidos**
| Documento | Conteúdo | Quando Ler |
|-----------|----------|------------|
| `GUIA_RAPIDO.md` | Comandos mais usados | **REFERÊNCIA DIÁRIA** ⭐ |
| `INDEX.md` | Este arquivo - índice completo | Navegação |
| `README.md` | Introdução ao projeto | Primeiro contato |

---

## 🎯 **Workflows por Cenário**

### **1️⃣ Desenvolvimento Normal (Dia a Dia)**

```bash
# 1. Editar código local
cd /home/darley/octus/mqtt
nano app/Http/Controllers/MeuController.php

# 2. Testar localmente
php artisan serve --host=0.0.0.0 --port=8000

# 3. Deploy
cd /home/darley/octus
bash deploy-code-only.sh

# 4. Verificar
bash health-check.sh
```

### **2️⃣ Mudança Importante (Com Backup)**

```bash
# 1. Fazer backup
bash backup-production.sh

# 2. Deploy
bash deploy-code-only.sh

# 3. Verificar
bash health-check.sh

# 4. Se algo der errado
bash restore-backup.sh [timestamp]
```

### **3️⃣ Troubleshooting (Algo está Errado)**

```bash
# 1. Ver status
bash health-check.sh

# 2. Ver logs
bash view-production-logs.sh

# 3. Reiniciar serviços
sshpass -p "yhvh77" ssh darley@145.223.95.178 \
  "echo 'yhvh77' | sudo -S systemctl restart octus-*"

# 4. Se não resolver, restaurar backup
bash restore-backup.sh [timestamp]
```

### **4️⃣ Setup Inicial (Primeira Vez)**

```bash
# 1. Instalar requisitos no servidor
bash install-server-requirements.sh

# 2. Fazer setup completo
bash setup-server-complete.sh

# 3. Configurar domínio e SSL
bash setup-nginx-domain.sh

# 4. Criar serviços systemd
bash create-systemd-services.sh

# 5. Verificar tudo
bash health-check.sh
```

### **5️⃣ Comparar Ambientes**

```bash
# Ver diferenças entre local e produção
bash compare-local-prod.sh
```

---

## 📊 **Arquivos de Configuração**

| Arquivo | Descrição | Localização |
|---------|-----------|-------------|
| `.gitignore` | Arquivos ignorados pelo Git | `/home/darley/octus/.gitignore` |
| `.env` | Configurações do ambiente | Cada projeto Laravel |
| `.env.example` | Template de configuração | Cada projeto Laravel |
| `composer.json` | Dependências PHP | Cada projeto Laravel |
| `app-config.php` | Configuração centralizada | `/config/app-config.php` |
| `environment.production.env` | Variáveis de produção | `/config/environment.production.env` |

---

## 🌐 **URLs e Serviços**

### **Produção**
- **API Backend:** https://api.octus.cloud (porta 8000)
- **Interface Web:** https://octus.cloud (porta 8001)
- **App Mobile:** https://app.octus.cloud (porta 8002)

### **Servidor**
- **IP:** 145.223.95.178
- **Usuário:** darley
- **SSH:** `sshpass -p "yhvh77" ssh darley@145.223.95.178`

### **Serviços Systemd**
- `octus-api.service` - Backend API
- `octus-web.service` - Interface Web
- `octus-app.service` - App Mobile

---

## 🔑 **Comandos Essenciais**

### **Ver Status**
```bash
bash health-check.sh
```

### **Deploy Rápido**
```bash
bash deploy-code-only.sh
```

### **Backup Rápido**
```bash
bash backup-production.sh
```

### **Ver Logs**
```bash
bash view-production-logs.sh
```

### **Comparar Ambientes**
```bash
bash compare-local-prod.sh
```

### **Reiniciar Serviços**
```bash
sshpass -p "yhvh77" ssh darley@145.223.95.178 \
  "echo 'yhvh77' | sudo -S systemctl restart octus-*"
```

---

## 📁 **Estrutura de Diretórios**

```
/home/darley/octus/
│
├── 📱 mqtt/                              # Backend API (Laravel)
│   ├── app/                              # Lógica da aplicação
│   ├── routes/api.php                    # Rotas da API
│   ├── database/migrations/              # Migrações do banco
│   └── .env                              # ⚠️ Não commitar
│
├── 🌐 iot-config-web-laravel/            # Interface Web (Laravel)
│   ├── resources/views/                  # Templates Blade
│   └── .env                              # ⚠️ Não commitar
│
├── 📱 iot-config-app-laravel/            # App Mobile (Laravel)
│   └── .env                              # ⚠️ Não commitar
│
├── 🔧 esp32-octus-platformio/            # Firmware ESP32 (PlatformIO)
│   └── src/main.cpp
│
├── 🔧 firmwares/                         # Firmware ESP32 (Arduino)
│   └── octopus-frm-ESP32-on-off/
│
├── 📋 config/                            # Configurações globais
│   ├── app-config.php
│   └── environment.production.env
│
├── 💾 backups/                           # Backups automáticos
│   └── [timestamp]/
│
├── 🚀 Scripts de Deploy (.sh)
│   ├── deploy-code-only.sh              # ⭐ MAIS USADO
│   ├── backup-production.sh
│   ├── health-check.sh
│   ├── view-production-logs.sh
│   └── ... (outros scripts)
│
└── 📚 Documentação (.md)
    ├── GUIA_RAPIDO.md                    # ⭐ REFERÊNCIA DIÁRIA
    ├── WORKFLOW_DESENVOLVIMENTO.md       # ⭐ ESSENCIAL
    ├── INDEX.md                          # Este arquivo
    └── ... (outros guias)
```

---

## 🆘 **Suporte Rápido**

### **Site Fora do Ar?**
```bash
bash health-check.sh                      # Ver o que está errado
bash view-production-logs.sh              # Ver logs
sshpass -p "yhvh77" ssh darley@145.223.95.178 \
  "echo 'yhvh77' | sudo -S systemctl restart octus-*"  # Reiniciar
```

### **Erro Após Deploy?**
```bash
bash restore-backup.sh [timestamp]        # Restaurar backup
```

### **Não Sei Qual Script Usar?**
```bash
cat GUIA_RAPIDO.md                        # Ver guia rápido
```

---

## 🎓 **Ordem de Leitura Recomendada**

1. **Iniciante:**
   1. `README.md` - Entender o projeto
   2. `GUIA_RAPIDO.md` - Comandos essenciais ⭐
   3. `WORKFLOW_DESENVOLVIMENTO.md` - Como desenvolver ⭐
   4. `INDEX.md` - Este arquivo

2. **Intermediário:**
   1. `GUIA_SERVICOS_SYSTEMD.md` - Gerenciar serviços
   2. `GUIA_DOMINIO.md` - SSL e domínio
   3. Scripts individuais conforme necessidade

3. **Avançado:**
   1. Todos os scripts `.sh`
   2. Customizar workflows
   3. Criar seus próprios scripts

---

## ⭐ **Top 5 Mais Importantes**

1. **`GUIA_RAPIDO.md`** - Referência diária
2. **`WORKFLOW_DESENVOLVIMENTO.md`** - Como trabalhar
3. **`deploy-code-only.sh`** - Deploy diário
4. **`health-check.sh`** - Verificar sistema
5. **`backup-production.sh`** - Segurança

---

## 📞 **Contatos de Emergência**

- **Servidor:** darley@145.223.95.178 (senha: yhvh77)
- **Banco:** root@localhost (senha: yhvh77)
- **Domínio:** octus.cloud

---

**✨ Documentação completa e organizada! Use como referência!**

*Última atualização: $(date)*

