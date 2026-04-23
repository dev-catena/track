#!/bin/bash

# Script para alternar entre versões do firmware

VERSION=$1

if [ -z "$VERSION" ]; then
    echo "Uso: ./switch_version.sh [default|development|production|ota]"
    echo ""
    echo "Versões disponíveis:"
    echo "  default     - Versão padrão (produção)"
    echo "  development - Versão para desenvolvimento"
    echo "  production  - Versão para produção"
    echo "  ota         - Versão com funcionalidades OTA"
    exit 1
fi

case $VERSION in
    "default")
        cp backup_src/main_production.cpp src/main.cpp
        echo "✅ Versão padrão (produção) ativada"
        ;;
    "development")
        cp backup_src/main_development.cpp src/main.cpp
        echo "✅ Versão de desenvolvimento ativada"
        ;;
    "production")
        cp backup_src/main_production.cpp src/main.cpp
        echo "✅ Versão de produção ativada"
        ;;
    "ota")
        cp backup_src/main_with_ota.cpp src/main.cpp
        echo "✅ Versão com OTA ativada"
        ;;
    *)
        echo "❌ Versão inválida: $VERSION"
        echo "Use: default, development, production ou ota"
        exit 1
        ;;
esac

echo "📁 Arquivo ativo: src/main.cpp"
echo "🔧 Para compilar: pio run"
echo "📤 Para fazer upload: pio run -t upload"
