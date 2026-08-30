<?php

declare(strict_types=1);

namespace Alfred\Workflow;

/** Extract a subject ID from a public Bangumi subject URL. */
final class SubjectIdFromUrl
{
    private const HOSTS = ['bgm.tv', 'bangumi.tv', 'chii.in'];

    public function __invoke(string $subjectUrl): int
    {
        $url = parse_url($subjectUrl);

        if (
            false === $url
            || !isset($url['scheme'], $url['host'], $url['path'])
            || !in_array(strtolower($url['scheme']), ['http', 'https'], true)
            || !in_array(strtolower($url['host']), self::HOSTS, true)
            || isset($url['user'], $url['pass'])
        ) {
            throw new \InvalidArgumentException('The Bangumi subject URL is invalid.');
        }

        if (1 !== preg_match('#^/subject/([1-9][0-9]*)/?$#', $url['path'], $matches)) {
            throw new \InvalidArgumentException('The Bangumi subject URL must contain a positive subject ID.');
        }

        $subjectId = filter_var($matches[1], FILTER_VALIDATE_INT);

        if (!is_int($subjectId) || $subjectId < 1) {
            throw new \InvalidArgumentException('The Bangumi subject ID is invalid.');
        }

        return $subjectId;
    }
}
