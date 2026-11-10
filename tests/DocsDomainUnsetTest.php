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
