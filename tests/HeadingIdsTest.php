<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

it('generates unique heading ids for duplicate headings', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->file('dup.md', "# Title\n\n## Repeat\nText\n\n## Repeat\nMore\n");
    $html = Lemme::getPageHtml('dup');
    expect($html)->toContain('id="repeat"')->and($html)->toContain('id="repeat-1"');
});
