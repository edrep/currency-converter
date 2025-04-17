<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\CacheEngines;

/**
 * Class MemoryCacheEntry
 *
 * @package CurrencyConverter\CacheEngines
 */
class MemoryCacheEntry
{
    /**
     * @var int Epoch time of expiration
     */
    protected int $expiryTime;

    /**
     * @var mixed Stored data
     */
    protected $data;

    /**
     * MemoryCacheEntry constructor.
     * @param int $ttl Time-to-live in seconds
     * @param mixed $data
     */
    public function __construct(int $ttl, $data)
    {
        $this->expiryTime = time() + $ttl;
        $this->data = $data;
    }

    /**
     * @return mixed|null
     */
    public function getData()
    {
        if ($this->isExpired()) {
            return null;
        }

        return $this->data;
    }

    protected function isExpired(): bool
    {
        return time() > $this->expiryTime;
    }
}
