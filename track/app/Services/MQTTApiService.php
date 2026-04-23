<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MQTTApiService
{
    protected $auth;

    public function __construct(MQTTAuthService $auth)
    {
        $this->auth = $auth;
    }

    public function request($method, $endpoint, $data = [])
    {
        $token = $this->auth->getToken();
        $baseUrl = $this->auth->getBaseUrl();

        $response = Http::withToken($token)
            ->$method($baseUrl . $endpoint, $data);

        if ($response->status() === 401) {
            // re-authenticate and retry
            $newToken = $this->auth->refreshToken();

            $response = Http::withToken($newToken)
                ->$method($this->auth->baseUrl . $endpoint, $data);
        }

        return $response;
    }
}
