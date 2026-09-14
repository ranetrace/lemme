<?php

use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Support\ContentRenderer;
use Ranetrace\Lemme\Support\PageRepository;
use Ranetrace\Lemme\Support\SearchIndexBuilder;
use Ranetrace\Lemme\Tests\Support\DocsFactory;
use Ranetrace\Lemme\Tests\Support\StubMarkdownRenderer;
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

/**
 * The search index entry for one Markdown body.
 *
 * @return array<string, mixed>
 */
function indexEntryFor(DocsFactory $docs, string $markdown): array
{
    $docs->file('indexed.md', "---\ntitle: Indexed\n---\n".$markdown);

    return collect(Lemme::getSearchData())->firstWhere('slug', 'indexed');
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

it('builds the search index through the renderer class configured for the host application', function () {
    // A page and the index pointing at it have to be the same text. The index
    // used to be built by whatever spatie's container binding is configured as,
    // which is the host application's config/markdown.php, no matter what the
    // page itself was rendered through.
    config()->set('lemme.markdown.renderer', StubMarkdownRenderer::class);

    $entry = indexEntryFor($this->docs, "Body copy.\n");

    expect($entry['content'])->toContain(StubMarkdownRenderer::TEXT_MARKER);
});

it('leaves the host application container binding out of the index', function () {
    // The other half of the same question: a renderer bound by the host
    // application is not the one Lemme indexes with.
    app()->bind(MarkdownRenderer::class, fn () => new StubMarkdownRenderer);

    $entry = indexEntryFor($this->docs, "Body copy.\n");

    expect($entry['content'])->toContain('Body copy.')
        ->and($entry['content'])->not->toContain(StubMarkdownRenderer::TEXT_MARKER);
});

it('indexes with the extensions the page is rendered with', function () {
    // A table is markup to Lemme's renderer and a row of literal pipes to a
    // renderer without the GFM extension, so the index either reads the way the
    // page does or it does not.
    $entry = indexEntryFor($this->docs, "| head | other |\n|---|---|\n| cell | value |\n");

    expect($entry['content'])->toContain('cell')
        ->and($entry['content'])->toContain('value')
        ->and($entry['content'])->not->toContain('|');
});

it('rejects a configured renderer that is not a MarkdownRenderer when building the index', function () {
    // The rejection belongs to the factory now, so it answers for the index too.
    config()->set('lemme.markdown.renderer', stdClass::class);

    indexEntryFor($this->docs, "Body copy.\n");
})->throws(InvalidArgumentException::class, stdClass::class);
