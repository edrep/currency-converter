<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter;

use DateTime;
use Edrep\CurrencyConverter\Exceptions\UnsupportedCurrencyException;
use Edrep\CurrencyConverter\Providers\ForexProviderAbstract;
use Edrep\CurrencyConverter\Providers\RatesCollection;
use ReflectionException;

class CurrencyConverter
{
    /**
     * @var ForexProviderAbstract
     */
    private ForexProviderAbstract $forexProvider;

    /**
     * CurrencyConvertor constructor.
     * @param ForexProviderAbstract $provider
     */
    public function __construct(ForexProviderAbstract $provider)
    {
        $this->forexProvider = $provider;
    }

    /**
     * Currency conversion
     *
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param float $value
     * @param int $roundingPrecision
     * @param DateTime|null $date
     * @return float
     * @throws UnsupportedCurrencyException
     * @throws ReflectionException
     */
    public function convert(
        string $fromCurrency,
        string $toCurrency,
        float $value,
        int $roundingPrecision = 2,
        DateTime $date = null
    ): float {
        if (empty($value)) {
            return 0; // No need to process 0-values
        }

        $fromCurrency = $this->forexProvider->transformCurrency($fromCurrency);
        $toCurrency = $this->forexProvider->transformCurrency($toCurrency);

        if ($date === null) {
            $rates = $this->forexProvider->fetchLatestRates($fromCurrency);
        } else {
            $rates = $this->forexProvider->fetchHistoricalRates($fromCurrency, $date);
        }

        $outValue = $value * $rates[$toCurrency];

        if ($roundingPrecision !== null) {
            $outValue = round($outValue, $roundingPrecision);
        }

        return $outValue;
    }

    /**
     * @param string $currency
     * @param DateTime|null $date
     * @return RatesCollection
     * @throws ReflectionException
     * @throws UnsupportedCurrencyException
     */
    public function getRates(string $currency, DateTime $date = null): RatesCollection
    {
        $currency = $this->forexProvider->transformCurrency($currency);

        if ($date !== null) {
            return $this->forexProvider->fetchHistoricalRates($currency, $date);
        }

        return $this->forexProvider->fetchLatestRates($currency);
    }
}
