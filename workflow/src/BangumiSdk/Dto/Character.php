<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

use Alfred\Workflow\BangumiSdk\Enums\CharacterType;

/** A character returned by POST /v0/search/characters. */
class Character
{
    public function __construct(
        public int $id,
        public string $name,
        public CharacterType $type,
        public string $summary,
        public bool $locked,
        public CharacterStat $stat,
        public ?Image $images = null,
        public ?string $gender = null,
    ) {}

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        $typeValue = $data['type'] ?? null;
        $type = is_int($typeValue) ? CharacterType::tryFrom($typeValue) : null;

        if (null === $type) {
            throw new \UnexpectedValueException('Character field "type" is invalid.');
        }

        $stat = $data['stat'] ?? null;

        if (!is_array($stat) || array_is_list($stat)) {
            throw new \UnexpectedValueException('Character field "stat" must be an object.');
        }

        $images = $data['images'] ?? null;

        if (null !== $images && (!is_array($images) || array_is_list($images))) {
            throw new \UnexpectedValueException('Character field "images" must be an object or null.');
        }

        $gender = $data['gender'] ?? null;

        if (null !== $gender && !is_string($gender)) {
            throw new \UnexpectedValueException('Character field "gender" must be a string or null.');
        }

        return new self(
            id: self::requiredInt($data, 'id'),
            name: self::requiredString($data, 'name'),
            type: $type,
            summary: self::requiredString($data, 'summary'),
            locked: self::requiredBool($data, 'locked'),
            stat: new CharacterStat(
                comments: self::requiredInt($stat, 'comments', 'stat'),
                collects: self::requiredInt($stat, 'collects', 'stat'),
            ),
            images: null === $images ? null : new Image(
                large: self::requiredString($images, 'large', 'images'),
                medium: self::requiredString($images, 'medium', 'images'),
                small: self::requiredString($images, 'small', 'images'),
                grid: self::requiredString($images, 'grid', 'images'),
            ),
            gender: $gender,
        );
    }

    /** @param array<mixed> $data */
    private static function requiredString(array $data, string $key, string $parent = ''): string
    {
        $value = $data[$key] ?? null;

        if (!is_string($value)) {
            throw new \UnexpectedValueException(sprintf('Character field "%s" must be a string.', self::fieldName($parent, $key)));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function requiredInt(array $data, string $key, string $parent = ''): int
    {
        $value = $data[$key] ?? null;

        if (!is_int($value)) {
            throw new \UnexpectedValueException(sprintf('Character field "%s" must be an integer.', self::fieldName($parent, $key)));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function requiredBool(array $data, string $key): bool
    {
        $value = $data[$key] ?? null;

        if (!is_bool($value)) {
            throw new \UnexpectedValueException(sprintf('Character field "%s" must be a boolean.', $key));
        }

        return $value;
    }

    private static function fieldName(string $parent, string $key): string
    {
        return '' === $parent ? $key : $parent.'.'.$key;
    }
}
