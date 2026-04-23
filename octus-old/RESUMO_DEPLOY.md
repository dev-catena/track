# 📋 RESUMO DO DEPLOY - SISTEMA IOT

## ✅ O QUE FOI FEITO

### 1. Configurações Atualizadas
- ✅ IP de produção alterado de `181.215.135.118` para `145.223.95.178`
- ✅ Credenciais MySQL atualizadas (root/yhvh77)
- ✅ Arquivos de configuração atualizados:
  - `config/environment.production.env`
  - `config/app-config.php`
  - Firmwares ESP32 (`.ino` e `.cpp`)
  - Views Blade com chamadas de API

### 2. Scripts Criados
- ✅ `deploy-to-server.sh` - Deploy automático completo
- ✅ `setup-server-complete.sh` - Instalação de requisitos
- ✅ `start-services.sh` - Iniciar todos os serviços
- ✅ `stop-services.sh` - Parar todos os serviços
- ✅ `check-hardcoded-ips.sh` - Verificar IPs hardcoded
- ✅ `fix-hardcoded-ips.sh` - Corrigir IPs hardcoded
- ✅ `setup-systemd-services.sh` - Configurar serviços systemd

### 3. Arquivos Transferidos
- ✅ Todos os arquivos do projeto transferidos para `/home/darley/octus/`:
  - `mqtt/` - Backend API principal
  - `iot-config-web-laravel/` - Interface Web
  - `iot-config-app-laravel/` - App Mobile
  - `firmwares/` - Firmwares ESP32
  - `esp32-octus-platformio/` - Projeto PlatformIO
  - `config/` - Configurações centralizadas
  - `scripts/` - Scripts auxiliares

### 4. Banco de Dados
- ✅ Banco `mqtt` criado com charset utf8mb4
- ✅ Migrations prontas para executar
- ✅ Seeders configurados com dados de teste

---

## ⚠️ PRÓXIMOS PASSOS NECESSÁRIOS

### IMPORTANTE: O servidor precisa de instalação manual

Como o servidor não tem PHP instalado e requer permissões sudo, você precisa:

### 🔑 PASSO 1: Conectar ao Servidor
```bash
ssh darley@145.223.95.178
# Senha: yhvh77
```

### 📦 PASSO 2: Instalar PHP e Composer

No servidor, execute:
```bash
# Instalar PHP
sudo apt-get update
sudo apt-get install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql \
    php8.3-mbstring php8.3-xml php8.3-zip php8.3-curl php8.3-gd \
    php8.3-bcmath php8.3-intl unzip git

# Instalar Composer
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# Verificar
php -v
composer --version
```

### 🔨 PASSO 3: Instalar Dependências

```bash
cd /home/darley/octus

# MQTT Backend
cd mqtt
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache

# IOT Config Web
cd ../iot-config-web-laravel
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate --force
php artisan config:cache

# IOT Config App
cd ../iot-config-app-laravel
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate --force
php artisan config:cache
```

### 🚀 PASSO 4: Iniciar Serviços

```bash
cd /home/darley/octus
chmod +x start-services.sh stop-services.sh
./start-services.sh
```

---

## 🌐 URLs DO SISTEMA

Após iniciar os serviços, acesse:

- **Backend API**: http://145.223.95.178:8000
- **Interface Web**: http://145.223.95.178:8001
- **App Config**: http://145.223.95.178:8002

---

## 🔐 CREDENCIAIS DE ACESSO

### Usuários de Teste (criados pelo seeding):

| Perfil | Email | Senha |
|--------|-------|-------|
| Admin | admin@sistema.com | admin123 |
| Gerente | carlos.silva@techcorp.com | gerente123 |
| Supervisor | ana.santos@techcorp.com | supervisor123 |
| Técnico | pedro.oliveira@techcorp.com | tecnico123 |
| Operador | maria.costa@techcorp.com | operador123 |

### MySQL:
- **Host**: 127.0.0.1
- **Port**: 3306
- **Database**: mqtt
- **Username**: root
- **Password**: yhvh77

---

## 📁 ESTRUTURA DO SERVIDOR

```
/home/darley/octus/
├── mqtt/                           # Backend API (porta 8000)
├── iot-config-web-laravel/        # Interface Web (porta 8001)
├── iot-config-app-laravel/        # App Mobile (porta 8002)
├── firmwares/                     # Firmwares ESP32
├── esp32-octus-platformio/        # Projeto PlatformIO
├── config/                        # Configurações centralizadas
├── scripts/                       # Scripts auxiliares
├── start-services.sh              # Iniciar serviços
├── stop-services.sh               # Parar serviços
├── GUIA_DEPLOY_MANUAL.md          # Guia completo passo a passo
└── RESUMO_DEPLOY.md               # Este arquivo
```

---

## 🔧 COMANDOS ÚTEIS

### Gerenciar Serviços
```bash
# Iniciar
./start-services.sh

# Parar
./stop-services.sh

# Ver status
ps aux | grep "php artisan serve"

# Ver logs
tail -f /tmp/mqtt-backend.log
tail -f /tmp/iot-web.log
tail -f /tmp/iot-app.log
```

### Laravel
```bash
# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Ver rotas
php artisan route:list

# Executar migrations
php artisan migrate

# Executar seeders
php artisan db:seed
```

### Sistema
```bash
# Ver portas abertas
sudo lsof -i :8000
sudo lsof -i :8001
sudo lsof -i :8002

# Verificar MySQL
sudo systemctl status mysql
mysql -uroot -pyhvh77 -e "SHOW DATABASES;"

# Firewall (se necessário)
sudo ufw allow 8000/tcp
sudo ufw allow 8001/tcp
sudo ufw allow 8002/tcp
sudo ufw reload
```

---

## 📊 DADOS POPULADOS NO SISTEMA

Após executar `php artisan db:seed`, o sistema terá:

- **6 Empresas** de diferentes setores
- **20 Departamentos** com hierarquia
- **10 Tipos de Dispositivos** IoT
- **12 Usuários** com diferentes perfis
- **265 Tópicos MQTT** pré-configurados

---

## 🎯 ENDPOINTS DA API

### Autenticação
```bash
POST /api/auth/login
POST /api/auth/register
POST /api/auth/logout
GET  /api/auth/me
```

### Dispositivos
```bash
GET    /api/pending-devices
POST   /api/pending-devices/{id}/activate
GET    /api/device-types
POST   /api/device-types
```

### Empresas e Departamentos
```bash
GET    /api/companies
POST   /api/companies
GET    /api/departments
POST   /api/departments
```

### Tópicos MQTT
```bash
GET    /api/mqtt/topics
POST   /api/mqtt/topics
GET    /api/mqtt/topics/{id}
```

---

## 🔥 RESOLUÇÃO DE PROBLEMAS COMUNS

### Problema: "Connection refused"
```bash
# Verificar se serviço está rodando
ps aux | grep "php artisan serve"

# Reiniciar serviço
./stop-services.sh
./start-services.sh
```

### Problema: "Permission denied"
```bash
sudo chmod -R 775 mqtt/storage
sudo chmod -R 775 mqtt/bootstrap/cache
sudo chmod -R 775 iot-config-web-laravel/storage
sudo chmod -R 775 iot-config-app-laravel/storage
```

### Problema: "Class not found"
```bash
cd mqtt
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Problema: "SQLSTATE[HY000] [2002]"
```bash
# Verificar MySQL
sudo systemctl start mysql
sudo systemctl enable mysql

# Testar conexão
mysql -uroot -pyhvh77 -e "SHOW DATABASES;"
```

---

## 📚 DOCUMENTAÇÃO ADICIONAL

Documentos disponíveis no projeto:

- `GUIA_DEPLOY_MANUAL.md` - Guia passo a passo completo
- `BACKEND_APIS_DOCUMENTATION.md` - Documentação das APIs
- `FUNCIONALIDADES_IMPLEMENTADAS.md` - Funcionalidades do sistema
- `ROTEIRO_USO_SISTEMA_IOT.md` - Como usar o sistema

---

## ✨ PRÓXIMOS PASSOS RECOMENDADOS

1. **Instalar PHP e Composer no servidor** (OBRIGATÓRIO)
2. **Instalar dependências e executar migrations** (OBRIGATÓRIO)
3. **Iniciar os serviços** (OBRIGATÓRIO)
4. Testar endpoints da API
5. Configurar Nginx como reverse proxy (OPCIONAL)
6. Configurar SSL/HTTPS (OPCIONAL)
7. Configurar broker MQTT (Mosquitto) (OPCIONAL)
8. Configurar systemd para inicialização automática (OPCIONAL)

---

## 📞 SUPORTE

Para ajuda detalhada, consulte o arquivo `GUIA_DEPLOY_MANUAL.md` no servidor.

---

**🎉 Sistema pronto para implantação!**

Autor: Assistente AI
Data: 18/11/2025
Servidor: 145.223.95.178

