<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Enums;

enum CharacterType: int
{
    case Character = 1;
    case Mechanic = 2;
    case Ship = 3;
    case Organization = 4;
}
