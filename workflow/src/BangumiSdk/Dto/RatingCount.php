<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

/**
 * Number of subject ratings for each score.
 *
 * @property null|int $score1  Number of one-point ratings.
 * @property null|int $score2  Number of two-point ratings.
 * @property null|int $score3  Number of three-point ratings.
 * @property null|int $score4  Number of four-point ratings.
 * @property null|int $score5  Number of five-point ratings.
 * @property null|int $score6  Number of six-point ratings.
 * @property null|int $score7  Number of seven-point ratings.
 * @property null|int $score8  Number of eight-point ratings.
 * @property null|int $score9  Number of nine-point ratings.
 * @property null|int $score10 Number of ten-point ratings.
 */
class RatingCount
{
    public function __construct(
        public ?int $score1 = null,
        public ?int $score2 = null,
        public ?int $score3 = null,
        public ?int $score4 = null,
        public ?int $score5 = null,
        public ?int $score6 = null,
        public ?int $score7 = null,
        public ?int $score8 = null,
        public ?int $score9 = null,
        public ?int $score10 = null,
    ) {}
}
