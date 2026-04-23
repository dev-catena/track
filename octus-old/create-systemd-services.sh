#!/bin/bash

# ╔════════════════════════════════════════════════════╗
# ║   CRIAR SERVIÇOS SYSTEMD PARA AUTO-RESTART         ║
# ╚════════════════════════════════════════════════════╝

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   CRIANDO SERVIÇOS SYSTEMD                         ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

# ============================================
# SERVIÇO 1: MQTT Backend (API)
# ============================================
echo -e "${BLUE}[1/3]${NC} Criando serviço octus-api.service..."

sudo tee /etc/systemd/system/octus-api.service > /dev/null << 'EOF'
[Unit]
Description=Octus IOT - Backend API (MQTT)
After=network.target mysql.service
Wants=mysql.service

[Service]
Type=simple
User=darley
Group=darley
WorkingDirectory=/home/darley/octus/mqtt
ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8000
Restart=always
RestartSec=5
StandardOutput=append:/var/log/octus-api.log
StandardError=append:/var/log/octus-api-error.log

# Configurações de segurança
PrivateTmp=yes
NoNewPrivileges=true

# Variáveis de ambiente
Environment="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

[Install]
WantedBy=multi-user.target
EOF

echo -e "${GREEN}  ✓ octus-api.service criado${NC}"

# ============================================
# SERVIÇO 2: IOT Config Web
# ============================================
echo ""
echo -e "${BLUE}[2/3]${NC} Criando serviço octus-web.service..."

sudo tee /etc/systemd/system/octus-web.service > /dev/null << 'EOF'
[Unit]
Description=Octus IOT - Interface Web
After=network.target mysql.service octus-api.service
Wants=mysql.service

[Service]
Type=simple
User=darley
Group=darley
WorkingDirectory=/home/darley/octus/iot-config-web-laravel
ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8001
Restart=always
RestartSec=5
StandardOutput=append:/var/log/octus-web.log
StandardError=append:/var/log/octus-web-error.log

# Configurações de segurança
PrivateTmp=yes
NoNewPrivileges=true

# Variáveis de ambiente
Environment="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

[Install]
WantedBy=multi-user.target
EOF

echo -e "${GREEN}  ✓ octus-web.service criado${NC}"

# ============================================
# SERVIÇO 3: IOT Config App
# ============================================
echo ""
echo -e "${BLUE}[3/3]${NC} Criando serviço octus-app.service..."

sudo tee /etc/systemd/system/octus-app.service > /dev/null << 'EOF'
[Unit]
Description=Octus IOT - App Mobile
After=network.target mysql.service octus-api.service
Wants=mysql.service

[Service]
Type=simple
User=darley
Group=darley
WorkingDirectory=/home/darley/octus/iot-config-app-laravel
ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8002
Restart=always
RestartSec=5
StandardOutput=append:/var/log/octus-app.log
StandardError=append:/var/log/octus-app-error.log

# Configurações de segurança
PrivateTmp=yes
NoNewPrivileges=true

# Variáveis de ambiente
Environment="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

[Install]
WantedBy=multi-user.target
EOF

echo -e "${GREEN}  ✓ octus-app.service criado${NC}"

# ============================================
# ATIVAR E INICIAR SERVIÇOS
# ============================================
echo ""
echo -e "${BLUE}[4/4]${NC} Ativando e iniciando serviços..."

# Parar processos antigos
echo "  → Parando processos antigos..."
sudo lsof -t -i:8000 2>/dev/null | xargs -r sudo kill -9 || true
sudo lsof -t -i:8001 2>/dev/null | xargs -r sudo kill -9 || true
sudo lsof -t -i:8002 2>/dev/null | xargs -r sudo kill -9 || true

sleep 2

# Recarregar systemd
echo "  → Recarregando systemd..."
sudo systemctl daemon-reload

# Habilitar serviços para iniciar no boot
echo "  → Habilitando serviços para iniciar no boot..."
sudo systemctl enable octus-api.service
sudo systemctl enable octus-web.service
sudo systemctl enable octus-app.service

# Iniciar serviços
echo "  → Iniciando serviços..."
sudo systemctl start octus-api.service
sudo systemctl start octus-web.service
sudo systemctl start octus-app.service

sleep 3

# ============================================
# VERIFICAR STATUS
# ============================================
echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   VERIFICANDO STATUS DOS SERVIÇOS                  ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

for service in octus-api octus-web octus-app; do
    if sudo systemctl is-active --quiet $service.service; then
        echo -e "  ${GREEN}✓${NC} $service.service está ${GREEN}RODANDO${NC}"
    else
        echo -e "  ${YELLOW}⚠${NC} $service.service está ${YELLOW}PARADO${NC}"
    fi
done

# ============================================
# RESUMO
# ============================================
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✅ SERVIÇOS SYSTEMD CONFIGURADOS!                 ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📋 Serviços criados:${NC}"
echo "   • octus-api.service  (porta 8000) - Backend API"
echo "   • octus-web.service  (porta 8001) - Interface Web"
echo "   • octus-app.service  (porta 8002) - App Mobile"
echo ""
echo -e "${YELLOW}🔄 Auto-restart:${NC} Habilitado (reinicia em 5 segundos se cair)"
echo -e "${YELLOW}🚀 Inicialização:${NC} Automática no boot do sistema"
echo ""
echo -e "${YELLOW}📊 Comandos úteis:${NC}"
echo ""
echo "  ${GREEN}# Ver status de todos os serviços${NC}"
echo "  sudo systemctl status octus-*"
echo ""
echo "  ${GREEN}# Ver status de um serviço específico${NC}"
echo "  sudo systemctl status octus-api"
echo "  sudo systemctl status octus-web"
echo "  sudo systemctl status octus-app"
echo ""
echo "  ${GREEN}# Parar um serviço${NC}"
echo "  sudo systemctl stop octus-api"
echo ""
echo "  ${GREEN}# Iniciar um serviço${NC}"
echo "  sudo systemctl start octus-api"
echo ""
echo "  ${GREEN}# Reiniciar um serviço${NC}"
echo "  sudo systemctl restart octus-api"
echo ""
echo "  ${GREEN}# Ver logs em tempo real${NC}"
echo "  sudo journalctl -u octus-api -f"
echo "  sudo journalctl -u octus-web -f"
echo "  sudo journalctl -u octus-app -f"
echo ""
echo "  ${GREEN}# Ver logs do sistema${NC}"
echo "  sudo tail -f /var/log/octus-api.log"
echo "  sudo tail -f /var/log/octus-web.log"
echo "  sudo tail -f /var/log/octus-app.log"
echo ""
echo -e "${GREEN}✨ Sistema configurado para reiniciar automaticamente!${NC}"
echo ""

