<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

it('generates directory slug for nested index file', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->file('guide/index.md', "# Guide Home\n");
    $docs->file('guide/intro.md', "# Intro\n");

    $pages = Lemme::getPages();
    $slugs = $pages->pluck('slug')->all();
    expect($slugs)->toContain('guide');
    expect($slugs)->toContain('intro');
});
