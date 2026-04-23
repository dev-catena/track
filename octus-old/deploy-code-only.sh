#!/bin/bash

# ╔════════════════════════════════════════════════════╗
# ║   DEPLOY APENAS CÓDIGO (PRESERVA CONFIGURAÇÕES)    ║
# ╚════════════════════════════════════════════════════╝

set -e  # Parar se houver erro

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

SERVER_IP="145.223.95.178"
SERVER_USER="darley"
SERVER_PASS="yhvh77"
REMOTE_PATH="/home/darley/octus"

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   DEPLOY CÓDIGO PARA PRODUÇÃO                      ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

# ============================================
# 1. VERIFICAR CONEXÃO
# ============================================
echo -e "${BLUE}[1/7]${NC} Verificando conexão com servidor..."
if sshpass -p "$SERVER_PASS" ssh -o ConnectTimeout=5 $SERVER_USER@$SERVER_IP "echo 'OK'" > /dev/null 2>&1; then
    echo -e "${GREEN}  ✓ Conexão estabelecida${NC}"
else
    echo -e "${RED}  ✗ Erro ao conectar no servidor${NC}"
    exit 1
fi

# ============================================
# 2. FAZER BACKUP ANTES DO DEPLOY
# ============================================
echo ""
echo -e "${BLUE}[2/7]${NC} Fazendo backup de segurança..."
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Baixar .env de produção
sshpass -p "$SERVER_PASS" scp $SERVER_USER@$SERVER_IP:$REMOTE_PATH/mqtt/.env "$BACKUP_DIR/mqtt.env" 2>/dev/null || true
sshpass -p "$SERVER_PASS" scp $SERVER_USER@$SERVER_IP:$REMOTE_PATH/iot-config-web-laravel/.env "$BACKUP_DIR/web.env" 2>/dev/null || true
sshpass -p "$SERVER_PASS" scp $SERVER_USER@$SERVER_IP:$REMOTE_PATH/iot-config-app-laravel/.env "$BACKUP_DIR/app.env" 2>/dev/null || true

echo -e "${GREEN}  ✓ Backup salvo em: $BACKUP_DIR${NC}"

# ============================================
# 3. SINCRONIZAR CÓDIGO-FONTE
# ============================================
echo ""
echo -e "${BLUE}[3/7]${NC} Sincronizando código-fonte..."

# Projetos para sincronizar
PROJECTS=("mqtt" "iot-config-web-laravel" "iot-config-app-laravel")

for project in "${PROJECTS[@]}"; do
    echo -e "  → Sincronizando ${YELLOW}$project${NC}..."
    
    # Sincronizar apenas código (excluindo .env, vendor, storage, cache)
    sshpass -p "$SERVER_PASS" rsync -avz --delete \
        --exclude='.env' \
        --exclude='.env.*' \
        --exclude='vendor/' \
        --exclude='node_modules/' \
        --exclude='storage/logs/*' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        --exclude='bootstrap/cache/*' \
        --exclude='.git/' \
        --exclude='.gitignore' \
        --exclude='*.log' \
        --exclude='.DS_Store' \
        "$project/" $SERVER_USER@$SERVER_IP:$REMOTE_PATH/$project/
    
    echo -e "${GREEN}    ✓ $project sincronizado${NC}"
done

# ============================================
# 4. INSTALAR DEPENDÊNCIAS
# ============================================
echo ""
echo -e "${BLUE}[4/7]${NC} Instalando dependências no servidor..."

sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP << 'ENDSSH'
cd /home/darley/octus

for project in mqtt iot-config-web-laravel iot-config-app-laravel; do
    echo "  → Instalando dependências do $project..."
    cd $project
    composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | grep -v "Warning: TTY mode" || true
    cd ..
done
ENDSSH

echo -e "${GREEN}  ✓ Dependências instaladas${NC}"

# ============================================
# 5. RODAR MIGRAÇÕES
# ============================================
echo ""
echo -e "${BLUE}[5/7]${NC} Executando migrações de banco de dados..."

sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP << 'ENDSSH'
cd /home/darley/octus/mqtt
php artisan migrate --force --no-interaction 2>&1 | grep -v "Nothing to migrate"
ENDSSH

echo -e "${GREEN}  ✓ Migrações executadas${NC}"

# ============================================
# 6. LIMPAR CACHE
# ============================================
echo ""
echo -e "${BLUE}[6/7]${NC} Limpando e reconstruindo cache..."

sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP << 'ENDSSH'
cd /home/darley/octus

for project in mqtt iot-config-web-laravel iot-config-app-laravel; do
    cd $project
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    php artisan config:cache
    php artisan route:cache
    cd ..
done
ENDSSH

echo -e "${GREEN}  ✓ Cache limpo e reconstruído${NC}"

# ============================================
# 7. REINICIAR SERVIÇOS
# ============================================
echo ""
echo -e "${BLUE}[7/7]${NC} Reiniciando serviços..."

sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "echo 'yhvh77' | sudo -S systemctl restart octus-api octus-web octus-app"

sleep 3

# Verificar se serviços estão rodando
echo ""
echo -e "${YELLOW}Verificando status dos serviços...${NC}"
sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "echo 'yhvh77' | sudo -S systemctl is-active octus-api octus-web octus-app" | while read status; do
    if [ "$status" = "active" ]; then
        echo -e "  ${GREEN}✓${NC} Serviço rodando"
    else
        echo -e "  ${RED}✗${NC} Serviço com problema"
    fi
done

# ============================================
# RESUMO
# ============================================
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✅ DEPLOY CONCLUÍDO COM SUCESSO!                  ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📋 O que foi feito:${NC}"
echo "  ✅ Código sincronizado"
echo "  ✅ Configurações .env preservadas"
echo "  ✅ Dependências instaladas"
echo "  ✅ Migrações executadas"
echo "  ✅ Cache limpo e reconstruído"
echo "  ✅ Serviços reiniciados"
echo ""
echo -e "${YELLOW}📁 Backup salvo em:${NC} $BACKUP_DIR"
echo ""
echo -e "${YELLOW}🌐 Testar aplicação:${NC}"
echo "  • https://api.octus.cloud"
echo "  • https://octus.cloud"
echo "  • https://app.octus.cloud"
echo ""
echo -e "${YELLOW}📊 Ver logs:${NC}"
echo "  bash view-production-logs.sh"
echo ""
echo -e "${GREEN}✨ Deploy finalizado!${NC}"
echo ""

