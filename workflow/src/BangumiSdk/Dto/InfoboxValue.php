<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

/** A structured value in a subject infobox entry. */
class InfoboxValue
{
    public function __construct(
        public string $v,
        public ?string $k = null,
    ) {}
}
