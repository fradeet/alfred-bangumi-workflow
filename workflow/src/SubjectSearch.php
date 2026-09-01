<?php

declare(strict_types=1);

namespace Alfred\Workflow;

use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Dto\PagedSearchResult;
use Alfred\Workflow\BangumiSdk\Dto\Subject;
use Alfred\Workflow\BangumiSdk\Requests\SearchSubjectsRequest;

/** Search Bangumi subjects by keyword. */
final class SubjectSearch
{
    public function __construct(private readonly BangumiConnector $connector) {}

    /** @return list<Subject> */
    public function __invoke(string $keyword, int $limit = 20, int $offset = 0): array
    {
        try {
            $result = $this->connector->send(new SearchSubjectsRequest($keyword, $limit, $offset))->dtoOrFail();
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Unable to search Bangumi subjects.', previous: $exception);
        }

        if (!$result instanceof PagedSearchResult) {
            throw new \RuntimeException('Bangumi returned invalid subject search results.');
        }

        $subjects = [];

        foreach ($result->data as $subject) {
            if (!$subject instanceof Subject) {
                throw new \RuntimeException('Bangumi returned an invalid subject search result.');
            }

            $subjects[] = $subject;
        }

        return $subjects;
    }
}
