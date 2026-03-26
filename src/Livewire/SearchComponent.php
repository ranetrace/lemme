<?php

namespace Ranetrace\Lemme\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Ranetrace\Lemme\Facades\Lemme;

class SearchComponent extends Component
{
    public string $search = '';

    /** @var array<int, mixed> */
    public array $results = [];

    public function mount(): void
    {
        $this->initSearchData();
    }

    #[On('init-search-data')]
    public function initSearchData(): void
    {
        $searchData = Lemme::getSearchData();

        $this->dispatch('search-data-ready', data: $searchData);
    }

    public function updatedSearch(): void
    {
        if (empty(trim($this->search))) {
            $this->reset('results');

            return;
        }

        // Dispatch search event to JavaScript
        $this->dispatch('perform-search', query: trim($this->search));
    }

    #[On('search-results')]
    public function handleSearchResults(array $results): void
    {
        $this->results = $results;
    }

    public function render(): View
    {
        return view('lemme::livewire.search-component');
    }
}
