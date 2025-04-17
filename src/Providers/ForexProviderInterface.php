<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\Providers;

use DateTime;

interface ForexProviderInterface
{
    /**
     * Fetches the latest exchange rates
     * @param string $baseCurrency
     * @return RatesCollection
     */
    public function fetchLatestRates(string $baseCurrency): RatesCollection;

    /**
     * Fetches exchange rates for specific date
     * @param string $baseCurrency
     * @param DateTime $date
     * @return RatesCollection
     */
    public function fetchHistoricalRates(string $baseCurrency, DateTime $date): RatesCollection;
}
