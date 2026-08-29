<?php

declare(strict_types=1);

namespace Alfred\Workflow\BangumiSdk\Connectors;

use Saloon\Http\Connector;

class BangumiConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://api.bgm.tv';
    }

    #[\Override]
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'alfred-bangumi-workflow/1.0 (https://github.com/fradeet/alfred-bangumi-workflow)',
        ];
    }
}
