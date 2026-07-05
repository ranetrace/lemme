<?php

use Illuminate\Support\Facades\Cache;
use Ranetrace\Lemme\Data\PageData;
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

it('caches pages as an object-free payload so consumers need no serializable_classes allow-list', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', true);
    config()->set('lemme.cache.ttl', 3600);

    $docs->file('guide/install.md', "---\ntitle: Install\n---\n# Install\n");

    Lemme::getPages(); // warm cache

    $cached = Cache::get('lemme.pages');

    // A consuming app running with cache.serializable_classes => false must be able
    // to unserialize the payload without any value degrading to __PHP_Incomplete_Class,
    // i.e. the cache must hold no objects at all.
    expect($cached)->toBeArray();
    expect(unserialize(serialize($cached), ['allowed_classes' => false]))->toEqual($cached);
});

it('rehydrates cached pages back into PageData objects', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', true);
    config()->set('lemme.cache.ttl', 3600);

    $docs->file('install.md', "---\ntitle: Install\n---\n# Install\n");

    Lemme::getPages(); // warm cache

    // The second call is served from the cached array and must rebuild real DTOs.
    $pages = Lemme::getPages();

    expect($pages->first())->toBeInstanceOf(PageData::class)
        ->and($pages->first()['title'])->toBe('Install')
        ->and($pages->first()['slug'])->toBe('install');
});
