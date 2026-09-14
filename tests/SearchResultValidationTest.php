<?php

use Livewire\Livewire;
use Ranetrace\Lemme\Livewire\SearchComponent;
use Ranetrace\Lemme\Support\SearchResultValidator;

/**
 * One entry in the shape the browser posts back.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function searchResultEntry(array $overrides = []): array
{
    return array_merge([
        'title' => 'Installation Guide',
        'category' => 'Guides',
        'url' => '/docs/installation',
        'content' => 'How to install the system',
        'slug' => 'installation',
        'score' => 0.1,
    ], $overrides);
}

/**
 * What the component keeps after being handed the payload, through the listener
 * the browser reaches, which is the boundary being tested.
 *
 * @param  array<array-key, mixed>  $payload
 * @return array<int, array<string, mixed>>
 */
function handledSearchResults(array $payload): array
{
    return Livewire::test(SearchComponent::class)
        ->call('handleSearchResults', $payload)
        ->get('results');
}

it('keeps a payload in the shape the index sends', function () {
    $payload = [searchResultEntry(), searchResultEntry([
        'title' => 'Configuration Guide',
        'url' => '/docs/configuration',
        'slug' => 'configuration',
        'score' => 0.2,
    ])];

    expect(handledSearchResults($payload))->toEqual($payload);
});

it('keeps an absolute url on the application host, which is the form the index carries', function () {
    // Page urls are built with url(), so every genuine result is absolute. A
    // path-only rule would reject the whole feature on a real site.
    $url = url('docs/installation');

    expect($url)->toStartWith('http://')
        ->and(handledSearchResults([searchResultEntry(['url' => $url])]))
        ->toEqual([searchResultEntry(['url' => $url])]);
});

it('keeps an absolute url on the documentation subdomain', function () {
    config()->set('lemme.subdomain', 'docs');

    $url = 'http://docs.localhost/installation';

    expect(handledSearchResults([searchResultEntry(['url' => $url])]))
        ->toEqual([searchResultEntry(['url' => $url])]);
});

it('rejects a javascript url', function () {
    // Blade escapes an href's text and leaves its scheme alone, so this is the
    // one the escaping in the view cannot answer.
    expect(handledSearchResults([searchResultEntry(['url' => 'javascript:alert(1)'])]))->toBe([])
        ->and(handledSearchResults([searchResultEntry(['url' => 'JaVaScRiPt:alert(1)'])]))->toBe([]);
});

it('rejects a url split by whitespace or a control character', function () {
    // A browser strips these before reading the scheme, so a url parser that
    // keeps them sees something other than what the browser will run.
    expect(handledSearchResults([searchResultEntry(['url' => "java\tscript:alert(1)"])]))->toBe([])
        ->and(handledSearchResults([searchResultEntry(['url' => "\njavascript:alert(1)"])]))->toBe([])
        ->and(handledSearchResults([searchResultEntry(['url' => "/docs/in\x00stallation"])]))->toBe([]);
});

it('rejects an absolute url on another host', function () {
    expect(handledSearchResults([searchResultEntry(['url' => 'https://evil.example/docs'])]))->toBe([])
        ->and(handledSearchResults([searchResultEntry(['url' => 'https://localhost.evil.example/docs'])]))->toBe([]);
});

it('rejects a protocol-relative url', function () {
    // Both of these leave the site: a browser reads /\ the way it reads //.
    expect(handledSearchResults([searchResultEntry(['url' => '//evil.example/docs'])]))->toBe([])
        ->and(handledSearchResults([searchResultEntry(['url' => '/\\evil.example/docs'])]))->toBe([]);
});

it('rejects an entry missing a key the view reads', function () {
    foreach (['title', 'category', 'url', 'content', 'slug'] as $key) {
        $entry = searchResultEntry();
        unset($entry[$key]);

        expect(handledSearchResults([$entry]))->toBe([]);
    }
});

it('rejects an entry whose text is not text', function () {
    expect(handledSearchResults([searchResultEntry(['title' => ['array']])]))->toBe([])
        ->and(handledSearchResults([searchResultEntry(['content' => 42])]))->toBe([]);
});

it('rejects a payload carrying an entry that is not an array', function () {
    expect(handledSearchResults(['a string']))->toBe([])
        ->and(handledSearchResults([searchResultEntry(), 'a string']))->toBe([]);
});

it('rejects a payload longer than the browser is asked for', function () {
    $payload = array_map(
        fn (int $index): array => searchResultEntry(['slug' => "page-{$index}", 'url' => "/docs/page-{$index}"]),
        range(1, SearchResultValidator::MAX_RESULTS + 1),
    );

    expect(handledSearchResults($payload))->toBe([])
        ->and(handledSearchResults(array_slice($payload, 0, SearchResultValidator::MAX_RESULTS)))
        ->toHaveCount(SearchResultValidator::MAX_RESULTS);
});

it('asks the browser for exactly the number of results it will take back', function () {
    // The cap is only safe while it matches the request. If the view asked for
    // more, every search would be rejected wholesale.
    Livewire::test(SearchComponent::class)
        ->assertSeeHtml('search($event.detail.query, '.SearchResultValidator::MAX_RESULTS.')');
});

it('drops every key the view does not read', function () {
    // Fuse sends its match ranges along on every search, and the view reads
    // those from the search instance in the browser rather than from here.
    $results = handledSearchResults([searchResultEntry([
        'matches' => [['key' => 'title', 'indices' => [[0, 3]]]],
        'onclick' => 'alert(1)',
    ])]);

    expect($results)->toHaveCount(1)
        ->and(array_keys($results[0]))
        ->toEqualCanonicalizing(['title', 'category', 'url', 'content', 'slug', 'score']);
});

it('takes an entry with no score at all', function () {
    $entry = searchResultEntry();
    unset($entry['score']);

    expect(handledSearchResults([$entry]))->toEqual([$entry]);
});

it('rejects a score outside the range the view does arithmetic on', function () {
    expect(handledSearchResults([searchResultEntry(['score' => 1.5])]))->toBe([])
        ->and(handledSearchResults([searchResultEntry(['score' => -0.1])]))->toBe([])
        ->and(handledSearchResults([searchResultEntry(['score' => '0.1'])]))->toBe([]);
});

it('never renders an href the payload made up', function () {
    // The end of the line this all exists for: whatever arrives, the list holds
    // links to this site or it holds nothing.
    $html = Livewire::test(SearchComponent::class)
        ->set('search', 'install')
        ->call('handleSearchResults', [searchResultEntry(['url' => 'javascript:alert(1)'])])
        ->html();

    expect($html)->not->toContain('javascript:alert(1)')
        ->and($html)->toContain('No results found');
});
