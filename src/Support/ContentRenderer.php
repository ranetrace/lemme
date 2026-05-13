<?php

namespace Ranetrace\Lemme\Support;

use Illuminate\Support\Facades\Cache;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
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

        $html = app(MarkdownRenderer::class)
            ->renderAnchors(false)
            ->addExtension(new HeadingPermalinkExtension)
            ->commonmarkOptions([
                'heading_permalink' => [
                    'insert' => 'none',
                    'apply_id_to_heading' => true,
                    'id_prefix' => '',
                    'fragment_prefix' => '',
                ],
            ])
            ->highlightTheme(['light' => 'github-light', 'dark' => 'github-dark'])
            ->toHtml($page['raw_content']);

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
}
