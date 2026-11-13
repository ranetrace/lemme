<?php

use Illuminate\Support\Facades\File;
use Ranetrace\Lemme\Tests\Support\ServesDocsOnDomain;

uses(ServesDocsOnDomain::class);

afterEach(function () {
    File::deleteDirectory(base_path('docs'));
    File::deleteDirectory(public_path('vendor/lemme'));
});

it('names the configured domain as the place the documentation is served', function () {
    // The application lives on `app.example.com` and the documentation answers
    // on `example.com` only, so the application host is a 404.
    $this->artisan('lemme:install')
        ->expectsOutputToContain('Your documentation will be served at: http://example.com/docs')
        ->doesntExpectOutputToContain('app.example.com')
        ->assertSuccessful();
});
