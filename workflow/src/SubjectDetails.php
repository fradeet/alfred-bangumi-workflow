<?php

declare(strict_types=1);

namespace Alfred\Workflow;

use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Dto\Subject;
use Alfred\Workflow\BangumiSdk\Requests\GetSubjectByIdRequest;

/** Fetch the details for a Bangumi subject selected by its public site URL. */
final class SubjectDetails
{
    public function __construct(
        private readonly BangumiConnector $connector = new BangumiConnector(),
        private readonly SubjectIdFromUrl $subjectIdFromUrl = new SubjectIdFromUrl(),
    ) {}

    public function __invoke(string $subjectUrl): Subject
    {
        $subjectId = ($this->subjectIdFromUrl)($subjectUrl);

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
}
