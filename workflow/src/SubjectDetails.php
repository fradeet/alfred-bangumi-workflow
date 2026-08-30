<?php

declare(strict_types=1);

namespace Alfred\Workflow;

use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Dto\Subject;
use Alfred\Workflow\BangumiSdk\Requests\GetSubjectByIdRequest;

/** Fetch the details for a Bangumi subject selected by its public site URL. */
final class SubjectDetails
{
    private const HOSTS = ['bgm.tv', 'bangumi.tv', 'chii.in'];

    public function __construct(private readonly BangumiConnector $connector = new BangumiConnector()) {}

    public function __invoke(string $subjectUrl): Subject
    {
        $subjectId = $this->subjectId($subjectUrl);

        try {
            $subject = $this->connector->send(new GetSubjectByIdRequest($subjectId))->dtoOrFail();
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                sprintf('Unable to request Bangumi subject %d.', $subjectId),
                previous: $exception,
            );
        }

        if (!$subject instanceof Subject) {
            throw new \RuntimeException('Bangumi returned invalid subject details.');
        }

        return $subject;
    }

    private function subjectId(string $subjectUrl): int
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
