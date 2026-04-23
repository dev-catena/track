#!/bin/bash
# Inicia Octus e Track em paralelo
# Uso: ./start-all.sh

cd "$(dirname "$0")"

echo "=== Iniciando aplicação ==="

echo "Iniciando Octus (porta 8000)..."
cd octus/mqtt && php artisan serve --host=0.0.0.0 --port=8000 &
OCTUS_PID=$!

sleep 2

echo "Iniciando Track (porta 8001)..."
cd "$(dirname "$0")/track" && php artisan serve --host=0.0.0.0 --port=8001 &
TRACK_PID=$!

echo ""
echo "=== Serviços iniciados ==="
echo "Octus: http://localhost:8000 (PID $OCTUS_PID)"
echo "Track: http://localhost:8001 (PID $TRACK_PID)"
echo ""
echo "Pressione Ctrl+C para encerrar todos"
trap "kill $OCTUS_PID $TRACK_PID 2>/dev/null; exit" INT TERM
wait
