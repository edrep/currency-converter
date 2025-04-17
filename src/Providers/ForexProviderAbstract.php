<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\Providers;

use DateTime;
use Edrep\CurrencyConverter\CacheEngines\CacheEngineAbstract;
use Edrep\CurrencyConverter\CacheEngines\MemoryCache;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use Edrep\CurrencyConverter\Exceptions\ConfigurationException;
use Edrep\CurrencyConverter\Exceptions\UnsupportedCurrencyException;

/**
 * Abstract class for Forex providers
 */
abstract class ForexProviderAbstract implements ForexProviderInterface
{
    /**
     * @var string The main currency to fetch rates for
     */
    protected string $mainCurrency;

    /**
     * @var array Array of supported currencies
     */
    protected array $supportedCurrencies;

    /**
     * @var CacheEngineAbstract
     */
    protected CacheEngineAbstract $cacheEngine;

    /**
     * @param CacheEngineAbstract|null $cacheEngine
     * @throws ConfigurationException
     */
    public function __construct(?CacheEngineAbstract $cacheEngine = null)
    {
        if ($cacheEngine === null) {
            $cacheEngine = new MemoryCache();
        }
        if (empty($this->supportedCurrencies)) {
            throw new ConfigurationException('Supported currencies not configured for ' . static::class);
        }

        if (empty($this->mainCurrency)) {
            throw new ConfigurationException('Main currency not configured for ' . static::class);
        }

        $this->cacheEngine = $cacheEngine;
    }

    /**
     * Validates a currency
     *
     * @param string $currency
     * @return bool
     * @throws UnsupportedCurrencyException
     */
    public function validateCurrency(string $currency): bool
    {
        if (in_array($currency, $this->supportedCurrencies, true)) {
            return true;
        }

        throw new UnsupportedCurrencyException('Unsupported currency: ' . $currency);
    }

    /**
     * @param string $currency
     * @return string
     * @throws UnsupportedCurrencyException
     */
    public function transformCurrency(string $currency): string
    {
        $currency = strtoupper($currency);

        $this->validateCurrency($currency);

        return $currency;
    }

    /**
     * @param string $baseCurrency
     * @return RatesCollection
     * @throws UnsupportedCurrencyException
     */
    public function fetchLatestRates(string $baseCurrency): RatesCollection
    {
        $baseCurrency = $this->transformCurrency($baseCurrency);

        $cacheKey = $this->cacheEngine->generateKey((new ReflectionClass($this))->getShortName(), $this->mainCurrency);

        $rates = $this->cacheEngine->get($cacheKey);

        if ($rates === null) {
            $rates = $this->fetchLatestRatesFromProvider();

            $this->cacheEngine->set($cacheKey, $rates);
        }

        return $this->computeBaseCurrencyRates($baseCurrency, $rates);
    }

    /**
     * @param string $baseCurrency
     * @param DateTime $date
     * @return RatesCollection
     * @throws UnsupportedCurrencyException
     */
    public function fetchHistoricalRates(string $baseCurrency, DateTime $date): RatesCollection
    {
        $baseCurrency = $this->transformCurrency($baseCurrency);

        $date->setTime(0, 0);
        $dateToday = (new DateTime('now', $date->getTimezone()))->setTime(0, 0);

        // Fetch latest rates for today
        if ($date == $dateToday) {
            return $this->fetchLatestRates($baseCurrency);
        }

        if ($date > $dateToday) {
            throw new InvalidArgumentException('Date is in the future');
        }

        $cacheKey = $this->cacheEngine->generateKey(
            (new ReflectionClass($this))->getShortName(),
            $this->mainCurrency,
            $date
        );

        $mainRates = $this->cacheEngine->get($cacheKey);

        if ($mainRates === null) {
            $mainRates = $this->fetchHistoricalRatesFromProvider($date);

            $this->cacheEngine->set($cacheKey, $mainRates, 86400 * 180); // Store for 180 days (they won't ever change)
        }

        return $this->computeBaseCurrencyRates($baseCurrency, $mainRates);
    }

    /**
     * Computes base currency rates from provider's main currency rates
     *
     * @param string $baseCurrency
     * @param RatesCollection $mainRates
     * @return RatesCollection
     */
    protected function computeBaseCurrencyRates(string $baseCurrency, RatesCollection $mainRates): RatesCollection
    {
        $rates = new RatesCollection();

        // 1 MAIN CURRENCY = 1 MAIN CURRENCY
        if (!isset($mainRates[$this->mainCurrency])) {
            $mainRates[$this->mainCurrency] = 1;
        }

        // First - Convert to MainCurrency
        if ($baseCurrency !== $this->mainCurrency) {
            foreach ($mainRates as $currency => $rate) {
                $rates[$currency] = $rate / $mainRates[$baseCurrency];
            }
        } else {
            $rates = $mainRates;
        }

        return $rates;
    }

    /**
     * @return RatesCollection
     */
    abstract protected function fetchLatestRatesFromProvider(): RatesCollection;

    /**
     * @param DateTime $date
     * @return RatesCollection
     */
    abstract protected function fetchHistoricalRatesFromProvider(DateTime $date): RatesCollection;

    /**
     * Parsers provider response to RatesCollection
     *
     * @param ResponseInterface $ratesResponse
     * @return RatesCollection
     */
    abstract protected function parseRates(ResponseInterface $ratesResponse): RatesCollection;
}
