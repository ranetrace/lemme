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

        $html = $this->makeRenderer()->toHtml($page['raw_content']);

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
