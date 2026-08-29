<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

/**
 * A single daily schedule in the response returned by GET /calendar.
 *
 * The endpoint returns a list of these objects, one for each weekday.
 *
 * @property Weekday                  $weekday Localized weekday information.
 * @property list<LegacySubjectSmall> $items   Subjects broadcast on that weekday.
 */
class GetCalendarResponse
{
    /**
     * @param list<LegacySubjectSmall> $items
     */
    public function __construct(
        public Weekday $weekday,
        public array $items,
    ) {}

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        $weekday = self::requiredArray($data, 'weekday');
        $items = self::requiredArray($data, 'items');

        if (!array_is_list($items)) {
            throw new \UnexpectedValueException('Calendar field "items" must be a list.');
        }

        $subjects = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                throw new \UnexpectedValueException('Each calendar item must be an object.');
            }

            $subjects[] = self::createSubject($item);
        }

        return new self(
            weekday: new Weekday(
                en: self::requiredString($weekday, 'en'),
                cn: self::requiredString($weekday, 'cn'),
                ja: self::requiredString($weekday, 'ja'),
                id: self::requiredInt($weekday, 'id'),
            ),
            items: $subjects,
        );
    }

    /** @param array<mixed> $data */
    private static function createSubject(array $data): LegacySubjectSmall
    {
        $type = self::optionalInt($data, 'type');

        if (null !== $type && !in_array($type, [1, 2, 3, 4, 6], true)) {
            throw new \UnexpectedValueException('Calendar subject field "type" is invalid.');
        }

        return new LegacySubjectSmall(
            id: self::optionalInt($data, 'id'),
            url: self::optionalString($data, 'url'),
            type: $type,
            name: self::optionalString($data, 'name'),
            name_cn: self::optionalString($data, 'name_cn'),
            summary: self::optionalString($data, 'summary'),
            air_date: self::optionalString($data, 'air_date'),
            air_weekday: self::optionalInt($data, 'air_weekday'),
            images: self::createImage(self::optionalArray($data, 'images')),
            eps: self::optionalInt($data, 'eps'),
            eps_count: self::optionalInt($data, 'eps_count'),
            rating: self::createRating(self::optionalArray($data, 'rating')),
            rank: self::optionalInt($data, 'rank'),
            collection: self::createCollection(self::optionalArray($data, 'collection')),
        );
    }

    /** @param null|array<mixed> $data */
    private static function createImage(?array $data): ?Image
    {
        if (null === $data) {
            return null;
        }

        return new Image(
            large: self::optionalString($data, 'large'),
            common: self::optionalString($data, 'common'),
            medium: self::optionalString($data, 'medium'),
            small: self::optionalString($data, 'small'),
            grid: self::optionalString($data, 'grid'),
        );
    }

    /** @param null|array<mixed> $data */
    private static function createRating(?array $data): ?Rating
    {
        if (null === $data) {
            return null;
        }

        return new Rating(
            total: self::optionalInt($data, 'total'),
            count: self::createRatingCount(self::optionalArray($data, 'count')),
            score: self::optionalNumber($data, 'score'),
        );
    }

    /** @param null|array<mixed> $data */
    private static function createRatingCount(?array $data): ?RatingCount
    {
        if (null === $data) {
            return null;
        }

        return new RatingCount(
            score1: self::optionalInt($data, 1),
            score2: self::optionalInt($data, 2),
            score3: self::optionalInt($data, 3),
            score4: self::optionalInt($data, 4),
            score5: self::optionalInt($data, 5),
            score6: self::optionalInt($data, 6),
            score7: self::optionalInt($data, 7),
            score8: self::optionalInt($data, 8),
            score9: self::optionalInt($data, 9),
            score10: self::optionalInt($data, 10),
        );
    }

    /** @param null|array<mixed> $data */
    private static function createCollection(?array $data): ?Collection
    {
        if (null === $data) {
            return null;
        }

        return new Collection(
            wish: self::optionalInt($data, 'wish'),
            collect: self::optionalInt($data, 'collect'),
            doing: self::optionalInt($data, 'doing'),
            on_hold: self::optionalInt($data, 'on_hold'),
            dropped: self::optionalInt($data, 'dropped'),
        );
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private static function requiredArray(array $data, int|string $key): array
    {
        $value = $data[$key] ?? null;

        if (!is_array($value)) {
            throw new \UnexpectedValueException(sprintf('Calendar field "%s" must be an object or list.', $key));
        }

        return $value;
    }

    /**
     * @param array<mixed> $data
     *
     * @return null|array<mixed>
     */
    private static function optionalArray(array $data, int|string $key): ?array
    {
        $value = $data[$key] ?? null;

        if (null !== $value && !is_array($value)) {
            throw new \UnexpectedValueException(sprintf('Calendar field "%s" must be an object.', $key));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function requiredString(array $data, int|string $key): string
    {
        $value = $data[$key] ?? null;

        if (!is_string($value)) {
            throw new \UnexpectedValueException(sprintf('Calendar field "%s" must be a string.', $key));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function optionalString(array $data, int|string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (null !== $value && !is_string($value)) {
            throw new \UnexpectedValueException(sprintf('Calendar field "%s" must be a string.', $key));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function requiredInt(array $data, int|string $key): int
    {
        $value = $data[$key] ?? null;

        if (!is_int($value)) {
            throw new \UnexpectedValueException(sprintf('Calendar field "%s" must be an integer.', $key));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function optionalInt(array $data, int|string $key): ?int
    {
        $value = $data[$key] ?? null;

        if (null !== $value && !is_int($value)) {
            throw new \UnexpectedValueException(sprintf('Calendar field "%s" must be an integer.', $key));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function optionalNumber(array $data, int|string $key): float|int|null
    {
        $value = $data[$key] ?? null;

        if (null !== $value && !is_float($value) && !is_int($value)) {
            throw new \UnexpectedValueException(sprintf('Calendar field "%s" must be a number.', $key));
        }

        return $value;
    }
}
