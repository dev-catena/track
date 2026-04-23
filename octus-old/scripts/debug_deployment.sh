#!/bin/bash

echo "🔍 Diagnosticando problemas no deployment..."

# Verificar status dos serviços
echo "📊 Status dos serviços:"
sudo systemctl status nginx --no-pager -l
sudo systemctl status php8.3-fpm --no-pager -l
sudo systemctl status mysql --no-pager -l

# Verificar configurações do Nginx
echo "🌐 Testando configuração do Nginx:"
sudo nginx -t

# Verificar se os sites estão habilitados
echo "📋 Sites habilitados:"
ls -la /etc/nginx/sites-enabled/

# Verificar permissões dos diretórios
echo "🔑 Verificando permissões:"
ls -la /home/darley/mqtt/
ls -la /home/darley/mqtt/mqtt/public/
ls -la /home/darley/mqtt/iot-config-web-laravel/public/
ls -la /home/darley/mqtt/iot-config-app-laravel/public/

# Verificar se existe index.php
echo "📄 Verificando arquivos index.php:"
ls -la /home/darley/mqtt/mqtt/public/index.php
ls -la /home/darley/mqtt/iot-config-web-laravel/public/index.php
ls -la /home/darley/mqtt/iot-config-app-laravel/public/index.php

# Corrigir problemas de permissão
echo "🔧 Corrigindo permissões..."
sudo chown -R www-data:www-data /home/darley/mqtt
sudo chmod -R 755 /home/darley/mqtt
sudo chmod -R 775 /home/darley/mqtt/*/storage
sudo chmod -R 775 /home/darley/mqtt/*/bootstrap/cache

# Recriar chaves do aplicativo Laravel
echo "🔑 Regenerando chaves dos aplicativos..."
cd /home/darley/mqtt/mqtt && php artisan key:generate --force
cd /home/darley/mqtt/iot-config-web-laravel && php artisan key:generate --force
cd /home/darley/mqtt/iot-config-app-laravel && php artisan key:generate --force

# Limpar cache
echo "🧹 Limpando cache..."
cd /home/darley/mqtt/mqtt && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear
cd /home/darley/mqtt/iot-config-web-laravel && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear
cd /home/darley/mqtt/iot-config-app-laravel && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear

# Verificar logs de erro
echo "📜 Verificando logs de erro do Nginx:"
sudo tail -20 /var/log/nginx/error.log

echo "📜 Verificando logs do PHP-FPM:"
sudo tail -20 /var/log/php8.3-fpm.log

# Reiniciar serviços
echo "🔄 Reiniciando serviços..."
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx

# Testar conectividade local
echo "🔍 Testando conectividade local:"
curl -I http://localhost:8000
curl -I http://localhost:8001
curl -I http://localhost:8002

echo "✅ Diagnóstico concluído!" 