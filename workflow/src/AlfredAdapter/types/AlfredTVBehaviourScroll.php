<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Type;

/** Where a Text View scrolls after receiving a response. */
enum AlfredTVBehaviourScroll: string
{
    case Auto = 'auto';
    case Start = 'start';
    case End = 'end';
}
