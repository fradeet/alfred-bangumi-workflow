#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter;

use Alfred\Workflow\AlfredAdapter\Support\JsonEncoder;
use Alfred\Workflow\AlfredAdapter\Support\WorkflowEnvironment;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSF;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSFItem;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSFItemIcon;
use Alfred\Workflow\AlfredAdapter\Types\AlfredSFItemText;
use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Enums\PersonType;
use Alfred\Workflow\BangumiSiteUrl;
use Alfred\Workflow\ImageCache;
use Alfred\Workflow\PersonSearch as PersonSearchCore;

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

require dirname(__DIR__, 2).'/vendor/autoload.php';

/** Search persons and adapt them to Alfred Script Filter items. */
function personSearchResponse(
    string $keyword,
    string $cacheDirectory,
    string $siteDomain,
    BangumiConnector $connector,
): AlfredSF {
    if ('' === trim($keyword)) {
        return new AlfredSF(items: [new AlfredSFItem(
            title: '搜索 Bangumi 人物',
            subtitle: '输入声优、导演、作者、公司或组合名称',
            valid: false,
        )]);
    }

    $persons = (new PersonSearchCore($connector))($keyword);
    $imageUrls = [];

    foreach ($persons as $person) {
        $imageUrls[$person->id] = null === $person->images ? '' : ($person->images->medium ?? '');
    }

    $imagePaths = (new ImageCache())->cache($imageUrls, $cacheDirectory.DIRECTORY_SEPARATOR.'person-search');
    $buildSiteUrl = new BangumiSiteUrl();
    $items = [];

    foreach ($persons as $person) {
        $details = [personSearchTypeLabel($person->type)];

        foreach ($person->career as $career) {
            $details[] = personSearchCareerLabel($career);
        }

        if (null !== $person->short_summary && '' !== trim($person->short_summary)) {
            $details[] = preg_replace('/\s+/u', ' ', trim($person->short_summary)) ?? trim($person->short_summary);
        }

        $url = $buildSiteUrl(sprintf('https://bgm.tv/person/%d', $person->id), $siteDomain);

        $items[] = new AlfredSFItem(
            title: $person->name,
            arg: $url,
            icon: isset($imagePaths[$person->id]) ? new AlfredSFItemIcon($imagePaths[$person->id]) : null,
            match: $person->name,
            quicklookurl: $url,
            subtitle: implode(' · ', $details),
            text: new AlfredSFItemText(copy: $person->name, largetype: $person->name),
            uid: 'bangumi-person-'.$person->id,
        );
    }

    if ([] === $items) {
        $items[] = new AlfredSFItem(
            title: '未找到人物',
            subtitle: sprintf('没有与“%s”匹配的 Bangumi 人物', trim($keyword)),
            valid: false,
        );
    }

    return new AlfredSF(items: $items);
}

function personSearchTypeLabel(PersonType $type): string
{
    return match ($type) {
        PersonType::Individual => '个人',
        PersonType::Corporation => '公司',
        PersonType::Association => '组合',
    };
}

function personSearchCareerLabel(string $career): string
{
    return match ($career) {
        'producer' => '制作人',
        'mangaka' => '漫画家',
        'artist' => '艺术家',
        'seiyu' => '声优',
        'writer' => '作家',
        'illustrator' => '插画家',
        'actor' => '演员',
        default => $career,
    };
}

$jsonEncoder = new JsonEncoder();

try {
    $keyword = $argv[1] ?? '';
    $cacheDirectory = WorkflowEnvironment::cacheDirectory();
    $siteDomain = WorkflowEnvironment::value('BGM_SITE_DOMAIN', 'https://bgm.tv/');

    echo $jsonEncoder(personSearchResponse(
        $keyword,
        $cacheDirectory,
        $siteDomain,
        WorkflowEnvironment::bangumiConnector(),
    ));
} catch (\Throwable $exception) {
    fwrite(STDERR, $exception.PHP_EOL);

    echo $jsonEncoder(new AlfredSF(items: [new AlfredSFItem(
        title: 'Unable to Search Persons',
        subtitle: 'Open the debugger and try again',
        valid: false,
    )]));

    exit(1);
}
