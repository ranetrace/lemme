<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Ranetrace\Lemme\Http\Controllers\DocsController;
use Ranetrace\Lemme\Lemme;

// Routes for documentation
$subdomain = config('lemme.subdomain');
$routePrefix = config('lemme.route_prefix');

// A Markdown twin and the JSON API are documents for agents and tools: they
// read no session and render no form. They stay in the `web` group, so
// whatever an application added to it still runs, and leave out the part of it
// that starts a session and sets cookies. Excluding a class also excludes the
// classes that extend it. The CSRF middleware is listed under both of its
// names: `PreventRequestForgery` is the base class from Laravel 13 on, and
// `VerifyCsrfToken` is the base class on Laravel 12, where the other does not
// exist. It is written as a string because Laravel 13 marks it deprecated.
$sessionMiddleware = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
    'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
];

$registerRoutes = function () use ($sessionMiddleware) {
    Route::get('/', [DocsController::class, 'show'])->name('lemme.home');

    // Markdown twins (optional): /{slug}.md serves the raw Markdown source
    if (config('lemme.markdown.enabled', true)) {
        Route::get('/{slug}.md', [DocsController::class, 'showMarkdown'])
            ->name('lemme.page.markdown')
            ->where('slug', '[a-z0-9][a-z0-9\-/]*')
            ->withoutMiddleware($sessionMiddleware);
    }

    Route::get('/{slug}', [DocsController::class, 'show'])->name('lemme.page')->where('slug', '(?!api(?:/|$))[a-z0-9][a-z0-9\-/]*');

    // API routes (optional)
    if (config('lemme.api.enabled')) {
        Route::prefix('api')->withoutMiddleware($sessionMiddleware)->group(function () {
            Route::get('/', [DocsController::class, 'api'])->name('lemme.api');
            Route::get('/{slug}', [DocsController::class, 'apiPage'])->name('lemme.api.page')->where('slug', '[a-z0-9][a-z0-9\-/]*');
        });
    }
};

if ($subdomain && ! $routePrefix) {
    // Use subdomain routing
    Route::domain($subdomain.'.'.Lemme::baseHost())
        ->middleware(['web'])
        ->group($registerRoutes);
} else {
    // Route prefix, `docs` unless one is configured, on the configured domain
    // or on every host when none is
    $prefixedRoutes = Route::prefix($routePrefix ?: 'docs')->middleware(['web']);

    if (($domain = Lemme::domain()) !== null) {
        $prefixedRoutes->domain($domain);
    }

    $prefixedRoutes->group($registerRoutes);
}
