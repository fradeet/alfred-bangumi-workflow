<?php

declare(strict_types=1);

use Alfred\Workflow\DailyBroadcast;
use Alfred\Workflow\Hello;
use Alfred\Workflow\ImageCache;

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

require __DIR__.'/vendor/autoload.php';

require __DIR__.'/AlfredScriptFilterType.php';

/**
 * Dispatch a CLI task to the corresponding core class.
 *
 * @param list<string> $arguments
 */
function dispatchTask(string $task, array $arguments): AlfredSF
{
    return match ($task) {
        'hello' => new AlfredSF(
            items: [new AlfredSFItem(title: (new Hello())(...$arguments))],
        ),
        'daily-broadcast' => dailyBroadcast($arguments),
        default => throw new InvalidArgumentException(sprintf('Unknown task: %s', $task)),
    };
}

/**
 * Fetch a Bangumi daily schedule and adapt it to Alfred items.
 *
 * @param list<string> $arguments
 */
function dailyBroadcast(array $arguments): AlfredSF
{
    if (1 !== count($arguments)) {
        throw new InvalidArgumentException('Usage: daily-broadcast <cache-directory>');
    }

    $schedule = (new DailyBroadcast())();
    $weekday = $schedule['weekday']['cn'];
    $imageUrls = [];

    foreach ($schedule['items'] as $subject) {
        $imageUrls[$subject['id']] = $subject['image_common'];
    }

    $imagePaths = (new ImageCache())->cache($imageUrls, $arguments[0]);
    $items = [];

    foreach ($schedule['items'] as $subject) {
        $title = '' !== $subject['name_cn'] ? $subject['name_cn'] : $subject['name'];
        $details = [];

        if ($title !== $subject['name']) {
            $details[] = $subject['name'];
        }

        if (null !== $subject['rating'] && $subject['rating']['score'] > 0) {
            $details[] = sprintf('评分 %.1f', $subject['rating']['score']);
        }

        if (null !== $subject['eps'] && $subject['eps'] > 0) {
            $details[] = sprintf('全 %d 话', $subject['eps']);
        }

        if ('' !== $subject['air_date']) {
            $details[] = sprintf('%s 开播', $subject['air_date']);
        }

        $url = preg_replace('/^http:/', 'https:', $subject['url']);

        if (null === $url) {
            throw new RuntimeException('Unable to build the Bangumi subject URL.');
        }

        $items[] = new AlfredSFItem(
            title: $title,
            arg: $url,
            icon: isset($imagePaths[$subject['id']]) ? new AlfredSFItemIcon($imagePaths[$subject['id']]) : null,
            match: $title.' '.$subject['name'],
            quicklookurl: $url,
            subtitle: $weekday.([] === $details ? '' : ' · '.implode(' · ', $details)),
            uid: 'bangumi-subject-'.$subject['id'],
        );
    }

    if ([] === $items) {
        $items[] = new AlfredSFItem(
            title: $weekday.'暂无放送',
            valid: false,
        );
    }

    return new AlfredSF(
        items: $items,
        cache: new AlfredSFCache(seconds: 300, loosereload: true),
        skipknowledge: true,
    );
}

/**
 * Encode an Alfred Script Filter response as JSON.
 */
function toAlfredScriptFilterJson(AlfredSF $scriptFilter): string
{
    return json_encode(
        $scriptFilter,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    );
}

/**
 * Run the adapter with positional CLI arguments.
 *
 * @param list<string> $arguments
 */
function run(array $arguments): void
{
    $task = array_shift($arguments);

    if (null === $task) {
        throw new InvalidArgumentException('Usage: php AlfredAdapter.php <task> [argument ...]');
    }

    echo toAlfredScriptFilterJson(dispatchTask($task, $arguments));
}

/**
 * Read and validate positional arguments supplied by the CLI runtime.
 *
 * @return list<string>
 */
function cliArguments(): array
{
    $arguments = $_SERVER['argv'] ?? null;

    if (!is_array($arguments)) {
        throw new RuntimeException('AlfredAdapter.php must be run from the command line.');
    }

    $cliArguments = [];

    foreach (array_slice($arguments, 1) as $argument) {
        if (!is_string($argument)) {
            throw new RuntimeException('CLI arguments must be strings.');
        }

        $cliArguments[] = $argument;
    }

    return $cliArguments;
}

try {
    run(cliArguments());
} catch (Throwable $exception) {
    echo toAlfredScriptFilterJson(RETURN_ERROR_ALFRED);

    exit(1);
}
