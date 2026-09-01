<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Support;

use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnectorFactory;

/** Resolve shared Alfred workflow configuration. */
final class WorkflowEnvironment
{
    private const DEFAULT_CACHE_DIRECTORY = 'com.fradeet.bangumitv';

    /** Return the environment value or its fallback when unset or empty. */
    public static function value(string $name, string $fallback): string
    {
        $value = getenv($name);

        return false === $value || '' === $value ? $fallback : $value;
    }

    /** Return the cache directory shared by all Alfred operations. */
    public static function cacheDirectory(): string
    {
        return self::value(
            'alfred_workflow_cache',
            sys_get_temp_dir().'/'.self::DEFAULT_CACHE_DIRECTORY,
        );
    }

    /** Build the shared Bangumi connector using Alfred's runtime settings. */
    public static function bangumiConnector(): BangumiConnector
    {
        return (new BangumiConnectorFactory())(
            self::cacheDirectory().'/saloon-responses',
            cacheEnabled: '1' !== self::value('alfred_debug', '0'),
        );
    }
}
