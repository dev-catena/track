# 🔄 Workflow Desenvolvimento → Produção

## 📋 **Visão Geral**

Este guia mostra como desenvolver localmente e fazer deploy para produção sem conflitar configurações.

---

## 🏗️ **Estrutura de Ambientes**

### 🖥️ **LOCAL (Desenvolvimento)**
- IP: `127.0.0.1` / `localhost`
- Portas: 8000, 8001, 8002
- Banco: MySQL local
- Configurações: `.env` com valores locais

### 🌐 **PRODUÇÃO (Servidor)**
- IP/Domínio: `145.223.95.178` / `octus.cloud`
- Portas: 8000, 8001, 8002
- Banco: MySQL remoto
- Configurações: `.env` com valores de produção

---

## 📁 **Arquivos que NUNCA devem ser sincronizados**

Estes arquivos são específicos de cada ambiente e **NÃO devem ser sobrescritos**:

```
✗ .env                          (configurações locais vs produção)
✗ storage/logs/*                (logs específicos de cada ambiente)
✗ bootstrap/cache/*             (cache compilado)
✗ vendor/*                      (dependências - reinstalar com composer)
✗ node_modules/*                (dependências - reinstalar com npm)
✗ .git/*                        (histórico do git - se usar)
```

---

## ✅ **Arquivos que DEVEM ser sincronizados**

Estes são o código-fonte e devem ser atualizados:

```
✓ app/*                         (Models, Controllers, etc)
✓ config/*                      (arquivos de configuração)
✓ database/migrations/*         (migrações de banco)
✓ database/seeders/*            (seeders de dados)
✓ public/*                      (assets públicos)
✓ resources/*                   (views, CSS, JS)
✓ routes/*                      (rotas da aplicação)
✓ composer.json                 (dependências PHP)
✓ package.json                  (dependências JS)
```

---

## 🔧 **Sistema de Configuração Multi-Ambiente**

### 1. Manter `.env` separados

**LOCAL:** `/home/darley/octus/mqtt/.env`
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_DATABASE=mqtt_local
DB_USERNAME=root
DB_PASSWORD=sua_senha_local
```

**PRODUÇÃO (servidor):** `/home/darley/octus/mqtt/.env`
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.octus.cloud

DB_HOST=127.0.0.1
DB_DATABASE=mqtt
DB_USERNAME=root
DB_PASSWORD=yhvh77
```

### 2. Usar `.env.example` como template

Mantenha um `.env.example` atualizado com todas as variáveis necessárias:

```bash
# Em cada projeto Laravel
cp .env.example .env.local
cp .env.example .env.production

# Editar cada um com valores apropriados
```

---

## 🚀 **Workflow de Desenvolvimento**

### **1️⃣ Desenvolver Localmente**

```bash
# 1. Criar/editar código no seu editor
cd /home/darley/octus/mqtt
nano app/Http/Controllers/MeuController.php

# 2. Testar localmente
php artisan serve --host=0.0.0.0 --port=8000

# 3. Testar no navegador
curl http://localhost:8000/api/test
```

### **2️⃣ Testar Alterações Localmente**

```bash
# Limpar cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rodar migrações (se houver)
php artisan migrate

# Testar tudo funciona
php artisan test  # se tiver testes automatizados
```

### **3️⃣ Deploy para Produção**

```bash
# Execute o script de deploy
cd /home/darley/octus
bash deploy-code-only.sh
```

---

## 📝 **Scripts de Deploy**

### **Script 1: Deploy Apenas Código (RECOMENDADO)**

Este script sincroniza **apenas o código**, preservando `.env` e configurações:

```bash
bash deploy-code-only.sh
```

**O que faz:**
- ✅ Sincroniza código-fonte (app, routes, resources, etc)
- ✅ Preserva `.env` de produção
- ✅ Preserva logs e cache
- ✅ Roda `composer install` no servidor
- ✅ Roda migrações no servidor
- ✅ Limpa cache no servidor
- ✅ Reinicia serviços

### **Script 2: Backup de Produção**

Antes de qualquer deploy, faça backup:

```bash
bash backup-production.sh
```

**O que faz:**
- ✅ Baixa `.env` de produção
- ✅ Baixa banco de dados
- ✅ Salva backup com timestamp

### **Script 3: Deploy Completo (CUIDADO)**

Use apenas quando precisar resetar tudo:

```bash
bash deploy-complete.sh
```

**⚠️ ATENÇÃO:** Sobrescreve TUDO, inclusive `.env`

---

## 🔄 **Exemplo Prático**

### Cenário: Adicionar novo recurso

```bash
# === NO SEU PC (LOCAL) ===

# 1. Criar nova migration
cd /home/darley/octus/mqtt
php artisan make:migration create_devices_table

# 2. Editar migration
nano database/migrations/2025_11_19_create_devices_table.php

# 3. Criar controller
php artisan make:controller DeviceController

# 4. Editar controller
nano app/Http/Controllers/DeviceController.php

# 5. Adicionar rota
nano routes/api.php

# 6. Testar localmente
php artisan migrate
php artisan serve
curl http://localhost:8000/api/devices

# 7. Se funcionar, fazer deploy
cd /home/darley/octus
bash deploy-code-only.sh
```

---

## 📊 **Comparação dos Scripts de Deploy**

| Script | Sincroniza Código | Preserva .env | Preserva Logs | Roda Migrations | Uso |
|--------|------------------|---------------|---------------|-----------------|-----|
| `deploy-code-only.sh` | ✅ | ✅ | ✅ | ✅ | **Desenvolvimento diário** |
| `backup-production.sh` | ❌ | ✅ (baixa) | ✅ (baixa) | ❌ | **Antes de qualquer deploy** |
| `deploy-complete.sh` | ✅ | ❌ | ❌ | ✅ | **Setup inicial ou reset** |
| `rsync-complete.sh` | ✅ | ❌ | ❌ | ❌ | **Sincronização completa** |

---

## 🎯 **Melhores Práticas**

### ✅ **SEMPRE**

1. ✅ Testar localmente antes de fazer deploy
2. ✅ Fazer backup antes de deploy importante
3. ✅ Usar `deploy-code-only.sh` para updates diários
4. ✅ Verificar logs após deploy: `sudo journalctl -u octus-* -n 50`
5. ✅ Testar a aplicação após deploy: `curl https://api.octus.cloud`

### ❌ **NUNCA**

1. ❌ Editar `.env` diretamente no servidor (use scripts)
2. ❌ Fazer deploy sem testar localmente
3. ❌ Sobrescrever `.env` de produção sem backup
4. ❌ Fazer deploy direto para produção sem backup
5. ❌ Commitar `.env` no Git (se usar)

---

## 🔐 **Gerenciamento de Senhas e Segredos**

### Manter segredos seguros:

```bash
# LOCAL: Criar arquivo de segredos (NÃO commitar)
nano ~/.octus-secrets

# Conteúdo:
PROD_DB_PASSWORD=yhvh77
PROD_APP_KEY=base64:...
PROD_SSH_PASSWORD=yhvh77
```

### Usar variáveis de ambiente:

```bash
# No seu .bashrc ou .zshrc
export OCTUS_PROD_DB_PASS="yhvh77"
export OCTUS_PROD_SSH_PASS="yhvh77"
```

---

## 🔍 **Verificar Diferenças Entre Ambientes**

### Comparar arquivos locais vs produção:

```bash
# Baixar .env de produção para comparar
sshpass -p "yhvh77" scp darley@145.223.95.178:/home/darley/octus/mqtt/.env /tmp/prod.env

# Comparar
diff /home/darley/octus/mqtt/.env /tmp/prod.env
```

---

## 🐛 **Troubleshooting**

### Deploy falhou no meio

```bash
# 1. Ver logs
bash view-production-logs.sh

# 2. Restaurar backup se necessário
bash restore-backup.sh [data]

# 3. Tentar novamente
bash deploy-code-only.sh
```

### Código funciona local mas não em produção

```bash
# Verificar diferenças de ambiente
sshpass -p "yhvh77" ssh darley@145.223.95.178 "php -v"  # versão PHP
sshpass -p "yhvh77" ssh darley@145.223.95.178 "php -m"  # extensões instaladas

# Ver logs de erro
bash view-production-logs.sh
```

### Banco de dados diferente

```bash
# Baixar dump de produção para testar localmente
bash backup-production.sh

# Importar localmente
mysql -u root -p mqtt_local < backups/[data]/mqtt_database.sql
```

---

## 📚 **Próximos Passos**

1. **Git (Recomendado):** Versionar código com Git/GitHub
2. **Testes Automatizados:** Criar testes com PHPUnit
3. **CI/CD:** Automatizar deploy com GitHub Actions
4. **Staging:** Criar ambiente de staging para testes
5. **Rollback:** Sistema de rollback automático

---

## 🎯 **Resumo Rápido**

```bash
# Workflow diário:
1. Desenvolver localmente (/home/darley/octus/mqtt)
2. Testar localmente (php artisan serve)
3. Se OK, fazer deploy (bash deploy-code-only.sh)
4. Verificar produção (https://api.octus.cloud)

# Em caso de problemas:
1. Ver logs (bash view-production-logs.sh)
2. Restaurar backup (bash restore-backup.sh)
```

---

**Desenvolvimento organizado e seguro! 🚀**

