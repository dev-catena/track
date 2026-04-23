<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Testes de integração Track ↔ Octus
 * Usa Http::fake para simular respostas do Octus
 */
beforeEach(function () {
    Cache::flush(); // Limpar token MQTT em cache
});

test('dock repository fetches mqtt topics from octus api', function () {
    $baseUrl = config('services.mqtt.base_url', 'http://10.102.0.103:8000/api');

    Http::fake(function ($request) use ($baseUrl) {
        $url = (string) $request->url();
        if (str_contains($url, '/login')) {
            return Http::response([
                'data' => ['token' => 'fake-token', 'expires_at' => time() + 3600],
            ], 200);
        }
        if (str_contains($url, '/mqtt/topics') && !str_contains($url, '/mqtt/topics/')) {
            return Http::response([
                'success' => true,
                'data' => [
                    ['id' => 1, 'name' => 'iot-doca01', 'description' => 'Doca Sala 1'],
                    ['id' => 2, 'name' => 'iot-doca02', 'description' => 'Doca Sala 2'],
                ],
            ], 200);
        }
        return Http::response(['success' => true, 'data' => []], 200);
    });

    $dockRepo = app(\App\Repositories\Interfaces\DockInterface::class);
    $topics = $dockRepo->get_mqtt_topics(0);

    expect($topics)->toBeArray()
        ->and($topics)->toHaveCount(2)
        ->and($topics[0])->toHaveKeys(['id', 'name', 'description'])
        ->and($topics[0]['name'])->toBe('iot-doca01');
});
