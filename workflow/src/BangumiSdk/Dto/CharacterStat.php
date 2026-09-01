<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

class CharacterStat
{
    public function __construct(
        public int $comments,
        public int $collects,
    ) {}
}
