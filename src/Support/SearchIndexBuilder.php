<?php

namespace Ranetrace\Lemme\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Ranetrace\Lemme\Data\PageData;

/**
 * Builds and caches the lightweight search index array.
 */
class SearchIndexBuilder
{
    public function __construct(
        protected MarkdownRendererFactory $renderers = new MarkdownRendererFactory,
    ) {}

    /**
     * Build and cache search data.
     *
     * @param  Collection<array-key, PageData>  $pages  Pages in scoring order. The keys are
     *                                                  ignored, so a caller passes either the
     *                                                  slug-keyed collection or its values().
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
     * @param  Collection<array-key, PageData>  $pages  Pages in scoring order; the keys are ignored.
     * @return array<int, array<string, mixed>>
     */
    protected function buildSearchDataFromPages(Collection $pages, callable $urlResolver): array
    {
        // values() before toArray(): a slug-keyed collection becomes a string-keyed
        // array, which serialises to a JSON object, and the browser hands that object
        // straight to Fuse, which can only iterate a list. On the cached path the
        // caller already passes values(), so only an uncached site ever saw search
        // stop working.
        return $pages->values()->map(function ($page) use ($urlResolver) {
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

        // The same renderer a page is rendered through, so the index holds the
        // text the reader will find on the page. Resolving spatie's container
        // binding here instead indexed through the host application's markdown
        // config, which is configured for the host application's own content.
        //
        // Highlighting is off: the index only needs plain text, and Shiki is
        // both the expensive part of the pipeline and the part that shells out
        // to Node, which an index build has no reason to wait on.
        $html = $this->renderers->make()
            ->disableHighlighting()
            ->toHtml($content);
        $text = $this->toPlainText($this->withoutOpeningTitleHeading($html));

        // Counted in characters, not bytes: cutting a multi-byte character in half
        // leaves a byte sequence that is not valid UTF-8, and the whole index is
        // json_encoded on its way to the browser.
        if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength).'...';
        }

        return $text;
    }

    /**
     * Drop the `h1` a document opens with, so `content` holds the body alone.
     *
     * A documentation page carries its title twice: once in the front matter,
     * which is indexed as `title` and printed above every result, and once as
     * the heading the body opens with. Indexed together, the excerpt the browser
     * cuts from `content` began with the title the reader is already looking at,
     * spending the whole of a 120 character window repeating it instead of
     * showing the first sentence of the answer.
     *
     * The cut is made on the rendered html rather than on the markdown, because
     * by then the renderer has already decided what a heading is: a `#` inside a
     * fenced code block is code, a setext `Title` over `=====` is an `h1` with
     * no `#` in sight, and the title heading has its permalink id attached. Only
     * a leading `h1` goes. A heading further down is body content a reader may
     * well be searching for, and a page that opens with an `h2` never repeated
     * its title in the first place.
     */
    protected function withoutOpeningTitleHeading(string $html): string
    {
        $stripped = preg_replace('/\A\s*<h1\b[^>]*>.*?<\/h1>/is', '', $html, 1);

        return $stripped ?? $html;
    }

    /**
     * Reduce rendered HTML to the plain text the index and its excerpts are built from.
     *
     * The order is the point. Tags are stripped first, so no markup can reach the
     * index; entities are decoded only after that, so the `&lt;` a code block
     * escaped comes back as the character the page shows instead of staying entity
     * text that no query matches and that an excerpt would escape a second time.
     * Decoding first would turn an escaped code sample into markup and strip it.
     */
    protected function toPlainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // A non-breaking space arrives from `&nbsp;` and is whitespace to a reader,
        // so it collapses with the rest. Text preg cannot walk is kept as it is
        // rather than thrown away.
        $collapsed = preg_replace('/[\s\x{00A0}]+/u', ' ', $text);

        return trim($collapsed ?? $text);
    }

    protected function removeNumberPrefix(string $name): string
    {
        return (string) preg_replace('/^\d+[-_]/', '', $name);
    }
}
