<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Type;

/** Top-level response used to populate an Alfred Text View. */
class AlfredTV extends AlfredTVBase
{
    /** @param null|array<string, mixed> $variables */
    public function __construct(
        public string $response,
        public ?array $variables = null,
        public ?float $rerun = null,
        public ?string $footer = null,
        public ?bool $actionoutput = null,
        public ?AlfredTVBehaviour $behaviour = null,
    ) {}
}
