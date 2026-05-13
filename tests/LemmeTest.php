<?php

use Illuminate\Support\Collection;
use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());
    config()->set('lemme.cache.enabled', false);
});

afterEach(function () {
    $this->docs?->cleanup();
});

it('can get empty pages collection', function () {
    $pages = Lemme::getPages();

    expect($pages)->toBeInstanceOf(Collection::class)
        ->and($pages)->toBeEmpty();
});

it('can parse markdown files', function () {
    $this->docs->file('test.md', <<<'MD'
---
title: Test Page
description: A test page
---

# Test Content

This is a test page with some content.
MD);

    $pages = Lemme::getPages();
    expect($pages)->toHaveCount(1)
        ->and($pages->first()['title'])->toBe('Test Page')
        ->and($pages->first()['slug'])->toBe('test');
    $html = Lemme::getPageHtml('test');
    expect($html)->toContain('<h1 id="test-content">Test Content');
});

it('can get specific page', function () {
    $this->docs->file('getting-started.md', <<<'MD'
---
title: Getting Started
---

# Getting Started Guide
MD);

    $page = Lemme::getPage('getting-started');

    expect($page)->not->toBeNull()
        ->and($page['title'])->toBe('Getting Started')
        ->and($page['slug'])->toBe('getting-started');
});

it('generates navigation', function () {
    $this->docs
        ->file('index.md', "---\ntitle: Home\n---\n# Home Page\n")
        ->file('about.md', "---\ntitle: About\n---\n# About Page\n");

    $navigation = Lemme::getNavigation();

    expect($navigation)->toHaveCount(2)
        ->and($navigation->first()['title'])->toBe('About')
        ->and($navigation->last()['title'])->toBe('Home');
});

it('generates kebab case slugs from filenames', function () {
    $this->docs
        ->file('1_getting_started.md', "---\ntitle: Getting Started\n---\n# Getting Started\n")
        ->file('2-advanced-features.md', "---\ntitle: Advanced Features\n---\n# Advanced Features\n")
        ->file('10_camelCaseFile.md', "---\ntitle: Camel Case File\n---\n# Camel Case File\n");

    $pages = Lemme::getPages();
    $slugs = $pages->pluck('slug');

    expect($pages)->toHaveCount(3)
        ->and($slugs)->toContain('getting-started')
        ->and($slugs)->toContain('advanced-features')
        ->and($slugs)->toContain('camel-case-file');
});

it('uses frontmatter slug when provided', function () {
    $this->docs->file('1_getting_started.md', <<<'MD'
---
title: Getting Started
slug: custom-setup-guide
---

# Getting Started
MD);

    $page = Lemme::getPages()->first();

    expect($page['slug'])->toBe('custom-setup-guide')
        ->and($page['title'])->toBe('Getting Started');
});

it('generates slug from filename only not directory', function () {
    $this->docs
        ->file('1_getting-started/1_installation.md', "---\ntitle: Installation\n---\n# Installation Guide\n")
        ->file('2_api/1_authentication.md', "---\ntitle: Authentication\n---\n# API Authentication\n");

    $slugs = Lemme::getPages()->pluck('slug')->toArray();

    expect($slugs)->toContain('installation')
        ->and($slugs)->toContain('authentication')
        ->and($slugs)->not->toContain('getting-started-installation')
        ->and($slugs)->not->toContain('api-authentication');
});
