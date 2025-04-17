<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\CacheEngines;

use Edrep\CurrencyConverter\Providers\RatesCollection;

interface CacheEngineInterface
{
    /**
     * Stores data in cache
     *
     * @param string $key
     * @param RatesCollection $data
     * @param int $ttl Time-to-live in seconds
     * @return void
     */
    public function set(string $key, RatesCollection $data, int $ttl = 86400): void;

    /**
     * Fetches data from cache - if available
     *
     * @param string $key
     * @return RatesCollection|null
     */
    public function get(string $key): ?RatesCollection;
}
