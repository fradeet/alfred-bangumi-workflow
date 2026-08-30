<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

use Alfred\Workflow\BangumiSdk\Enums\SubjectType;

/** A subject returned by GET /v0/subjects/{subject_id}. */
class Subject
{
    /**
     * @param list<string>      $meta_tags
     * @param list<SubjectTag>  $tags
     * @param list<InfoboxItem> $infobox
     */
    public function __construct(
        public int $id,
        public SubjectType $type,
        public string $name,
        public string $name_cn,
        public string $summary,
        public bool $nsfw,
        public bool $locked,
        public string $platform,
        public array $meta_tags,
        public int $volumes,
        public int $eps,
        public bool $series,
        public int $total_episodes,
        public Rating $rating,
        public Image $images,
        public Collection $collection,
        public array $tags,
        public ?string $date = null,
        public array $infobox = [],
    ) {}

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        $typeValue = self::requiredInt($data, 'type');
        $type = SubjectType::tryFrom($typeValue);

        if (null === $type) {
            throw new \UnexpectedValueException('Subject field "type" is invalid.');
        }

        return new self(
            id: self::requiredInt($data, 'id'),
            type: $type,
            name: self::requiredString($data, 'name'),
            name_cn: self::requiredString($data, 'name_cn'),
            summary: self::requiredString($data, 'summary'),
            nsfw: self::requiredBool($data, 'nsfw'),
            locked: self::requiredBool($data, 'locked'),
            platform: self::requiredString($data, 'platform'),
            meta_tags: self::createStringList(self::requiredArray($data, 'meta_tags'), 'meta_tags'),
            volumes: self::requiredInt($data, 'volumes'),
            eps: self::requiredInt($data, 'eps'),
            series: self::requiredBool($data, 'series'),
            total_episodes: self::requiredInt($data, 'total_episodes'),
            rating: self::createRating(self::requiredArray($data, 'rating')),
            images: self::createImage(self::requiredArray($data, 'images')),
            collection: self::createCollection(self::requiredArray($data, 'collection')),
            tags: self::createTags(self::requiredArray($data, 'tags')),
            date: self::optionalString($data, 'date'),
            infobox: self::createInfobox(self::optionalArray($data, 'infobox') ?? []),
        );
    }

    /** @param array<mixed> $data */
    private static function createRating(array $data): Rating
    {
        return new Rating(
            rank: self::requiredInt($data, 'rank', 'rating'),
            total: self::requiredInt($data, 'total', 'rating'),
            count: self::createRatingCount(self::requiredArray($data, 'count', 'rating')),
            score: self::requiredNumber($data, 'score', 'rating'),
        );
    }

    /** @param array<mixed> $data */
    private static function createRatingCount(array $data): RatingCount
    {
        return new RatingCount(
            score1: self::optionalInt($data, 1, 'rating.count'),
            score2: self::optionalInt($data, 2, 'rating.count'),
            score3: self::optionalInt($data, 3, 'rating.count'),
            score4: self::optionalInt($data, 4, 'rating.count'),
            score5: self::optionalInt($data, 5, 'rating.count'),
            score6: self::optionalInt($data, 6, 'rating.count'),
            score7: self::optionalInt($data, 7, 'rating.count'),
            score8: self::optionalInt($data, 8, 'rating.count'),
            score9: self::optionalInt($data, 9, 'rating.count'),
            score10: self::optionalInt($data, 10, 'rating.count'),
        );
    }

    /** @param array<mixed> $data */
    private static function createImage(array $data): Image
    {
        return new Image(
            large: self::requiredString($data, 'large', 'images'),
            common: self::requiredString($data, 'common', 'images'),
            medium: self::requiredString($data, 'medium', 'images'),
            small: self::requiredString($data, 'small', 'images'),
            grid: self::requiredString($data, 'grid', 'images'),
        );
    }

    /** @param array<mixed> $data */
    private static function createCollection(array $data): Collection
    {
        return new Collection(
            wish: self::requiredInt($data, 'wish', 'collection'),
            collect: self::requiredInt($data, 'collect', 'collection'),
            doing: self::requiredInt($data, 'doing', 'collection'),
            on_hold: self::requiredInt($data, 'on_hold', 'collection'),
            dropped: self::requiredInt($data, 'dropped', 'collection'),
        );
    }

    /**
     * @param array<mixed> $data
     *
     * @return list<SubjectTag>
     */
    private static function createTags(array $data): array
    {
        self::assertList($data, 'tags');
        $tags = [];

        foreach ($data as $tag) {
            if (!is_array($tag)) {
                throw new \UnexpectedValueException('Each subject tag must be an object.');
            }

            $tags[] = new SubjectTag(
                name: self::requiredString($tag, 'name', 'tags'),
                count: self::requiredInt($tag, 'count', 'tags'),
                total_count: self::requiredInt($tag, 'total_count', 'tags'),
            );
        }

        return $tags;
    }

    /**
     * @param array<mixed> $data
     *
     * @return list<InfoboxItem>
     */
    private static function createInfobox(array $data): array
    {
        self::assertList($data, 'infobox');
        $infobox = [];

        foreach ($data as $item) {
            if (!is_array($item)) {
                throw new \UnexpectedValueException('Each subject infobox item must be an object.');
            }

            $value = $item['value'] ?? null;

            if (is_array($value)) {
                self::assertList($value, 'infobox value');
                $values = [];

                foreach ($value as $entry) {
                    if (!is_array($entry)) {
                        throw new \UnexpectedValueException('Each structured infobox value must be an object.');
                    }

                    $values[] = new InfoboxValue(
                        v: self::requiredString($entry, 'v', 'infobox value'),
                        k: self::optionalString($entry, 'k', 'infobox value'),
                    );
                }

                $value = $values;
            } elseif (!is_string($value)) {
                throw new \UnexpectedValueException('Subject field "infobox.value" must be a string or list.');
            }

            $infobox[] = new InfoboxItem(
                key: self::requiredString($item, 'key', 'infobox'),
                value: $value,
            );
        }

        return $infobox;
    }

    /**
     * @param array<mixed> $data
     *
     * @return list<string>
     */
    private static function createStringList(array $data, string $field): array
    {
        self::assertList($data, $field);
        $strings = [];

        foreach ($data as $value) {
            if (!is_string($value)) {
                throw new \UnexpectedValueException(sprintf('Each subject %s item must be a string.', $field));
            }

            $strings[] = $value;
        }

        return $strings;
    }

    /** @param array<mixed> $data */
    private static function assertList(array $data, string $field): void
    {
        if (!array_is_list($data)) {
            throw new \UnexpectedValueException(sprintf('Subject field "%s" must be a list.', $field));
        }
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private static function requiredArray(array $data, int|string $key, string $parent = ''): array
    {
        $value = $data[$key] ?? null;

        if (!is_array($value)) {
            throw new \UnexpectedValueException(sprintf('Subject field "%s" must be an object or list.', self::fieldName($parent, $key)));
        }

        return $value;
    }

    /**
     * @param array<mixed> $data
     *
     * @return null|array<mixed>
     */
    private static function optionalArray(array $data, int|string $key, string $parent = ''): ?array
    {
        $value = $data[$key] ?? null;

        if (null !== $value && !is_array($value)) {
            throw new \UnexpectedValueException(sprintf('Subject field "%s" must be an object or list.', self::fieldName($parent, $key)));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function requiredString(array $data, int|string $key, string $parent = ''): string
    {
        $value = $data[$key] ?? null;

        if (!is_string($value)) {
            throw new \UnexpectedValueException(sprintf('Subject field "%s" must be a string.', self::fieldName($parent, $key)));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function optionalString(array $data, int|string $key, string $parent = ''): ?string
    {
        $value = $data[$key] ?? null;

        if (null !== $value && !is_string($value)) {
            throw new \UnexpectedValueException(sprintf('Subject field "%s" must be a string.', self::fieldName($parent, $key)));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function requiredInt(array $data, int|string $key, string $parent = ''): int
    {
        $value = $data[$key] ?? null;

        if (!is_int($value)) {
            throw new \UnexpectedValueException(sprintf('Subject field "%s" must be an integer.', self::fieldName($parent, $key)));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function optionalInt(array $data, int|string $key, string $parent = ''): ?int
    {
        $value = $data[$key] ?? null;

        if (null !== $value && !is_int($value)) {
            throw new \UnexpectedValueException(sprintf('Subject field "%s" must be an integer.', self::fieldName($parent, $key)));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function requiredBool(array $data, int|string $key, string $parent = ''): bool
    {
        $value = $data[$key] ?? null;

        if (!is_bool($value)) {
            throw new \UnexpectedValueException(sprintf('Subject field "%s" must be a boolean.', self::fieldName($parent, $key)));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function requiredNumber(array $data, int|string $key, string $parent = ''): float|int
    {
        $value = $data[$key] ?? null;

        if (!is_float($value) && !is_int($value)) {
            throw new \UnexpectedValueException(sprintf('Subject field "%s" must be a number.', self::fieldName($parent, $key)));
        }

        return $value;
    }

    private static function fieldName(string $parent, int|string $key): string
    {
        return '' === $parent ? (string) $key : $parent.'.'.$key;
    }
}
