<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GasApiService
{
    protected $api;

    public function __construct()
    {
        $this->api = config('app.api_url');
    }

    public function getPengajuanPage($page, $perPage)
    {
        return Http::get($this->api, [
            'action' => 'getPengajuanPage',
            'page'   => $page,
            'perPage'=> $perPage,
        ])->json();
    }

    public function addPengajuan($payload)
    {
        return Http::post($this->api, array_merge($payload, [
            'action' => 'addPengajuan'
        ]))->json();
    }

    public function approvePengajuan($payload)
    {
        return Http::post($this->api, array_merge($payload, [
            'action' => 'approvePengajuan'
        ]))->json();
    }

    public function rejectPengajuan($payload)
    {
        return Http::post($this->api, array_merge($payload, [
            'action' => 'rejectPengajuan'
        ]))->json();
    }

    public function deletePengajuan($payload)
    {
        return Http::post($this->api, array_merge($payload, [
            'action' => 'deletePengajuan'
        ]))->json();
    }
}
