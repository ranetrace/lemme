<?php

namespace Ranetrace\Lemme\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Ranetrace\Lemme\Facades\Lemme;

class DocsController extends Controller
{
    /**
     * Display the documentation homepage or a specific page
     */
    public function show(Request $request, string $slug = ''): View|Response
    {
        if (empty($slug)) {
            $pages = Lemme::getPages();

            // Try to find an index page, otherwise show the first page
            $page = $pages->first(fn ($p) => $p['slug'] === '');

            if (! $page) {
                $page = $pages->first();
            }

            $slug = $page['slug'];
        } else {
            $page = Lemme::getPage($slug);
        }

        if (! $page) {
            abort(404, 'Documentation page not found');
        }

        if ($this->wantsMarkdown($request)) {
            return $this->markdownResponse($page);
        }

        $navigation = Lemme::getNavigation();
        $html = Lemme::getPageHtml($slug);

        // Determine platform
        $ua = (string) $request->header('User-Agent', '');
        $isMac = (bool) preg_match('/Mac|iPhone|iPad|iPod/i', $ua);

        return view('lemme::docs', [
            'page' => $page,
            'html' => $html,
            'navigation' => $navigation,
            'siteTitle' => config('lemme.site_title', 'Documentation'),
            'siteDescription' => config('lemme.site_description', 'Project Documentation'),
            'theme' => config('lemme.theme', 'default'),
            'isMac' => $isMac,
        ]);
    }

    /**
     * API endpoint to get all pages as JSON
     */
    public function api(Request $request): JsonResponse
    {
        return response()->json([
            'pages' => Lemme::getPages(),
            'navigation' => Lemme::getNavigation(),
        ]);
    }

    /**
     * API endpoint to get a specific page as JSON
     */
    public function apiPage(Request $request, string $slug): JsonResponse
    {
        $page = Lemme::getPage($slug);

        if (! $page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        return response()->json(['page' => $page]);
    }

    /**
     * Determine whether the request prefers a Markdown response.
     */
    protected function wantsMarkdown(Request $request): bool
    {
        if (! config('lemme.markdown.enabled', true)) {
            return false;
        }

        return str_contains((string) $request->header('Accept', ''), 'text/markdown');
    }

    /**
     * Build a text/markdown response for the given page.
     */
    protected function markdownResponse(\Ranetrace\Lemme\Data\PageData $page): Response
    {
        $markdown = $page->raw_content;
        $estimatedTokens = (int) ceil(mb_strlen($markdown) / 4);

        return new Response($markdown, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'X-Markdown-Tokens' => $estimatedTokens,
        ]);
    }
}
