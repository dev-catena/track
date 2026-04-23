# Backend Laravel (Track)

Documentação **completa** do monorepô (instalação, `.env`, MQTT, Thalamus, apps e ESP32): veja **[`../README.md`](../README.md)** na raiz do repositório.

## Resumo rápido (esta pasta)

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan config:clear
php artisan serve --host 0.0.0.0 --port 8000
```

- **PHP** ^8.2 | **Laravel** 12 | Ver `composer.json`
- **Diagnóstico MQTT:** `php artisan track:mqtt-test`
- **Diagnóstico Thalamus / operadores:** `php artisan track:face-diagnose`

O firmware das docas **não** fica nesta subpasta; está em **`../esp32-firmware/`** (veja o README da raiz).

## Licença

O framework Laravel é open-source sob [licença MIT](https://opensource.org/licenses/MIT).
