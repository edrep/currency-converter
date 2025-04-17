<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\CacheEngines;

use Redis;
use Edrep\CurrencyConverter\Providers\RatesCollection;
use RuntimeException;

class RedisCache extends CacheEngineAbstract
{
    protected Redis $client;

    /**
     * RedisCache constructor.
     * Connects to Redis at the given host/port (defaults to 127.0.0.1:6379).
     * Requires the php-redis extension.
     *
     * @param string $host
     * @param int $port
     * @throws RuntimeException if connection fails
     */
    public function __construct(string $host = '127.0.0.1', int $port = 6379)
    {
        $this->client = new Redis();
        if (!$this->client->connect($host, $port)) {
            throw new RuntimeException("Could not connect to Redis at $host:$port");
        }

        // Set the serializer. igbinary or fallback to serialize()
        if (extension_loaded('igbinary')) {
            $this->client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
        } else {
            $this->client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        }
    }

    public function set(string $key, RatesCollection $data, int $ttl = 86400): void
    {
        $this->client->set($key, $data, ['EX' => $ttl]);
    }

    public function get(string $key): ?RatesCollection
    {
        $cacheData = $this->client->get($key);

        // Missing or invalid cache
        if (empty($cacheData) || !$cacheData instanceof RatesCollection) {
            return null;
        }

        return $cacheData;
    }
}
