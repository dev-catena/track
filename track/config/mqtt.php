<?php

return [
    /*
    | Host do broker Mosquitto **visto pelo servidor Laravel** (não pelo celular).
    | Se o PHP roda no PC A e o MQTT no PC B, use o IP de B (ex.: 10.102.0.103), não localhost.
    | Erro socket [110] = timeout: firewall, IP errado ou broker desligado.
    */
    'host' => env('MQTT_HOST', 'localhost'),
    'port' => (int) env('MQTT_PORT', 1883),
    'username' => env('MQTT_BROKER_USERNAME', ''),
    'password' => env('MQTT_BROKER_PASSWORD', ''),
    'client_id' => env('MQTT_CLIENT_ID', 'track_mqtt_' . gethostname()),
    'keep_alive' => (int) env('MQTT_KEEP_ALIVE', 60),
    'connect_timeout' => (int) env('MQTT_CONNECT_TIMEOUT', 15),
];
