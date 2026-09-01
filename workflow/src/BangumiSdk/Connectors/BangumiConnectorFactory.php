<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Connectors;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

/** Build a Bangumi connector backed by a local filesystem cache. */
final class BangumiConnectorFactory
{
    public function __invoke(string $cacheDirectory, bool $cacheEnabled = true): BangumiConnector
    {
        $pool = new FilesystemAdapter(
            namespace: 'bangumi-api',
            directory: $cacheDirectory,
        );
        $connector = new BangumiConnector(new Psr16Cache($pool));

        if (!$cacheEnabled) {
            $connector->disableCaching();
        }

        return $connector;
    }
}
