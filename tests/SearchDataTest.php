<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

it('builds searchable content from markdown', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->file('search.md', <<<'MD'
```php
echo "Hi";
```

# Heading

**Bold** _Italic_ `Code` [Link](https://example.com)
MD);

    $data = Lemme::getSearchData();
    $entry = collect($data)->firstWhere('slug', 'search');
    expect($entry)->not->toBeNull()
        ->and($entry['content'])->toContain('Heading')
        ->and($entry['content'])->toContain('Bold')
        ->and($entry['content'])->toContain('Code')
        ->and($entry['content'])->not->toContain('[');
});

it('keeps rendered markup out of the searchable content', function () {
    // The content field is what a search result excerpt is cut from, so anything
    // that is markup rather than prose reaches the page as text: tag fragments and
    // class names showed up mid-sentence in a result.
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->file('errors.md', <<<'MD'
# Error tracking

Set <code class="language-env">RANETRACE_ERRORS_ENABLED</code> to true.

```env
RANETRACE_ERRORS_ENABLED=true
```
MD);

    try {
        $entry = collect(Lemme::getSearchData())->firstWhere('slug', 'errors');

        expect($entry)->not->toBeNull()
            ->and($entry['content'])->toContain('RANETRACE_ERRORS_ENABLED')
            ->and($entry['content'])->not->toContain('<')
            ->and($entry['content'])->not->toContain('>')
            ->and($entry['content'])->not->toContain('class=');
    } finally {
        $docs->cleanup();
    }
});

it('decodes the entities the renderer escaped, so the index holds what the page shows', function () {
    // Left as entity text, a query for the characters themselves matches nothing,
    // and an excerpt that escapes its text on the way to the page would render the
    // entity itself.
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->markdown('entities.md', 'Entities', 'Compare `a < b` with `a > b` in Tom & Jerry.');

    try {
        $entry = collect(Lemme::getSearchData())->firstWhere('slug', 'entities');

        expect($entry['content'])->toContain('a < b')
            ->and($entry['content'])->toContain('Tom & Jerry')
            ->and($entry['content'])->not->toContain('&lt;')
            ->and($entry['content'])->not->toContain('&amp;');
    } finally {
        $docs->cleanup();
    }
});

it('truncates indexed content on character boundaries', function () {
    // The index is json_encoded on its way to the browser, and half a multi-byte
    // character is not valid UTF-8, so a byte-counted cut loses the whole payload.
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    config()->set('lemme.search.max_content_length', 10);

    $docs->markdown('unicode.md', 'Unicode', 'Ünïcödé döcumentation about çafés.');

    try {
        $entry = collect(Lemme::getSearchData())->firstWhere('slug', 'unicode');

        expect(mb_check_encoding($entry['content'], 'UTF-8'))->toBeTrue()
            ->and($entry['content'])->toBe('Ünïcödé dö...');
    } finally {
        $docs->cleanup();
    }
});

it('indexes the pages as a list when the cache is off', function () {
    // The pages arrive keyed by slug. Kept that way, the index serialises to a JSON
    // object, and the browser hands that object to Fuse, which can only iterate a
    // list: search did nothing at all on a site with caching off.
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->markdown('first.md', 'First Page', 'The first body.');
    $docs->markdown('second.md', 'Second Page', 'The second body.');

    try {
        $data = Lemme::getSearchData();

        expect(array_is_list($data))->toBeTrue()
            ->and($data)->toHaveCount(2);
    } finally {
        $docs->cleanup();
    }
});
