<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

/** A tag attached to a Bangumi subject. */
class SubjectTag
{
    public function __construct(
        public string $name,
        public int $count,
        public int $total_count,
    ) {}
}
