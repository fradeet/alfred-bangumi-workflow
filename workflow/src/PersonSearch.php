<?php

declare(strict_types=1);

namespace Alfred\Workflow;

use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Dto\PagedSearchResult;
use Alfred\Workflow\BangumiSdk\Dto\Person;
use Alfred\Workflow\BangumiSdk\Requests\SearchPersonsRequest;

/** Search Bangumi persons by keyword. */
final class PersonSearch
{
    public function __construct(private readonly BangumiConnector $connector) {}

    /** @return list<Person> */
    public function __invoke(string $keyword, int $limit = 20, int $offset = 0): array
    {
        try {
            $result = $this->connector->send(new SearchPersonsRequest($keyword, $limit, $offset))->dtoOrFail();
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Unable to search Bangumi persons.', previous: $exception);
        }

        if (!$result instanceof PagedSearchResult) {
            throw new \RuntimeException('Bangumi returned invalid person search results.');
        }

        $persons = [];

        foreach ($result->data as $person) {
            if (!$person instanceof Person) {
                throw new \RuntimeException('Bangumi returned an invalid person search result.');
            }

            $persons[] = $person;
        }

        return $persons;
    }
}
