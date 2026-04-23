#!/bin/bash

# ╔════════════════════════════════════════════════════╗
# ║   BACKUP COMPLETO DE PRODUÇÃO                      ║
# ╚════════════════════════════════════════════════════╝

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

SERVER_IP="145.223.95.178"
SERVER_USER="darley"
SERVER_PASS="yhvh77"
REMOTE_PATH="/home/darley/octus"

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups/$TIMESTAMP"

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   BACKUP DE PRODUÇÃO                               ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

# Criar diretório de backup
mkdir -p "$BACKUP_DIR"

# ============================================
# 1. BACKUP DOS .ENV
# ============================================
echo -e "${BLUE}[1/4]${NC} Fazendo backup dos arquivos .env..."

sshpass -p "$SERVER_PASS" scp $SERVER_USER@$SERVER_IP:$REMOTE_PATH/mqtt/.env "$BACKUP_DIR/mqtt.env" 2>/dev/null
sshpass -p "$SERVER_PASS" scp $SERVER_USER@$SERVER_IP:$REMOTE_PATH/iot-config-web-laravel/.env "$BACKUP_DIR/web.env" 2>/dev/null
sshpass -p "$SERVER_PASS" scp $SERVER_USER@$SERVER_IP:$REMOTE_PATH/iot-config-app-laravel/.env "$BACKUP_DIR/app.env" 2>/dev/null

echo -e "${GREEN}  ✓ Arquivos .env salvos${NC}"

# ============================================
# 2. BACKUP DO BANCO DE DADOS
# ============================================
echo ""
echo -e "${BLUE}[2/4]${NC} Fazendo backup do banco de dados..."

sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "mysqldump -u root -pyhvh77 mqtt 2>/dev/null" > "$BACKUP_DIR/database.sql"

if [ -s "$BACKUP_DIR/database.sql" ]; then
    echo -e "${GREEN}  ✓ Banco de dados exportado${NC}"
    echo -e "    Tamanho: $(du -h "$BACKUP_DIR/database.sql" | cut -f1)"
else
    echo -e "${YELLOW}  ⚠ Banco de dados vazio ou erro ao exportar${NC}"
fi

# ============================================
# 3. BACKUP DOS LOGS
# ============================================
echo ""
echo -e "${BLUE}[3/4]${NC} Fazendo backup dos logs..."

mkdir -p "$BACKUP_DIR/logs"

sshpass -p "$SERVER_PASS" scp $SERVER_USER@$SERVER_IP:$REMOTE_PATH/mqtt/storage/logs/laravel.log "$BACKUP_DIR/logs/mqtt-laravel.log" 2>/dev/null || true
sshpass -p "$SERVER_PASS" scp $SERVER_USER@$SERVER_IP:$REMOTE_PATH/iot-config-web-laravel/storage/logs/laravel.log "$BACKUP_DIR/logs/web-laravel.log" 2>/dev/null || true
sshpass -p "$SERVER_PASS" scp $SERVER_USER@$SERVER_IP:$REMOTE_PATH/iot-config-app-laravel/storage/logs/laravel.log "$BACKUP_DIR/logs/app-laravel.log" 2>/dev/null || true

echo -e "${GREEN}  ✓ Logs salvos${NC}"

# ============================================
# 4. CRIAR ARQUIVO DE INFORMAÇÕES
# ============================================
echo ""
echo -e "${BLUE}[4/4]${NC} Criando arquivo de informações..."

cat > "$BACKUP_DIR/BACKUP_INFO.txt" << EOF
╔════════════════════════════════════════════════════╗
║   BACKUP DE PRODUÇÃO - OCTUS IOT                   ║
╚════════════════════════════════════════════════════╝

Data do Backup: $(date)
Servidor: $SERVER_IP
Usuário: $SERVER_USER

Arquivos incluídos:
  ✓ mqtt.env              - Configurações da API
  ✓ web.env               - Configurações da Web
  ✓ app.env               - Configurações do App
  ✓ database.sql          - Banco de dados completo
  ✓ logs/*                - Logs das aplicações

Para restaurar este backup:
  bash restore-backup.sh $TIMESTAMP

Tamanho total do backup:
$(du -sh "$BACKUP_DIR" | cut -f1)

EOF

echo -e "${GREEN}  ✓ Informações salvas${NC}"

# ============================================
# RESUMO
# ============================================
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✅ BACKUP CONCLUÍDO COM SUCESSO!                  ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📁 Localização:${NC} $BACKUP_DIR"
echo -e "${YELLOW}📊 Tamanho:${NC} $(du -sh "$BACKUP_DIR" | cut -f1)"
echo ""
echo -e "${YELLOW}📋 Arquivos salvos:${NC}"
ls -lh "$BACKUP_DIR" | tail -n +2 | awk '{print "  • " $9 " (" $5 ")"}'
echo ""
echo -e "${YELLOW}♻️  Para restaurar:${NC}"
echo "  bash restore-backup.sh $TIMESTAMP"
echo ""
echo -e "${GREEN}✨ Backup seguro!${NC}"
echo ""

