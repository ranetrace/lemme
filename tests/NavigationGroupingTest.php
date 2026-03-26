<?php

use Illuminate\Support\Collection;
use Ranetrace\Lemme\Support\NavigationBuilder;

// Keeping reflection-based tests for now; integration navigation test added separately.

beforeEach(function () {
    $this->builder = new NavigationBuilder;
});

it('builds hierarchical navigation with groups', function () {
    $pages = new Collection([
        ['title' => 'Home', 'slug' => '', 'relative_path' => 'index.md'],
        ['title' => 'Installation', 'slug' => 'getting-started-installation', 'relative_path' => '1_getting-started/installation.md'],
        ['title' => 'Configuration', 'slug' => 'getting-started-configuration', 'relative_path' => '1_getting-started/configuration.md'],
        ['title' => 'Authentication', 'slug' => 'api-authentication', 'relative_path' => '2_api/authentication.md'],
        ['title' => 'Webhooks', 'slug' => 'api-advanced-webhooks', 'relative_path' => '2_api/advanced/webhooks.md'],
    ]);

    $nav = $this->builder->build($pages, fn ($slug) => '/docs/'.$slug);

    // Expect first item is root page Home
    expect($nav[0]['type'])->toBe('page')
        ->and($nav[0]['title'])->toBe('Home');

    // Expect two groups (Getting Started, Api)
    $groups = $nav->filter(fn ($i) => $i['type'] === 'group')->values();
    expect($groups)->toHaveCount(2)
        ->and($groups[0]['title'])->toBe('Getting Started')
        ->and($groups[0]['children'])->toHaveCount(2)
        ->and($groups[1]['title'])->toBe('Api')
        ->and($groups[1]['children']->first()['title'])->toBe('Authentication');
});

it('formats group titles from directory names (implicitly via build)', function () {
    $pages = new Collection([
        ['title' => 'Intro', 'slug' => 'guide-intro', 'relative_path' => '10_guide/intro.md'],
    ]);

    $nav = $this->builder->build($pages, fn ($slug) => '/d/'.$slug);
    $group = $nav->firstWhere('type', 'group');
    expect($group['title'])->toBe('Guide');
});

it('removes number prefixes in group title formatting (implicit)', function () {
    $pages = new Collection([
        ['title' => 'Intro', 'slug' => 'advanced-intro', 'relative_path' => '001_advanced-topics/intro.md'],
    ]);
    $nav = $this->builder->build($pages, fn ($slug) => '/x/'.$slug);
    expect($nav->firstWhere('type', 'group')['title'])->toBe('Advanced Topics');
});

it('sorts groups respecting numeric prefixes (implicit order)', function () {
    $pages = new Collection([
        ['title' => 'A', 'slug' => 'b-a', 'relative_path' => '20_beta/a.md'],
        ['title' => 'A', 'slug' => 'a-a', 'relative_path' => '01_alpha/a.md'],
        ['title' => 'A', 'slug' => 'g-a', 'relative_path' => 'misc/a.md'],
    ]);
    $nav = $this->builder->build($pages, fn ($slug) => '/y/'.$slug);
    $groups = $nav->filter(fn ($i) => $i['type'] === 'group')->values();
    expect($groups->map(fn ($g) => $g['title'])->all())->toBe(['Alpha', 'Beta', 'Misc']);
});
