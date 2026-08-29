<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

/**
 * Localized weekday information returned by GET /calendar.
 *
 * @property string $en English abbreviated weekday name.
 * @property string $cn Chinese weekday name.
 * @property string $ja Japanese weekday name.
 * @property int    $id ISO-8601 weekday number from 1 to 7.
 */
class Weekday
{
    public function __construct(
        public string $en,
        public string $cn,
        public string $ja,
        public int $id,
    ) {}
}
