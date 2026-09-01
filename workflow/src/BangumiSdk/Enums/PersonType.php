<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Enums;

enum PersonType: int
{
    case Individual = 1;
    case Corporation = 2;
    case Association = 3;
}
