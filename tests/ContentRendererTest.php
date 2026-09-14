<?php

use Illuminate\Support\Facades\Cache;
use Ranetrace\Lemme\Support\ContentRenderer;
use Ranetrace\Lemme\Support\PageRepository;
use Ranetrace\Lemme\Support\SearchIndexBuilder;
use Ranetrace\Lemme\Tests\Support\DocsFactory;
use Ranetrace\Lemme\Tests\Support\StubMarkdownRenderer;
use Spatie\ShikiPhp\Shiki;

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());
    config()->set('lemme.cache.enabled', false);
});

afterEach(function () {
    $this->docs?->cleanup();

    // The working dir is a static on the vendor class, so a test that took
    // Shiki away has to hand it back.
    Shiki::setCustomWorkingDirPath(null);
});

/**
 * Renders one Markdown body as a documentation page and hands back its HTML.
 */
function renderDocsBody(DocsFactory $docs, string $body): string
{
    $docs->file('block.md', "---\ntitle: Block\n---\n".$body);
    $page = (new PageRepository(new SearchIndexBuilder))->all()->firstWhere('slug', 'block');

    return (new ContentRenderer)->render($page);
}

/**
 * Takes Shiki away for the rest of the test, which is what a web server that
 * cannot reach a Node binary looks like: every highlight throws, the vendor
 * highlighter swallows the exception and hands back the inside of the `pre`
 * element it was given. Pointing Shiki at a directory holding no `shiki.js`
 * reproduces that without touching the PATH.
 */
function makeShikiUnavailable(): void
{
    Shiki::setCustomWorkingDirPath(sys_get_temp_dir());
}

/**
 * The page HTML with every code block removed, so a block that lost its `pre`
 * shows up as a stray `<code` in what is left.
 */
function htmlOutsideCodeBlocks(string $html): string
{
    return preg_replace('/<pre\b[^>]*>.*?<\/pre>/s', '', $html) ?? $html;
}

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
    // ```env is not a language Shiki knows, so the highlight throws and the
    // vendor renderer answers with the inside of the pre element: a bare
    // <code>, which browsers render inline, collapsing the snippet onto one
    // line and picking up the prose stylesheet's backtick decorations.
    $this->docs->file('unknown-lang.md', "---\ntitle: Unknown\n---\n# Env\n\n```env\nAPP_ENV=production\n```\n");
    $repo = new PageRepository(new SearchIndexBuilder);
    $page = $repo->all()->first();
    $renderer = new ContentRenderer;
    $html = $renderer->render($page);

    expect($html)->toContain('<pre><code class="language-env">')
        ->and($html)->toContain('APP_ENV=production');
});

it('renders a fence with no language as a block when Shiki is unavailable', function () {
    // A fence with no info string gets a <code> with no class at all, which is
    // why the language class is no help here: keeping the pre is the renderer's
    // job, not a pattern match on the output.
    makeShikiUnavailable();

    $html = renderDocsBody($this->docs, "```\nAPP_ENV=production\nAPP_DEBUG=false\n```\n");

    expect($html)->toContain('<pre><code>')
        ->and($html)->toContain('APP_DEBUG=false')
        ->and(htmlOutsideCodeBlocks($html))->not->toContain('<code');
});

it('renders an indented code block as a block when Shiki is unavailable', function () {
    // Indented code takes the same failure path as a fence, through the same
    // renderer.
    makeShikiUnavailable();

    $html = renderDocsBody($this->docs, "Run it:\n\n    php artisan lemme:reindex\n");

    expect($html)->toContain('<pre><code>')
        ->and($html)->toContain('php artisan lemme:reindex')
        ->and(htmlOutsideCodeBlocks($html))->not->toContain('<code');
});

it('renders a known language inside a pre', function () {
    // Holds whichever way the environment answers: with Shiki installed and
    // reachable this is its own <pre class="shiki">, and without it the plain
    // <pre><code class="language-php"> fallback. Either way no code block is
    // left outside a pre.
    $html = renderDocsBody($this->docs, "```php\n\$total = 1 + 1;\n```\n");

    expect($html)->toContain('<pre')
        ->and($html)->toContain('total')
        ->and(htmlOutsideCodeBlocks($html))->not->toContain('<code');
});

it('leaves inline code inline', function () {
    // The counterpart of the rule above: only block nodes belong in a pre, and
    // a span of inline code must keep reading as part of its sentence.
    makeShikiUnavailable();

    $html = renderDocsBody($this->docs, "Set `APP_DEBUG` to false.\n");

    expect($html)->toContain('<code>APP_DEBUG</code>')
        ->and($html)->not->toContain('<pre');
});

it('renders through the renderer class configured for the host application', function () {
    config()->set('lemme.markdown.renderer', StubMarkdownRenderer::class);

    $html = renderDocsBody($this->docs, "# Swapped\n");

    expect($html)->toContain(StubMarkdownRenderer::MARKER)
        ->and($html)->toContain('<h1 id="swapped">');
});

it('falls back to the packaged renderer when the config predates the key', function () {
    // An application that published the config before this key existed keeps
    // its own copy of the file, so the key simply is not there on upgrade.
    $markdown = config('lemme.markdown');
    unset($markdown['renderer']);
    config()->set('lemme.markdown', $markdown);

    makeShikiUnavailable();

    $html = renderDocsBody($this->docs, "```env\nAPP_ENV=production\n```\n");

    expect($html)->toContain('<pre><code class="language-env">');
});

it('rejects a configured renderer that is not a MarkdownRenderer', function () {
    // Failing here says which class is wrong; letting it through fails later on
    // an undefined method, inside a render of someone's documentation page.
    config()->set('lemme.markdown.renderer', stdClass::class);

    renderDocsBody($this->docs, "# Nope\n");
})->throws(InvalidArgumentException::class, stdClass::class);

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
