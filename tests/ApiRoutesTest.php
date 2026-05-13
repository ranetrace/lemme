<?php

use Illuminate\Support\Facades\Route;
use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

beforeEach(function () {
    config()->set('lemme.subdomain', null);
    config()->set('lemme.route_prefix', 'docs');
    config()->set('lemme.cache.enabled', false);
    config()->set('lemme.api.enabled', true);
    // Manually (re)load routes if API routes not yet registered due to timing
    if (! Route::has('lemme.api')) {
        require __DIR__.'/../routes/web.php';
    }
});

it('returns pages and navigation from api index', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());

    $docs
        ->file('getting-started.md', "# Getting Started\n")
        ->file('installation.md', "# Installation\n");

    $response = $this->get(route('lemme.api'));
    $response->assertOk()
        ->assertJsonStructure(['pages', 'navigation']);

    $json = $response->json();
    expect($json['pages'])
        ->toBeArray()
        ->and(collect($json['pages'])->pluck('slug'))->toContain('getting-started', 'installation');
});

it('returns a specific page via api', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    $docs->file('reference.md', "# Reference\n");

    // Build pages
    Lemme::getPages();

    $response = $this->get(route('lemme.api.page', ['slug' => 'reference']));
    $response->assertOk()
        ->assertJsonStructure(['page' => ['title', 'slug', 'raw_content']]);
});

it('returns 404 json for unknown api page', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());

    $response = $this->get(route('lemme.api.page', ['slug' => 'does-not-exist']));
    $response->assertStatus(404)
        ->assertJson(['error' => 'Page not found']);
});

it('enforces slug character pattern on api page route (uppercase rejected)', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    $docs->file('valid.md', "# Valid\n"); // slug: valid
    Lemme::getPages();

    // Request with uppercase should fail route matching -> 404
    $response = $this->get('/docs/api/Valid');
    $response->assertStatus(404);

    // Lowercase variant works
    $this->get('/docs/api/valid')->assertOk();
});

it('enforces slug pattern (underscore rejected)', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    $docs->file('with_part.md', "# With Part\n"); // slug becomes with-part
    Lemme::getPages();

    // Underscore form should 404 (route pattern disallows '_')
    $this->get('/docs/api/with_part')->assertStatus(404);
    // Hyphenated slug works
    $this->get('/docs/api/with-part')->assertOk();
});

it('treats /docs/api as api index not page route', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    $docs->file('api.md', "# Not the API Index\n"); // slug: api (would clash if not excluded)
    Lemme::getPages();

    // Because of negative lookahead, /docs/api is the JSON index, not page show.
    $response = $this->get('/docs/api');
    $response->assertOk()->assertJsonStructure(['pages', 'navigation']);
});
