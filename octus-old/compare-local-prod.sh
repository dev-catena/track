#!/bin/bash

# ╔════════════════════════════════════════════════════╗
# ║   COMPARAR LOCAL VS PRODUÇÃO                       ║
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

TEMP_DIR="/tmp/octus-comparison-$$"

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   COMPARAR LOCAL VS PRODUÇÃO                       ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

mkdir -p "$TEMP_DIR"

# ============================================
# 1. BAIXAR ARQUIVOS DE PRODUÇÃO
# ============================================
echo -e "${BLUE}[1/3]${NC} Baixando arquivos de produção..."

sshpass -p "$SERVER_PASS" scp $SERVER_USER@$SERVER_IP:$REMOTE_PATH/mqtt/.env "$TEMP_DIR/prod-mqtt.env" 2>/dev/null || echo "  ⚠ mqtt/.env não encontrado"
sshpass -p "$SERVER_PASS" scp $SERVER_USER@$SERVER_IP:$REMOTE_PATH/iot-config-web-laravel/.env "$TEMP_DIR/prod-web.env" 2>/dev/null || echo "  ⚠ web/.env não encontrado"
sshpass -p "$SERVER_PASS" scp $SERVER_USER@$SERVER_IP:$REMOTE_PATH/iot-config-app-laravel/.env "$TEMP_DIR/prod-app.env" 2>/dev/null || echo "  ⚠ app/.env não encontrado"

echo -e "${GREEN}  ✓ Arquivos baixados${NC}"

# ============================================
# 2. COMPARAR .ENV
# ============================================
echo ""
echo -e "${BLUE}[2/3]${NC} Comparando arquivos .env..."
echo ""

# MQTT API
if [ -f "mqtt/.env" ] && [ -f "$TEMP_DIR/prod-mqtt.env" ]; then
    echo -e "${YELLOW}═══ MQTT API (.env) ═══${NC}"
    echo ""
    diff --side-by-side --width=150 \
        <(grep -v "^#" mqtt/.env | grep -v "^$" | sort) \
        <(grep -v "^#" "$TEMP_DIR/prod-mqtt.env" | grep -v "^$" | sort) \
        | grep -E "(\||<|>)" || echo -e "${GREEN}  ✓ Arquivos idênticos${NC}"
    echo ""
fi

# IOT Config Web
if [ -f "iot-config-web-laravel/.env" ] && [ -f "$TEMP_DIR/prod-web.env" ]; then
    echo -e "${YELLOW}═══ IOT Config Web (.env) ═══${NC}"
    echo ""
    diff --side-by-side --width=150 \
        <(grep -v "^#" iot-config-web-laravel/.env | grep -v "^$" | sort) \
        <(grep -v "^#" "$TEMP_DIR/prod-web.env" | grep -v "^$" | sort) \
        | grep -E "(\||<|>)" || echo -e "${GREEN}  ✓ Arquivos idênticos${NC}"
    echo ""
fi

# IOT Config App
if [ -f "iot-config-app-laravel/.env" ] && [ -f "$TEMP_DIR/prod-app.env" ]; then
    echo -e "${YELLOW}═══ IOT Config App (.env) ═══${NC}"
    echo ""
    diff --side-by-side --width=150 \
        <(grep -v "^#" iot-config-app-laravel/.env | grep -v "^$" | sort) \
        <(grep -v "^#" "$TEMP_DIR/prod-app.env" | grep -v "^$" | sort) \
        | grep -E "(\||<|>)" || echo -e "${GREEN}  ✓ Arquivos idênticos${NC}"
    echo ""
fi

# ============================================
# 3. VERIFICAR VERSÕES
# ============================================
echo ""
echo -e "${BLUE}[3/3]${NC} Verificando versões de software..."
echo ""

echo -e "${YELLOW}LOCAL:${NC}"
echo "  PHP: $(php -v | head -1)"
echo "  Composer: $(composer --version 2>/dev/null | head -1 || echo 'não instalado')"
echo "  MySQL: $(mysql --version 2>/dev/null || echo 'não instalado')"
echo ""

echo -e "${YELLOW}PRODUÇÃO:${NC}"
sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP << 'ENDSSH'
echo "  PHP: $(php -v | head -1)"
echo "  Composer: $(composer --version 2>/dev/null | head -1 || echo 'não instalado')"
echo "  MySQL: $(mysql --version 2>/dev/null || echo 'não instalado')"
ENDSSH

# ============================================
# LIMPEZA
# ============================================
echo ""
rm -rf "$TEMP_DIR"

echo -e "${GREEN}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✅ COMPARAÇÃO CONCLUÍDA                           ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📊 Legenda da comparação:${NC}"
echo "  ${GREEN}✓${NC} Sem diferenças = Arquivos idênticos"
echo "  ${YELLOW}|${NC} = Valores diferentes entre local e produção"
echo "  ${YELLOW}<${NC} = Apenas no arquivo local"
echo "  ${YELLOW}>${NC} = Apenas no arquivo de produção"
echo ""
echo -e "${YELLOW}💡 Dica:${NC} As diferenças são esperadas! Cada ambiente tem suas configurações."
echo ""

