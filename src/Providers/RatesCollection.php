<?php

namespace Edrep\CurrencyConverter\Providers;

use ArrayObject;
use Edrep\CurrencyConverter\Exceptions\InvalidRatesException;

class RatesCollection extends ArrayObject
{
    public function __construct($input = array(), $flags = 0, $iterator_class = "ArrayIterator")
    {
        parent::__construct($input, $flags, $iterator_class);
    }


    /**
     * @param $currency
     * @return mixed|null
     * @throws InvalidRatesException
     */
    public function getRate($currency)
    {
        if (!$this->offsetExists($currency)) {
            throw new InvalidRatesException('Rates missing currency ' . $currency);
        }


        return $this->offsetGet($currency);
    }
}
