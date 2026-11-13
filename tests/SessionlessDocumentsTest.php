<?php

use Illuminate\Support\Facades\Route;
use Ranetrace\Lemme\Tests\Support\DocsFactory;
use Ranetrace\Lemme\Tests\Support\ReportsWhetherTheRequestHasASession;

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $this->docs->file('getting-started.md', "---\ntitle: Getting Started\n---\n# Getting Started\n");
});

afterEach(function () {
    $this->docs?->cleanup();
});

it('serves a markdown twin without starting a session or setting a cookie', function () {
    $response = $this->get('/docs/getting-started.md')->assertOk();

    expect($response->headers->getCookies())->toBeEmpty();
});

it('still gives the html page its session, which the search component posts with', function () {
    $response = $this->get('/docs/getting-started')->assertOk();

    expect(collect($response->headers->getCookies())->map->getName()->all())
        ->toContain('XSRF-TOKEN', config('session.cookie'));
});

it('still runs what an application added to the web group on a markdown twin', function () {
    Route::pushMiddlewareToGroup('web', ReportsWhetherTheRequestHasASession::class);

    $this->get('/docs/getting-started.md')
        ->assertOk()
        ->assertHeader('X-Added-By-The-Application', 'without a session');
});
