<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Documentation Directory
    |--------------------------------------------------------------------------
    |
    | This is the directory where your markdown documentation files are stored.
    | By default, it's 'docs' but you can change it to any directory you prefer.
    |
    */
    'docs_directory' => env('LEMME_DOCS_DIRECTORY', 'docs'),

    /*
    |--------------------------------------------------------------------------
    | Subdomain
    |--------------------------------------------------------------------------
    |
    | The subdomain where your documentation will be served.
    | Leave as null to use route prefix instead (e.g., yoursite.com/docs).
    |
    */
    'subdomain' => env('LEMME_SUBDOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The route prefix where your documentation will be served.
    | By default, it's 'docs' (e.g., yoursite.com/docs).
    | Set to null to use subdomain routing instead.
    |
    */
    'route_prefix' => env('LEMME_ROUTE_PREFIX', 'docs'),

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    |
    | The theme to use for your documentation site.
    | Available themes: 'default', 'dark', 'minimal'
    |
    */
    'theme' => env('LEMME_THEME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Site Title
    |--------------------------------------------------------------------------
    |
    | The title of your documentation site.
    |
    */
    'site_title' => env('LEMME_SITE_TITLE', 'Documentation'),

    /*
    |--------------------------------------------------------------------------
    | Site Description
    |--------------------------------------------------------------------------
    |
    | A brief description of your documentation site.
    |
    */
    'site_description' => env('LEMME_SITE_DESCRIPTION', 'Project Documentation'),

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | Configure how navigation is generated from your markdown files.
    |
    */
    'navigation' => [
        'auto_generate' => true,
        'sort_by' => 'filename', // 'filename', 'title', 'created_at', 'modified_at'
        'sort_direction' => 'asc',

        // Directory-based grouping
        'grouping' => [
            'enabled' => true,
            'sort_groups_by' => 'directory_name', // 'directory_name', 'title'
            'sort_groups_direction' => 'asc',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Enable caching for better performance in production.
    |
    */
    'cache' => [
        'enabled' => env('LEMME_CACHE_ENABLED', true),
        'ttl' => env('LEMME_CACHE_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    |
    | Configure search functionality settings.
    |
    */
    'search' => [
        'max_content_length' => env('LEMME_SEARCH_MAX_CONTENT_LENGTH', 0), // 0 = no limit (index full content)
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Responses (Markdown for Agents)
    |--------------------------------------------------------------------------
    |
    | When enabled, clients that send an `Accept: text/markdown` header will
    | receive the raw Markdown source instead of the rendered HTML page. This
    | follows the "Markdown for Agents" convention popularised by Cloudflare,
    | making your documentation directly consumable by AI agents and tools.
    |
    | Response headers include `Content-Type: text/markdown` and an
    | `X-Markdown-Tokens` estimate so callers can budget context windows.
    |
    */
    'markdown' => [
        'enabled' => env('LEMME_MARKDOWN_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | Lemme can expose a small JSON API that returns the pages collection and
    | individual page data. This is useful for building a completely custom
    | frontend (SPA/mobile) or doing programmatic documentation processing.
    |
    | Disabled by default to avoid leaking documentation structure when you
    | only need the rendered site. Enable explicitly via env:
    |   LEMME_API_ENABLED=true
    |
    */
    'api' => [
        'enabled' => env('LEMME_API_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    |
    | Configure how the logo in the documentation layout is rendered.
    | Supported types:
    | - view      : renders a Blade view (default existing partial)
    | - component : renders a Blade component
    | - image     : renders an <img> tag (provide image path relative to public/ or full URL)
    | - text      : renders plain text inside a <span>
    |
    | You can override via env vars, e.g.:
    |   LEMME_LOGO_TYPE=image
    |   LEMME_LOGO_IMAGE="images/logo.svg"
    |   LEMME_LOGO_ALT="My Project"
    |
    */
    'logo' => [
        'type' => env('LEMME_LOGO_TYPE', 'view'),
        'view' => env('LEMME_LOGO_VIEW', 'lemme::partials.logo'),
        'component' => env('LEMME_LOGO_COMPONENT', null),
        'image' => env('LEMME_LOGO_IMAGE', null),
        'text' => env('LEMME_LOGO_TEXT', null),
        'alt' => env('LEMME_LOGO_ALT', 'Logo'),
        // Additional CSS classes applied to the root element of image/text variants
        'classes' => env('LEMME_LOGO_CLASSES', 'h-6 text-black dark:text-white'),
    ],
];
