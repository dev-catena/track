# ⚡ Guia Rápido - Octus IOT

## 🚀 Comandos Mais Usados

### 📝 **Desenvolvimento Diário**

```bash
# 1. Fazer alterações no código local
cd /home/darley/octus/mqtt
nano app/Http/Controllers/MeuController.php

# 2. Testar localmente
php artisan serve --host=0.0.0.0 --port=8000

# 3. Deploy para produção (se tudo OK)
cd /home/darley/octus
bash deploy-code-only.sh

# 4. Ver se funcionou
curl https://api.octus.cloud
```

---

## 🔧 **Scripts Disponíveis**

| Script | Descrição | Quando Usar |
|--------|-----------|-------------|
| `deploy-code-only.sh` | Deploy apenas do código | **USO DIÁRIO** - após desenvolver |
| `backup-production.sh` | Backup completo | Antes de mudanças importantes |
| `view-production-logs.sh` | Ver logs do servidor | Quando algo der errado |
| `compare-local-prod.sh` | Comparar local vs produção | Para verificar diferenças |
| `restore-backup.sh [data]` | Restaurar backup | Emergência - algo quebrou |
| `init-git.sh` | Inicializar Git | Uma vez, no início |

---

## 📊 **Gerenciar Serviços**

```bash
# Ver status de todos os serviços
sshpass -p "yhvh77" ssh darley@145.223.95.178 "echo 'yhvh77' | sudo -S systemctl status octus-*"

# Reiniciar um serviço específico
sshpass -p "yhvh77" ssh darley@145.223.95.178 "echo 'yhvh77' | sudo -S systemctl restart octus-api"

# Ver logs em tempo real
sshpass -p "yhvh77" ssh darley@145.223.95.178 "echo 'yhvh77' | sudo -S journalctl -u octus-api -f"
```

---

## 🔍 **Troubleshooting Rápido**

### ❌ **Site fora do ar**

```bash
# 1. Ver logs
bash view-production-logs.sh

# 2. Verificar serviços
sshpass -p "yhvh77" ssh darley@145.223.95.178 "echo 'yhvh77' | sudo -S systemctl status octus-*"

# 3. Reiniciar serviços
sshpass -p "yhvh77" ssh darley@145.223.95.178 "echo 'yhvh77' | sudo -S systemctl restart octus-api octus-web octus-app"
```

### 🐛 **Erro após deploy**

```bash
# 1. Ver último backup
ls -lh backups/

# 2. Restaurar backup
bash restore-backup.sh [data_do_backup]
```

### 🔐 **Erro de permissão**

```bash
# Corrigir permissões no servidor
sshpass -p "yhvh77" ssh darley@145.223.95.178 << 'EOF'
cd /home/darley/octus
sudo chown -R darley:darley mqtt iot-config-web-laravel iot-config-app-laravel
sudo chmod -R 755 mqtt iot-config-web-laravel iot-config-app-laravel
sudo chmod -R 775 mqtt/storage iot-config-web-laravel/storage iot-config-app-laravel/storage
EOF
```

---

## 📁 **Estrutura de Arquivos**

```
/home/darley/octus/
├── mqtt/                              # API Backend
│   ├── app/                          # Controllers, Models
│   ├── routes/api.php                # Rotas da API
│   └── .env                          # ⚠️ NUNCA COMMITAR
├── iot-config-web-laravel/           # Interface Web
│   └── .env                          # ⚠️ NUNCA COMMITAR
├── iot-config-app-laravel/           # App Mobile
│   └── .env                          # ⚠️ NUNCA COMMITAR
├── backups/                          # Backups automáticos
└── *.sh                              # Scripts de gerenciamento
```

---

## ⚠️ **NUNCA FAÇA ISSO**

```bash
# ❌ NÃO sobrescrever .env de produção
# ❌ NÃO fazer deploy sem testar local
# ❌ NÃO commitar senhas no Git
# ❌ NÃO editar direto no servidor
# ❌ NÃO fazer deploy sem backup
```

---

## ✅ **SEMPRE FAÇA ISSO**

```bash
# ✅ Testar localmente antes
php artisan serve

# ✅ Fazer backup antes de deploy importante
bash backup-production.sh

# ✅ Usar deploy-code-only.sh (preserva .env)
bash deploy-code-only.sh

# ✅ Verificar logs após deploy
bash view-production-logs.sh

# ✅ Testar o site após deploy
curl https://api.octus.cloud
```

---

## 🔄 **Workflow Completo**

```bash
# 1️⃣ DESENVOLVER LOCAL
cd /home/darley/octus/mqtt
nano app/Http/Controllers/DeviceController.php

# 2️⃣ TESTAR LOCAL
php artisan serve --host=0.0.0.0 --port=8000
curl http://localhost:8000/api/devices

# 3️⃣ BACKUP (se mudança importante)
cd /home/darley/octus
bash backup-production.sh

# 4️⃣ DEPLOY
bash deploy-code-only.sh

# 5️⃣ TESTAR PRODUÇÃO
curl https://api.octus.cloud/api/devices

# 6️⃣ VERIFICAR LOGS (se necessário)
bash view-production-logs.sh
```

---

## 📞 **Acesso SSH Direto**

```bash
# Conectar no servidor
sshpass -p "yhvh77" ssh darley@145.223.95.178

# Navegar até o projeto
cd /home/darley/octus

# Ver status dos serviços
sudo systemctl status octus-*

# Ver logs em tempo real
sudo journalctl -u octus-api -f
```

---

## 🎯 **Comandos de Uma Linha**

```bash
# Deploy rápido
cd /home/darley/octus && bash deploy-code-only.sh

# Backup rápido
cd /home/darley/octus && bash backup-production.sh

# Ver logs rápido
cd /home/darley/octus && bash view-production-logs.sh

# Comparar ambientes
cd /home/darley/octus && bash compare-local-prod.sh

# Reiniciar tudo
sshpass -p "yhvh77" ssh darley@145.223.95.178 "echo 'yhvh77' | sudo -S systemctl restart octus-*"
```

---

## 📚 **Documentação Completa**

- 📖 [Workflow de Desenvolvimento](WORKFLOW_DESENVOLVIMENTO.md)
- 🚀 [Guia de Deploy Manual](GUIA_DEPLOY_MANUAL.md)
- 🌐 [Configuração de Domínio](GUIA_DOMINIO.md)
- ⚙️ [Serviços Systemd](GUIA_SERVICOS_SYSTEMD.md)
- 📝 [Resumo do Deploy](RESUMO_DEPLOY.md)

---

## 🆘 **Emergência**

```bash
# 1. Parar tudo
sshpass -p "yhvh77" ssh darley@145.223.95.178 "echo 'yhvh77' | sudo -S systemctl stop octus-*"

# 2. Ver último backup
ls -lh backups/

# 3. Restaurar
bash restore-backup.sh [timestamp]

# 4. Ligar tudo
sshpass -p "yhvh77" ssh darley@145.223.95.178 "echo 'yhvh77' | sudo -S systemctl start octus-*"
```

---

## 📊 **URLs de Produção**

- 🌐 **API:** https://api.octus.cloud
- 🌐 **Web:** https://octus.cloud  
- 🌐 **App:** https://app.octus.cloud

---

**⚡ Mantenha este guia à mão para referência rápida!**

