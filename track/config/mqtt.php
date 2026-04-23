<?php

return [
    'host' => env('MQTT_HOST', 'localhost'),
    'port' => (int) env('MQTT_PORT', 1883),
    'username' => env('MQTT_BROKER_USERNAME', ''),
    'password' => env('MQTT_BROKER_PASSWORD', ''),
    'client_id' => env('MQTT_CLIENT_ID', 'track_mqtt_' . gethostname()),
    'keep_alive' => (int) env('MQTT_KEEP_ALIVE', 60),
    'connect_timeout' => (int) env('MQTT_CONNECT_TIMEOUT', 3),
];
