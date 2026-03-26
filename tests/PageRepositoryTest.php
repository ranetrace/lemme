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
