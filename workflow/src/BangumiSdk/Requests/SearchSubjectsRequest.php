<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Requests;

use Alfred\Workflow\BangumiSdk\Dto\PagedSearchResult;
use Alfred\Workflow\BangumiSdk\Dto\Subject;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class SearchSubjectsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $keyword,
        private readonly int $limit = 20,
        private readonly int $offset = 0,
    ) {
        self::validateParameters($keyword, $limit, $offset);
    }

    public function resolveEndpoint(): string
    {
        return '/v0/search/subjects';
    }

    /** @return PagedSearchResult<Subject> */
    public function createDtoFromResponse(Response $response): PagedSearchResult
    {
        $data = self::responseObject($response);
        $items = self::responseList($data);
        $subjects = [];

        foreach ($items as $item) {
            if (!is_array($item) || array_is_list($item)) {
                throw new \UnexpectedValueException('Each subject search result must be an object.');
            }

            $subjects[] = Subject::fromArray($item);
        }

        return new PagedSearchResult(
            total: self::requiredInt($data, 'total'),
            limit: self::requiredInt($data, 'limit'),
            offset: self::requiredInt($data, 'offset'),
            data: $subjects,
        );
    }

    /** @return array<string, int> */
    protected function defaultQuery(): array
    {
        return ['limit' => $this->limit, 'offset' => $this->offset];
    }

    /** @return array<string, string> */
    protected function defaultBody(): array
    {
        return [
            'keyword' => trim($this->keyword),
        ];
    }

    private static function validateParameters(string $keyword, int $limit, int $offset): void
    {
        if ('' === trim($keyword)) {
            throw new \InvalidArgumentException('The search keyword must not be empty.');
        }

        if ($limit < 1 || $limit > 100) {
            throw new \InvalidArgumentException('The search limit must be between 1 and 100.');
        }

        if ($offset < 0) {
            throw new \InvalidArgumentException('The search offset must not be negative.');
        }
    }

    /** @return array<mixed> */
    private static function responseObject(Response $response): array
    {
        $data = $response->json();

        if (array_is_list($data)) {
            throw new \UnexpectedValueException('The subject search response must be an object.');
        }

        return $data;
    }

    /** @param array<mixed> $data
     *
     * @return list<mixed>
     */
    private static function responseList(array $data): array
    {
        $items = $data['data'] ?? null;

        if (!is_array($items) || !array_is_list($items)) {
            throw new \UnexpectedValueException('Subject search field "data" must be a list.');
        }

        return $items;
    }

    /** @param array<mixed> $data */
    private static function requiredInt(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        if (!is_int($value)) {
            throw new \UnexpectedValueException(sprintf('Subject search field "%s" must be an integer.', $key));
        }

        return $value;
    }
}
