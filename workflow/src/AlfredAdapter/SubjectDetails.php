<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter;

use Alfred\Workflow\AlfredAdapter\Type\AlfredTV;
use Alfred\Workflow\AlfredAdapter\Type\AlfredTVBehaviour;
use Alfred\Workflow\AlfredAdapter\Type\AlfredTVBehaviourResponse;
use Alfred\Workflow\AlfredAdapter\Type\AlfredTVBehaviourScroll;
use Alfred\Workflow\BangumiSdk\Dto\InfoboxValue;
use Alfred\Workflow\BangumiSdk\Dto\Subject;
use Alfred\Workflow\BangumiSdk\Enums\SubjectType;
use Alfred\Workflow\SubjectDetails as SubjectDetailsCore;

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

require dirname(__DIR__, 2).'/vendor/autoload.php';

/** Return the subject URL supplied to the Alfred Text View. */
function subjectDetailsInput(): string
{
    $arguments = $_SERVER['argv'] ?? null;
    $subjectUrl = is_array($arguments) ? ($arguments[1] ?? null) : null;

    if (!is_string($subjectUrl) || '' === $subjectUrl) {
        throw new \InvalidArgumentException('A Bangumi subject URL is required.');
    }

    return $subjectUrl;
}

/** Fetch a Bangumi subject and adapt it to an Alfred Text View. */
function subjectDetailsResponse(string $subjectUrl): AlfredTV
{
    $subject = (new SubjectDetailsCore())($subjectUrl);

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
    $markdown = ['# '.subjectDetailsEscapeMarkdown($title)];

    if ($title !== $subject->name) {
        $markdown[] = '';
        $markdown[] = subjectDetailsEscapeMarkdown($subject->name);
    }

    $facts = [
        '**类型**：'.subjectDetailsTypeName($subject->type),
    ];

    if ('' !== $subject->platform) {
        $facts[] = '**平台**：'.subjectDetailsEscapeMarkdown($subject->platform);
    }

    if (null !== $subject->date && '' !== $subject->date) {
        $facts[] = '**首发日期**：'.subjectDetailsEscapeMarkdown($subject->date);
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
                subjectDetailsEscapeMarkdown($item->key),
                subjectDetailsInfoboxValueText($item->value),
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

function subjectDetailsTypeName(SubjectType $type): string
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
function subjectDetailsInfoboxValueText(array|string $value): string
{
    if (is_string($value)) {
        return subjectDetailsEscapeMarkdown($value);
    }

    return implode('；', array_map(
        static function (InfoboxValue $entry): string {
            $text = subjectDetailsEscapeMarkdown($entry->v);

            return null !== $entry->k && '' !== $entry->k
                ? subjectDetailsEscapeMarkdown($entry->k).'：'.$text
                : $text;
        },
        $value,
    ));
}

function subjectDetailsEscapeMarkdown(string $value): string
{
    return str_replace(
        ['&', '<', '>'],
        ['&amp;', '&lt;', '&gt;'],
        $value,
    );
}

/** Encode an Alfred response without escaping URLs or Unicode. */
function subjectDetailsJson(AlfredTV $response): string
{
    return json_encode(
        $response,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    );
}

try {
    echo subjectDetailsJson(subjectDetailsResponse(subjectDetailsInput()));
} catch (\Throwable $exception) {
    fwrite(STDERR, $exception.PHP_EOL);

    echo subjectDetailsJson(new AlfredTV(
        response: 'Unable to load subject details. Open the debugger and try again.',
        footer: 'Bangumi · Error',
        actionoutput: false,
    ));

    exit(1);
}
