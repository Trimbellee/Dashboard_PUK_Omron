<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppsScriptService
{
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('app.api_url');
    }

    /**
     * GLOBAL REQUEST HANDLER
     */
    public function request(
        string $method = 'get',
        array $params = [],
        bool $asForm = false,
        int $timeout = 300
    ): array {
        try {
            $client = Http::timeout($timeout);

            if ($asForm) {
                $client = $client->asForm();
            }

            $response = match (strtolower($method)) {
                'post' => $client->post($this->apiUrl, $params),
                default => $client->get($this->apiUrl, $params),
            };

            if (!$response->ok()) {
                throw new \Exception(
                    'Apps Script HTTP error: ' . $response->status()
                );
            }

            return [
                'success' => true,
                'data'    => $response->json()
            ];

        } catch (\Throwable $e) {
            Log::error('APPS SCRIPT ERROR', [
                'message' => $e->getMessage(),
                'params'  => $params
            ]);

            return [
                'success' => false,
                'message' => 'Server sedang bermasalah. Silakan coba lagi.'
            ];
        }
    }

     // 🔥 KHUSUS EXPORT
    public function rawJson(array $payload): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiUrl, $payload);

            return $response->json() ?? [
                'success' => false,
                'message' => 'Invalid response'
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal export'
            ];
        }
    }

    /**
     * ============================
     * GET PENGAJUAN (SHEET Pengajuan)
     * ============================
     */
    public function getPengajuanBendahara(array $params = [])
    {
        return $this->request('get', array_merge([
            'action' => 'getPengajuanBendahara'
        ], $params));
    }

    public function getBelanjaKoperasi(array $params = [])
    {
        return $this->request('get', array_merge([
            'action' => 'getBelanjaKoperasi'
        ], $params));
    }

}
