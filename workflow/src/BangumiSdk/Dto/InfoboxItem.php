<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

/** A single subject infobox entry. */
class InfoboxItem
{
    /** @param list<InfoboxValue>|string $value */
    public function __construct(
        public string $key,
        public array|string $value,
    ) {}
}
