<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\CacheEngines;

use Edrep\CurrencyConverter\Providers\RatesCollection;

class MemoryCache extends CacheEngineAbstract
{
    /**
     * @var MemoryCacheEntry[]
     */
    protected array $storage = [];

    public function set(string $key, RatesCollection $data, int $ttl = 86400): void
    {
        $this->storage[$key] = new MemoryCacheEntry($ttl, $data);
    }

    public function get(string $key): ?RatesCollection
    {
        if (isset($this->storage[$key])) {
            return $this->storage[$key]->getData();
        }

        return null;
    }
}
