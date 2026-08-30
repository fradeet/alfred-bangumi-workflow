<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter;

/** How Alfred should interpret and validate a result item. */
enum AlfredSFItemType: string
{
    case Default = 'default';
    case File = 'file';
    case FileSkipcheck = 'file:skipcheck';
}
