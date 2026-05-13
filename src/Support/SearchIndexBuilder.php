<?php

namespace Ranetrace\Lemme\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Ranetrace\Lemme\Data\PageData;
use Spatie\LaravelMarkdown\MarkdownRenderer;

/**
 * Builds and caches the lightweight search index array.
 */
class SearchIndexBuilder
{
    /**
     * Build and cache search data.
     *
     * @param  Collection<string, PageData>  $pages  Pages keyed by slug (values order used for scoring order).
     */
    public function buildAndCache(Collection $pages, callable $urlResolver): void
    {
        $searchData = $this->buildSearchDataFromPages($pages, $urlResolver);
        Cache::put('lemme.search_data', $searchData, config('lemme.cache.ttl', 3600));
    }

    /**
     * Get search data, regenerating if needed.
     *
     * @param  Collection<string, PageData>  $pages  Pages keyed by slug.
     * @return array<int, array<string, mixed>>
     */
    public function getSearchData(Collection $pages, callable $urlResolver): array
    {
        if (config('lemme.cache.enabled') && Cache::has('lemme.search_data')) {
            return Cache::get('lemme.search_data');
        }

        return $this->buildSearchDataFromPages($pages, $urlResolver);
    }

    /**
     * @param  Collection<string, PageData>  $pages  Pages keyed by slug.
     * @return array<int, array<string, mixed>>
     */
    protected function buildSearchDataFromPages(Collection $pages, callable $urlResolver): array
    {
        return $pages->map(function ($page) use ($urlResolver) {
            return [
                'title' => $page['title'],
                'category' => $this->getCategoryFromPath($page['relative_path']),
                'url' => $urlResolver($page['slug']),
                'content' => $this->getSearchableContent($page['raw_content']),
                'slug' => $page['slug'],
            ];
        })->toArray();
    }

    protected function getCategoryFromPath(string $path): string
    {
        $pathParts = explode('/', $path);
        if (count($pathParts) > 1) {
            $directory = $pathParts[0];
            $cleaned = $this->removeNumberPrefix($directory);
            $formatted = str_replace(['-', '_'], ' ', $cleaned);

            return ucwords(strtolower($formatted));
        }

        return 'General';
    }

    protected function getSearchableContent(string $content): string
    {
        $maxLength = (int) config('lemme.search.max_content_length', 0);
        // Skip Shiki syntax highlighting — search only needs plain text and Shiki is the
        // expensive part of the render pipeline.
        $html = app(MarkdownRenderer::class)
            ->disableHighlighting()
            ->toHtml($content);
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($html)));
        if ($maxLength > 0 && strlen($text) > $maxLength) {
            return substr($text, 0, $maxLength).'...';
        }

        return $text;
    }

    protected function removeNumberPrefix(string $name): string
    {
        return (string) preg_replace('/^\d+[-_]/', '', $name);
    }
}
