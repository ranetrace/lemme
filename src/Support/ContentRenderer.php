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

        $html = app(MarkdownRenderer::class)
            ->highlightTheme(['light' => 'github-light', 'dark' => 'github-dark'])
            ->toHtml($page['raw_content']);

        $html = $this->injectHeadingIdsIntoHtml($html);

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

    protected function injectHeadingIdsIntoHtml(string $html): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        // DOMDocument::loadHTML historically expects ISO-8859-1 unless entities are encoded.
        // Without converting to HTML-ENTITIES, multibyte UTF-8 characters (emoji, box drawing) can be mangled.
        $wrapped = '<div id="__lemme_root__">'.$html.'</div>';
        $encoded = function_exists('mb_convert_encoding')
            ? mb_convert_encoding($wrapped, 'HTML-ENTITIES', 'UTF-8')
            : $wrapped; // Fallback – most environments have mbstring enabled.

        $dom->loadHTML($encoded, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $counts = [];
        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
            $nodes = $dom->getElementsByTagName($tag);
            for ($i = 0; $i < $nodes->length; $i++) {
                $node = $nodes->item($i);
                if (! $node instanceof \DOMElement) {
                    continue;
                }
                $text = trim($node->textContent ?? '');
                if ($text === '') {
                    continue;
                }
                $base = $this->generateHeadingId($text);
                $counts[$base] = ($counts[$base] ?? 0) + 1;
                $id = $counts[$base] > 1 ? $base.'-'.$counts[$base] : $base;
                $node->setAttribute('id', $id);
            }
        }
        $wrapper = $dom->getElementById('__lemme_root__');
        $result = '';
        if ($wrapper) {
            foreach ($wrapper->childNodes as $child) {
                $result .= $dom->saveHTML($child);
            }
        }

        return $result ?: $html;
    }

    protected function generateHeadingId(string $text): string
    {
        $text = preg_replace('/[*_`]/', '', $text);
        $id = strtolower($text);
        $id = preg_replace('/[^a-z0-9\s-]/', '', $id);
        $id = preg_replace('/[\s-]+/', '-', $id);
        $id = trim($id, '-');

        return $id ?: 'heading';
    }
}
