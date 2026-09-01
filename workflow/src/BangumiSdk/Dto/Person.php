<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

use Alfred\Workflow\BangumiSdk\Enums\PersonType;

/** A person returned by POST /v0/search/persons. */
class Person
{
    /** @param list<string> $career */
    public function __construct(
        public int $id,
        public string $name,
        public PersonType $type,
        public array $career,
        public ?string $short_summary,
        public bool $locked,
        public ?Image $images = null,
    ) {}

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        $typeValue = $data['type'] ?? null;
        $type = is_int($typeValue) ? PersonType::tryFrom($typeValue) : null;

        if (null === $type) {
            throw new \UnexpectedValueException('Person field "type" is invalid.');
        }

        $career = $data['career'] ?? null;

        if (!is_array($career) || !array_is_list($career)) {
            throw new \UnexpectedValueException('Person field "career" must be a list.');
        }

        foreach ($career as $careerName) {
            if (!is_string($careerName)) {
                throw new \UnexpectedValueException('Each person career must be a string.');
            }
        }

        $images = $data['images'] ?? null;

        if (null !== $images && (!is_array($images) || array_is_list($images))) {
            throw new \UnexpectedValueException('Person field "images" must be an object or null.');
        }

        return new self(
            id: self::requiredInt($data, 'id'),
            name: self::requiredString($data, 'name'),
            type: $type,
            career: $career,
            short_summary: self::optionalString($data, 'short_summary'),
            locked: self::requiredBool($data, 'locked'),
            images: null === $images ? null : new Image(
                large: self::requiredString($images, 'large', 'images'),
                medium: self::requiredString($images, 'medium', 'images'),
                small: self::requiredString($images, 'small', 'images'),
                grid: self::requiredString($images, 'grid', 'images'),
            ),
        );
    }

    /** @param array<mixed> $data */
    private static function requiredString(array $data, string $key, string $parent = ''): string
    {
        $value = $data[$key] ?? null;

        if (!is_string($value)) {
            throw new \UnexpectedValueException(sprintf('Person field "%s" must be a string.', self::fieldName($parent, $key)));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function optionalString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (null !== $value && !is_string($value)) {
            throw new \UnexpectedValueException(sprintf('Person field "%s" must be a string or null.', $key));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function requiredInt(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        if (!is_int($value)) {
            throw new \UnexpectedValueException(sprintf('Person field "%s" must be an integer.', $key));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function requiredBool(array $data, string $key): bool
    {
        $value = $data[$key] ?? null;

        if (!is_bool($value)) {
            throw new \UnexpectedValueException(sprintf('Person field "%s" must be a boolean.', $key));
        }

        return $value;
    }

    private static function fieldName(string $parent, string $key): string
    {
        return '' === $parent ? $key : $parent.'.'.$key;
    }
}
