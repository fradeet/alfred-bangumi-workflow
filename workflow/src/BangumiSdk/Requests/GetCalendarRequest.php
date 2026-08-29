<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Requests;

use Alfred\Workflow\BangumiSdk\Dto\GetCalendarResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetCalendarRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/calendar';
    }

    /** @return list<GetCalendarResponse> */
    public function createDtoFromResponse(Response $response): array
    {
        $data = $response->json();

        if (!array_is_list($data)) {
            throw new \UnexpectedValueException('The calendar response must be a list.');
        }

        $calendar = [];

        foreach ($data as $day) {
            if (!is_array($day)) {
                throw new \UnexpectedValueException('Each calendar day must be an object.');
            }

            $calendar[] = GetCalendarResponse::fromArray($day);
        }

        return $calendar;
    }
}
