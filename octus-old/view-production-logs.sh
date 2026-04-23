#!/bin/bash

# ╔════════════════════════════════════════════════════╗
# ║   VER LOGS DE PRODUÇÃO                             ║
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
echo -e "${BLUE}║   LOGS DE PRODUÇÃO                                 ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

# Menu
echo -e "${YELLOW}Escolha uma opção:${NC}"
echo ""
echo "  1) Ver logs da API (octus-api)"
echo "  2) Ver logs da Web (octus-web)"
echo "  3) Ver logs do App (octus-app)"
echo "  4) Ver logs de TODOS os serviços"
echo "  5) Ver logs do Laravel (API)"
echo "  6) Ver logs do Laravel (Web)"
echo "  7) Ver logs do Laravel (App)"
echo "  8) Ver logs do Nginx"
echo "  9) Ver status dos serviços"
echo "  0) Sair"
echo ""
read -p "Opção: " opcao

case $opcao in
    1)
        echo ""
        echo -e "${BLUE}═══ Logs da API (últimas 50 linhas) ═══${NC}"
        sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "echo 'yhvh77' | sudo -S journalctl -u octus-api -n 50 --no-pager"
        ;;
    2)
        echo ""
        echo -e "${BLUE}═══ Logs da Web (últimas 50 linhas) ═══${NC}"
        sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "echo 'yhvh77' | sudo -S journalctl -u octus-web -n 50 --no-pager"
        ;;
    3)
        echo ""
        echo -e "${BLUE}═══ Logs do App (últimas 50 linhas) ═══${NC}"
        sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "echo 'yhvh77' | sudo -S journalctl -u octus-app -n 50 --no-pager"
        ;;
    4)
        echo ""
        echo -e "${BLUE}═══ Logs de TODOS os serviços (últimas 100 linhas) ═══${NC}"
        sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "echo 'yhvh77' | sudo -S journalctl -u 'octus-*' -n 100 --no-pager"
        ;;
    5)
        echo ""
        echo -e "${BLUE}═══ Logs do Laravel API (últimas 30 linhas) ═══${NC}"
        sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "tail -30 /home/darley/octus/mqtt/storage/logs/laravel.log"
        ;;
    6)
        echo ""
        echo -e "${BLUE}═══ Logs do Laravel Web (últimas 30 linhas) ═══${NC}"
        sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "tail -30 /home/darley/octus/iot-config-web-laravel/storage/logs/laravel.log"
        ;;
    7)
        echo ""
        echo -e "${BLUE}═══ Logs do Laravel App (últimas 30 linhas) ═══${NC}"
        sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "tail -30 /home/darley/octus/iot-config-app-laravel/storage/logs/laravel.log"
        ;;
    8)
        echo ""
        echo -e "${BLUE}═══ Logs do Nginx (últimas 30 linhas) ═══${NC}"
        echo ""
        echo -e "${YELLOW}--- Access Log ---${NC}"
        sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "echo 'yhvh77' | sudo -S tail -20 /var/log/nginx/access.log"
        echo ""
        echo -e "${YELLOW}--- Error Log ---${NC}"
        sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "echo 'yhvh77' | sudo -S tail -20 /var/log/nginx/error.log"
        ;;
    9)
        echo ""
        echo -e "${BLUE}═══ Status dos Serviços ═══${NC}"
        echo ""
        sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP << 'ENDSSH'
echo "yhvh77" | sudo -S systemctl status octus-api octus-web octus-app --no-pager | grep -E "(●|Active:|Main PID:|Tasks:|Memory:|CPU:)" | head -30
ENDSSH
        
        echo ""
        echo -e "${BLUE}═══ Portas em Uso ═══${NC}"
        sshpass -p "$SERVER_PASS" ssh $SERVER_USER@$SERVER_IP "echo 'yhvh77' | sudo -S ss -tuln | grep -E ':(8000|8001|8002|80|443)'"
        ;;
    0)
        echo ""
        echo -e "${GREEN}Até logo!${NC}"
        echo ""
        exit 0
        ;;
    *)
        echo ""
        echo -e "${RED}Opção inválida!${NC}"
        echo ""
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}✓ Logs exibidos${NC}"
echo ""

