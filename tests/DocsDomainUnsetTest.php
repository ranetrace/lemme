<?php

use Ranetrace\Lemme\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;
use Ranetrace\Lemme\Tests\Support\ServesDocsOnDefaultPrefix;

uses(ServesDocsOnDefaultPrefix::class);

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());

    $this->docs->file('getting-started.md', "---\ntitle: Getting Started\n---\n# Getting Started\n");
});

afterEach(function () {
    $this->docs?->cleanup();
});

it('serves the documentation on every host while no domain is set', function (string $host) {
    $this->get("http://{$host}/docs/getting-started")->assertOk();
    $this->get("http://{$host}/docs/getting-started.md")->assertOk();
})->with(['example.com', 'app.example.com', 'another.example']);

it('reads a blank domain as no domain', function (?string $domain) {
    config()->set('lemme.domain', $domain);

    expect(Lemme::domain())->toBeNull();
})->with([null, '', '   ']);

it('names the request host as the canonical url while no domain is set', function (string $host) {
    $this->get("http://{$host}/docs/getting-started")
        ->assertSee('<link rel="canonical" href="http://'.$host.'/docs/getting-started">', false);
})->with(['example.com', 'app.example.com']);

it('names the page itself as canonical when the docs root shows it for want of an index page', function () {
    // Without an `index.md` the docs root shows the first page, which then
    // answers on two urls.
    $this->get('http://example.com/docs')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="http://example.com/docs/getting-started">', false);
});
