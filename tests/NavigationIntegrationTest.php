<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

it('builds navigation tree with groups and pages', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    config()->set('lemme.navigation.grouping.enabled', true);

    $docs->file('index.md', "---\ntitle: Home\n---\n# Home\n");
    $docs->file('1_getting-started/1_installation.md', "---\ntitle: Installation\n---\n# Installation\n");
    $docs->file('1_getting-started/2_configuration.md', "---\ntitle: Configuration\n---\n# Configuration\n");
    $docs->file('2_api/advanced/webhooks.md', "---\ntitle: Webhooks\n---\n# Webhooks\n");

    $nav = Lemme::getNavigation();

    expect($nav)->toHaveCount(3); // root page + two groups
    $home = $nav->first();
    expect($home['type'])->toBe('page')->and($home['title'])->toBe('Home');

    $groups = $nav->slice(1)->values();
    expect($groups[0]['type'])->toBe('group')
        ->and($groups[0]['title'])->toBe('Getting Started')
        ->and($groups[0]['children'])->toHaveCount(2);

    expect($groups[1]['title'])->toBe('Api')
        ->and($groups[1]['children']->first()['title'])->toBe('Advanced')
        ->and($groups[1]['children']->first()['children']->first()['title'])->toBe('Webhooks');
});
