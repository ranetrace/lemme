<?php

use Livewire\Livewire;
use Ranetrace\Lemme\Livewire\SearchComponent;

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
