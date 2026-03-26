<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

it('throws when duplicate slugs are generated across directories', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->file('one/foo.md', "# Foo One\n");
    $docs->file('two/foo.md', "# Foo Two\n");

    Lemme::getPages();
})->throws(RuntimeException::class);
