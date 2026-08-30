<?php

declare(strict_types=1);

use Alfred\Workflow\BangumiSdk\Dto\InfoboxValue;
use Alfred\Workflow\BangumiSdk\Dto\LegacySubjectSmall;
use Alfred\Workflow\BangumiSdk\Dto\Subject;
use Alfred\Workflow\BangumiSdk\Enums\SubjectType;
use Alfred\Workflow\BangumiSiteUrl;
use Alfred\Workflow\DailyBroadcast;
use Alfred\Workflow\ImageCache;
use Alfred\Workflow\SubjectDetails;

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

require __DIR__.'/vendor/autoload.php';

require __DIR__.'/AlfredScriptFilterType.php';

require __DIR__.'/AlfredTextViewType.php';

/**
 * Dispatch a CLI task to the corresponding core class.
 *
 * @param list<string> $arguments
 */
function dispatchTask(string $task, array $arguments): AlfredSF|AlfredTV
{
    return match ($task) {
        'daily-broadcast' => dailyBroadcast($arguments),
        'subject-details' => subjectDetails($arguments),
        default => throw new InvalidArgumentException(sprintf('Unknown task: %s', $task)),
    };
}

/**
 * Fetch a Bangumi subject and adapt it to an Alfred Text View.
 *
 * @param list<string> $arguments
 */
function subjectDetails(array $arguments): AlfredTV
{
    if (1 !== count($arguments)) {
        throw new InvalidArgumentException('Usage: subject-details <subject-url>');
    }

    $subjectUrl = $arguments[0];
    $subject = (new SubjectDetails())($subjectUrl);

    return new AlfredTV(
        response: subjectDetailsMarkdown($subject, $subjectUrl),
        footer: sprintf('Bangumi · Subject #%d', $subject->id),
        actionoutput: false,
        behaviour: new AlfredTVBehaviour(
            response: AlfredTVBehaviourResponse::Replace,
            scroll: AlfredTVBehaviourScroll::Start,
        ),
    );
}

/** Build the Markdown displayed in the subject Text View. */
function subjectDetailsMarkdown(Subject $subject, string $subjectUrl): string
{
    $title = '' !== $subject->name_cn ? $subject->name_cn : $subject->name;
    $markdown = ['# '.escapeMarkdown($title)];

    if ($title !== $subject->name) {
        $markdown[] = '';
        $markdown[] = escapeMarkdown($subject->name);
    }

    $facts = [
        '**类型**：'.subjectTypeName($subject->type),
    ];

    if ('' !== $subject->platform) {
        $facts[] = '**平台**：'.escapeMarkdown($subject->platform);
    }

    if (null !== $subject->date && '' !== $subject->date) {
        $facts[] = '**首发日期**：'.escapeMarkdown($subject->date);
    }

    if ($subject->eps > 0 || $subject->total_episodes > 0) {
        $episodes = $subject->eps > 0 ? (string) $subject->eps : '未知';

        if ($subject->total_episodes > 0 && $subject->total_episodes !== $subject->eps) {
            $episodes .= sprintf('（总计 %d）', $subject->total_episodes);
        }

        $facts[] = '**话数**：'.$episodes;
    }

    if ($subject->volumes > 0) {
        $facts[] = sprintf('**卷数**：%d', $subject->volumes);
    }

    $markdown[] = '';

    foreach ($facts as $fact) {
        $markdown[] = '- '.$fact;
    }

    if (null !== $subject->rating->score && $subject->rating->score > 0) {
        $rating = sprintf('**%.1f / 10**', $subject->rating->score);

        if (null !== $subject->rating->rank && $subject->rating->rank > 0) {
            $rating .= sprintf(' · 排名 #%d', $subject->rating->rank);
        }

        if (null !== $subject->rating->total && $subject->rating->total > 0) {
            $rating .= sprintf(' · %d 人评分', $subject->rating->total);
        }

        $markdown[] = '';
        $markdown[] = '## 评分';
        $markdown[] = '';
        $markdown[] = $rating;
    }

    if ([] !== $subject->tags) {
        $tags = array_map(
            static fn ($tag): string => '`'.str_replace('`', '｀', $tag->name).'`',
            $subject->tags,
        );
        $markdown[] = '';
        $markdown[] = '## 标签';
        $markdown[] = '';
        $markdown[] = implode(' ', $tags);
    }

    if ([] !== $subject->infobox) {
        $markdown[] = '';
        $markdown[] = '## 条目信息';
        $markdown[] = '';

        foreach ($subject->infobox as $item) {
            $markdown[] = sprintf(
                '- **%s**：%s',
                escapeMarkdown($item->key),
                infoboxValueText($item->value),
            );
        }
    }

    $markdown[] = '';
    $markdown[] = '## 简介';
    $markdown[] = '';
    $markdown[] = '' !== trim($subject->summary) ? $subject->summary : '暂无简介。';
    $markdown[] = '';
    $markdown[] = sprintf('[在 Bangumi 打开原条目](%s)', $subjectUrl);

    return implode("\n", $markdown);
}

function subjectTypeName(SubjectType $type): string
{
    return match ($type) {
        SubjectType::Book => '书籍',
        SubjectType::Anime => '动画',
        SubjectType::Music => '音乐',
        SubjectType::Game => '游戏',
        SubjectType::Real => '三次元',
    };
}

/** @param list<InfoboxValue>|string $value */
function infoboxValueText(array|string $value): string
{
    if (is_string($value)) {
        return escapeMarkdown($value);
    }

    return implode('；', array_map(
        static function (InfoboxValue $entry): string {
            $text = escapeMarkdown($entry->v);

            return null !== $entry->k && '' !== $entry->k
                ? escapeMarkdown($entry->k).'：'.$text
                : $text;
        },
        $value,
    ));
}

function escapeMarkdown(string $value): string
{
    return str_replace(
        ['&', '<', '>'],
        ['&amp;', '&lt;', '&gt;'],
        $value,
    );
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
    return toAlfredJson($scriptFilter);
}

/** Encode an Alfred response as JSON. */
function toAlfredJson(AlfredSF|AlfredTV $response): string
{
    return json_encode(
        $response,
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

    echo toAlfredJson(dispatchTask($task, $arguments));
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
