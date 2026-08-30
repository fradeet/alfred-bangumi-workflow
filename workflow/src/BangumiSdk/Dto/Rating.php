<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

/**
 * Subject rating statistics returned by Bangumi's legacy API.
 *
 * @property null|int         $rank  Subject ranking.
 * @property null|int         $total Number of ratings.
 * @property null|RatingCount $count Number of ratings for each score.
 * @property null|float|int   $score Average score.
 */
class Rating
{
    public function __construct(
        public ?int $rank = null,
        public ?int $total = null,
        public ?RatingCount $count = null,
        public float|int|null $score = null,
    ) {}
}
