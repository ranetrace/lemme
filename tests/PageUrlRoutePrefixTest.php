<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\ServesDocsOnRoutePrefix;

uses(ServesDocsOnRoutePrefix::class);

it('links a page under the configured route prefix on the application host', function () {
    expect(Lemme::getPageUrl('getting-started'))->toBe('http://example.com/guides/getting-started');
});

it('links the home page to the prefix root', function () {
    expect(Lemme::getPageUrl(''))->toBe('http://example.com/guides');
});

it('keeps the slashes in a nested slug unencoded', function () {
    expect(Lemme::getPageUrl('getting-started/install'))->toBe('http://example.com/guides/getting-started/install');
});
