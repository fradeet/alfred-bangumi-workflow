#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter;

use Alfred\Workflow\AlfredAdapter\Support\JsonEncoder;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSF;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSFItem;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSFItemIcon;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSFItemText;
use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnectorFactory;
use Alfred\Workflow\BangumiSdk\Enums\SubjectType;
use Alfred\Workflow\BangumiSiteUrl;
use Alfred\Workflow\ImageCache;
use Alfred\Workflow\SubjectRelations as SubjectRelationsCore;

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

require dirname(__DIR__, 2).'/vendor/autoload.php';

/** Return the subject URL supplied to the Alfred Grid. */
function subjectRelationsInput(): string
{
    $arguments = $_SERVER['argv'] ?? null;
    $subjectUrl = is_array($arguments) ? ($arguments[1] ?? null) : null;

    if (!is_string($subjectUrl) || '' === $subjectUrl) {
        throw new \InvalidArgumentException('A Bangumi subject URL is required.');
    }

    return $subjectUrl;
}

/** Return the environment value or its fallback when unset or empty. */
function subjectRelationsEnvironment(string $name, string $fallback): string
{
    $value = getenv($name);

    return false === $value || '' === $value ? $fallback : $value;
}

/** Fetch related subjects and adapt them to Alfred Grid items. */
function subjectRelationsResponse(
    string $subjectUrl,
    string $cacheDirectory,
    string $siteDomain,
    BangumiConnector $connector,
): AlfredSF {
    $relations = (new SubjectRelationsCore($connector))($subjectUrl);
    $imageUrls = [];

    foreach ($relations as $relation) {
        $imageUrls[$relation->id] = $relation->images->common ?? '';
    }

    $imagePaths = (new ImageCache())->cache($imageUrls, $cacheDirectory);
    $buildSiteUrl = new BangumiSiteUrl();
    $items = [];

    foreach ($relations as $relation) {
        $title = '' !== $relation->name_cn ? $relation->name_cn : $relation->name;
        $subtitle = [$relation->relation, subjectRelationsTypeName($relation->type)];

        if ($title !== $relation->name) {
            $subtitle[] = $relation->name;
        }

        $url = $buildSiteUrl(sprintf('https://bgm.tv/subject/%d', $relation->id), $siteDomain);

        $items[] = new AlfredSFItem(
            title: $title,
            arg: $url,
            icon: isset($imagePaths[$relation->id]) ? new AlfredSFItemIcon($imagePaths[$relation->id]) : null,
            match: $title.' '.$relation->name.' '.$relation->relation,
            quicklookurl: $url,
            subtitle: implode(' · ', $subtitle),
            text: new AlfredSFItemText(copy: $title, largetype: $title),
            uid: 'bangumi-subject-'.$relation->id,
        );
    }

    if ([] === $items) {
        $items[] = new AlfredSFItem(
            title: '暂无关联条目',
            valid: false,
        );
    }

    return new AlfredSF(items: $items);
}

function subjectRelationsTypeName(SubjectType $type): string
{
    return match ($type) {
        SubjectType::Book => '书籍',
        SubjectType::Anime => '动画',
        SubjectType::Music => '音乐',
        SubjectType::Game => '游戏',
        SubjectType::Real => '三次元',
    };
}

$jsonEncoder = new JsonEncoder();

try {
    $defaultCacheDirectory = sys_get_temp_dir().'/com.fradeet.bangumitv';
    $cacheDirectory = subjectRelationsEnvironment('alfred_workflow_cache', $defaultCacheDirectory);
    $siteDomain = subjectRelationsEnvironment('BGM_SITE_DOMAIN', 'https://bgm.tv/');
    $subjectUrl = subjectRelationsInput();
    $connector = (new BangumiConnectorFactory())(
        $cacheDirectory.'/saloon-responses',
        cacheEnabled: '1' !== subjectRelationsEnvironment('alfred_debug', '0'),
    );

    echo $jsonEncoder(subjectRelationsResponse($subjectUrl, $cacheDirectory, $siteDomain, $connector));
} catch (\Throwable $exception) {
    fwrite(STDERR, $exception.PHP_EOL);

    echo $jsonEncoder(new AlfredSF(
        items: [
            new AlfredSFItem(
                title: 'Unable to Load Relations',
                subtitle: 'Open the debugger and try again',
                valid: false,
            ),
        ],
    ));

    exit(1);
}
