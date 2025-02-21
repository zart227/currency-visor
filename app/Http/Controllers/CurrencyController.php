<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ConvertCurrencyRequest;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CurrencyController extends Controller
{
    public function __construct(
        private readonly CurrencyService $currencyService,
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
            $result = $this->currencyService->convertCurrency(
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
} 