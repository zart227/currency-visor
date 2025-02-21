<?php

use App\Http\Controllers\CurrencyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Маршруты аутентификации
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Маршруты для конвертации валют
Route::prefix('currency')->group(function () {
    Route::post('convert', [CurrencyController::class, 'convert'])
        ->name('api.currency.convert');
    Route::get('supported-currencies', [CurrencyController::class, 'supportedCurrencies'])
        ->name('api.currency.supported-currencies');
}); 