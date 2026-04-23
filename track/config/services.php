<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mqtt' => [
        'base_url' => env('MQTT_BASE_URL'),
        'username' => env('MQTT_USERNAME'),
        'password' => env('MQTT_PASSWORD'),
    ],

    /*
    | Thalamus Face API (registro ZIP + reconhecimento por imagem).
    | add/zip: POST octet-stream; recognize: POST conforme THALAMUS_FACE_RECOGNIZE_MODE.
    */
    'thalamus_face' => [
        'base_url' => env('THALAMUS_FACE_BASE_URL'),
        'banco_imagens' => env('THALAMUS_FACE_BANCO_IMAGENS', 'thalamus'),
        'id_prefix' => env('THALAMUS_FACE_ID_PREFIX', 'track'),
        'recognize_path' => env('THALAMUS_FACE_RECOGNIZE_PATH', '/face/api/recognize/image'),
        /** octet_stream: corpo binário (image/jpeg). multipart: campo multipart (THALAMUS_FACE_RECOGNIZE_FIELD). */
        'recognize_mode' => env('THALAMUS_FACE_RECOGNIZE_MODE', 'octet_stream'),
        'recognize_multipart_field' => env('THALAMUS_FACE_RECOGNIZE_FIELD', 'image'),
        /** Log detalhado em storage/logs ao tentar face_login (sem ligar APP_DEBUG). */
        'trace_login' => env('THALAMUS_FACE_TRACE_LOGIN', false),
    ],
];
