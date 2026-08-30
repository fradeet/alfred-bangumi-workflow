<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Types;

/** Alfred Script Filter result-cache configuration. */
class AlfredSFCache extends AlfredSFBase
{
    public function __construct(
        public int $seconds,
        public ?bool $loosereload = null,
    ) {}
}
