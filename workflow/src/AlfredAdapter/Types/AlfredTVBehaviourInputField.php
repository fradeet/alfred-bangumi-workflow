<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Types;

/** What happens to the Text View input field after actioning it. */
enum AlfredTVBehaviourInputField: string
{
    case Clear = 'clear';
    case Select = 'select';
}
