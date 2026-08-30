<?php

declare(strict_types=1);

namespace Alfred\Workflow;

use Alfred\Workflow\BangumiSdk\Connectors\BangumiConnector;
use Alfred\Workflow\BangumiSdk\Dto\GetCalendarResponse;
use Alfred\Workflow\BangumiSdk\Dto\LegacySubjectSmall;
use Alfred\Workflow\BangumiSdk\Enums\SubjectType;
use Alfred\Workflow\BangumiSdk\Requests\GetCalendarRequest;

/** Fetch all anime in Bangumi's current calendar. */
final class SeasonalAnime
{
    public function __construct(private readonly BangumiConnector $connector = new BangumiConnector()) {}

    /**
     * @return list<LegacySubjectSmall>
     */
    public function __invoke(): array
    {
        try {
            $calendar = $this->connector->send(new GetCalendarRequest())->dtoOrFail();
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Unable to request the Bangumi calendar.', previous: $exception);
        }

        if (!is_array($calendar) || !array_is_list($calendar)) {
            throw new \RuntimeException('Bangumi returned an invalid calendar.');
        }

        $subjects = [];
        $subjectIds = [];

        foreach ($calendar as $schedule) {
            if (!$schedule instanceof GetCalendarResponse) {
                throw new \RuntimeException('Bangumi returned an invalid daily schedule.');
            }

            foreach ($schedule->items as $subject) {
                if (SubjectType::Anime !== $subject->type) {
                    continue;
                }

                if (null !== $subject->id && isset($subjectIds[$subject->id])) {
                    continue;
                }

                if (null !== $subject->id) {
                    $subjectIds[$subject->id] = true;
                }

                $subjects[] = $subject;
            }
        }

        return $subjects;
    }
}
