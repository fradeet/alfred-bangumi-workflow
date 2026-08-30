<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Type;

/** Alfred Script Filter result-cache configuration. */
class AlfredSFCache extends AlfredSFBase
{
    public function __construct(
        public int $seconds,
        public ?bool $loosereload = null,
    ) {}
}
