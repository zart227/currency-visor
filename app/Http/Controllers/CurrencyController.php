<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ICurrencyService;
use App\Http\Requests\ConvertCurrencyRequest;
use App\Services\RestCurrencyService;
use App\Services\SoapCurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CurrencyController extends Controller
{
    public function __construct(
        private readonly ICurrencyService $currencyService,
    ) {}

    public function index(): Response
    {
        try {
            $rates = $this->currencyService->getLatestRates();
            
            return Inertia::render('Currency/Index', [
                'rates' => $rates['rates'],
                'base' => $rates['base'],
                'date' => $rates['date'],
                'next_update' => $rates['next_update'],
            ]);
        } catch (RuntimeException $e) {
            return Inertia::render('Currency/Index', [
                'rates' => [],
                'base' => 'USD',
                'date' => now()->toDateTimeString(),
                'next_update' => now()->addDay()->toDateTimeString(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function convert(ConvertCurrencyRequest $request): JsonResponse
    {
        try {
            // Выбираем сервис в зависимости от метода API
            $service = match ($request->input('api_method', 'rest')) {
                'soap' => app()->make(SoapCurrencyService::class),
                default => app()->make(RestCurrencyService::class),
            };

            $result = $service->convertCurrency(
                amount: $request->validated('amount'),
                from: $request->validated('from'),
                to: $request->validated('to')
            );

            return response()->json([
                'result' => $result,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function supportedCurrencies(Request $request): JsonResponse
    {
        try {
            // Выбираем сервис в зависимости от метода API
            $service = match ($request->input('api_method', 'rest')) {
                'soap' => app()->make(SoapCurrencyService::class),
                default => app()->make(RestCurrencyService::class),
            };

            // Получаем список поддерживаемых валют
            if ($service instanceof SoapCurrencyService) {
                return response()->json([
                    'currencies' => $service->getSupportedCurrencies(),
                ]);
            }

            // Для REST API возвращаем пустой список, так как он поддерживает все валюты
            return response()->json([
                'currencies' => [],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
} 