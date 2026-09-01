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
use Alfred\Workflow\BangumiSdk\Enums\CharacterType;
use Alfred\Workflow\BangumiSiteUrl;
use Alfred\Workflow\CharacterSearch as CharacterSearchCore;
use Alfred\Workflow\ImageCache;

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

require dirname(__DIR__, 2).'/vendor/autoload.php';

/** Search characters and adapt them to Alfred Script Filter items. */
function characterSearchResponse(
    string $keyword,
    string $cacheDirectory,
    string $siteDomain,
    BangumiConnector $connector,
): AlfredSF {
    if ('' === trim($keyword)) {
        return new AlfredSF(items: [new AlfredSFItem(
            title: '搜索 Bangumi 角色',
            subtitle: '输入角色、机体、舰船或组织名称',
            valid: false,
        )]);
    }

    $characters = (new CharacterSearchCore($connector))($keyword);
    $imageUrls = [];

    foreach ($characters as $character) {
        $imageUrls[$character->id] = null === $character->images ? '' : ($character->images->medium ?? '');
    }

    $imagePaths = (new ImageCache())->cache($imageUrls, $cacheDirectory.DIRECTORY_SEPARATOR.'character-search');
    $buildSiteUrl = new BangumiSiteUrl();
    $items = [];

    foreach ($characters as $character) {
        $details = [characterSearchTypeLabel($character->type)];

        if (null !== $character->gender && '' !== $character->gender) {
            $details[] = match ($character->gender) {
                'male' => '男性',
                'female' => '女性',
                default => $character->gender,
            };
        }

        if ($character->stat->collects > 0) {
            $details[] = sprintf('%d 人收藏', $character->stat->collects);
        }

        $url = $buildSiteUrl(sprintf('https://bgm.tv/character/%d', $character->id), $siteDomain);

        $items[] = new AlfredSFItem(
            title: $character->name,
            arg: $url,
            icon: isset($imagePaths[$character->id]) ? new AlfredSFItemIcon($imagePaths[$character->id]) : null,
            match: $character->name,
            quicklookurl: $url,
            subtitle: implode(' · ', $details),
            text: new AlfredSFItemText(copy: $character->name, largetype: $character->name),
            uid: 'bangumi-character-'.$character->id,
        );
    }

    if ([] === $items) {
        $items[] = new AlfredSFItem(
            title: '未找到角色',
            subtitle: sprintf('没有与“%s”匹配的 Bangumi 角色', trim($keyword)),
            valid: false,
        );
    }

    return new AlfredSF(items: $items);
}

function characterSearchTypeLabel(CharacterType $type): string
{
    return match ($type) {
        CharacterType::Character => '角色',
        CharacterType::Mechanic => '机体',
        CharacterType::Ship => '舰船',
        CharacterType::Organization => '组织',
    };
}

$jsonEncoder = new JsonEncoder();

try {
    $keyword = $argv[1] ?? '';
    $cacheDirectory = WorkflowEnvironment::cacheDirectory();
    $siteDomain = WorkflowEnvironment::value('BGM_SITE_DOMAIN', 'https://bgm.tv/');

    echo $jsonEncoder(characterSearchResponse(
        $keyword,
        $cacheDirectory,
        $siteDomain,
        WorkflowEnvironment::bangumiConnector(),
    ));
} catch (\Throwable $exception) {
    fwrite(STDERR, $exception.PHP_EOL);

    echo $jsonEncoder(new AlfredSF(items: [new AlfredSFItem(
        title: 'Unable to Search Characters',
        subtitle: 'Open the debugger and try again',
        valid: false,
    )]));

    exit(1);
}
