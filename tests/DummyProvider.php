<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\Tests;

use DateTime;
use Edrep\CurrencyConverter\Providers\RatesCollection;
use Edrep\CurrencyConverter\Providers\ForexProviderAbstract;

class DummyProvider extends ForexProviderAbstract
{
    protected string $mainCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'RON'];

    protected function fetchLatestRatesFromProvider(): RatesCollection
    {
        // 1 USD = 4.5 RON, 1 USD = 0.9 EUR
        return new RatesCollection([
            'USD' => 1.0,
            'EUR' => 0.9,
            'RON' => 4.5,
        ]);
    }

    protected function fetchHistoricalRatesFromProvider(DateTime $date): RatesCollection
    {
        // Just return the same for testing
        return $this->fetchLatestRatesFromProvider();
    }

    protected function parseRates($ratesResponse): RatesCollection
    {
        // Not needed for dummy, just return default rates
        return $this->fetchLatestRatesFromProvider();
    }
}
