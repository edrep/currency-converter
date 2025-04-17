<?php

declare(strict_types=1);

namespace Edrep\CurrencyConverter\CacheEngines;

use DateTime;

abstract class CacheEngineAbstract implements CacheEngineInterface
{
    protected const KEY_PREFIX = 'edrep-currency-converter';

    /**
     * @param string $providerClass
     * @param string $baseCurrency
     * @param DateTime|null $date
     * @return string
     */
    public function generateKey(string $providerClass, string $baseCurrency, DateTime $date = null): string
    {
        if ($date !== null) {
            $dateFmt = $date->format('Y-m-d');
        } else {
            $dateFmt = 'latest';
        }

        return sprintf('%s_%s_%s_%s', self::KEY_PREFIX, $providerClass, $baseCurrency, $dateFmt);
    }
}
