#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter;

use Alfred\Workflow\AlfredAdapter\Support\JsonEncoder;
use Alfred\Workflow\AlfredAdapter\Support\WorkflowEnvironment;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSF;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSFCache;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSFItem;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSFItemIcon;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSFItemText;
use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Dto\LegacySubjectSmall;
use Alfred\Workflow\BangumiSiteUrl;
use Alfred\Workflow\DailyBroadcast as DailyBroadcastCore;
use Alfred\Workflow\ImageCache;

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

require dirname(__DIR__, 2).'/vendor/autoload.php';

/**
 * Return subject fields required to build an Alfred item.
 *
 * @return array{id: int, url: string, name: string}
 */
function dailyBroadcastRequiredSubjectFields(LegacySubjectSmall $subject): array
{
    if (
        null === $subject->id
        || null === $subject->url
        || '' === $subject->url
        || null === $subject->name
        || '' === $subject->name
    ) {
        throw new \RuntimeException('Bangumi returned an incomplete subject.');
    }

    return [
        'id' => $subject->id,
        'url' => $subject->url,
        'name' => $subject->name,
    ];
}

/** Fetch the daily schedule and adapt it to Alfred Script Filter items. */
function dailyBroadcastResponse(
    string $cacheDirectory,
    string $siteDomain,
    BangumiConnector $connector,
): AlfredSF {
    $schedule = (new DailyBroadcastCore($connector))();
    $weekday = $schedule->weekday->cn;
    $imageUrls = [];

    foreach ($schedule->items as $subject) {
        $subjectFields = dailyBroadcastRequiredSubjectFields($subject);
        $imageUrls[$subjectFields['id']] = $subject->images->common ?? '';
    }

    $imagePaths = (new ImageCache())->cache($imageUrls, $cacheDirectory);
    $buildSiteUrl = new BangumiSiteUrl();
    $items = [];

    foreach ($schedule->items as $subject) {
        $subjectFields = dailyBroadcastRequiredSubjectFields($subject);
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

        $url = $buildSiteUrl($subjectFields['url'], $siteDomain);

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
        cache: new AlfredSFCache(seconds: 43200),
    );
}

$jsonEncoder = new JsonEncoder();

try {
    $cacheDirectory = WorkflowEnvironment::cacheDirectory();
    $siteDomain = WorkflowEnvironment::value('BGM_SITE_DOMAIN', 'https://bgm.tv/');
    $connector = WorkflowEnvironment::bangumiConnector();

    echo $jsonEncoder(dailyBroadcastResponse($cacheDirectory, $siteDomain, $connector));
} catch (\Throwable $exception) {
    fwrite(STDERR, $exception.PHP_EOL);

    echo $jsonEncoder(new AlfredSF(
        items: [
            new AlfredSFItem(
                title: 'Unable to Load Results',
                subtitle: 'Open the debugger and try again',
                valid: false,
            ),
        ],
    ));

    exit(1);
}
