<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MQTTAuthService
{
    private $baseUrl;
    private $username;
    private $password;

    public function __construct()
    {
        $this->baseUrl = config('services.mqtt.base_url');
        $this->username = config('services.mqtt.username');
        $this->password = config('services.mqtt.password');
    }


    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function getToken()
    {
        // Try cache first
        if (Cache::has('mqtt_auth_token')) {
            return Cache::get('mqtt_auth_token');
        }

        // Otherwise login
        return $this->loginAndCache();
    }

    public function loginAndCache()
    {
        $response = Http::post("{$this->baseUrl}/login", [
            'email' => $this->username,
            'password' => $this->password,
        ]);

        if ($response->successful()) {
            $json = $response->json();

            $token = $json['data']['token'] ?? null;
            $expiresAt = $json['data']['expires_at'] ?? null;

            if ($token) {

                $expiry = $expiresAt
                    ? \Carbon\Carbon::createFromTimestamp($expiresAt)
                    : now()->addHour();

                Cache::put('mqtt_auth_token', $token, $expiry->subMinute());

                return $token;
            }
        }

        Log::error('MQTTAuthService: Login failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new \Exception('Login to mqtt API failed');
    }

    public function refreshToken()
    {
        // Just call login again
        return $this->loginAndCache();
    }
}
