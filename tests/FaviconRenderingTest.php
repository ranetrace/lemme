<?php

use Illuminate\Support\Facades\File;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    config()->set('lemme.route_prefix', 'docs');
    config()->set('lemme.subdomain', null);

    $this->docs->file('page.md', "---\ntitle: My Page\n---\n# My Page\n");
});

afterEach(function () {
    $this->docs?->cleanup();

    if (isset($this->faviconViewDir)) {
        File::deleteDirectory($this->faviconViewDir);
    }
});

it('emits no favicon markup by default (type=none)', function () {
    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertDontSee('rel="icon"', false);
    $response->assertDontSee('apple-touch-icon', false);
});

it('emits no favicon markup when type is explicitly none', function () {
    config()->set('lemme.favicon.type', 'none');

    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertDontSee('rel="icon"', false);
    $response->assertDontSee('apple-touch-icon', false);
});

it('wraps a relative href through asset() for type=file', function () {
    config()->set('lemme.favicon.type', 'file');
    config()->set('lemme.favicon.href', 'favicon.ico');

    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertSee('<link rel="icon" href="'.asset('favicon.ico').'">', false);
});

it('uses an absolute URL href verbatim for type=file', function () {
    config()->set('lemme.favicon.type', 'file');
    config()->set('lemme.favicon.href', 'https://cdn.example.com/icon.png');

    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertSee('<link rel="icon" href="https://cdn.example.com/icon.png">', false);
});

it('uses a root-relative href verbatim for type=file', function () {
    config()->set('lemme.favicon.type', 'file');
    config()->set('lemme.favicon.href', '/static/favicon.ico');

    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertSee('<link rel="icon" href="/static/favicon.ico">', false);
});

it('emits a type attribute only when mime is set', function () {
    config()->set('lemme.favicon.type', 'file');
    config()->set('lemme.favicon.href', '/favicon.svg');
    config()->set('lemme.favicon.mime', 'image/svg+xml');

    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertSee('<link rel="icon" href="/favicon.svg" type="image/svg+xml">', false);
});

it('emits an apple-touch-icon link only when apple_touch is set', function () {
    config()->set('lemme.favicon.type', 'file');
    config()->set('lemme.favicon.href', '/favicon.ico');
    config()->set('lemme.favicon.apple_touch', 'apple-touch-icon.png');

    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertSee('<link rel="apple-touch-icon" href="'.asset('apple-touch-icon.png').'">', false);
});

it('emits nothing for type=file when href is empty', function () {
    config()->set('lemme.favicon.type', 'file');
    config()->set('lemme.favicon.href', null);

    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertDontSee('rel="icon"', false);
});

it('renders the configured Blade view inside <head> for type=view', function () {
    $this->faviconViewDir = sys_get_temp_dir().'/lemme-favicon-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($this->faviconViewDir);
    File::put(
        $this->faviconViewDir.'/custom-favicon.blade.php',
        '<link rel="icon" href="/from-view.svg" type="image/svg+xml"><!-- FAVICON_VIEW_MARKER -->'
    );
    view()->addLocation($this->faviconViewDir);

    config()->set('lemme.favicon.type', 'view');
    config()->set('lemme.favicon.view', 'custom-favicon');

    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertSee('FAVICON_VIEW_MARKER', false);
    $response->assertSee('<link rel="icon" href="/from-view.svg" type="image/svg+xml">', false);
});

it('emits nothing and does not error for type=view with a missing view', function () {
    config()->set('lemme.favicon.type', 'view');
    config()->set('lemme.favicon.view', 'this-favicon-view-does-not-exist');

    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertDontSee('rel="icon"', false);
});
