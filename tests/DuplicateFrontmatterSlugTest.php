<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

it('throws when duplicate explicit frontmatter slugs provided', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->file('one.md', "---\ntitle: One\nslug: duplicate\n---\n# One\n");
    $docs->file('two.md', "---\ntitle: Two\nslug: duplicate\n---\n# Two\n");

    Lemme::getPages();
})->throws(RuntimeException::class);
