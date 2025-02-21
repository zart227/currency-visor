<?php

declare(strict_types=1);

namespace App\Contracts;

interface ICurrencyService
{
    /**
     * Получить последние курсы валют
     *
     * @return array{
     *     rates: array<string, float>,
     *     base: string,
     *     date: string,
     *     next_update: string
     * }
     */
    public function getLatestRates(): array;

    /**
     * Конвертировать валюту
     *
     * @param float $amount Сумма для конвертации
     * @param string $from Исходная валюта
     * @param string $to Целевая валюта
     * @return float Результат конвертации
     */
    public function convertCurrency(float $amount, string $from, string $to): float;
} 