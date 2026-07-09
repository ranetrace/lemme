<?php

namespace Ranetrace\Lemme\Support;

use Illuminate\Support\Facades\Cache;
use Ranetrace\Lemme\Data\PageData;
use Spatie\LaravelMarkdown\MarkdownRenderer;

class ContentRenderer
{
    public function render(PageData $page): string
    {
        $slug = $page['slug'];
        $cacheKey = "lemme.html.{$slug}.{$page['modified_at']}";
        $pointerKey = "lemme.html.current.{$slug}";

        if (config('lemme.cache.enabled') && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $html = $this->wrapUnhighlightedCodeBlocks(
            $this->makeRenderer()->toHtml($page['raw_content'])
        );

        if (config('lemme.cache.enabled')) {
            $previousKey = Cache::get($pointerKey);
            if ($previousKey && $previousKey !== $cacheKey) {
                Cache::forget($previousKey);
            }
            Cache::put($cacheKey, $html, config('lemme.cache.ttl', 3600));
            Cache::put($pointerKey, $cacheKey, config('lemme.cache.ttl', 3600));
        }

        return $html;
    }

    /**
     * Guarantee every fenced code block renders as a block, even when Shiki
     * cannot highlight its language.
     *
     * When Shiki rejects a fence's language (for example ```env, which it does
     * not know), the Shiki highlighter swallows the error and returns the base
     * renderer's *inner* content: a bare `<code class="language-x">` with no
     * surrounding `<pre>`. Browsers treat that as inline code, and a typographic
     * stylesheet even decorates it with literal backticks. Re-wrap those orphaned
     * elements so an unhighlighted fence still reads as a code block rather than
     * inline text. Successfully highlighted blocks are emitted as
     * `<pre class="shiki">…` with an unclassed inner `<code>`, so they never match.
     */
    protected function wrapUnhighlightedCodeBlocks(string $html): string
    {
        return preg_replace(
            '/<code class="language-[^"]*">.*?<\/code>/s',
            '<pre>$0</pre>',
            $html,
        ) ?? $html;
    }

    public function clearCacheForPages(iterable $pages): void
    {
        foreach ($pages as $page) {
            $cacheKey = "lemme.html.{$page['slug']}.{$page['modified_at']}";
            $pointerKey = "lemme.html.current.{$page['slug']}";
            Cache::forget($cacheKey);
            Cache::forget($pointerKey);
        }
    }

    /**
     * Build a MarkdownRenderer scoped to Lemme.
     *
     * Owning the instance (instead of resolving spatie's container binding)
     * guarantees Lemme's extensions and options never bleed into the host
     * app's MarkdownRenderer.
     */
    protected function makeRenderer(): MarkdownRenderer
    {
        $extensions = array_map(
            fn (string $class): object => new $class,
            (array) config('lemme.markdown.extensions', []),
        );

        $renderer = new MarkdownRenderer(
            commonmarkOptions: (array) config('lemme.markdown.commonmark_options', []),
            highlightTheme: config('lemme.markdown.highlight_theme', 'github-light'),
            cacheStoreName: false,
            renderAnchors: false,
        );

        foreach ($extensions as $extension) {
            $renderer->addExtension($extension);
        }

        return $renderer;
    }
}
