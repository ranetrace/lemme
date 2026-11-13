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

it('writes the sample files to the configured documentation directory', function () {
    $directory = 'tests/runtime/handbook_'.bin2hex(random_bytes(6));
    config()->set('lemme.docs_directory', $directory);

    try {
        $this->artisan('lemme:install')
            ->expectsOutputToContain("Your docs are located in: {$directory}/")
            ->assertSuccessful();

        expect(base_path($directory.'/index.md'))->toBeFile()
            ->and(base_path($directory.'/1_welcome/1_getting-started.md'))->toBeFile()
            ->and(base_path('docs'))->not->toBeDirectory();
    } finally {
        File::deleteDirectory(base_path($directory));
    }
});
