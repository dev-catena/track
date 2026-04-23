#!/bin/bash

# Script completo para configurar sistema OTA
# Uso: sudo ./setup-ota-complete.sh

echo "🚀 CONFIGURAÇÃO COMPLETA DO SISTEMA OTA"
echo "======================================"

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Este script deve ser executado como root (sudo)"
    exit 1
fi

# 1. Instalar nginx
echo ""
echo "📦 1. Instalando nginx..."
if ! command -v nginx &> /dev/null; then
    ./install-nginx.sh
    if [ $? -ne 0 ]; then
        echo "❌ Falha na instalação do nginx"
        exit 1
    fi
else
    echo "✅ nginx já está instalado"
fi

# 2. Configurar nginx OTA
echo ""
echo "⚙️ 2. Configurando nginx OTA..."
./setup-nginx-ota.sh
if [ $? -ne 0 ]; then
    echo "❌ Falha na configuração do nginx OTA"
    exit 1
fi

# 3. Criar estrutura de firmware
echo ""
echo "📁 3. Criando estrutura de firmware..."
./create-firmware-structure.sh
if [ $? -ne 0 ]; then
    echo "❌ Falha na criação da estrutura de firmware"
    exit 1
fi

# 4. Testar configuração
echo ""
echo "🧪 4. Testando configuração..."

# Testar nginx
if curl -s http://firmware.iot.local:8080/api/version > /dev/null; then
    echo "✅ Servidor nginx OTA funcionando"
else
    echo "❌ Servidor nginx OTA não está respondendo"
    exit 1
fi

# Testar estrutura de firmware
if curl -s http://firmware.iot.local:8080/firmware/ > /dev/null; then
    echo "✅ Estrutura de firmware acessível"
else
    echo "❌ Estrutura de firmware não acessível"
    exit 1
fi

# 5. Status final
echo ""
echo "🎉 CONFIGURAÇÃO OTA CONCLUÍDA!"
echo "=============================="
echo ""
echo "🌐 URLs disponíveis:"
echo "   http://firmware.iot.local:8080/"
echo "   http://firmware.iot.local:8080/api/version"
echo "   http://firmware.iot.local:8080/status"
echo ""
echo "📁 Diretório de firmwares: /var/www/firmware/"
echo "📝 Logs: /var/log/nginx/ota-*.log"
echo ""
echo "🔧 Comandos úteis:"
echo "   sudo systemctl status nginx"
echo "   sudo systemctl restart nginx"
echo "   tail -f /var/log/nginx/ota-access.log"
echo ""
echo "🧪 Teste a configuração:"
echo "   curl http://firmware.iot.local:8080/api/version"
echo "   curl http://firmware.iot.local:8080/firmware/sensor_de_temperatura/latest/version.json"
echo ""
echo "✅ Sistema OTA pronto para uso!"
