<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Enums;

enum SubjectType: int
{
    case Book = 1;
    case Anime = 2;
    case Music = 3;
    case Game = 4;
    case Real = 6;
}
