#!/bin/bash

echo "🌐 Configurando virtual hosts do Nginx..."

# Criar configuração para o projeto principal MQTT (porta 8000)
sudo tee /etc/nginx/sites-available/iot-main > /dev/null <<EOF
server {
    listen 8000;
    server_name 181.215.135.118;
    root /home/darley/mqtt/mqtt/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Criar configuração para o projeto Web Laravel (porta 8001)
sudo tee /etc/nginx/sites-available/iot-web > /dev/null <<EOF
server {
    listen 8001;
    server_name 181.215.135.118;
    root /home/darley/mqtt/iot-config-web-laravel/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Criar configuração para o projeto App Laravel (porta 8002)
sudo tee /etc/nginx/sites-available/iot-app > /dev/null <<EOF
server {
    listen 8002;
    server_name 181.215.135.118;
    root /home/darley/mqtt/iot-config-app-laravel/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Habilitar os sites
sudo ln -sf /etc/nginx/sites-available/iot-main /etc/nginx/sites-enabled/
sudo ln -sf /etc/nginx/sites-available/iot-web /etc/nginx/sites-enabled/
sudo ln -sf /etc/nginx/sites-available/iot-app /etc/nginx/sites-enabled/

# Remover site padrão
sudo rm -f /etc/nginx/sites-enabled/default

# Testar configuração do Nginx
sudo nginx -t

# Reiniciar Nginx
sudo systemctl restart nginx

# Configurar .env para cada projeto
echo "📄 Configurando arquivos .env..."

# .env para projeto principal MQTT
cat > /home/darley/mqtt/mqtt/.env <<EOF
APP_NAME="IoT MQTT System"
APP_ENV=production
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=false
APP_TIMEZONE=America/Sao_Paulo
APP_URL=http://181.215.135.118:8000

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iot_main_db
DB_USERNAME=iot_user
DB_PASSWORD=yhvh77

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

JWT_SECRET=$(openssl rand -base64 64)
JWT_ALGO=HS256
JWT_TTL=1440

MQTT_HOST=127.0.0.1
MQTT_PORT=1883
MQTT_CLIENT_ID=iot_system
MQTT_USERNAME=
MQTT_PASSWORD=
MQTT_KEEPALIVE=60
MQTT_CLEAN_SESSION=true
EOF

# .env para projeto Web Laravel
cat > /home/darley/mqtt/iot-config-web-laravel/.env <<EOF
APP_NAME="IoT Web Dashboard"
APP_ENV=production
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=false
APP_TIMEZONE=America/Sao_Paulo
APP_URL=http://181.215.135.118:8001

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iot_web_db
DB_USERNAME=iot_user
DB_PASSWORD=yhvh77

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=web_

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

MAIN_API_URL=http://181.215.135.118:8000/api
EOF

# .env para projeto App Laravel
cat > /home/darley/mqtt/iot-config-app-laravel/.env <<EOF
APP_NAME="IoT Config App"
APP_ENV=production
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=false
APP_TIMEZONE=America/Sao_Paulo
APP_URL=http://181.215.135.118:8002

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iot_app_db
DB_USERNAME=iot_user
DB_PASSWORD=yhvh77

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=app_

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

MAIN_API_URL=http://181.215.135.118:8000/api
EOF

# Ajustar permissões
sudo chown -R www-data:www-data /home/darley/mqtt
sudo chmod -R 755 /home/darley/mqtt
sudo chmod -R 775 /home/darley/mqtt/*/storage
sudo chmod -R 775 /home/darley/mqtt/*/bootstrap/cache

# Limpar cache do Laravel em todos os projetos
cd /home/darley/mqtt/mqtt && php artisan config:cache && php artisan route:cache && php artisan view:cache
cd /home/darley/mqtt/iot-config-web-laravel && php artisan config:cache && php artisan route:cache && php artisan view:cache
cd /home/darley/mqtt/iot-config-app-laravel && php artisan config:cache && php artisan route:cache && php artisan view:cache

# Iniciar serviços
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
sudo systemctl restart mysql
sudo systemctl restart mosquitto

echo "✅ Configuração do Nginx e .env concluída!"
echo "🌐 URLs de acesso:"
echo "   - Sistema Principal: http://181.215.135.118:8000"
echo "   - Dashboard Web:     http://181.215.135.118:8001"
echo "   - App Config:        http://181.215.135.118:8002"
echo "   - MQTT Broker:       181.215.135.118:1883" 