<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

it('respects cache for pages until cleared', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', true);
    config()->set('lemme.cache.ttl', 3600);

    $docs->file('a.md', "# A\n");

    $first = Lemme::getPages();
    expect($first)->toHaveCount(1);

    // Add new file after cache warm
    $docs->file('b.md', "# B\n");
    $second = Lemme::getPages();
    expect($second)->toHaveCount(1); // still cached

    // Clear cache and expect both
    Lemme::clearCache();
    $third = Lemme::getPages();
    expect($third)->toHaveCount(2);
});

it('caches search data alongside pages', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', true);
    config()->set('lemme.cache.ttl', 3600);

    $docs->file('alpha.md', "# Alpha\n");
    Lemme::getPages(); // warm cache

    $data = Lemme::getSearchData();
    expect($data)->toBeArray()->and($data)->toHaveCount(1);
});
