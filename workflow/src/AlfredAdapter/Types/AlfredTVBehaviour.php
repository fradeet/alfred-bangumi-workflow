<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Types;

/** Controls how an Alfred Text View updates and handles input. */
class AlfredTVBehaviour extends AlfredTVBase
{
    public function __construct(
        public ?AlfredTVBehaviourResponse $response = null,
        public ?AlfredTVBehaviourScroll $scroll = null,
        public ?AlfredTVBehaviourInputField $inputfield = null,
    ) {}
}
