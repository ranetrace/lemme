<?php

namespace Ranetrace\Lemme\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Support\SearchResultValidator;

class SearchComponent extends Component
{
    public string $search = '';

    /** @var array<int, array<string, mixed>> */
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

    /**
     * Take the results the browser found, once they are shown to be ours.
     *
     * This listener is reachable from the console with any payload, and the view
     * prints what it is given: the url into an `href`, the title and the excerpt
     * as text. So the payload is validated rather than trusted, and a payload
     * that does not fit the shape leaves the list empty rather than partly
     * rendered. SearchResultValidator holds the shape and the reasoning.
     *
     * @param  array<array-key, mixed>  $results
     */
    #[On('search-results')]
    public function handleSearchResults(array $results): void
    {
        $this->results = (new SearchResultValidator)->validate($results);
    }

    public function render(): View
    {
        return view('lemme::livewire.search-component');
    }
}
