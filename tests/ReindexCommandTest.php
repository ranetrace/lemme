<?php

use Illuminate\Support\Facades\Cache;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

it('reindexes and warms cache via artisan command', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', true);

    $docs->file('alpha.md', "# Alpha\n");
    $docs->file('beta.md', "# Beta\n");

    $this->artisan('lemme:reindex --clear')
        ->assertExitCode(0)
        ->expectsOutputToContain('Indexed 2 pages');

    // Pages are cached as a plain, object-free array (not a Collection of DTOs).
    $pages = Cache::get('lemme.pages');
    expect($pages)->toBeArray()->and($pages)->toHaveCount(2);
    expect(Cache::has('lemme.search_data'))->toBeTrue();
});
