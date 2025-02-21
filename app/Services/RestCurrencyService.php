<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ICurrencyService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RestCurrencyService implements ICurrencyService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.exchange_rate.key');
        $this->apiUrl = config('services.exchange_rate.url');
    }

    public function getLatestRates(): array
    {
        $response = Http::get("{$this->apiUrl}/{$this->apiKey}/latest/USD");
        
        if ($response->failed()) {
            $this->handleApiError($response->json());
        }

        $data = $response->json();
        
        return [
            'rates' => $data['conversion_rates'],
            'base' => $data['base_code'],
            'date' => $data['time_last_update_utc'],
            'next_update' => $data['time_next_update_utc'],
        ];
    }

    public function convertCurrency(float $amount, string $from, string $to): float
    {
        $response = Http::get("{$this->apiUrl}/{$this->apiKey}/pair/{$from}/{$to}/{$amount}");

        if ($response->failed()) {
            $this->handleApiError($response->json());
        }

        $data = $response->json();
        
        return (float) $data['conversion_result'];
    }

    private function handleApiError(array $response): never
    {
        $errorType = $response['error-type'] ?? 'unknown';
        $errorMessage = match ($errorType) {
            'unsupported-code' => 'Неподдерживаемый код валюты',
            'malformed-request' => 'Неверный формат запроса',
            'invalid-key' => 'Неверный API ключ',
            'inactive-account' => 'Аккаунт не активирован',
            'quota-reached' => 'Достигнут лимит запросов',
            default => 'Неизвестная ошибка при получении курсов валют'
        };

        throw new RuntimeException($errorMessage);
    }
} 