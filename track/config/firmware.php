<?php

return [
    /*
    | URL base para download do firmware (ESP32 precisa acessar).
    | Ex: http://10.102.0.103:8001
    */
    'base_url' => rtrim(env('FIRMWARE_BASE_URL', env('APP_URL')), '/'),

    /*
    | Diretório onde os arquivos .bin ficam (storage/app/firmware).
    */
    'storage_path' => storage_path('app/firmware'),
];
