<?php

declare(strict_types=1);

namespace Alfred\Workflow;

/**
 * Build a public Bangumi subject URL using the selected site mirror.
 */
final class BangumiSiteUrl
{
    public function __invoke(string $subjectUrl, string $siteDomain): string
    {
        $subject = parse_url($subjectUrl);
        $site = parse_url($siteDomain);

        if (false === $subject || !isset($subject['path'])) {
            throw new \InvalidArgumentException('The Bangumi subject URL is invalid.');
        }

        if (
            false === $site
            || !isset($site['scheme'], $site['host'])
            || !in_array($site['scheme'], ['http', 'https'], true)
        ) {
            throw new \InvalidArgumentException('The Bangumi site domain must be an HTTP or HTTPS URL.');
        }

        $url = $site['scheme'].'://'.$site['host'];

        if (isset($site['port'])) {
            $url .= ':'.$site['port'];
        }

        $url .= '/'.ltrim($subject['path'], '/');

        if (isset($subject['query'])) {
            $url .= '?'.$subject['query'];
        }

        if (isset($subject['fragment'])) {
            $url .= '#'.$subject['fragment'];
        }

        return $url;
    }
}
