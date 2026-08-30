<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter;

/** Typed Universal Action content for an Alfred result. */
class AlfredSFItemAction extends AlfredSFBase
{
    /** @param null|list<string>|string $text */
    public function __construct(
        public array|string|null $text = null,
        public ?string $url = null,
        public ?string $file = null,
        public ?string $auto = null,
    ) {}
}
