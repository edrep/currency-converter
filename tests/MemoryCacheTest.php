<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\Tests;

use PHPUnit\Framework\TestCase;
use Edrep\CurrencyConverter\CacheEngines\MemoryCache;
use Edrep\CurrencyConverter\Providers\RatesCollection;

class MemoryCacheTest extends TestCase
{
    public function testSetAndGet(): void
    {
        $cache = new MemoryCache();
        $rates = new RatesCollection(['USD' => 1, 'EUR' => 0.9]);
        $cache->set('test', $rates, 2);
        $this->assertEquals($rates, $cache->get('test'));
    }

    public function testExpiry(): void
    {
        $cache = new MemoryCache();
        $rates = new RatesCollection(['USD' => 1]);
        $cache->set('expire', $rates, 1);
        sleep(2);
        $this->assertNull($cache->get('expire'));
    }
}
