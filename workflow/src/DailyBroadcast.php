<?php

declare(strict_types=1);

namespace Alfred\Workflow;

use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Dto\GetCalendarResponse;
use Alfred\Workflow\BangumiSdk\Dto\LegacySubjectSmall;
use Alfred\Workflow\BangumiSdk\Requests\GetCalendarRequest;

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
    public function __construct(private readonly BangumiConnector $connector = new BangumiConnector()) {}

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
        try {
            $calendar = $this->connector->send(new GetCalendarRequest())->dtoOrFail();
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Unable to request the Bangumi calendar.', previous: $exception);
        }

        if (!is_array($calendar) || !array_is_list($calendar)) {
            throw new \RuntimeException('Bangumi returned an invalid calendar.');
        }

        $schedules = [];

        foreach ($calendar as $schedule) {
            if (!$schedule instanceof GetCalendarResponse) {
                throw new \RuntimeException('Bangumi returned an invalid daily schedule.');
            }

            $schedules[] = $this->mapSchedule($schedule);
        }

        return $schedules;
    }

    /**
     * @return Schedule
     */
    private function mapSchedule(GetCalendarResponse $schedule): array
    {
        $parsedItems = [];

        foreach ($schedule->items as $item) {
            $parsedItems[] = $this->mapSubject($item);
        }

        return [
            'weekday' => [
                'id' => $schedule->weekday->id,
                'en' => $schedule->weekday->en,
                'cn' => $schedule->weekday->cn,
                'ja' => $schedule->weekday->ja,
            ],
            'items' => $parsedItems,
        ];
    }

    /**
     * @return Subject
     */
    private function mapSubject(LegacySubjectSmall $subject): array
    {
        if (
            null === $subject->id
            || null === $subject->url
            || '' === $subject->url
            || null === $subject->name
            || '' === $subject->name
        ) {
            throw new \RuntimeException('Bangumi returned an incomplete subject.');
        }

        return [
            'id' => $subject->id,
            'url' => $subject->url,
            'name' => $subject->name,
            'name_cn' => $subject->name_cn ?? '',
            'air_date' => $subject->air_date ?? '',
            'eps' => $subject->eps,
            'rating' => null === $subject->rating?->score ? null : ['score' => $subject->rating->score],
            'image_common' => $subject->images->common ?? '',
        ];
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
