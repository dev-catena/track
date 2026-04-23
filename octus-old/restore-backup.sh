#!/bin/bash

# ╔════════════════════════════════════════════════════╗
# ║   RESTAURAR BACKUP DE PRODUÇÃO                     ║
# ╚════════════════════════════════════════════════════╝

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

SERVER_IP="145.223.95.178"
SERVER_USER="darley"
SERVER_PASS="yhvh77"
REMOTE_PATH="/home/darley/octus"

# ============================================
# VERIFICAR ARGUMENTOS
# ============================================
if [ -z "$1" ]; then
    echo ""
    echo -e "${YELLOW}═══ Backups Disponíveis ═══${NC}"
    echo ""
    
    if [ -d "backups" ]; then
        ls -1 backups/ | while read backup; do
            if [ -f "backups/$backup/BACKUP_INFO.txt" ]; then
                echo -e "${GREEN}  ✓ $backup${NC}"
                grep "Data do Backup:" "backups/$backup/BACKUP_INFO.txt" | sed 's/^/    /'
            fi
        done
    else
        echo -e "${RED}  Nenhum backup encontrado${NC}"
    fi
    
    echo ""
    echo -e "${YELLOW}Uso:${NC}"
    echo "  bash restore-backup.sh [timestamp]"
    echo ""
    echo -e "${YELLOW}Exemplo:${NC}"
    echo "  bash restore-backup.sh 20251119_143052"
    echo ""
    exit 1
fi

BACKUP_TIMESTAMP="$1"
BACKUP_DIR="backups/$BACKUP_TIMESTAMP"

# Verificar se backup existe
if [ ! -d "$BACKUP_DIR" ]; then
    echo ""
    echo -e "${RED}✗ Backup não encontrado: $BACKUP_DIR${NC}"
    echo ""
    exit 1
fi

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   RESTAURAR BACKUP DE PRODUÇÃO                     ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

# Mostrar informações do backup
if [ -f "$BACKUP_DIR/BACKUP_INFO.txt" ]; then
    cat "$BACKUP_DIR/BACKUP_INFO.txt"
    echo ""
fi

# Confirmar restauração
echo -e "${RED}⚠️  ATENÇÃO: Esta operação irá SOBRESCREVER as configurações atuais!${NC}"
echo ""
read -p "Deseja continuar? (sim/não): " confirma

if [ "$confirma" != "sim" ]; then
    echo ""
    echo -e "${YELLOW}Operação cancelada${NC}"
    echo ""
    exit 0
fi

# ============================================
# 1. PARAR SERVIÇOS
# ============================================
echo ""
echo -e "${BLUE}[1/4]${NC} Parando serviços..."

sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "echo 'yhvh77' | sudo -S systemctl stop octus-api octus-web octus-app"

echo -e "${GREEN}  ✓ Serviços parados${NC}"

# ============================================
# 2. RESTAURAR .ENV
# ============================================
echo ""
echo -e "${BLUE}[2/4]${NC} Restaurando arquivos .env..."

if [ -f "$BACKUP_DIR/mqtt.env" ]; then
    sshpass -p "$SERVER_PASS" scp "$BACKUP_DIR/mqtt.env" $SERVER_USER@$SERVER_IP:$REMOTE_PATH/mqtt/.env
    echo -e "${GREEN}  ✓ mqtt/.env restaurado${NC}"
fi

if [ -f "$BACKUP_DIR/web.env" ]; then
    sshpass -p "$SERVER_PASS" scp "$BACKUP_DIR/web.env" $SERVER_USER@$SERVER_IP:$REMOTE_PATH/iot-config-web-laravel/.env
    echo -e "${GREEN}  ✓ iot-config-web-laravel/.env restaurado${NC}"
fi

if [ -f "$BACKUP_DIR/app.env" ]; then
    sshpass -p "$SERVER_PASS" scp "$BACKUP_DIR/app.env" $SERVER_USER@$SERVER_IP:$REMOTE_PATH/iot-config-app-laravel/.env
    echo -e "${GREEN}  ✓ iot-config-app-laravel/.env restaurado${NC}"
fi

# ============================================
# 3. RESTAURAR BANCO DE DADOS
# ============================================
echo ""
echo -e "${BLUE}[3/4]${NC} Restaurando banco de dados..."

if [ -f "$BACKUP_DIR/database.sql" ]; then
    echo -e "${YELLOW}  ⚠ Isso irá sobrescrever o banco de dados atual!${NC}"
    read -p "  Confirmar restauração do banco? (sim/não): " confirma_db
    
    if [ "$confirma_db" = "sim" ]; then
        sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "mysql -u root -pyhvh77 mqtt" < "$BACKUP_DIR/database.sql"
        echo -e "${GREEN}  ✓ Banco de dados restaurado${NC}"
    else
        echo -e "${YELLOW}  ⊘ Banco de dados não restaurado${NC}"
    fi
else
    echo -e "${YELLOW}  ⚠ Arquivo database.sql não encontrado no backup${NC}"
fi

# ============================================
# 4. INICIAR SERVIÇOS
# ============================================
echo ""
echo -e "${BLUE}[4/4]${NC} Iniciando serviços..."

sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "echo 'yhvh77' | sudo -S systemctl start octus-api octus-web octus-app"

sleep 3

# Verificar se serviços estão rodando
sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "echo 'yhvh77' | sudo -S systemctl is-active octus-api octus-web octus-app" | while read status; do
    if [ "$status" = "active" ]; then
        echo -e "  ${GREEN}✓${NC} Serviço restaurado e rodando"
    else
        echo -e "  ${RED}✗${NC} Serviço com problema"
    fi
done

# ============================================
# RESUMO
# ============================================
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✅ BACKUP RESTAURADO COM SUCESSO!                 ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📋 Restaurado:${NC}"
echo "  ✅ Arquivos .env"
echo "  ✅ Banco de dados (se confirmado)"
echo "  ✅ Serviços reiniciados"
echo ""
echo -e "${YELLOW}🌐 Testar aplicação:${NC}"
echo "  • https://api.octus.cloud"
echo "  • https://octus.cloud"
echo "  • https://app.octus.cloud"
echo ""
echo -e "${GREEN}✨ Restauração finalizada!${NC}"
echo ""

