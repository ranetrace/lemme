<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\ServesDocsOnDefaultPrefix;

uses(ServesDocsOnDefaultPrefix::class);

it('falls back to the docs prefix when neither a subdomain nor a prefix is configured', function () {
    // The routes fall back to `docs`, so the urls have to as well. Read from
    // config alone the prefix is empty here, which is how a link to the bare
    // slug used to be built.
    expect(Lemme::getPageUrl('getting-started'))->toBe('http://example.com/docs/getting-started');
});

it('links the home page to the docs root', function () {
    expect(Lemme::getPageUrl(''))->toBe('http://example.com/docs');
});
