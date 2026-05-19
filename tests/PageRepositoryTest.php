<?php

use Illuminate\Support\Facades\Cache;
use Ranetrace\Lemme\Support\PageRepository;
use Ranetrace\Lemme\Support\SearchIndexBuilder;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());
    config()->set('lemme.cache.enabled', false);
});

afterEach(function () {
    $this->docs?->cleanup();
});

it('parses markdown into PageData with headings', function () {
    $this->docs->file('guide.md', <<<'MD'
---
# frontmatter intentionally blank to test fallback
---

# Main Title

## Sub Section

Content here.
MD);

    $repo = new PageRepository(new SearchIndexBuilder);
    $pages = $repo->all();
    expect($pages)->toHaveCount(1);
    $page = $pages->first();
    expect($page['slug'])->toBe('guide')
        ->and($page['title'])->toBe('Guide')
        ->and($page['headings'])->toBeArray()
        ->and($page['headings'])->toHaveCount(2)
        ->and($page['headings'][0]['id'])->toBe('main-title')
        ->and($page['headings'][1]['id'])->toBe('sub-section');
});

it('detects duplicate slugs and throws', function () {
    $this->docs
        ->file('1_intro.md', "---\ntitle: Intro\n---\n# Intro\n")
        ->file('2-intro.md', "---\ntitle: Another Intro\n---\n# Another Intro\n");

    $repo = new PageRepository(new SearchIndexBuilder);
    expect(fn () => $repo->all())->toThrow(RuntimeException::class);
});

it('does not collect headings from hash comments inside a fenced code block', function () {
    $this->docs->file('usage.md', <<<'MD'
---
title: Usage Guide
---

## Usage

```bash
# Install the dependencies
npm install

# Build the project
npm run build

# Start the development server
npm run dev
```
MD);

    $repo = new PageRepository(new SearchIndexBuilder);
    $headings = $repo->all()->first()['headings'];
    $texts = collect($headings)->pluck('text');

    expect($texts->all())->toBe(['Usage'])
        ->and($headings[0]['id'])->toBe('usage')
        ->and($texts->implode('|'))->not->toContain('Install the dependencies')
        ->and($texts->implode('|'))->not->toContain('Build the project')
        ->and($texts->implode('|'))->not->toContain('Start the development server');
});

it('treats hashes inside every code-block variant as code, never headings', function () {
    $this->docs->file('fences.md', <<<'MD'
---
title: Fences
---

## Real One

~~~
# tilde fence comment, not a heading
~~~

````
```
# inner triple fence does not close a quad fence, still code
````

    # four-space indented code block, not a heading

A paragraph with `# inline code hash` that is not a heading.

### Real Two
MD);

    $repo = new PageRepository(new SearchIndexBuilder);
    $headings = $repo->all()->first()['headings'];
    $texts = collect($headings)->pluck('text');

    expect($texts->all())->toBe(['Real One', 'Real Two'])
        ->and(collect($headings)->pluck('level')->all())->toBe([2, 3])
        ->and($texts->implode('|'))->not->toContain('tilde')
        ->and($texts->implode('|'))->not->toContain('inner triple')
        ->and($texts->implode('|'))->not->toContain('four-space')
        ->and($texts->implode('|'))->not->toContain('inline code hash');
});

it('caches pages when enabled and reuses cache', function () {
    config()->set('lemme.cache.enabled', true);
    Cache::flush();
    $this->docs->file('cache.md', "---\ntitle: Cache Test\n---\n# Cache Test\n");
    $repo = new PageRepository(new SearchIndexBuilder);
    $first = $repo->all();
    // Mutate file to ensure cached version returned even if file changes without clearCache
    sleep(1);
    $this->docs->file('cache.md', "---\ntitle: Cache Test\n---\n# Changed\n");
    $second = $repo->all();
    expect($second->first()['modified_at'])->toBe($first->first()['modified_at']);
});
