<?php

use Ranetrace\Lemme\Tests\Support\DocsFactory;

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    config()->set('lemme.route_prefix', 'docs');
    config()->set('lemme.subdomain', null);
    config()->set('lemme.markdown.enabled', true);
});

afterEach(function () {
    $this->docs?->cleanup();
});

it('serves the raw markdown source at the .md twin url', function () {
    $this->docs->file('getting-started.md', <<<'MD'
---
title: Getting Started
---

# Getting Started

Welcome to the docs.
MD);

    $response = $this->get('/docs/getting-started.md');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertHeader('X-Markdown-Tokens')
        ->assertSee('# Getting Started')
        ->assertSee('Welcome to the docs.')
        ->assertDontSee('<h1', false)
        ->assertDontSee('title: Getting Started');

    expect((int) $response->headers->get('X-Markdown-Tokens'))->toBeGreaterThan(0);
});

it('serves the .md twin of a nested slug', function () {
    $this->docs->file('guide/advanced.md', <<<'MD'
---
title: Advanced
slug: guide/advanced
---

# Advanced Guide
MD);

    $response = $this->get('/docs/guide/advanced.md');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# Advanced Guide');
});

it('returns 404 for an unknown .md twin', function () {
    $this->docs->file('exists.md', "---\ntitle: Exists\n---\n# Exists\n");

    $this->get('/docs/does-not-exist.md')->assertStatus(404);
});

it('serves the home page markdown at index.md', function () {
    $this->docs->file('index.md', <<<'MD'
---
title: Home
---

# Welcome Home
MD);

    $response = $this->get('/docs/index.md');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# Welcome Home');
});

it('prefers a real index page over the home page fallback', function () {
    $this->docs
        ->file('index.md', "---\ntitle: Home\n---\n\n# Welcome Home\n")
        ->file('own-index.md', "---\ntitle: Index\nslug: index\n---\n\n# The Real Index\n");

    $response = $this->get('/docs/index.md');

    $response->assertOk()
        ->assertSee('# The Real Index')
        ->assertDontSee('# Welcome Home');
});

it('still renders the html page for the wildcard route', function () {
    $this->docs->file('page.md', "---\ntitle: My Page\n---\n\n# My Page\n");

    $response = $this->get('/docs/page');

    $response->assertOk()->assertSee('My Page');
    expect($response->headers->get('Content-Type'))->toStartWith('text/html');
});

it('links each html page to its markdown twin exactly once', function () {
    $this->docs->file('page.md', "---\ntitle: My Page\n---\n\n# My Page\n");

    $response = $this->get('/docs/page');
    $content = $response->assertOk()->getContent();

    expect(substr_count($content, '<link rel="alternate" type="text/markdown"'))->toBe(1);

    preg_match('/<link rel="alternate" type="text\/markdown" href="([^"]+)">/', $content, $matches);
    expect($matches[1])->toEndWith('/docs/page.md');
});

it('links the home page to the index markdown twin', function () {
    $this->docs->file('index.md', "---\ntitle: Home\n---\n\n# Welcome Home\n");

    $response = $this->get('/docs');
    $content = $response->assertOk()->getContent();

    expect(substr_count($content, '<link rel="alternate" type="text/markdown"'))->toBe(1);

    preg_match('/<link rel="alternate" type="text\/markdown" href="([^"]+)">/', $content, $matches);
    expect($matches[1])->toEndWith('/docs/index.md');
});
