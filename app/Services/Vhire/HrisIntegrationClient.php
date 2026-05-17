<?php

namespace App\Services\Vhire;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HrisIntegrationClient
{
    public function post(string $path, array $payload, ?string $idempotencyKey = null): Response
    {
        $baseUrl = rtrim((string) config('recruitment.hris_api.base_url'), '/');
        $token = (string) config('recruitment.hris_api.token');

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('Konfigurasi HRIS API belum lengkap.');
        }

        $headers = [];

        if ($idempotencyKey) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return Http::timeout((int) config('recruitment.hris_api.timeout', 20))
            ->withToken($token)
            ->withHeaders($headers)
            ->acceptJson()
            ->asJson()
            ->post($baseUrl . '/' . ltrim($path, '/'), $payload);
    }
}
