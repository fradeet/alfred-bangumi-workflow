<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Types;

/** Top-level response returned by an Alfred Script Filter. */
class AlfredSF extends AlfredSFBase
{
    /**
     * @param list<AlfredSFItem>        $items
     * @param null|array<string, mixed> $variables
     */
    public function __construct(
        public array $items,
        public ?array $variables = null,
        public ?string $rerun = null,
        public ?AlfredSFCache $cache = null,
        public ?bool $skipknowledge = null,
    ) {}
}
