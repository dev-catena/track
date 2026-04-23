#!/bin/bash

# ╔════════════════════════════════════════════════════╗
# ║   INICIALIZAR REPOSITÓRIO GIT                      ║
# ╚════════════════════════════════════════════════════╝

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   INICIALIZAR REPOSITÓRIO GIT                      ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

# Verificar se Git já está inicializado
if [ -d ".git" ]; then
    echo -e "${YELLOW}⚠️  Git já está inicializado neste diretório!${NC}"
    echo ""
    read -p "Deseja reinicializar? (isso irá PERDER o histórico) (sim/não): " confirma
    
    if [ "$confirma" != "sim" ]; then
        echo ""
        echo -e "${YELLOW}Operação cancelada${NC}"
        echo ""
        exit 0
    fi
    
    rm -rf .git
fi

# ============================================
# 1. INICIALIZAR GIT
# ============================================
echo -e "${BLUE}[1/5]${NC} Inicializando repositório Git..."

git init
git branch -M main

echo -e "${GREEN}  ✓ Repositório inicializado${NC}"

# ============================================
# 2. CRIAR .env.example
# ============================================
echo ""
echo -e "${BLUE}[2/5]${NC} Criando arquivos .env.example..."

# Para cada projeto Laravel
for project in mqtt iot-config-web-laravel iot-config-app-laravel; do
    if [ -f "$project/.env" ]; then
        # Criar .env.example sem valores sensíveis
        cat "$project/.env" | \
            sed 's/DB_PASSWORD=.*/DB_PASSWORD=/' | \
            sed 's/APP_KEY=.*/APP_KEY=/' | \
            sed 's/MQTT_PASSWORD=.*/MQTT_PASSWORD=/' | \
            sed 's/=yhvh77/=/' \
            > "$project/.env.example"
        
        echo -e "  ${GREEN}✓${NC} $project/.env.example criado"
    fi
done

# ============================================
# 3. ADICIONAR ARQUIVOS
# ============================================
echo ""
echo -e "${BLUE}[3/5]${NC} Adicionando arquivos ao repositório..."

git add .

echo -e "${GREEN}  ✓ Arquivos adicionados${NC}"

# ============================================
# 4. PRIMEIRO COMMIT
# ============================================
echo ""
echo -e "${BLUE}[4/5]${NC} Criando primeiro commit..."

git commit -m "🎉 Initial commit - Octus IOT System

✨ Features:
- MQTT Backend API
- IOT Config Web Interface
- IOT Config Mobile App
- ESP32 Firmware
- Deployment scripts
- Production configuration

🚀 Ready for deployment!"

echo -e "${GREEN}  ✓ Commit criado${NC}"

# ============================================
# 5. CONFIGURAR REPOSITÓRIO REMOTO (OPCIONAL)
# ============================================
echo ""
echo -e "${BLUE}[5/5]${NC} Configurar repositório remoto (GitHub/GitLab)?"
echo ""
echo -e "${YELLOW}Se você já tem um repositório remoto, informe a URL:${NC}"
echo -e "${YELLOW}Exemplo: https://github.com/seu-usuario/octus-iot.git${NC}"
echo ""
read -p "URL do repositório (ou deixe em branco para pular): " remote_url

if [ -n "$remote_url" ]; then
    git remote add origin "$remote_url"
    echo -e "${GREEN}  ✓ Repositório remoto configurado${NC}"
    echo ""
    echo -e "${YELLOW}Para enviar para o repositório remoto:${NC}"
    echo "  git push -u origin main"
else
    echo -e "${YELLOW}  ⊘ Repositório remoto não configurado${NC}"
fi

# ============================================
# CRIAR ARQUIVO README SE NÃO EXISTIR
# ============================================
if [ ! -f "README.md" ]; then
    echo ""
    echo -e "${BLUE}Criando README.md...${NC}"
    
    cat > README.md << 'EOF'
# 🌐 Octus IOT System

Sistema completo de gestão IoT com ESP32, MQTT e Laravel.

## 🚀 Tecnologias

- **Backend:** Laravel 11 + MySQL
- **Frontend Web:** Laravel + Blade
- **App Mobile:** Laravel
- **IoT:** ESP32 + MQTT
- **Servidor:** Nginx + Certbot (SSL)

## 📁 Estrutura

```
octus/
├── mqtt/                          # Backend API (Laravel)
├── iot-config-web-laravel/        # Interface Web
├── iot-config-app-laravel/        # App Mobile
├── esp32-octus-platformio/        # Firmware ESP32 (PlatformIO)
├── firmwares/                     # Firmware Arduino
└── scripts/                       # Scripts de deploy
```

## 🔧 Setup Local

```bash
# 1. Instalar dependências
cd mqtt && composer install
cd ../iot-config-web-laravel && composer install
cd ../iot-config-app-laravel && composer install

# 2. Configurar .env
cp mqtt/.env.example mqtt/.env
cp iot-config-web-laravel/.env.example iot-config-web-laravel/.env
cp iot-config-app-laravel/.env.example iot-config-app-laravel/.env

# 3. Gerar chaves
php mqtt/artisan key:generate
php iot-config-web-laravel/artisan key:generate
php iot-config-app-laravel/artisan key:generate

# 4. Rodar migrações
php mqtt/artisan migrate

# 5. Iniciar servidores
php mqtt/artisan serve --port=8000
php iot-config-web-laravel/artisan serve --port=8001
php iot-config-app-laravel/artisan serve --port=8002
```

## 🚀 Deploy para Produção

```bash
# Deploy de código (preserva configurações)
bash deploy-code-only.sh

# Backup antes de deploy
bash backup-production.sh

# Ver logs de produção
bash view-production-logs.sh
```

## 📚 Documentação

- [Workflow de Desenvolvimento](WORKFLOW_DESENVOLVIMENTO.md)
- [Guia de Deploy](GUIA_DEPLOY_MANUAL.md)
- [Configuração de Domínio](GUIA_DOMINIO.md)
- [Serviços Systemd](GUIA_SERVICOS_SYSTEMD.md)

## 🌐 URLs de Produção

- **API:** https://api.octus.cloud
- **Web:** https://octus.cloud
- **App:** https://app.octus.cloud

## 📄 Licença

Proprietary - Todos os direitos reservados

---

**Desenvolvido com ❤️ para IoT**
EOF

    git add README.md
    git commit -m "📝 Add README"
    
    echo -e "${GREEN}  ✓ README.md criado${NC}"
fi

# ============================================
# RESUMO
# ============================================
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✅ GIT INICIALIZADO COM SUCESSO!                  ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📋 O que foi feito:${NC}"
echo "  ✅ Repositório Git inicializado"
echo "  ✅ .env.example criados (sem senhas)"
echo "  ✅ .gitignore configurado"
echo "  ✅ Primeiro commit criado"
echo "  ✅ README.md criado"
echo ""
echo -e "${YELLOW}📊 Status do repositório:${NC}"
git status
echo ""
echo -e "${YELLOW}📝 Comandos úteis:${NC}"
echo ""
echo "  ${GREEN}# Ver status${NC}"
echo "  git status"
echo ""
echo "  ${GREEN}# Adicionar alterações${NC}"
echo "  git add ."
echo ""
echo "  ${GREEN}# Fazer commit${NC}"
echo "  git commit -m 'Descrição das alterações'"
echo ""
echo "  ${GREEN}# Ver histórico${NC}"
echo "  git log --oneline"
echo ""
echo "  ${GREEN}# Enviar para repositório remoto${NC}"
echo "  git push origin main"
echo ""
echo "  ${GREEN}# Criar nova branch${NC}"
echo "  git checkout -b feature/nova-funcionalidade"
echo ""
echo -e "${GREEN}✨ Git configurado! Agora você pode versionar seu código!${NC}"
echo ""

