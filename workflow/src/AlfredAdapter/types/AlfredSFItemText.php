<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Type;

/** Copy and Large Type text for an Alfred result. */
class AlfredSFItemText extends AlfredSFBase
{
    public function __construct(
        public string $copy,
        public string $largetype,
    ) {}
}
