<?php

declare(strict_types=1);

use Alfred\Workflow\BangumiSdk\Dto\LegacySubjectSmall;
use Alfred\Workflow\BangumiSiteUrl;
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
    if (2 !== count($arguments)) {
        throw new InvalidArgumentException('Usage: daily-broadcast <cache-directory> <site-domain>');
    }

    $schedule = (new DailyBroadcast())();
    $weekday = $schedule->weekday->cn;
    $imageUrls = [];

    foreach ($schedule->items as $subject) {
        $subjectFields = requiredSubjectFields($subject);
        $imageUrls[$subjectFields['id']] = $subject->images->common ?? '';
    }

    $imagePaths = (new ImageCache())->cache($imageUrls, $arguments[0]);
    $buildSiteUrl = new BangumiSiteUrl();
    $items = [];

    foreach ($schedule->items as $subject) {
        $subjectFields = requiredSubjectFields($subject);
        $nameCn = $subject->name_cn ?? '';
        $title = '' !== $nameCn ? $nameCn : $subjectFields['name'];
        $details = [];

        if ($title !== $subjectFields['name']) {
            $details[] = $subjectFields['name'];
        }

        if (null !== $subject->rating?->score && $subject->rating->score > 0) {
            $details[] = sprintf('评分 %.1f', $subject->rating->score);
        }

        if (null !== $subject->eps && $subject->eps > 0) {
            $details[] = sprintf('全 %d 话', $subject->eps);
        }

        if (null !== $subject->air_date && '' !== $subject->air_date) {
            $details[] = sprintf('%s 开播', $subject->air_date);
        }

        $url = $buildSiteUrl($subjectFields['url'], $arguments[1]);

        $items[] = new AlfredSFItem(
            title: $title,
            arg: $url,
            icon: isset($imagePaths[$subjectFields['id']]) ? new AlfredSFItemIcon($imagePaths[$subjectFields['id']]) : null,
            match: $title.' '.$subjectFields['name'],
            quicklookurl: $url,
            subtitle: $weekday.([] === $details ? '' : ' · '.implode(' · ', $details)),
            text: new AlfredSFItemText(copy: $title, largetype: $title),
            uid: 'bangumi-subject-'.$subjectFields['id'],
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
        cache: new AlfredSFCache(seconds: 43200, loosereload: true),
        skipknowledge: true,
    );
}

/**
 * Return subject fields required to build an Alfred item.
 *
 * @return array{id: int, url: string, name: string}
 */
function requiredSubjectFields(LegacySubjectSmall $subject): array
{
    if (
        null === $subject->id
        || null === $subject->url
        || '' === $subject->url
        || null === $subject->name
        || '' === $subject->name
    ) {
        throw new RuntimeException('Bangumi returned an incomplete subject.');
    }

    return [
        'id' => $subject->id,
        'url' => $subject->url,
        'name' => $subject->name,
    ];
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
