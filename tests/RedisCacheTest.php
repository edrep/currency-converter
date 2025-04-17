<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\Tests;

use PHPUnit\Framework\TestCase;
use Edrep\CurrencyConverter\CacheEngines\RedisCache;
use Edrep\CurrencyConverter\Providers\RatesCollection;

class RedisCacheTest extends TestCase
{
    public function testSetAndGet(): void
    {
        try {
            $cache = new RedisCache();
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis not available: ' . $e->getMessage());
            return;
        }
        $rates = new RatesCollection(['USD' => 1, 'EUR' => 0.9]);
        $cache->set('test', $rates, 2);
        $this->assertEquals($rates, $cache->get('test'));
    }
}
