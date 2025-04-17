<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\Tests;

use PHPUnit\Framework\TestCase;
use Edrep\CurrencyConverter\CurrencyConverter;
use Edrep\CurrencyConverter\Providers\RatesCollection;

class CurrencyConverterTest extends TestCase
{
    public function testConvertUsdToRon(): void
    {
        $provider = new DummyProvider();
        $converter = new CurrencyConverter($provider);
        $result = $converter->convert('USD', 'RON', 10);
        $this->assertEquals(45.0, $result);
    }

    public function testConvertUsdToEur(): void
    {
        $provider = new DummyProvider();
        $converter = new CurrencyConverter($provider);
        $result = $converter->convert('USD', 'EUR', 10);
        $this->assertEquals(9.0, $result);
    }

    public function testConvertZeroValue(): void
    {
        $provider = new DummyProvider();
        $converter = new CurrencyConverter($provider);
        $result = $converter->convert('USD', 'RON', 0);
        $this->assertEquals(0.0, $result);
    }

    public function testGetRates(): void
    {
        $provider = new DummyProvider();
        $converter = new CurrencyConverter($provider);
        $rates = $converter->getRates('USD');
        $this->assertInstanceOf(RatesCollection::class, $rates);
        $this->assertEquals(1.0, $rates['USD']);
        $this->assertEquals(0.9, $rates['EUR']);
        $this->assertEquals(4.5, $rates['RON']);
    }
}
