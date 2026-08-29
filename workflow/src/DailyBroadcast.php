<?php

declare(strict_types=1);

namespace Alfred\Workflow;

/**
 * Fetch and validate Bangumi's legacy calendar endpoint.
 *
 * @phpstan-type Weekday array{id: int, en: string, cn: string, ja: string}
 * @phpstan-type Subject array{
 *     id: int,
 *     url: string,
 *     name: string,
 *     name_cn: string,
 *     air_date: string,
 *     eps: int|null,
 *     rating: array{score: float|int}|null,
 *     image_common: string
 * }
 * @phpstan-type Schedule array{weekday: Weekday, items: list<Subject>}
 */
final class DailyBroadcast
{
    private const ENDPOINT = 'https://api.bgm.tv/calendar';
    private const USER_AGENT = 'alfred-bangumi-workflow/1.0 (https://github.com/fradeet/alfred-bangumi-workflow)';

    /**
     * Return today's broadcasts using the local system date.
     *
     * @return Schedule
     */
    public function __invoke(): array
    {
        $weekdayId = $this->systemWeekdayId();

        foreach ($this->fetchCalendar() as $schedule) {
            if ($weekdayId === $schedule['weekday']['id']) {
                return $schedule;
            }
        }

        throw new \RuntimeException(sprintf('Bangumi did not return a schedule for weekday %d.', $weekdayId));
    }

    /**
     * @return list<Schedule>
     */
    private function fetchCalendar(): array
    {
        $curl = curl_init(self::ENDPOINT);

        if (false === $curl) {
            throw new \RuntimeException('Unable to initialize the Bangumi request.');
        }

        curl_setopt_array($curl, [
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => self::USER_AGENT,
        ]);

        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);

        if (!is_string($body)) {
            throw new \RuntimeException('Unable to request Bangumi: '.$error);
        }

        if (200 !== $status) {
            throw new \RuntimeException(sprintf('Bangumi returned HTTP %d.', $status));
        }

        try {
            $calendar = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Bangumi returned invalid JSON.', previous: $exception);
        }

        if (!is_array($calendar) || !array_is_list($calendar)) {
            throw new \RuntimeException('Bangumi returned an invalid calendar.');
        }

        $schedules = [];

        foreach ($calendar as $schedule) {
            $schedules[] = $this->parseSchedule($schedule);
        }

        return $schedules;
    }

    /**
     * @return Schedule
     */
    private function parseSchedule(mixed $value): array
    {
        if (!is_array($value)) {
            throw new \RuntimeException('Bangumi returned an invalid daily schedule.');
        }

        $weekday = $value['weekday'] ?? null;
        $items = $value['items'] ?? null;

        if (!is_array($weekday) || !is_array($items) || !array_is_list($items)) {
            throw new \RuntimeException('Bangumi returned an incomplete daily schedule.');
        }

        $parsedItems = [];

        foreach ($items as $item) {
            $parsedItems[] = $this->parseSubject($item);
        }

        return [
            'weekday' => [
                'id' => $this->requiredInt($weekday, 'id'),
                'en' => $this->requiredString($weekday, 'en'),
                'cn' => $this->requiredString($weekday, 'cn'),
                'ja' => $this->requiredString($weekday, 'ja'),
            ],
            'items' => $parsedItems,
        ];
    }

    /**
     * @return Subject
     */
    private function parseSubject(mixed $value): array
    {
        if (!is_array($value)) {
            throw new \RuntimeException('Bangumi returned an invalid subject.');
        }

        $eps = $value['eps'] ?? null;
        $rating = $value['rating'] ?? null;
        $images = $value['images'] ?? null;
        $parsedRating = null;

        if (is_array($rating) && isset($rating['score']) && (is_float($rating['score']) || is_int($rating['score']))) {
            $parsedRating = ['score' => $rating['score']];
        }

        return [
            'id' => $this->requiredInt($value, 'id'),
            'url' => $this->requiredString($value, 'url'),
            'name' => $this->requiredString($value, 'name'),
            'name_cn' => $this->optionalString($value, 'name_cn'),
            'air_date' => $this->optionalString($value, 'air_date'),
            'eps' => is_int($eps) ? $eps : null,
            'rating' => $parsedRating,
            'image_common' => is_array($images) ? $this->optionalString($images, 'common') : '',
        ];
    }

    /** @param array<mixed> $values */
    private function requiredInt(array $values, string $key): int
    {
        $value = $values[$key] ?? null;

        if (!is_int($value)) {
            throw new \RuntimeException(sprintf('Bangumi response field "%s" must be an integer.', $key));
        }

        return $value;
    }

    /** @param array<mixed> $values */
    private function requiredString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;

        if (!is_string($value) || '' === $value) {
            throw new \RuntimeException(sprintf('Bangumi response field "%s" must be a non-empty string.', $key));
        }

        return $value;
    }

    /** @param array<mixed> $values */
    private function optionalString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;

        return is_string($value) ? $value : '';
    }

    private function systemWeekdayId(): int
    {
        $timezonePath = readlink('/etc/localtime');

        if (is_string($timezonePath) && 1 === preg_match('#/zoneinfo/(.+)$#', $timezonePath, $matches)) {
            try {
                $timezone = new \DateTimeZone($matches[1]);

                return (int) (new \DateTimeImmutable('now', $timezone))->format('N');
            } catch (\Exception) {
                // Fall back to PHP's configured timezone below.
            }
        }

        return (int) (new \DateTimeImmutable())->format('N');
    }
}
