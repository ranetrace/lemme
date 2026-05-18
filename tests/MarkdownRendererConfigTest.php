<?php

use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use Ranetrace\Lemme\Support\ContentRenderer;
use Ranetrace\Lemme\Support\PageRepository;
use Ranetrace\Lemme\Support\SearchIndexBuilder;
use Ranetrace\Lemme\Tests\Support\DocsFactory;
use Spatie\LaravelMarkdown\MarkdownRenderer;

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());
    config()->set('lemme.cache.enabled', false);
});

afterEach(function () {
    $this->docs?->cleanup();
});

function renderMarkdown(DocsFactory $docs, string $markdown): string
{
    $docs->file('page.md', "---\ntitle: Page\n---\n".$markdown);
    $repo = new PageRepository(new SearchIndexBuilder);
    $page = $repo->all()->firstWhere('slug', 'page');

    return (new ContentRenderer)->render($page);
}

it('renders markdown tables out of the box via the GFM extension', function () {
    $html = renderMarkdown($this->docs, "| h1 | h2 |\n|---|---|\n| a | b |\n");

    expect($html)
        ->toContain('<table>')
        ->toContain('<td>a</td>')
        ->toContain('<td>b</td>');
});

it('honors the configured extension list when rendering', function () {
    config()->set('lemme.markdown.extensions', []);

    $html = renderMarkdown($this->docs, "| h1 | h2 |\n|---|---|\n| a | b |\n");

    expect($html)->not->toContain('<table>');
});

it('still applies stable heading ids via the HeadingPermalink extension', function () {
    $html = renderMarkdown($this->docs, "## My Heading\n");

    expect($html)->toContain('id="my-heading"');
});

it('keeps GFM and HeadingPermalink enabled by default', function () {
    expect(config('lemme.markdown.extensions'))
        ->toContain(GithubFlavoredMarkdownExtension::class)
        ->toContain(HeadingPermalinkExtension::class);
});

it('does not mutate the host app MarkdownRenderer when rendering', function () {
    renderMarkdown($this->docs, "## leakage check\n\n| a | b |\n|---|---|\n| 1 | 2 |\n");

    $shared = app(MarkdownRenderer::class);

    $reflection = new ReflectionClass(MarkdownRenderer::class);
    $extensionsProp = $reflection->getProperty('extensions');
    $extensionsProp->setAccessible(true);
    $commonmarkOptionsProp = $reflection->getProperty('commonmarkOptions');
    $commonmarkOptionsProp->setAccessible(true);

    expect($extensionsProp->getValue($shared))->toBe([])
        ->and($commonmarkOptionsProp->getValue($shared))->toBe([]);
});
