<?php

declare(strict_types=1);

namespace Alfred\Workflow;

use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Dto\Character;
use Alfred\Workflow\BangumiSdk\Dto\PagedSearchResult;
use Alfred\Workflow\BangumiSdk\Requests\SearchCharactersRequest;

/** Search Bangumi characters by keyword. */
final class CharacterSearch
{
    public function __construct(private readonly BangumiConnector $connector) {}

    /** @return list<Character> */
    public function __invoke(string $keyword, int $limit = 20, int $offset = 0): array
    {
        try {
            $result = $this->connector->send(new SearchCharactersRequest($keyword, $limit, $offset))->dtoOrFail();
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Unable to search Bangumi characters.', previous: $exception);
        }

        if (!$result instanceof PagedSearchResult) {
            throw new \RuntimeException('Bangumi returned invalid character search results.');
        }

        $characters = [];

        foreach ($result->data as $character) {
            if (!$character instanceof Character) {
                throw new \RuntimeException('Bangumi returned an invalid character search result.');
            }

            $characters[] = $character;
        }

        return $characters;
    }
}
