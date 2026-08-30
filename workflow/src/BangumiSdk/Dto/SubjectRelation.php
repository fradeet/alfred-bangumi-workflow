<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Dto;

use Alfred\Workflow\BangumiSdk\Enums\SubjectType;

/** A related subject returned by GET /v0/subjects/{subject_id}/subjects. */
class SubjectRelation
{
    public function __construct(
        public int $id,
        public SubjectType $type,
        public string $name,
        public string $name_cn,
        public string $relation,
        public ?Image $images = null,
    ) {}

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        $typeValue = self::requiredInt($data, 'type');
        $type = SubjectType::tryFrom($typeValue);

        if (null === $type) {
            throw new \UnexpectedValueException('Subject relation field "type" is invalid.');
        }

        return new self(
            id: self::requiredInt($data, 'id'),
            type: $type,
            name: self::requiredString($data, 'name'),
            name_cn: self::requiredString($data, 'name_cn'),
            relation: self::requiredString($data, 'relation'),
            images: self::createImage($data),
        );
    }

    /** @param array<mixed> $data */
    private static function createImage(array $data): ?Image
    {
        $images = $data['images'] ?? null;

        if (null === $images) {
            return null;
        }

        if (!is_array($images) || array_is_list($images)) {
            throw new \UnexpectedValueException('Subject relation field "images" must be an object.');
        }

        return new Image(
            large: self::requiredString($images, 'large', 'images'),
            common: self::requiredString($images, 'common', 'images'),
            medium: self::requiredString($images, 'medium', 'images'),
            small: self::requiredString($images, 'small', 'images'),
            grid: self::requiredString($images, 'grid', 'images'),
        );
    }

    /** @param array<mixed> $data */
    private static function requiredString(array $data, string $key, string $parent = ''): string
    {
        $value = $data[$key] ?? null;

        if (!is_string($value)) {
            throw new \UnexpectedValueException(sprintf('Subject relation field "%s" must be a string.', self::fieldName($parent, $key)));
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function requiredInt(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        if (!is_int($value)) {
            throw new \UnexpectedValueException(sprintf('Subject relation field "%s" must be an integer.', $key));
        }

        return $value;
    }

    private static function fieldName(string $parent, string $key): string
    {
        return '' === $parent ? $key : $parent.'.'.$key;
    }
}
