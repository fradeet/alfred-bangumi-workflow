<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

/**
 * Subject collection statistics returned by Bangumi's legacy API.
 *
 * @property null|int $wish    Users who want to consume the subject.
 * @property null|int $collect Users who have completed the subject.
 * @property null|int $doing   Users currently consuming the subject.
 * @property null|int $on_hold Users who put the subject on hold.
 * @property null|int $dropped Users who dropped the subject.
 */
class Collection
{
    public function __construct(
        public ?int $wish = null,
        public ?int $collect = null,
        public ?int $doing = null,
        public ?int $on_hold = null,
        public ?int $dropped = null,
    ) {}
}
