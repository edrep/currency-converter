<?php

namespace Edrep\CurrencyConverter\Providers;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Edrep\CurrencyConverter\CacheEngines\CacheEngineInterface;
use Edrep\CurrencyConverter\CacheEngines\MemoryCache;
use Edrep\CurrencyConverter\Exceptions\ConfigurationException;
use Edrep\CurrencyConverter\Exceptions\InvalidRatesException;

class FixerProvider extends ForexProviderAbstract
{
    protected const API_BASE_HTTP = 'http://data.fixer.io/api';
    protected const API_BASE_HTTPS = 'https://data.fixer.io/api';

    private string $apiKey;
    protected array $supportedCurrencies = ['AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CNY', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HRK', 'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JPY', 'KRW', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'RUB', 'SEK', 'SGD', 'THB', 'TRY', 'USD', 'ZAR'];
    protected string $mainCurrency = 'EUR';

    /**
     * Fixer constructor.
     * @param string $apiKey
     * @param CacheEngineInterface|null $cacheEngine Defaults to MemoryCache
     * @throws ConfigurationException
     */
    public function __construct(string $apiKey, CacheEngineInterface $cacheEngine = null)
    {
        $this->apiKey = $apiKey;

        if ($cacheEngine === null) {
            $cacheEngine = new MemoryCache();
        }


        parent::__construct($cacheEngine);
    }


    /**
     * @return RatesCollection
     * @throws GuzzleException
     * @throws InvalidRatesException
     */
    protected function fetchLatestRatesFromProvider(): RatesCollection
    {
        try {
            return $this->parseRates($this->callApiLatest());
        } catch (InvalidRatesException $e) {
            // Fallback in case HTTPS requests are not available on this account
            return $this->parseRates($this->callApiLatest(self::API_BASE_HTTP));
        }
    }


    /**
     * @param DateTime $date
     * @return RatesCollection
     * @throws GuzzleException
     * @throws InvalidRatesException
     */
    protected function fetchHistoricalRatesFromProvider(DateTime $date): RatesCollection
    {
        try {
            return $this->parseRates($this->callApiHistorical($date));
        } catch (InvalidRatesException $e) {
            return $this->parseRates($this->callApiHistorical($date, self::API_BASE_HTTP));
        }
    }


    /**
     * @param ResponseInterface $ratesResponse
     * @return RatesCollection
     * @throws InvalidRatesException
     */
    protected function parseRates(ResponseInterface $ratesResponse): RatesCollection
    {
        $ratesResponse = json_decode($ratesResponse->getBody(), true);

        if (empty($ratesResponse) || empty($ratesResponse['rates']) || $ratesResponse['success'] !== true) {
            throw new InvalidRatesException('Rates parsing failed');
        }


        $ratesResponse = $ratesResponse['rates'];

        return new RatesCollection($ratesResponse);
    }


    /**
     * @param string $apiBase
     * @return ResponseInterface
     * @throws GuzzleException
     */
    protected function callApiLatest(string $apiBase = self::API_BASE_HTTPS): ResponseInterface
    {
        return (new Client(['verify' => false]))->get(
            sprintf(
                '%s/latest?base=%s&access_key=%s',
                $apiBase,
                $this->mainCurrency,
                $this->apiKey
            )
        );
    }


    /**
     * @param DateTime $date
     * @param string $apiBase
     * @return ResponseInterface
     * @throws GuzzleException
     */
    protected function callApiHistorical(DateTime $date, string $apiBase = self::API_BASE_HTTPS): ResponseInterface
    {
        return (new Client(['verify' => false]))->get(
            sprintf(
                '%s/%s?base=%s&access_key=%s',
                $apiBase,
                $date->format('Y-m-d'),
                $this->mainCurrency,
                $this->apiKey
            )
        );
    }
}
