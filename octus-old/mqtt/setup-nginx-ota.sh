#!/bin/bash

# Script para configurar nginx OTA - Sistema MQTT IoT
# Uso: sudo ./setup-nginx-ota.sh

echo "🚀 Configurando nginx para OTA - Sistema MQTT IoT"
echo "================================================="

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Este script deve ser executado como root (sudo)"
    exit 1
fi

# 1. Criar diretório para firmwares
echo "📁 Criando estrutura de diretórios..."
mkdir -p /var/www/firmware
chown -R www-data:www-data /var/www/firmware
chmod -R 755 /var/www/firmware

# 2. Verificar se arquivo de configuração existe
if [ ! -f "nginx-ota-config.conf" ]; then
    echo "❌ Arquivo nginx-ota-config.conf não encontrado!"
    echo "Execute primeiro: sudo ./install-nginx.sh"
    exit 1
fi

# 3. Copiar configuração nginx
echo "⚙️ Configurando nginx..."
cp nginx-ota-config.conf /etc/nginx/sites-available/ota-firmware

# 3. Habilitar site
ln -sf /etc/nginx/sites-available/ota-firmware /etc/nginx/sites-enabled/

# 4. Testar configuração nginx
echo "🧪 Testando configuração nginx..."
nginx -t

if [ $? -eq 0 ]; then
    echo "✅ Configuração nginx OK"

    # 5. Recarregar nginx
    echo "🔄 Recarregando nginx..."
    systemctl reload nginx

    if [ $? -eq 0 ]; then
        echo "✅ nginx recarregado com sucesso"
    else
        echo "❌ Erro ao recarregar nginx"
        exit 1
    fi
else
    echo "❌ Erro na configuração nginx"
    exit 1
fi

# 6. Criar logs específicos
echo "📝 Configurando logs..."
touch /var/log/nginx/ota-access.log
touch /var/log/nginx/ota-error.log
touch /var/log/nginx/firmware-downloads.log
chown www-data:www-data /var/log/nginx/ota-*.log
chown www-data:www-data /var/log/nginx/firmware-downloads.log

# 7. Adicionar entrada no /etc/hosts se necessário
if ! grep -q "firmware.iot.local" /etc/hosts; then
    echo "🌐 Adicionando entrada no /etc/hosts..."
    echo "127.0.0.1 firmware.iot.local" >> /etc/hosts
fi

# 8. Status final
echo ""
echo "✅ Configuração concluída!"
echo "================================"
echo "🌐 URLs disponíveis:"
echo "   http://firmware.iot.local:8080/firmware/"
echo "   http://10.102.0.101:8080/firmware/"
echo "   http://firmware.iot.local:8080/api/version"
echo "   http://firmware.iot.local:8080/status"
echo ""
echo "📁 Diretório de firmwares: /var/www/firmware/"
echo "📝 Logs: /var/log/nginx/ota-*.log"
echo ""
echo "🧪 Teste a configuração:"
echo "   curl http://firmware.iot.local:8080/api/version"

# 9. Mostrar status do nginx
echo ""
echo "📊 Status do nginx:"
systemctl status nginx --no-pager -l
