<?php

use Ranetrace\Lemme\Tests\Support\DocsFactory;

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    config()->set('lemme.route_prefix', 'docs');
    config()->set('lemme.subdomain', null);
});

afterEach(function () {
    $this->docs?->cleanup();
});

it('lets the content column shrink below its widest code block', function () {
    // The page is a grid and the content is one of its items. A grid item is
    // never narrower than its content unless told so, and a code block's
    // content is its longest line: without `min-w-0` that line sets the width
    // of the whole page on a phone, and the block's own scrollbar never shows.
    $this->docs->file('page.md', "---\ntitle: Page\n---\n# Page\n\n```\n".str_repeat('long ', 80)."\n```\n");

    $html = $this->get('/docs/page')->assertOk()->getContent();

    preg_match('/<main\b[^>]*\bclass="([^"]*)"/', $html, $main);

    expect(explode(' ', $main[1] ?? ''))->toContain('min-w-0');
});

it('breaks a word that is longer than the line', function () {
    // Inline code holds environment variables and urls with nowhere to break,
    // and one longer than a phone screen widens the page like a code block did.
    $this->docs->file('page.md', "---\ntitle: Page\n---\n# Page\n\nSet `".str_repeat('LONG_', 20)."=true`.\n");

    $html = $this->get('/docs/page')->assertOk()->getContent();

    preg_match('/<div\b[^>]*\bclass="([^"]*\bprose\b[^"]*)"/', $html, $prose);

    expect(explode(' ', $prose[1] ?? ''))->toContain('wrap-break-word');
});

it('renders a table inside a scroll container of its own', function () {
    // A table cannot scroll itself, and one wider than the page widens the page.
    $this->docs->file('page.md', "---\ntitle: Page\n---\n# Page\n\n| Key | Value |\n|---|---|\n| a | b |\n");

    $html = $this->get('/docs/page')->assertOk()->getContent();

    expect($html)->toMatch('/<div[^>]*\bclass="[^"]*\boverflow-x-auto\b[^"]*"[^>]*>\s*<table>/');
});

it('lets a keyboard reach the table scroll container', function () {
    // A region that scrolls sideways and cannot take focus leaves its far
    // columns out of reach without a pointer.
    $this->docs->file('page.md', "---\ntitle: Page\n---\n# Page\n\n| Key | Value |\n|---|---|\n| a | b |\n");

    $html = $this->get('/docs/page')->assertOk()->getContent();

    preg_match('/<div[^>]*>\s*<table>/', $html, $wrapper);

    expect($wrapper[0] ?? '')
        ->toContain('tabindex="0"')
        ->toContain('role="region"')
        ->toContain('aria-label="Table"');
});

it('keeps the table wrapper an application configures over its own', function () {
    config()->set('lemme.markdown.commonmark_options.table.wrap', [
        'enabled' => true,
        'tag' => 'figure',
        'attributes' => ['class' => 'app-table'],
    ]);

    $this->docs->file('page.md', "---\ntitle: Page\n---\n# Page\n\n| Key | Value |\n|---|---|\n| a | b |\n");

    $html = $this->get('/docs/page')->assertOk()->getContent();

    expect($html)
        ->toContain('<figure class="app-table"><table>')
        ->not->toContain('overflow-x-auto [&amp;&gt;table]');
});

it('renders a page without tables when no table extension is configured', function () {
    // The wrapper is an option of the table extension, and it is set whether
    // or not that extension is registered.
    config()->set('lemme.markdown.extensions', []);

    $this->docs->file('page.md', "---\ntitle: Page\n---\n# Page\n\n| Key | Value |\n|---|---|\n| a | b |\n");

    $this->get('/docs/page')->assertOk()->assertDontSee('<table>', false);
});
