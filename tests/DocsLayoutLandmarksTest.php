<?php

use Ranetrace\Lemme\Tests\Support\DocsFactory;

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    config()->set('lemme.route_prefix', 'docs');
    config()->set('lemme.subdomain', null);

    // Two pages and a heading, so the sidebar, the mobile panel and the
    // "on this page" list all have something to render.
    $this->docs->file('page.md', "---\ntitle: My Page\n---\n# My Page\n\n## A Section\n");
    $this->docs->file('other.md', "---\ntitle: Other Page\n---\n# Other Page\n");
});

afterEach(function () {
    $this->docs?->cleanup();
});

/**
 * The rendered page as a queryable document.
 *
 * The layout carries Alpine attributes such as `@keydown.cmd.k` that the HTML
 * parser rejects by name, so parse errors are collected and dropped: the
 * elements themselves survive, which is all these tests read.
 */
function docsDocument(string $html): DOMXPath
{
    $document = new DOMDocument;

    libxml_use_internal_errors(true);
    $document->loadHTML($html, LIBXML_NOERROR);
    libxml_clear_errors();
    libxml_use_internal_errors(false);

    return new DOMXPath($document);
}

it('wraps the page content in a single main landmark', function () {
    $html = $this->get('/docs/page')->assertOk()->getContent();

    $mains = docsDocument($html)->query('//main[@id="main-content"]');

    expect($mains)->toHaveCount(1)
        ->and($mains->item(0)->textContent)->toContain('My Page');
});

it('opens the body with a skip link to the main landmark', function () {
    // A keyboard visitor lands on the skip link first or tabs through the whole
    // header before reaching a word of documentation, so its position in the
    // markup is the feature.
    $html = $this->get('/docs/page')->assertOk()->getContent();

    $body = Str::after($html, '<body');
    preg_match('/<(a|button|input|select|textarea)\b[^>]*>/i', $body, $firstFocusable);

    expect($firstFocusable[0] ?? '')->toContain('href="#main-content"');
});

it('labels every navigation landmark', function () {
    // Three navigation landmarks share the page. Unlabelled they are announced
    // as three identical "navigation" regions with no way to tell them apart.
    $html = $this->get('/docs/page')->assertOk()->getContent();

    $xpath = docsDocument($html);
    $labels = [];

    foreach ($xpath->query('//nav') as $nav) {
        $labels[] = $nav->getAttribute('aria-label');
    }

    expect($labels)->toHaveCount(3)
        ->and($labels)->not->toContain('')
        ->and($labels)->toContain('On this page')
        ->and(array_count_values($labels)['Documentation'] ?? 0)->toBe(2);
});
