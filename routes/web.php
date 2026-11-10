<?php

use Illuminate\Support\Facades\Route;
use Ranetrace\Lemme\Http\Controllers\DocsController;
use Ranetrace\Lemme\Lemme;

// Routes for documentation
$subdomain = config('lemme.subdomain');
$routePrefix = config('lemme.route_prefix');

if ($subdomain && ! $routePrefix) {
    // Use subdomain routing
    Route::domain($subdomain.'.'.Lemme::baseHost())
        ->middleware(['web'])
        ->group(function () {
            Route::get('/', [DocsController::class, 'show'])->name('lemme.home');

            // Markdown twins (optional): /{slug}.md serves the raw Markdown source
            if (config('lemme.markdown.enabled', true)) {
                Route::get('/{slug}.md', [DocsController::class, 'showMarkdown'])->name('lemme.page.markdown')->where('slug', '[a-z0-9][a-z0-9\-/]*');
            }

            Route::get('/{slug}', [DocsController::class, 'show'])->name('lemme.page')->where('slug', '(?!api(?:/|$))[a-z0-9][a-z0-9\-/]*');

            // API routes (optional)
            if (config('lemme.api.enabled')) {
                Route::prefix('api')->group(function () {
                    Route::get('/', [DocsController::class, 'api'])->name('lemme.api');
                    Route::get('/{slug}', [DocsController::class, 'apiPage'])->name('lemme.api.page')->where('slug', '[a-z0-9][a-z0-9\-/]*');
                });
            }
        });
} else {
    // Route prefix, `docs` unless one is configured, on the configured domain
    // or on every host when none is
    $prefixedRoutes = Route::prefix($routePrefix ?: 'docs')->middleware(['web']);

    if (($domain = Lemme::domain()) !== null) {
        $prefixedRoutes->domain($domain);
    }

    $prefixedRoutes->group(function () {
        Route::get('/', [DocsController::class, 'show'])->name('lemme.home');

        // Markdown twins (optional): /{slug}.md serves the raw Markdown source
        if (config('lemme.markdown.enabled', true)) {
            Route::get('/{slug}.md', [DocsController::class, 'showMarkdown'])->name('lemme.page.markdown')->where('slug', '[a-z0-9][a-z0-9\-/]*');
        }

        Route::get('/{slug}', [DocsController::class, 'show'])->name('lemme.page')->where('slug', '(?!api(?:/|$))[a-z0-9][a-z0-9\-/]*');

        // API routes (optional)
        if (config('lemme.api.enabled')) {
            Route::prefix('api')->group(function () {
                Route::get('/', [DocsController::class, 'api'])->name('lemme.api');
                Route::get('/{slug}', [DocsController::class, 'apiPage'])->name('lemme.api.page')->where('slug', '[a-z0-9][a-z0-9\-/]*');
            });
        }
    });
}
