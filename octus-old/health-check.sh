#!/bin/bash

# ╔════════════════════════════════════════════════════╗
# ║   VERIFICAÇÃO DE SAÚDE DO SISTEMA                  ║
# ╚════════════════════════════════════════════════════╝

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

SERVER_IP="145.223.95.178"
SERVER_USER="darley"
SERVER_PASS="yhvh77"

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   VERIFICAÇÃO DE SAÚDE - OCTUS IOT                 ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

# Contadores
total_checks=0
passed_checks=0
failed_checks=0

# ============================================
# FUNÇÃO PARA CHECKS
# ============================================
check() {
    local name="$1"
    local command="$2"
    
    total_checks=$((total_checks + 1))
    
    if eval "$command" > /dev/null 2>&1; then
        echo -e "  ${GREEN}✓${NC} $name"
        passed_checks=$((passed_checks + 1))
        return 0
    else
        echo -e "  ${RED}✗${NC} $name"
        failed_checks=$((failed_checks + 1))
        return 1
    fi
}

# ============================================
# 1. CONECTIVIDADE
# ============================================
echo -e "${BLUE}[1/6] Verificando conectividade...${NC}"

check "Ping servidor" "ping -c 1 -W 2 $SERVER_IP"
check "SSH servidor" "sshpass -p '$SERVER_PASS' ssh -o ConnectTimeout=3 $SERVER_USER@$SERVER_IP 'echo OK'"

echo ""

# ============================================
# 2. SERVIÇOS SYSTEMD
# ============================================
echo -e "${BLUE}[2/6] Verificando serviços...${NC}"

check "octus-api.service" "sshpass -p '$SERVER_PASS' ssh $SERVER_USER@$SERVER_IP 'echo \"$SERVER_PASS\" | sudo -S systemctl is-active octus-api' | grep -q active"
check "octus-web.service" "sshpass -p '$SERVER_PASS' ssh $SERVER_USER@$SERVER_IP 'echo \"$SERVER_PASS\" | sudo -S systemctl is-active octus-web' | grep -q active"
check "octus-app.service" "sshpass -p '$SERVER_PASS' ssh $SERVER_USER@$SERVER_IP 'echo \"$SERVER_PASS\" | sudo -S systemctl is-active octus-app' | grep -q active"

echo ""

# ============================================
# 3. PORTAS
# ============================================
echo -e "${BLUE}[3/6] Verificando portas...${NC}"

check "Porta 8000 (API)" "sshpass -p '$SERVER_PASS' ssh $SERVER_USER@$SERVER_IP 'echo \"$SERVER_PASS\" | sudo -S ss -tuln | grep -q :8000'"
check "Porta 8001 (Web)" "sshpass -p '$SERVER_PASS' ssh $SERVER_USER@$SERVER_IP 'echo \"$SERVER_PASS\" | sudo -S ss -tuln | grep -q :8001'"
check "Porta 8002 (App)" "sshpass -p '$SERVER_PASS' ssh $SERVER_USER@$SERVER_IP 'echo \"$SERVER_PASS\" | sudo -S ss -tuln | grep -q :8002'"
check "Porta 80 (HTTP)" "sshpass -p '$SERVER_PASS' ssh $SERVER_USER@$SERVER_IP 'echo \"$SERVER_PASS\" | sudo -S ss -tuln | grep -q :80'"
check "Porta 443 (HTTPS)" "sshpass -p '$SERVER_PASS' ssh $SERVER_USER@$SERVER_IP 'echo \"$SERVER_PASS\" | sudo -S ss -tuln | grep -q :443'"

echo ""

# ============================================
# 4. APLICAÇÕES WEB
# ============================================
echo -e "${BLUE}[4/6] Verificando aplicações web...${NC}"

check "https://api.octus.cloud" "curl -sSf -k -m 5 https://api.octus.cloud"
check "https://octus.cloud" "curl -sSf -k -m 5 https://octus.cloud"
check "https://app.octus.cloud" "curl -sSf -k -m 5 https://app.octus.cloud"

echo ""

# ============================================
# 5. BANCO DE DADOS
# ============================================
echo -e "${BLUE}[5/6] Verificando banco de dados...${NC}"

check "MySQL rodando" "sshpass -p '$SERVER_PASS' ssh $SERVER_USER@$SERVER_IP 'echo \"$SERVER_PASS\" | sudo -S systemctl is-active mysql' | grep -q active"
check "Conexão MySQL" "sshpass -p '$SERVER_PASS' ssh $SERVER_USER@$SERVER_IP 'mysql -u root -pyhvh77 -e \"SELECT 1\" 2>/dev/null'"
check "Database mqtt" "sshpass -p '$SERVER_PASS' ssh $SERVER_USER@$SERVER_IP 'mysql -u root -pyhvh77 -e \"USE mqtt; SELECT 1\" 2>/dev/null'"

echo ""

# ============================================
# 6. CERTIFICADOS SSL
# ============================================
echo -e "${BLUE}[6/6] Verificando certificados SSL...${NC}"

check_ssl() {
    local domain="$1"
    local days_until_expire=$(echo | openssl s_client -servername "$domain" -connect "$domain:443" 2>/dev/null | openssl x509 -noout -checkend 0 2>/dev/null && echo "valid" || echo "invalid")
    [ "$days_until_expire" = "valid" ]
}

check "SSL api.octus.cloud" "check_ssl api.octus.cloud"
check "SSL octus.cloud" "check_ssl octus.cloud"
check "SSL app.octus.cloud" "check_ssl app.octus.cloud"

echo ""

# ============================================
# RESUMO
# ============================================
echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   RESUMO DA VERIFICAÇÃO                            ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

success_rate=$((passed_checks * 100 / total_checks))

echo -e "${YELLOW}Total de verificações:${NC} $total_checks"
echo -e "${GREEN}Passou:${NC} $passed_checks"
echo -e "${RED}Falhou:${NC} $failed_checks"
echo -e "${YELLOW}Taxa de sucesso:${NC} $success_rate%"
echo ""

if [ $failed_checks -eq 0 ]; then
    echo -e "${GREEN}╔════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║   ✅ SISTEMA 100% OPERACIONAL!                     ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════╝${NC}"
    exit 0
elif [ $success_rate -ge 80 ]; then
    echo -e "${YELLOW}╔════════════════════════════════════════════════════╗${NC}"
    echo -e "${YELLOW}║   ⚠️  SISTEMA PARCIALMENTE OPERACIONAL             ║${NC}"
    echo -e "${YELLOW}╚════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${YELLOW}💡 Sugestão:${NC} Execute 'bash view-production-logs.sh' para investigar"
    exit 1
else
    echo -e "${RED}╔════════════════════════════════════════════════════╗${NC}"
    echo -e "${RED}║   ✗ SISTEMA COM PROBLEMAS CRÍTICOS!               ║${NC}"
    echo -e "${RED}╚════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${RED}🚨 Ação necessária:${NC}"
    echo "  1. Ver logs: bash view-production-logs.sh"
    echo "  2. Reiniciar: sshpass -p 'yhvh77' ssh darley@145.223.95.178 'echo yhvh77 | sudo -S systemctl restart octus-*'"
    echo "  3. Restaurar backup: bash restore-backup.sh [timestamp]"
    exit 2
fi

