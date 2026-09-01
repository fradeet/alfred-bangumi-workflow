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
use Alfred\Workflow\BangumiSdk\Enums\SubjectType;
use Alfred\Workflow\BangumiSiteUrl;
use Alfred\Workflow\ImageCache;
use Alfred\Workflow\SubjectSearch as SubjectSearchCore;

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

require dirname(__DIR__, 2).'/vendor/autoload.php';

/** Search subjects and adapt them to Alfred Script Filter items. */
function subjectSearchResponse(
    string $keyword,
    string $cacheDirectory,
    string $siteDomain,
    BangumiConnector $connector,
): AlfredSF {
    if ('' === trim($keyword)) {
        return new AlfredSF(items: [new AlfredSFItem(
            title: '搜索 Bangumi 条目',
            subtitle: '输入动画、书籍、音乐、游戏或三次元条目名称',
            valid: false,
        )]);
    }

    $subjects = (new SubjectSearchCore($connector))($keyword);
    $imageUrls = [];

    foreach ($subjects as $subject) {
        $imageUrls[$subject->id] = $subject->images->common ?? '';
    }

    $imagePaths = (new ImageCache())->cache($imageUrls, $cacheDirectory);
    $buildSiteUrl = new BangumiSiteUrl();
    $items = [];

    foreach ($subjects as $subject) {
        $title = '' !== $subject->name_cn ? $subject->name_cn : $subject->name;
        $details = [subjectSearchTypeLabel($subject->type)];

        if ($title !== $subject->name) {
            $details[] = $subject->name;
        }

        if ($subject->rating->score > 0) {
            $details[] = sprintf('评分 %.1f', $subject->rating->score);
        }

        if ($subject->rating->rank > 0) {
            $details[] = sprintf('排名 #%d', $subject->rating->rank);
        }

        $url = $buildSiteUrl(sprintf('https://bgm.tv/subject/%d', $subject->id), $siteDomain);

        $items[] = new AlfredSFItem(
            title: $title,
            arg: $url,
            icon: isset($imagePaths[$subject->id]) ? new AlfredSFItemIcon($imagePaths[$subject->id]) : null,
            match: $title.' '.$subject->name,
            quicklookurl: $url,
            subtitle: implode(' · ', $details),
            text: new AlfredSFItemText(copy: $title, largetype: $title),
            uid: 'bangumi-subject-'.$subject->id,
        );
    }

    if ([] === $items) {
        $items[] = new AlfredSFItem(
            title: '未找到条目',
            subtitle: sprintf('没有与“%s”匹配的 Bangumi 条目', trim($keyword)),
            valid: false,
        );
    }

    return new AlfredSF(items: $items);
}

function subjectSearchTypeLabel(SubjectType $type): string
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
    $keyword = $argv[1] ?? '';
    $cacheDirectory = WorkflowEnvironment::cacheDirectory();
    $siteDomain = WorkflowEnvironment::value('BGM_SITE_DOMAIN', 'https://bgm.tv/');

    echo $jsonEncoder(subjectSearchResponse(
        $keyword,
        $cacheDirectory,
        $siteDomain,
        WorkflowEnvironment::bangumiConnector(),
    ));
} catch (\Throwable $exception) {
    fwrite(STDERR, $exception.PHP_EOL);

    echo $jsonEncoder(new AlfredSF(items: [new AlfredSFItem(
        title: 'Unable to Search Subjects',
        subtitle: 'Open the debugger and try again',
        valid: false,
    )]));

    exit(1);
}
