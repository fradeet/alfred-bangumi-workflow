<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Types;

/** A single result row in an Alfred Script Filter response. */
class AlfredSFItem extends AlfredSFBase
{
    /**
     * @param null|AlfredSFItemAction|array<array-key, mixed>|string $action
     * @param null|list<string>|string                               $arg
     * @param null|array<string, array<string, mixed>>               $mods
     * @param null|array<string, mixed>                              $variables
     */
    public function __construct(
        public string $title,
        public AlfredSFItemAction|array|string|null $action = null,
        public array|string|null $arg = null,
        public ?string $autocomplete = null,
        public ?AlfredSFItemIcon $icon = null,
        public ?string $match = null,
        public ?array $mods = null,
        public ?string $quicklookurl = null,
        public ?string $subtitle = null,
        public ?AlfredSFItemType $type = null,
        public ?AlfredSFItemText $text = null,
        public ?string $uid = null,
        public ?array $variables = null,
        public ?bool $valid = null,
    ) {}
}
