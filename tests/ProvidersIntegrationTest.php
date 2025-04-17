<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\Tests;

use PHPUnit\Framework\TestCase;
use Edrep\CurrencyConverter\CurrencyConverter;
use Edrep\CurrencyConverter\Providers\BnrProvider;
use Edrep\CurrencyConverter\Providers\FixerProvider;

class ProvidersIntegrationTest extends TestCase
{
    public function testBnrProviderConversion(): void
    {
        $provider = new BnrProvider();
        $converter = new CurrencyConverter($provider);
        $result = $converter->convert('EUR', 'RON', 10);
        $this->assertIsNumeric($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testFixerProviderConversion(): void
    {
        $apiKey = getenv('FIXER_API_KEY');
        if (!$apiKey) {
            $this->markTestSkipped('No Fixer API key set in FIXER_API_KEY env variable.');
        }
        $provider = new FixerProvider($apiKey);
        $converter = new CurrencyConverter($provider);
        $result = $converter->convert('EUR', 'USD', 10);
        $this->assertIsNumeric($result);
        $this->assertGreaterThan(0, $result);
    }
}
