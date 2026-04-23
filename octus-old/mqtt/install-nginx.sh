#!/bin/bash

# Script para instalar nginx e configurar OTA
# Uso: sudo ./install-nginx.sh

echo "🚀 Instalando nginx e configurando OTA"
echo "====================================="

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Este script deve ser executado como root (sudo)"
    exit 1
fi

# 1. Atualizar sistema
echo "📦 Atualizando sistema..."
apt update && apt upgrade -y

# 2. Instalar nginx
echo "🌐 Instalando nginx..."
apt install -y nginx

# 3. Verificar se nginx foi instalado
if ! command -v nginx &> /dev/null; then
    echo "❌ Falha na instalação do nginx"
    exit 1
fi

echo "✅ nginx instalado com sucesso!"

# 4. Parar nginx se estiver rodando
systemctl stop nginx

# 5. Criar estrutura de diretórios
echo "📁 Criando estrutura de diretórios..."
mkdir -p /var/www/firmware
mkdir -p /var/log/nginx
chown -R www-data:www-data /var/www/firmware
chmod -R 755 /var/www/firmware

# 6. Copiar configuração nginx
echo "⚙️ Configurando nginx..."
cp nginx-ota-config.conf /etc/nginx/sites-available/ota-firmware

# 7. Habilitar site
ln -sf /etc/nginx/sites-available/ota-firmware /etc/nginx/sites-enabled/

# 8. Remover site padrão se existir
if [ -f /etc/nginx/sites-enabled/default ]; then
    rm /etc/nginx/sites-enabled/default
fi

# 9. Testar configuração nginx
echo "🧪 Testando configuração nginx..."
nginx -t

if [ $? -eq 0 ]; then
    echo "✅ Configuração nginx OK"

    # 10. Iniciar nginx
    echo "🔄 Iniciando nginx..."
    systemctl start nginx
    systemctl enable nginx

    if [ $? -eq 0 ]; then
        echo "✅ nginx iniciado com sucesso"
    else
        echo "❌ Erro ao iniciar nginx"
        exit 1
    fi
else
    echo "❌ Erro na configuração nginx"
    exit 1
fi

# 11. Criar logs específicos
echo "📝 Configurando logs..."
touch /var/log/nginx/ota-access.log
touch /var/log/nginx/ota-error.log
touch /var/log/nginx/firmware-downloads.log
chown www-data:www-data /var/log/nginx/ota-*.log
chown www-data:www-data /var/log/nginx/firmware-downloads.log

# 12. Adicionar entrada no /etc/hosts
if ! grep -q "firmware.iot.local" /etc/hosts; then
    echo "🌐 Adicionando entrada no /etc/hosts..."
    echo "127.0.0.1 firmware.iot.local" >> /etc/hosts
fi

# 13. Status final
echo ""
echo "✅ Instalação concluída!"
echo "================================"
echo "🌐 URLs disponíveis:"
echo "   http://firmware.iot.local:8080/"
echo "   http://firmware.iot.local:8080/api/version"
echo "   http://firmware.iot.local:8080/status"
echo ""
echo "📁 Diretório de firmwares: /var/www/firmware/"
echo "📝 Logs: /var/log/nginx/ota-*.log"
echo ""
echo "🧪 Teste a configuração:"
echo "   curl http://firmware.iot.local:8080/api/version"

# 14. Mostrar status do nginx
echo ""
echo "📊 Status do nginx:"
systemctl status nginx --no-pager -l
