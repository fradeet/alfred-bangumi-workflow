<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Type;

/** What happens to the Text View input field after actioning it. */
enum AlfredTVBehaviourInputField: string
{
    case Clear = 'clear';
    case Select = 'select';
}
