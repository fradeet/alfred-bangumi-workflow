<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

/**
 * @template T
 */
class PagedSearchResult
{
    /** @param list<T> $data */
    public function __construct(
        public int $total,
        public int $limit,
        public int $offset,
        public array $data,
    ) {}
}
