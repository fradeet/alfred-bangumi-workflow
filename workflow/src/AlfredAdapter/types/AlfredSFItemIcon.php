<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Type;

/** Icon displayed alongside an Alfred result. */
class AlfredSFItemIcon extends AlfredSFBase
{
    public function __construct(
        public string $path,
        public ?string $type = null,
    ) {}
}
