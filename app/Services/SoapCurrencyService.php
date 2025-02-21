<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ICurrencyService;
use Carbon\Carbon;
use RuntimeException;
use SoapClient;
use SoapFault;

class SoapCurrencyService implements ICurrencyService
{
    private ?SoapClient $client = null;
    private string $wsdlUrl = 'http://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx?WSDL';
    private array $supportedCurrencies = [];
    private ?array $currentRates = null;

    public function __construct()
    {
        if (!extension_loaded('soap')) {
            throw new RuntimeException('Расширение SOAP не установлено на сервере');
        }

        try {
            $this->client = new SoapClient($this->wsdlUrl, [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ]);
            
            // Получаем список поддерживаемых валют при инициализации
            $this->loadSupportedCurrencies();
        } catch (SoapFault $e) {
            throw new RuntimeException("Ошибка подключения к SOAP сервису ЦБ РФ: {$e->getMessage()}");
        }
    }

    private function loadSupportedCurrencies(): void
    {
        try {
            // Получаем список ежедневных валют
            $response = $this->client->EnumValutes(['Seld' => false]);
            $data = $response->EnumValutesResult->any;
            
            // Парсим XML ответ
            $xml = new \SimpleXMLElement($data);
            
            // Сохраняем коды валют
            foreach ($xml->ValuteData->EnumValutes as $valute) {
                $code = trim((string) $valute->VcharCode);
                if (!empty($code)) {
                    $this->supportedCurrencies[$code] = [
                        'name' => trim((string) $valute->Vname),
                        'nominal' => (int) $valute->Vnom,
                    ];
                }
            }
            
            // Добавляем рубль в список поддерживаемых валют
            $this->supportedCurrencies['RUB'] = [
                'name' => 'Российский рубль',
                'nominal' => 1,
            ];
        } catch (SoapFault $e) {
            throw new RuntimeException("Ошибка получения списка валют: {$e->getMessage()}");
        }
    }

    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    public function getLatestRates(): array
    {
        try {
            if ($this->currentRates === null) {
                // Получаем последнюю дату публикации курсов
                $latestDate = $this->client->GetLatestDateTime()->GetLatestDateTimeResult;
                
                // Получаем курсы на эту дату
                $response = $this->client->GetCursOnDate([
                    'On_date' => $latestDate
                ]);

                $data = $response->GetCursOnDateResult->any;
                
                // Парсим XML ответ
                $xml = new \SimpleXMLElement($data);
                $rates = [];
                
                // Обрабатываем курсы валют
                foreach ($xml->ValuteData->ValuteCursOnDate as $valute) {
                    $code = trim((string) $valute->VchCode);
                    if (!empty($code)) {
                        $rate = (float) strval($valute->Vcurs);
                        $nominal = (int) $valute->Vnom;
                        
                        // Рассчитываем курс за единицу валюты
                        $rates[$code] = $rate / $nominal;
                    }
                }

                // Добавляем рубль
                $rates['RUB'] = 1.0;

                $nextUpdate = Carbon::parse($latestDate)->addDay()->startOfDay();
                
                $this->currentRates = [
                    'rates' => $rates,
                    'base' => 'RUB',
                    'date' => Carbon::parse($latestDate)->toDateTimeString(),
                    'next_update' => $nextUpdate->toDateTimeString(),
                ];
            }

            return $this->currentRates;
        } catch (SoapFault $e) {
            $this->handleSoapError($e);
        }
    }

    public function convertCurrency(float $amount, string $from, string $to): float
    {
        try {
            // Проверяем поддержку валют
            if (!isset($this->supportedCurrencies[$from])) {
                throw new RuntimeException("Валюта {$from} не поддерживается ЦБ РФ");
            }
            if (!isset($this->supportedCurrencies[$to])) {
                throw new RuntimeException("Валюта {$to} не поддерживается ЦБ РФ");
            }

            $rates = $this->getLatestRates();

            // Проверяем наличие курсов валют
            if (!isset($rates['rates'][$from])) {
                throw new RuntimeException("Курс для валюты {$from} не найден в актуальных данных ЦБ РФ");
            }
            if (!isset($rates['rates'][$to])) {
                throw new RuntimeException("Курс для валюты {$to} не найден в актуальных данных ЦБ РФ");
            }

            // Если одна из валют - рубль, конвертируем напрямую
            if ($from === 'RUB') {
                return $amount / $rates['rates'][$to];
            }
            
            if ($to === 'RUB') {
                return $amount * $rates['rates'][$from];
            }

            // Конвертируем через рубль
            $amountInRub = $amount * $rates['rates'][$from];
            return $amountInRub / $rates['rates'][$to];
        } catch (SoapFault $e) {
            $this->handleSoapError($e);
        }
    }

    private function handleSoapError(SoapFault $fault): never
    {
        $errorMessage = match ($fault->faultcode) {
            'SOAP-ENV:Client' => 'Ошибка в параметрах запроса к API ЦБ РФ',
            'SOAP-ENV:Server' => 'Ошибка сервера ЦБ РФ',
            default => 'Неизвестная ошибка при работе с API ЦБ РФ: ' . $fault->getMessage()
        };

        throw new RuntimeException($errorMessage);
    }
} 