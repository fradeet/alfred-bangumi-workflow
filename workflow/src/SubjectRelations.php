<?php

declare(strict_types=1);

namespace Alfred\Workflow;

use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Dto\SubjectRelation;
use Alfred\Workflow\BangumiSdk\Requests\GetRelatedSubjectsBySubjectIdRequest;

/** Fetch the related subjects for a Bangumi subject selected by its public site URL. */
final class SubjectRelations
{
    public function __construct(
        private readonly BangumiConnector $connector = new BangumiConnector(),
        private readonly SubjectIdFromUrl $subjectIdFromUrl = new SubjectIdFromUrl(),
    ) {}

    /** @return list<SubjectRelation> */
    public function __invoke(string $subjectUrl): array
    {
        $subjectId = ($this->subjectIdFromUrl)($subjectUrl);

        try {
            $relations = $this->connector->send(new GetRelatedSubjectsBySubjectIdRequest($subjectId))->dtoOrFail();
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                sprintf('Unable to request relations for Bangumi subject %d.', $subjectId),
                previous: $exception,
            );
        }

        if (!is_array($relations) || !array_is_list($relations)) {
            throw new \RuntimeException('Bangumi returned invalid subject relations.');
        }

        foreach ($relations as $relation) {
            if (!$relation instanceof SubjectRelation) {
                throw new \RuntimeException('Bangumi returned an invalid subject relation.');
            }
        }

        return $relations;
    }
}
