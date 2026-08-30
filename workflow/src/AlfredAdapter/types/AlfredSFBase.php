<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Type;

/** Base value object for Alfred Script Filter JSON structures. */
class AlfredSFBase implements \JsonSerializable
{
    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $properties = [];

        foreach (get_object_vars($this) as $name => $value) {
            if (is_string($name) && null !== $value) {
                $properties[$name] = $value;
            }
        }

        return $properties;
    }
}
