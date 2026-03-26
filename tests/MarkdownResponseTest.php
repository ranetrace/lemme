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

it('returns markdown when accept header is text/markdown', function () {
    $this->docs->file('getting-started.md', <<<'MD'
---
title: Getting Started
---

# Getting Started

Welcome to the docs.
MD);

    $response = $this->get('/docs/getting-started', ['Accept' => 'text/markdown']);

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertHeader('X-Markdown-Tokens')
        ->assertSee('# Getting Started')
        ->assertSee('Welcome to the docs.');
});

it('does not include frontmatter in markdown response', function () {
    $this->docs->file('page.md', <<<'MD'
---
title: My Page
description: Some description
---

# My Page

Content here.
MD);

    $response = $this->get('/docs/page', ['Accept' => 'text/markdown']);

    $response->assertOk()
        ->assertDontSee('title: My Page')
        ->assertDontSee('description: Some description')
        ->assertSee('# My Page');
});

it('returns html when no accept header for markdown', function () {
    $this->docs->file('page.md', <<<'MD'
---
title: My Page
---

# My Page
MD);

    $response = $this->get('/docs/page');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
});

it('returns markdown for the index page', function () {
    $this->docs->file('index.md', <<<'MD'
---
title: Home
---

# Welcome Home
MD);

    $response = $this->get('/docs', ['Accept' => 'text/markdown']);

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# Welcome Home');
});

it('returns 404 for missing page with markdown accept header', function () {
    $this->docs->file('exists.md', "---\ntitle: Exists\n---\n# Exists\n");

    $response = $this->get('/docs/does-not-exist', ['Accept' => 'text/markdown']);

    $response->assertStatus(404);
});

it('sets x-markdown-tokens header with estimated count', function () {
    $content = str_repeat('word ', 100);
    $this->docs->file('tokens.md', "---\ntitle: Tokens\n---\n\n{$content}");

    $response = $this->get('/docs/tokens', ['Accept' => 'text/markdown']);

    $response->assertOk();
    $tokens = (int) $response->headers->get('X-Markdown-Tokens');
    expect($tokens)->toBeGreaterThan(0);
});

it('ignores accept text/markdown when markdown feature is disabled', function () {
    config()->set('lemme.markdown.enabled', false);

    $this->docs->file('page.md', "---\ntitle: Page\n---\n# Page\n");

    $response = $this->get('/docs/page', ['Accept' => 'text/markdown']);

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
});
