<?php

declare(strict_types=1);

namespace Alfred\Workflow;

use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Dto\GetCalendarResponse;
use Alfred\Workflow\BangumiSdk\Requests\GetCalendarRequest;

/** Fetch today's schedule from Bangumi's legacy calendar endpoint. */
final class DailyBroadcast
{
    public function __construct(private readonly BangumiConnector $connector) {}

    /**
     * Return today's broadcasts using the local system date.
     */
    public function __invoke(): GetCalendarResponse
    {
        $weekdayId = $this->systemWeekdayId();

        foreach ($this->fetchCalendar() as $schedule) {
            if ($weekdayId === $schedule->weekday->id) {
                return $schedule;
            }
        }

        throw new \RuntimeException(sprintf('Bangumi did not return a schedule for weekday %d.', $weekdayId));
    }

    /**
     * @return list<GetCalendarResponse>
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

            $schedules[] = $schedule;
        }

        return $schedules;
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
