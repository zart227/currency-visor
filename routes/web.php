<?php

use App\Http\Controllers\CurrencyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CurrencyController::class, 'index'])
    ->name('currency.index');
