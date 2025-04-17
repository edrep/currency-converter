# Currency Converter

A PHP library for currency conversion using pluggable providers. Designed for extensibility and easy integration.

## Installation

Install via Composer:

```bash
composer require edrep/currency-converter
```

## Usage

```php
use Edrep\CurrencyConverter\CurrencyConverter;
use Edrep\CurrencyConverter\Providers\YourProvider;

// By default, the provider uses the in-memory cache engine
$provider = new YourProvider();
$converter = new CurrencyConverter($provider);

$result = $converter->convert('USD', 'EUR', 100);
echo $result;

// To use a custom cache engine (e.g., RedisCache):
use Edrep\CurrencyConverter\CacheEngines\RedisCache;
$redisCache = new RedisCache('127.0.0.1', 6379);
$providerWithRedis = new YourProvider($redisCache);
$converterWithRedis = new CurrencyConverter($providerWithRedis);
```

## Cache Engines
- By default, the library uses an in-memory cache engine (`MemoryCache`).
- You can provide any cache engine implementing the required interface, such as `RedisCache` for persistent caching.

## Extending

Implement `ForexProviderInterface` to add your own rate provider.

## License

This project is licensed under the MIT License. See the LICENSE file for details.
