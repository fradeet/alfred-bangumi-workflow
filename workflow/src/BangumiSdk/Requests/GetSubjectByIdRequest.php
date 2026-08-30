<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Requests;

use Alfred\Workflow\BangumiSdk\Dto\Subject;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetSubjectByIdRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly int $subjectId)
    {
        if ($subjectId < 1) {
            throw new \InvalidArgumentException('The subject ID must be greater than zero.');
        }
    }

    public function resolveEndpoint(): string
    {
        return sprintf('/v0/subjects/%d', $this->subjectId);
    }

    public function createDtoFromResponse(Response $response): Subject
    {
        $data = $response->json();

        if (array_is_list($data)) {
            throw new \UnexpectedValueException('The subject response must be an object.');
        }

        return Subject::fromArray($data);
    }
}
