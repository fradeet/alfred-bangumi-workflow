<?php

declare(strict_types=1);

namespace Alfred\Workflow\AlfredAdapter\Support;

/** Cache successful Alfred JSON responses in local files. */
final class LocalResponseCache
{
    public function __construct(
        private readonly string $directory,
        private readonly int $ttlSeconds,
        private readonly bool $bypass = false,
    ) {
        if ($ttlSeconds < 1) {
            throw new \InvalidArgumentException('The response cache lifetime must be greater than zero.');
        }
    }

    public function get(string $key): ?string
    {
        if ($this->bypass) {
            return null;
        }

        $path = $this->path($key);

        if (!is_file($path) || is_link($path)) {
            return null;
        }

        $modifiedAt = @filemtime($path);

        if (false === $modifiedAt || $modifiedAt <= time() - $this->ttlSeconds) {
            return null;
        }

        $json = @file_get_contents($path);

        if (false === $json || '' === $json) {
            return null;
        }

        try {
            $response = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($response) || !isset($response['items']) || !is_array($response['items'])) {
            return null;
        }

        return $json;
    }

    public function put(string $key, string $json): void
    {
        if ($this->bypass) {
            return;
        }

        if (!is_dir($this->directory) && !@mkdir($this->directory, 0o755, true) && !is_dir($this->directory)) {
            return;
        }

        $temporaryPath = @tempnam($this->directory, '.response-');

        if (false === $temporaryPath) {
            return;
        }

        $path = $this->path($key);

        if (false === @file_put_contents($temporaryPath, $json, LOCK_EX) || !@rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
        }
    }

    private function path(string $key): string
    {
        return rtrim($this->directory, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .hash('sha256', $key)
            .'.json';
    }
}
