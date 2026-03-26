<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

it('falls back to filename for title and slug when frontmatter missing', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->file('installation_guide.md', "# Installation Guide\n");

    $page = Lemme::getPages()->first();
    expect($page['title'])->toBe('Installation Guide')
        ->and($page['slug'])->toBe('installation-guide');
});

it('ignores invalid yaml frontmatter gracefully', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    // Malformed YAML (missing closing quote)
    $docs->file('bad.md', "---\ntitle: \"Broken\n---\n# Broken\n");
    $docs->file('good.md', "---\ntitle: Good\n---\n# Good\n");

    $pages = Lemme::getPages();
    expect($pages)->toHaveCount(1)->and($pages->first()['title'])->toBe('Good');
});
