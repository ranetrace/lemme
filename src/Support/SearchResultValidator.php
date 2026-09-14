<?php

namespace Ranetrace\Lemme\Support;

use Ranetrace\Lemme\Lemme;

/**
 * The gate between the browser and the rendered result list.
 *
 * `SearchComponent::handleSearchResults()` is a Livewire listener, so anything
 * with a console can call it with any payload it likes, and the view then prints
 * that payload: the url goes into an `href`, the title and the excerpt are read
 * back out as text. A url is the dangerous one, because escaping does nothing to
 * `javascript:`, so the url is checked by construction rather than by pattern:
 * a root-relative path, or an absolute url on a host this site serves docs on.
 *
 * A failing entry rejects the whole payload instead of being dropped from it.
 * The browser sends the index back exactly as it received it, so one entry that
 * does not fit the shape means the payload is not ours; dropping that entry
 * alone would render the rest as a list that still looks like a search result,
 * which is the outcome a caller tampering with the payload is after.
 *
 * The filter is written out rather than handed to `Validator`: the package
 * requires `illuminate/support` and not `illuminate/validation`, and the rules
 * that matter here (a same-site url, an entry carrying nothing beyond the keys
 * the view reads) are not rule strings anyway.
 */
class SearchResultValidator
{
    /**
     * The most results the browser may post back.
     *
     * It is also the number the view asks the search instance for, which is why
     * the view reads it from here: the cap and the request have to be one number
     * or a legitimate result set starts being rejected.
     */
    public const MAX_RESULTS = 5;

    /**
     * The payload as the view may render it, or an empty list.
     *
     * @param  array<array-key, mixed>  $results
     * @return array<int, array<string, mixed>>
     */
    public function validate(array $results): array
    {
        if (count($results) > self::MAX_RESULTS) {
            return [];
        }

        $validated = [];

        foreach ($results as $result) {
            $entry = $this->validateEntry($result);

            if ($entry === null) {
                return [];
            }

            $validated[] = $entry;
        }

        return $validated;
    }

    /**
     * One entry reduced to the keys the view reads, or null if it does not fit.
     *
     * The entry is rebuilt rather than filtered in place, so a key nobody asked
     * for cannot ride along into the component's state. The browser sends one of
     * those on every search: Fuse's `matches`, which the view reads from the
     * search instance in the browser and never from here.
     *
     * @return array<string, mixed>|null
     */
    protected function validateEntry(mixed $result): ?array
    {
        if (! is_array($result)) {
            return null;
        }

        foreach (['title', 'category', 'content', 'slug'] as $key) {
            if (! isset($result[$key]) || ! is_string($result[$key])) {
                return null;
            }
        }

        if (! $this->isSameSiteUrl($result['url'] ?? null)) {
            return null;
        }

        $entry = [
            'title' => $result['title'],
            'category' => $result['category'],
            'url' => $result['url'],
            'content' => $result['content'],
            'slug' => $result['slug'],
        ];

        if (array_key_exists('score', $result)) {
            $score = $result['score'];

            // A JSON number arrives as an int or a float, so a numeric string is
            // already something other than what the sender emits. `is_finite`
            // covers the NAN a float cast can carry, which no comparison rejects.
            if (! is_int($score) && ! is_float($score)) {
                return null;
            }

            if (! is_finite((float) $score) || $score < 0 || $score > 1) {
                return null;
            }

            $entry['score'] = $score;
        }

        return $entry;
    }

    /**
     * Whether the url points at this site, decided on the url's shape.
     *
     * Two forms pass. A root-relative path is one `/` followed by something that
     * is not a second `/` or a backslash, since a browser reads both `//host` and
     * `/\host` as protocol-relative and would leave the site. An absolute url
     * passes only on http or https and only on a host this site serves
     * documentation on, which is the form the index actually carries: page urls
     * are built through the named documentation routes, which generate absolute
     * urls, so a path-only rule would reject the whole feature.
     *
     * Whitespace and control characters are rejected up front, everywhere in the
     * string: they are what splits a `javascript:` scheme into something
     * `parse_url()` reads as a path while a browser still runs it.
     */
    protected function isSameSiteUrl(mixed $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        if (preg_match('/[\x00-\x20\x7F]/', $url) === 1) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//') && ! str_starts_with($url, '/\\');
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        foreach ($this->documentationHosts() as $host) {
            if (strcasecmp($parts['host'], $host) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * The hosts this installation serves documentation on.
     *
     * Both hosts stay in the list because a documentation route lives on one or
     * the other: the route-prefix layout serves the pages on the application's
     * own host, the subdomain layout on `<subdomain>.<base host>`.
     * `Lemme::getPageUrl()` builds the index through the named routes, so it
     * writes whichever host the configured layout actually serves, and this
     * list is what says that host is still this site.
     *
     * Listing the host the current layout does not use costs nothing: it is a
     * host this installation owns either way, and an index cached before a
     * layout change is then tolerated instead of being thrown out entry by entry
     * as foreign.
     *
     * @return array<int, string>
     */
    protected function documentationHosts(): array
    {
        $hosts = [Lemme::baseHost()];

        $subdomain = config('lemme.subdomain');

        if (is_string($subdomain) && $subdomain !== '') {
            $hosts[] = $subdomain.'.'.Lemme::baseHost();
        }

        return $hosts;
    }
}
