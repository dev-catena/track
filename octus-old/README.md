# �� Sistema IoT Completo - Octus Project

**Sistema completo de gestão de dispositivos IoT com ESP32, Laravel e MQTT**

## 📋 Índice

- [Arquitetura do Sistema](#-arquitetura-do-sistema)
- [Instalação Rápida](#-instalação-rápida)
- [Configuração de Ambiente](#-configuração-de-ambiente)
- [Deploy](#-deploy)
- [Firmware ESP32](#-firmware-esp32)
- [Endpoints da API](#-endpoints-da-api)
- [Solução de Problemas](#-solução-de-problemas)

## 🏗 Arquitetura do Sistema

### **Componentes:**
- **🔧 Backend API (MQTT)** - Porta 8000 - Gerencia dispositivos e MQTT
- **🌐 Interface Web** - Porta 8001 - Dashboard administrativo
- **📱 App Mobile** - Porta 8002 - Gestão de dispositivos móvel
- **⚡ Firmware ESP32** - PlatformIO/Arduino IDE

### **Tecnologias:**
- **Backend:** Laravel 11 + MySQL + MQTT
- **Frontend:** Blade Templates + Bootstrap + Vite
- **Hardware:** ESP32 + WiFi + Sensores/Atuadores
- **Deploy:** Scripts automatizados

## 🚀 Instalação Rápida

### **Pré-requisitos:**
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.2 composer nodejs npm mysql-server git

# Verificar versões
php --version    # >= 8.2
composer --version
node --version   # >= 16
mysql --version
```

### **Clone e Setup:**
```bash
# Clone do repositório
git clone https://github.com/CelDarley/octus.git
cd octus

# Setup automático para desenvolvimento
chmod +x deploy-development.sh
./deploy-development.sh

# Iniciar servidores
./start_dev_servers.sh
```

### **Banco de Dados:**
```sql
-- Criar banco MySQL
CREATE DATABASE iot_config CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'iot_user'@'localhost' IDENTIFIED BY 'sua_senha';
GRANT ALL PRIVILEGES ON iot_config.* TO 'iot_user'@'localhost';
FLUSH PRIVILEGES;
```

## ⚙️ Configuração de Ambiente

### **Arquivos de Configuração:**

#### **Desenvolvimento:**
```bash
# Usar: config/environments/development.env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
DB_HOST=127.0.0.1
DB_DATABASE=iot_config
DB_USERNAME=root
DB_PASSWORD=
```

#### **Produção:**
```bash
# Usar: config/environments/production.env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://SEU_IP
DB_HOST=localhost
DB_DATABASE=iot_config
DB_USERNAME=iot_user
DB_PASSWORD="SUA_SENHA"
```

### **Configurações Específicas por Projeto:**

| Projeto | Porta | APP_URL | API_BASE_URL |
|---------|-------|---------|--------------|
| mqtt | 8000 | http://localhost:8000 | - |
| iot-config-web-laravel | 8001 | http://localhost:8001 | http://localhost:8000/api |
| iot-config-app-laravel | 8002 | http://localhost:8002 | http://localhost:8000/api |

## 🚀 Deploy

### **Desenvolvimento:**
```bash
# Setup completo
./deploy-development.sh

# Iniciar servidores
./start_dev_servers.sh

# Acessar:
# - Backend API: http://localhost:8000
# - Interface Web: http://localhost:8001
# - App Mobile: http://localhost:8002
```

### **Produção:**
```bash
# Deploy automático
chmod +x deploy-production-v2.sh
./deploy-production-v2.sh

# Gerenciar servidores
./start_servers.sh          # Iniciar
pkill -f "php.*serve"       # Parar
```

### **Verificar Status:**
```bash
# Verificar serviços rodando
ps aux | grep "php.*serve"

# Verificar logs
tail -f */storage/logs/laravel.log
```

## 📱 Firmware ESP32

### **Versões Disponíveis:**

```bash
firmwares/octopus-frm-ESP32-on-off/
├── octopus-frm-ESP32-on-off.ino              # Produção
├── octopus-frm-ESP32-on-off-development.ino  # Desenvolvimento  
└── octopus-frm-ESP32-on-off-production.ino   # Backup produção
```

### **Configuração por Ambiente:**

| Ambiente | Servidor Backend | Servidor MQTT |
|----------|------------------|---------------|
| **Desenvolvimento** | `10.102.0.115` | `10.102.0.115` |
| **Produção** | `181.215.135.118` | `181.215.135.118` |

### **Compilação com PlatformIO:**
```bash
# Instalar PlatformIO
curl -fsSL https://raw.githubusercontent.com/platformio/platformio-core-installer/master/get-platformio.py -o get-platformio.py
python3 get-platformio.py

# Usar projeto configurado
cd esp32-octus-platformio
pio run --target upload
pio device monitor
```

### **Fluxo de Configuração ESP32:**
1. **Primeira inicialização:** ESP32 cria AP "IOT-Zontec"
2. **Configuração WiFi:** Conectar em http://192.168.4.1:5000
3. **Registro automático:** Dispositivo aparece na lista pendente
4. **Ativação manual:** Configurar tipo e departamento

## 🌐 Endpoints da API

### **Dispositivos:**
```http
GET    /api/devices              # Listar dispositivos
POST   /api/devices              # Registrar dispositivo
PUT    /api/devices/{id}         # Atualizar dispositivo
DELETE /api/devices/{id}         # Excluir dispositivo
POST   /api/devices/{id}/activate # Ativar dispositivo
```

### **MQTT:**
```http
POST   /api/mqtt/send-command    # Enviar comando MQTT
GET    /api/mqtt/topics          # Listar tópicos
POST   /api/mqtt/topics          # Criar tópico
DELETE /api/mqtt/topics/{id}     # Excluir tópico
```

### **Autenticação:**
```http
POST   /api/login               # Login
POST   /api/logout              # Logout
GET    /api/user                # Dados do usuário
```

## 🔧 Solução de Problemas

### **Erro 500 - Internal Server Error:**
```bash
# Verificar logs
tail -f storage/logs/laravel.log

# Gerar APP_KEY
php artisan key:generate

# Limpar cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### **Erro 419 - Page Expired (CSRF):**
```bash
# Configurações de sessão já corrigidas:
SESSION_LIFETIME=604800          # 7 dias
SESSION_EXPIRE_ON_CLOSE=false
SESSION_DRIVER=file
```

### **Vite Manifest Not Found:**
```bash
# Compilar assets
npm install
npm run build    # Produção
npm run dev      # Desenvolvimento
```

### **Permissões:**
```bash
# Linux/Mac
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### **ESP32 não conecta:**
1. Verificar rede WiFi disponível
2. Resetar ESP32 (botão 10s para limpar EEPROM)
3. Verificar IP do servidor no firmware
4. Conferir logs serial: `pio device monitor`

### **Banco de dados:**
```bash
# Verificar conexão
php artisan tinker
DB::connection()->getPdo();

# Migrar novamente
php artisan migrate:fresh --seed
```

## 📊 Monitoramento

### **Logs Importantes:**
```bash
# Aplicação
tail -f storage/logs/laravel.log

# Nginx (se usando)
tail -f /var/log/nginx/error.log

# Sistema
journalctl -f -u mysql
```

### **Performance:**
```bash
# Processos PHP
ps aux | grep php

# Uso de memória
free -h

# Espaço em disco
df -h
```

## 🤝 Contribuição

1. Fork o projeto
2. Crie sua feature branch (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## 📞 Suporte

- **GitHub Issues:** [https://github.com/CelDarley/octus/issues](https://github.com/CelDarley/octus/issues)
- **Email:** suporte@octus.com.br
- **Documentação:** [docs.octus.com.br](https://docs.octus.com.br)

---

**💡 Desenvolvido com Laravel + ESP32 + MQTT** 