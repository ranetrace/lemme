<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

it('sorts pages by title descending correctly', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    config()->set('lemme.navigation.sort_by', 'title');
    config()->set('lemme.navigation.sort_direction', 'desc');

    $docs->file('a.md', "---\ntitle: Alpha\n---\n# A\n");
    $docs->file('b.md', "---\ntitle: Beta\n---\n# B\n");

    $pages = Lemme::getPages();

    expect($pages->pluck('title')->all())->toBe(['Beta', 'Alpha']);
});

it('sorts pages by filename descending respecting numeric prefixes', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    config()->set('lemme.navigation.sort_by', 'filename');
    config()->set('lemme.navigation.sort_direction', 'desc');

    $docs->file('1_first.md', "# First\n");
    $docs->file('2_second.md', "# Second\n");

    $pages = Lemme::getPages();

    // 2_second should come before 1_first when sorting desc
    expect($pages->pluck('relative_path')->map(fn ($p) => basename($p))->all())
        ->toBe(['2_second.md', '1_first.md']);
});
