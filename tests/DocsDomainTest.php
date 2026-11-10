<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;
use Ranetrace\Lemme\Tests\Support\ServesDocsOnDomain;

uses(ServesDocsOnDomain::class);

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());

    $this->docs->file('index.md', "---\ntitle: Home\n---\n# Home\n");
    $this->docs->file('getting-started.md', "---\ntitle: Getting Started\n---\n# Getting Started\n");
});

afterEach(function () {
    $this->docs?->cleanup();
});

it('serves the documentation on the configured domain', function (string $path) {
    $this->get("http://example.com{$path}")->assertOk();
})->with([
    'the home page' => ['/docs'],
    'a page' => ['/docs/getting-started'],
    'a markdown twin' => ['/docs/getting-started.md'],
]);

it('answers 404 for the documentation on any other host', function (string $path) {
    $this->get("http://app.example.com{$path}")->assertNotFound();
})->with([
    'the home page' => ['/docs'],
    'a page' => ['/docs/getting-started'],
    'a markdown twin' => ['/docs/getting-started.md'],
]);

it('links pages on the configured domain from any host', function () {
    // The navigation and the search index are built through the named routes,
    // so they point at the one host that answers, not at the application's.
    expect(Lemme::getPageUrl('getting-started'))->toBe('http://example.com/docs/getting-started')
        ->and(Lemme::getPageUrl(''))->toBe('http://example.com/docs');
});
