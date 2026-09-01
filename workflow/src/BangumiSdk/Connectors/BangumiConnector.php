<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Connectors;

use Psr\SimpleCache\CacheInterface;
use Saloon\CachePlugin\Contracts\Cacheable;
use Saloon\CachePlugin\Contracts\Driver;
use Saloon\CachePlugin\Drivers\PsrCacheDriver;
use Saloon\CachePlugin\Traits\HasCaching;
use Saloon\Http\Connector;

class BangumiConnector extends Connector implements Cacheable
{
    use HasCaching;

    private const CACHE_EXPIRY_SECONDS = 8 * 60 * 60;

    public function __construct(private readonly CacheInterface $cache) {}

    public function resolveBaseUrl(): string
    {
        return 'https://api.bgm.tv';
    }

    public function resolveCacheDriver(): Driver
    {
        return new PsrCacheDriver($this->cache);
    }

    public function cacheExpiryInSeconds(): int
    {
        return self::CACHE_EXPIRY_SECONDS;
    }

    #[\Override]
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'alfred-bangumi-workflow/1.0 (https://github.com/fradeet/alfred-bangumi-workflow)',
        ];
    }
}
