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

it('leaves the title heading a page opens with out of the indexed content', function () {
    // The title is already its own indexed field and is printed above every
    // result, so indexing the h1 that repeats it spent the whole excerpt window
    // saying the title twice before reaching the first sentence of the answer.
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->markdown('errors.md', 'PHP error tracking', <<<'MD'
# PHP error tracking

Error tracking is on by default.
MD);

    try {
        $entry = collect(Lemme::getSearchData())->firstWhere('slug', 'errors');

        expect($entry['content'])->toBe('Error tracking is on by default.');
    } finally {
        $docs->cleanup();
    }
});

it('leaves out the setext title heading a page opens with', function () {
    // Underlined with `=`, a title is an h1 with no `#` anywhere in the source,
    // which is why the cut is made on the rendered html and not on the markdown.
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->markdown('setext.md', 'Underlined title', <<<'MD'
Underlined title
================

The body starts here.
MD);

    try {
        $entry = collect(Lemme::getSearchData())->firstWhere('slug', 'setext');

        expect($entry['content'])->toBe('The body starts here.');
    } finally {
        $docs->cleanup();
    }
});

it('indexes a page that does not open with a heading unchanged', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->markdown('notes.md', 'Notes', <<<'MD'
Just a paragraph to begin with.

## A section

And its body.
MD);

    try {
        $entry = collect(Lemme::getSearchData())->firstWhere('slug', 'notes');

        expect($entry['content'])->toBe('Just a paragraph to begin with. A section And its body.');
    } finally {
        $docs->cleanup();
    }
});

it('keeps a top level heading that is not the one the page opens with', function () {
    // Only the opening heading repeats the title. A heading further down is body
    // content, and a reader searching for those words expects to find the page.
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->markdown('reference.md', 'Reference', <<<'MD'
The opening line.

# A later top level heading

And its body.
MD);

    try {
        $entry = collect(Lemme::getSearchData())->firstWhere('slug', 'reference');

        expect($entry['content'])->toBe('The opening line. A later top level heading And its body.');
    } finally {
        $docs->cleanup();
    }
});
