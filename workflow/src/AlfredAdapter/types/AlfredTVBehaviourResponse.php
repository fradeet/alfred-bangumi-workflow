<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Type;

/** How new Text View output updates existing content. */
enum AlfredTVBehaviourResponse: string
{
    case Replace = 'replace';
    case Append = 'append';
    case Prepend = 'prepend';
    case ReplaceLast = 'replacelast';
}
