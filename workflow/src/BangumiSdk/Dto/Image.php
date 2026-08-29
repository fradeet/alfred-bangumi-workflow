<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

/**
 * Cover image URLs returned by Bangumi's legacy API.
 *
 * @property null|string $large  Large cover image URL.
 * @property null|string $common Common cover image URL.
 * @property null|string $medium Medium cover image URL.
 * @property null|string $small  Small cover image URL.
 * @property null|string $grid   Grid cover image URL.
 */
class Image
{
    public function __construct(
        public ?string $large = null,
        public ?string $common = null,
        public ?string $medium = null,
        public ?string $small = null,
        public ?string $grid = null,
    ) {}
}
