<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Support;

/** Encode an Alfred response without escaping URLs or Unicode. */
final class JsonEncoder
{
    public function __invoke(\JsonSerializable $response): string
    {
        return json_encode(
            $response,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
