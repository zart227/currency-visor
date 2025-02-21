<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ICurrencyService;
use App\Services\RestCurrencyService;
use App\Services\SoapCurrencyService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Регистрируем REST сервис
        $this->app->bind(RestCurrencyService::class, function ($app) {
            return new RestCurrencyService();
        });

        // Регистрируем SOAP сервис
        $this->app->bind(SoapCurrencyService::class, function ($app) {
            return new SoapCurrencyService();
        });

        // Регистрируем основной сервис в зависимости от конфигурации
        $this->app->bind(ICurrencyService::class, function ($app) {
            return match (config('services.exchange_rate.driver', 'rest')) {
                'soap' => $app->make(SoapCurrencyService::class),
                default => $app->make(RestCurrencyService::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
