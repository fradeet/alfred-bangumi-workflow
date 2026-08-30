<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Requests;

use Alfred\Workflow\BangumiSdk\Dto\SubjectRelation;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetRelatedSubjectsBySubjectIdRequest extends Request
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
        return sprintf('/v0/subjects/%d/subjects', $this->subjectId);
    }

    /** @return list<SubjectRelation> */
    public function createDtoFromResponse(Response $response): array
    {
        $data = $response->json();

        if (!array_is_list($data)) {
            throw new \UnexpectedValueException('The subject relations response must be a list.');
        }

        $relations = [];

        foreach ($data as $relation) {
            if (!is_array($relation) || array_is_list($relation)) {
                throw new \UnexpectedValueException('Each subject relation must be an object.');
            }

            $relations[] = SubjectRelation::fromArray($relation);
        }

        return $relations;
    }
}
