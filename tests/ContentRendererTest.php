<?php

use Illuminate\Support\Facades\Cache;
use Ranetrace\Lemme\Support\ContentRenderer;
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

it('renders markdown to html with heading ids', function () {
    $this->docs->file('renderer.md', "---\ntitle: Renderer\n---\n# Title Here\n\n## Sub Title\n");
    $repo = new PageRepository(new SearchIndexBuilder);
    $page = $repo->all()->first();
    $renderer = new ContentRenderer;
    $html = $renderer->render($page);
    expect($html)->toContain('<h1 id="title-here">')
        ->and($html)->toContain('<h2 id="sub-title">');
});

it('adds numeric suffix for duplicate heading text', function () {
    $this->docs->file('dup.md', "---\ntitle: Dups\n---\n# Repeat\n\n# Repeat\n");
    $repo = new PageRepository(new SearchIndexBuilder);
    $page = $repo->all()->first();
    $renderer = new ContentRenderer;
    $html = $renderer->render($page);
    expect($html)->toContain('id="repeat"')
        ->and($html)->toContain('id="repeat-1"');
});

it('caches html and rotates keys when page modified', function () {
    config()->set('lemme.cache.enabled', true);
    Cache::flush();
    $this->docs->file('rotate.md', "---\ntitle: Rotate\n---\n# First\n");
    $repo = new PageRepository(new SearchIndexBuilder);
    $page = $repo->all()->first();
    $renderer = resolve(ContentRenderer::class); // ensures container binding works
    $firstHtml = $renderer->render($page);
    expect($firstHtml)->toContain('First');
    $pointerKey = 'lemme.html.current.rotate';
    $initialPointer = Cache::get($pointerKey);
    expect($initialPointer)->not->toBeNull();

    // Modify file, clear repo cache, re-render
    sleep(1);
    $this->docs->file('rotate.md', "---\ntitle: Rotate\n---\n# Second\n");
    $repo->clearCache();
    $page = $repo->all()->first();
    $secondHtml = $renderer->render($page);
    expect($secondHtml)->toContain('Second');
    $newPointer = Cache::get($pointerKey);
    expect($newPointer)->not->toBe($initialPointer);
    expect(Cache::has($initialPointer))->toBeFalse();
});

it('renders a fence Shiki cannot highlight as a block, not inline code', function () {
    // ```env is not a language Shiki knows, so its highlighter hands back a bare
    // <code> with no <pre>. Without the wrap it renders inline (with backtick
    // decorations from the prose stylesheet) instead of as a code block.
    $this->docs->file('unknown-lang.md', "---\ntitle: Unknown\n---\n# Env\n\n```env\nAPP_ENV=production\n```\n");
    $repo = new PageRepository(new SearchIndexBuilder);
    $page = $repo->all()->first();
    $renderer = new ContentRenderer;
    $html = $renderer->render($page);

    expect($html)->toContain('<pre><code class="language-env">')
        ->and($html)->toContain('APP_ENV=production');
});

it('preserves utf8 characters like emoji and box drawing symbols', function () {
    $emoji = '✨';
    $box = '├──'; // common box drawing sequence
    $this->docs->file('utf8.md', "---\ntitle: UTF8\n---\n# Heading {$emoji}\n\nCode:\n\n````\n{$box} path\n````\n");
    $repo = new PageRepository(new SearchIndexBuilder);
    $page = $repo->all()->first();
    $renderer = new ContentRenderer;
    $html = $renderer->render($page);
    expect($html)->toContain($emoji)
        ->and($html)->toContain($box);
});
