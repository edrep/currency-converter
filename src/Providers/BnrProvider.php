<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\Providers;

use DateTime;
use DOMDocument;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use SimpleXMLElement;
use Edrep\CurrencyConverter\CacheEngines\CacheEngineInterface;
use Edrep\CurrencyConverter\CacheEngines\MemoryCache;
use Edrep\CurrencyConverter\Exceptions\ConfigurationException;
use Edrep\CurrencyConverter\Exceptions\InvalidRatesException;

class BnrProvider extends ForexProviderAbstract
{
    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36';
    protected string $mainCurrency = 'RON';
    protected array $supportedCurrencies = ['AED','AUD','BGN','BRL','CAD','CHF','CNY','CZK','DKK','EGP','EUR','GBP','HRK','HUF','INR','JPY','KRW','MDL','MXN','NOK','NZD','PLN','RON','RSD','RUB','SEK','THB','TRY','UAH','USD','XAU','XDR','ZAR'];

    /**
     * @var string|null The historical date we're looking for
     */
    protected ?string $historicalDate = null;

    /**
     * @var Client
     */
    protected Client $guzzle;

    /**
     * Bnr constructor.
     * @param CacheEngineInterface|null $cacheEngine Defaults to MemoryCache
     * @throws ConfigurationException
     */
    public function __construct(?CacheEngineInterface $cacheEngine = null)
    {
        if ($cacheEngine === null) {
            $cacheEngine = new MemoryCache();
        }

        parent::__construct($cacheEngine);

        $this->guzzle = new Client([
            'headers' => [
                'User-Agent' => self::USER_AGENT
            ],
            'verify' => false,
        ]);
    }

    /**
     * @param int $retries
     * @return RatesCollection
     * @throws InvalidRatesException
     * @throws GuzzleException
     */
    protected function fetchLatestRatesFromProvider(int $retries = 5): RatesCollection
    {
        try {
            $rates = $this->guzzle->get('https://bnr.ro/nbrfxrates.xml');

            return $this->parseRates($rates);
        } catch (Exception $e) {
            if ($retries) {
                return $this->fetchLatestRatesFromProvider($retries - 1);
            }
            throw $e;
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
        // Fetch rates for the requested year
        $rates = $this->guzzle->get(sprintf(
            'https://bnr.ro/files/xml/years/nbrfxrates%d.xml',
            $date->format('Y')
        ));

        $this->historicalDate = $date->format('Y-m-d');

        $historicalRates = $this->parseRates($rates);

        // Reset the historical date for future use
        $this->historicalDate = null;

        return $historicalRates;
    }

    /**
     * @param ResponseInterface $ratesResponse
     * @return RatesCollection
     * @throws GuzzleException
     * @throws InvalidRatesException
     */
    protected function parseRates(ResponseInterface $ratesResponse): RatesCollection
    {
        $rates = new RatesCollection();

        foreach ($this->getRatesFromResponse($ratesResponse) as $rate) {
            $rates[(string)$rate['currency']] = 1 / (float)$rate; // Reverse rate
        }

        return $rates;
    }

    /**
     * @param ResponseInterface $ratesResponse
     * @return SimpleXMLElement
     * @throws GuzzleException
     * @throws InvalidRatesException
     * @throws Exception
     */
    protected function getRatesFromResponse(ResponseInterface $ratesResponse): SimpleXMLElement
    {
        if (!$this->isValidRatesXml($ratesResponse)) {
            throw new InvalidRatesException('Invalid rates XML. Response body: ' . $ratesResponse->getBody());
        }

        $xml = new SimpleXMLElement((string)$ratesResponse->getBody());

        // Search for the rates on the historical date (or immediately before it)
        if ($this->historicalDate) {
            $lastDayRates = $dayRates = null;

            foreach ($xml->Body->Cube as $cube) {
                if (strtotime($cube['date']) > strtotime($this->historicalDate)) {
                    $dayRates = $lastDayRates ?? $cube->Rate;
                    break;
                }

                $lastDayRates = $cube->Rate;
            }

            if ($dayRates === null) {
                $dayRates = $lastDayRates;
            }
        } else {
            $dayRates = $xml->Body->Cube->Rate;
        }

        return $dayRates;
    }

    /**
     * Validates the XML response against the BNR schema, downloading the schema to the system temp directory if needed.
     * @param ResponseInterface $ratesResponse
     * @return bool
     * @throws GuzzleException
     */
    protected function isValidRatesXml(ResponseInterface $ratesResponse): bool
    {
        $xml = new DOMDocument();
        $xml->loadXML((string)$ratesResponse->getBody());

        $schemaPath = $this->getBnrSchemaPath();
        if ($schemaPath === null) {
            throw new RuntimeException('BNR schema could not be downloaded or found.');
        }
        return $xml->schemaValidate($schemaPath);
    }

    /**
     * Returns the local path to the BNR XSD schema, downloading it to the system temp directory if not present.
     * @return string|null
     * @throws GuzzleException
     */
    protected function getBnrSchemaPath(): ?string
    {
        $schemaUrl = 'https://www.bnr.ro/xsd/nbrfxrates.xsd';
        $schemaLocal = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bnr_schema.xsd';

        if (!file_exists($schemaLocal)) {
            try {
                $response = $this->guzzle->get($schemaUrl);
                if ($response->getStatusCode() === 200) {
                    file_put_contents($schemaLocal, (string)$response->getBody());
                } else {
                    return null;
                }
            } catch (Exception $e) {
                throw new RuntimeException('Failed to download BNR schema: ' . $e->getMessage());
            }
        }
        return $schemaLocal;
    }
}
