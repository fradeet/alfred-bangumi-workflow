<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

/**
 * A compact subject returned by Bangumi's legacy API.
 *
 * @property null|int        $id          Subject ID.
 * @property null|string     $url         Subject URL.
 * @property null|1|2|3|4|6  $type        Subject type: book, anime, music, game, or real.
 * @property null|string     $name        Original subject name.
 * @property null|string     $name_cn     Chinese subject name.
 * @property null|string     $summary     Subject summary.
 * @property null|string     $air_date    Broadcast start date.
 * @property null|int        $air_weekday Broadcast weekday.
 * @property null|Image      $images      Cover image URLs.
 * @property null|int        $eps         Number of episodes.
 * @property null|int        $eps_count   Number of episodes.
 * @property null|Rating     $rating      Rating statistics.
 * @property null|int        $rank        Subject ranking.
 * @property null|Collection $collection  Collection statistics.
 */
class LegacySubjectSmall
{
    /**
     * @param null|1|2|3|4|6 $type
     */
    public function __construct(
        public ?int $id = null,
        public ?string $url = null,
        public ?int $type = null,
        public ?string $name = null,
        public ?string $name_cn = null,
        public ?string $summary = null,
        public ?string $air_date = null,
        public ?int $air_weekday = null,
        public ?Image $images = null,
        public ?int $eps = null,
        public ?int $eps_count = null,
        public ?Rating $rating = null,
        public ?int $rank = null,
        public ?Collection $collection = null,
    ) {}
}
