#!/bin/bash

echo "🔧 Corrigindo URL da API..."

# Fazer backup do .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Atualizar a URL da API
if grep -q "API_BASE_URL" .env; then
    # Se existe, substitui
    sed -i 's|API_BASE_URL=.*|API_BASE_URL=http://10.102.0.103:8000/api|g' .env
    echo "✅ URL da API atualizada!"
else
    # Se não existe, adiciona
    echo "" >> .env
    echo "API_BASE_URL=http://10.102.0.103:8000/api" >> .env
    echo "✅ URL da API adicionada!"
fi

# Limpar cache
php artisan config:clear
php artisan cache:clear

echo ""
echo "📋 Configuração atual:"
php artisan tinker --execute="echo 'API_BASE_URL: ' . config('app.api_base_url') . PHP_EOL;"

echo ""
echo "✨ Correção concluída!"

