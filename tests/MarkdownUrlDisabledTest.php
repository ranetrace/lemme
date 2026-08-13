<?php

use Illuminate\Support\Facades\Route;
use Ranetrace\Lemme\Tests\Support\DisablesMarkdownResponses;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

uses(DisablesMarkdownResponses::class);

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());
    config()->set('lemme.cache.enabled', false);
});

afterEach(function () {
    $this->docs?->cleanup();
});

it('does not register the markdown twin route when the feature is disabled', function () {
    $this->docs->file('page.md', "---\ntitle: My Page\n---\n\n# My Page\n");

    expect(Route::has('lemme.page.markdown'))->toBeFalse();

    $this->get('/docs/page.md')->assertStatus(404);
});

it('omits the alternate link tag when the feature is disabled', function () {
    $this->docs->file('page.md', "---\ntitle: My Page\n---\n\n# My Page\n");

    $response = $this->get('/docs/page');

    $response->assertOk()->assertDontSee('type="text/markdown"', false);
});
