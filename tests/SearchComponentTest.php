<?php

use Livewire\Livewire;
use Ranetrace\Lemme\Livewire\SearchComponent;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

beforeEach(function () {
    // Encryption key required by Livewire components
    config()->set('app.key', 'base64:'.base64_encode('a'.str_repeat('b', 31)));
});

it('can render search component', function () {
    Livewire::test(SearchComponent::class)
        ->assertStatus(200)
        ->assertViewIs('lemme::livewire.search-component');
});

it('initializes with empty search and results', function () {
    Livewire::test(SearchComponent::class)
        ->assertSet('search', '')
        ->assertSet('results', []);
});

it('can initialize search data', function () {
    Livewire::test(SearchComponent::class)
        ->call('initSearchData')
        ->assertDispatched('search-data-ready');
});

it('dispatches search-data-ready with the search data on mount', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    $docs->markdown('mounting.md', 'Mounting Guide', 'How to mount the widget.');

    try {
        // Mounting the component is the failing path in the browser: the JS
        // bundle must already have created window.lemmeSearchInstance before
        // this mount-time event fires. Guard the server side of that contract.
        Livewire::test(SearchComponent::class)
            ->assertDispatched('search-data-ready', function (string $event, array $params): bool {
                return collect($params['data'] ?? [])->contains(
                    fn ($entry) => ($entry['slug'] ?? null) === 'mounting'
                );
            });
    } finally {
        $docs->cleanup();
    }
});

it('re-dispatches search-data-ready with the search data on the init-search-data event', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    $docs->markdown('reindex.md', 'Reindex Guide', 'How to reindex the docs.');

    try {
        Livewire::test(SearchComponent::class)
            ->call('initSearchData')
            ->assertDispatched('search-data-ready', function (string $event, array $params): bool {
                return collect($params['data'] ?? [])->contains(
                    fn ($entry) => ($entry['slug'] ?? null) === 'reindex'
                );
            });
    } finally {
        $docs->cleanup();
    }
});

it('dispatches search event when search updates', function () {
    Livewire::test(SearchComponent::class)
        ->set('search', 'installation')
        ->assertDispatched('perform-search', query: 'installation');
});

it('handles search results', function () {
    $mockResults = [
        [
            'title' => 'Installation Guide',
            'category' => 'Guides',
            'url' => '/docs/installation',
            'content' => 'How to install the system',
            'score' => 0.1,
        ],
    ];

    Livewire::test(SearchComponent::class)
        ->call('handleSearchResults', $mockResults)
        ->assertSet('results', $mockResults);
});

it('clears results when search is empty', function () {
    Livewire::test(SearchComponent::class)
        ->set('search', 'test')
        ->set('search', '')
        ->assertSet('results', []);
});
