<?php

use Illuminate\Support\Facades\Route;
use Ranetrace\Lemme\Http\Controllers\DocsController;

// Routes for documentation
$subdomain = config('lemme.subdomain');
$routePrefix = config('lemme.route_prefix');

if ($subdomain && ! $routePrefix) {
    // Use subdomain routing
    Route::domain($subdomain.'.'.\Ranetrace\Lemme\Lemme::baseHost())
        ->middleware(['web'])
        ->group(function () {
            Route::get('/', [DocsController::class, 'show'])->name('lemme.home');
            Route::get('/{slug}', [DocsController::class, 'show'])->name('lemme.page')->where('slug', '(?!api(?:/|$))[a-z0-9][a-z0-9\-/]*');

            // API routes (optional)
            if (config('lemme.api.enabled')) {
                Route::prefix('api')->group(function () {
                    Route::get('/', [DocsController::class, 'api'])->name('lemme.api');
                    Route::get('/{slug}', [DocsController::class, 'apiPage'])->name('lemme.api.page')->where('slug', '[a-z0-9][a-z0-9\-/]*');
                });
            }
        });
} elseif ($routePrefix) {
    // Use route prefix
    Route::prefix($routePrefix)
        ->middleware(['web'])
        ->group(function () {
            Route::get('/', [DocsController::class, 'show'])->name('lemme.home');
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
    // Default: use main domain with docs prefix
    Route::prefix('docs')
        ->middleware(['web'])
        ->group(function () {
            Route::get('/', [DocsController::class, 'show'])->name('lemme.home');
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
