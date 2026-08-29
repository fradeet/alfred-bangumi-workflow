<?php

declare(strict_types=1);

namespace Alfred\Workflow;

/** Cache remote images locally, downloading missing images concurrently. */
final class ImageCache
{
    private const MAX_AGE_SECONDS = 365 * 24 * 60 * 60;
    private const USER_AGENT = 'alfred-bangumi-workflow/1.0 (https://github.com/fradeet/alfred-bangumi-workflow)';

    /**
     * @param array<int, string> $images image URLs keyed by subject ID
     *
     * @return array<int, string> local image paths keyed by subject ID
     */
    public function cache(array $images, string $cacheDirectory): array
    {
        $coverDirectory = rtrim($cacheDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'covers';

        if (!is_dir($coverDirectory) && !@mkdir($coverDirectory, 0o755, true) && !is_dir($coverDirectory)) {
            return [];
        }

        $this->removeExpiredImages($coverDirectory);

        $paths = [];
        $downloads = [];

        foreach ($images as $subjectId => $imageUrl) {
            if ('' === $imageUrl) {
                continue;
            }

            $path = $coverDirectory.DIRECTORY_SEPARATOR.$subjectId.'.'.$this->extension($imageUrl);

            if (is_file($path) && filesize($path) > 0) {
                $paths[$subjectId] = $path;

                continue;
            }

            $downloads[$subjectId] = [
                'url' => $this->httpsUrl($imageUrl),
                'path' => $path,
            ];
        }

        if ([] === $downloads) {
            return $paths;
        }

        $multiHandle = curl_multi_init();
        $handles = [];

        foreach ($downloads as $subjectId => $download) {
            $handle = curl_init($download['url']);

            if (false === $handle) {
                continue;
            }

            curl_setopt_array($handle, [
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_USERAGENT => self::USER_AGENT,
            ]);
            curl_multi_add_handle($multiHandle, $handle);
            $handles[$subjectId] = $handle;
        }

        do {
            $status = curl_multi_exec($multiHandle, $running);

            if (CURLM_OK !== $status) {
                break;
            }

            if ($running > 0) {
                curl_multi_select($multiHandle, 1.0);
            }
        } while ($running > 0);

        foreach ($handles as $subjectId => $handle) {
            $body = curl_multi_getcontent($handle);
            $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

            if (200 === $status && is_string($body) && '' !== $body) {
                $path = $downloads[$subjectId]['path'];
                $temporaryPath = $path.'.'.getmypid().'.tmp';

                if (false !== @file_put_contents($temporaryPath, $body, LOCK_EX) && @rename($temporaryPath, $path)) {
                    $paths[$subjectId] = $path;
                } elseif (is_file($temporaryPath)) {
                    @unlink($temporaryPath);
                }
            }

            curl_multi_remove_handle($multiHandle, $handle);
        }

        return $paths;
    }

    private function removeExpiredImages(string $coverDirectory): void
    {
        $expiresBefore = time() - self::MAX_AGE_SECONDS;

        foreach (new \DirectoryIterator($coverDirectory) as $file) {
            if (
                $file->isFile()
                && !$file->isLink()
                && 1 === preg_match('/^\d+\.(?:gif|jpe?g|png|webp)$/', $file->getFilename())
                && $file->getMTime() <= $expiresBefore
            ) {
                @unlink($file->getPathname());
            }
        }
    }

    private function httpsUrl(string $url): string
    {
        $httpsUrl = preg_replace('/^http:/', 'https:', $url);

        return $httpsUrl ?? $url;
    }

    private function extension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = is_string($path) ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';

        return in_array($extension, ['gif', 'jpeg', 'jpg', 'png', 'webp'], true) ? $extension : 'jpg';
    }
}
