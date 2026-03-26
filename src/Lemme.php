<?php

namespace Ranetrace\Lemme;

use Illuminate\Support\Collection;
use Ranetrace\Lemme\Data\PageData;
use Ranetrace\Lemme\Support\ContentRenderer;
use Ranetrace\Lemme\Support\NavigationBuilder;
use Ranetrace\Lemme\Support\PageRepository;
use Ranetrace\Lemme\Support\SearchIndexBuilder;

class Lemme
{
    public function __construct(
        protected PageRepository $pages = new PageRepository,
        protected NavigationBuilder $navigationBuilder = new NavigationBuilder,
        protected SearchIndexBuilder $searchIndexBuilder = new SearchIndexBuilder,
        protected ContentRenderer $renderer = new ContentRenderer,
    ) {}

    /**
     * Return base scheme from app configuration, with sensible fallback.
     */
    public static function baseScheme(): string
    {
        $scheme = (string) parse_url((string) config('app.url'), PHP_URL_SCHEME);
        if (! $scheme) {
            $fallback = (string) url('/');
            $scheme = (string) parse_url($fallback, PHP_URL_SCHEME);
        }

        return $scheme ?: 'http';
    }

    /**
     * Return base host from app configuration, with sensible fallback.
     */
    public static function baseHost(): string
    {
        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! $host) {
            $fallback = (string) url('/');
            $host = (string) parse_url($fallback, PHP_URL_HOST);
        }

        return $host ?: 'localhost';
    }

    /**
     * Get all documentation pages keyed by slug.
     *
     * @return Collection<string, PageData>
     */
    public function getPages(): Collection
    {
        return $this->pages->all();
    }

    /**
     * Get a specific page by slug
     */
    public function getPage(string $slug): ?PageData
    {
        return $this->pages->findBySlug($slug);
    }

    /**
     * Get rendered HTML for a specific page
     */
    public function getPageHtml(string $slug): ?string
    {
        $page = $this->getPage($slug);
        if (! $page) {
            return null;
        }

        return $this->renderer->render($page);
    }

    /**
     * Get navigation structure
     */
    /**
     * @return Collection<int, mixed>
     */
    public function getNavigation(): Collection
    {
        return $this->navigationBuilder->build($this->getPages(), fn ($slug) => $this->getPageUrl($slug));
    }

    /**
     * Get URL for a page
     */
    public function getPageUrl(string $slug): string
    {
        $prefix = config('lemme.route_prefix');

        if ($prefix) {
            return url($prefix.'/'.$slug);
        }

        return url($slug);
    }

    /**
     * Clear the cache
     */
    public function clearCache(): void
    {
        $pages = $this->getPages();
        $this->pages->clearCache();
        $this->renderer->clearCacheForPages($pages);
    }

    /**
     * Get search data (cached or generate from pages)
     */
    public function getSearchData(): array
    {
        return $this->searchIndexBuilder->getSearchData($this->getPages(), fn ($slug) => $this->getPageUrl($slug));
    }
}
