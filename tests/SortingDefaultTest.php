<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

it('sorts pages by filename ascending by default honoring numeric prefixes', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    // Ensure defaults
    config()->set('lemme.navigation.sort_by', null);
    config()->set('lemme.navigation.sort_direction', null);

    $docs->file('2_second.md', "# Second\n");
    $docs->file('1_first.md', "# First\n");

    $pages = Lemme::getPages();
    expect($pages->pluck('relative_path')->map(fn ($p) => basename($p))->all())
        ->toBe(['1_first.md', '2_second.md']);
});
