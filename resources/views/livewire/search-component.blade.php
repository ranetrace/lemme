<div x-init="$watch('searchModalOpen', value => { if (value) { setTimeout(() => $refs.searchInput.focus(), 100) } })"
     x-data="{ highlightedResults: [] }"
     @search-data-ready.window="
         if (window.lemmeSearchInstance) {
             window.lemmeSearchInstance.init($event.detail.data);
         } else {
             console.error('lemmeSearchInstance not available');
         }
     "
     @perform-search.window="
         if (window.lemmeSearchInstance) {
             const results = window.lemmeSearchInstance.search($event.detail.query, 5);
             highlightedResults = results;
             $wire.call('handleSearchResults', results);
         } else {
             console.error('lemmeSearchInstance not available for search');
         }
     ">
    <div class="group relative flex h-12">
        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true" class="pointer-events-none absolute top-0 left-3 h-full w-5 stroke-zinc-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12.01 12a4.25 4.25 0 1 0-6.02-6 4.25 4.25 0 0 0 6.02 6Zm0 0 3.24 3.25"></path>
        </svg>
        {{-- The field has no visible label: the magnifying glass beside it is decorative
             and the placeholder disappears on the first keystroke. This names it without
             taking any room. --}}
        <label for="lemme-search" class="sr-only">Search the documentation</label>
        <input
            id="lemme-search"
            wire:model.live.debounce.300ms="search"
            x-ref="searchInput"
            {{-- The outline is drawn inside the box rather than around it: the field is
                 full bleed at the top of the panel, and the panel clips whatever crosses
                 its rounded edge, so an outline sitting outside the box would be cut off
                 on three sides. --}}
            class="flex-auto appearance-none bg-transparent pl-10 text-zinc-900 placeholder:text-zinc-500 focus:w-full focus:flex-none focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-lemme-accent sm:text-sm dark:text-white [&::-webkit-search-cancel-button]:hidden [&::-webkit-search-decoration]:hidden [&::-webkit-search-results-button]:hidden [&::-webkit-search-results-decoration]:hidden pr-4"
            aria-autocomplete="both"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="none"
            enterkeyhint="search"
            spellcheck="false"
            placeholder="Find something..."
            maxlength="512"
            type="search">
    </div>

    <div class="border-t border-zinc-200 bg-white dark:border-zinc-100/5 dark:bg-white/2.5">
        @if (count($results) > 0)
            {{-- A named list of links, not a listbox. A listbox promises its options can
                 be walked with the arrow keys and that the active one is reported through
                 aria-activedescendant, and nothing here does either: the results are
                 plain links reached with Tab. Claiming the role would take the links out
                 of the reading a screen reader gives them and hand back nothing. Wiring
                 the whole combobox pattern is the real fix, and a bigger change than a
                 label. --}}
            <ul aria-label="Search results" class="max-h-80 overflow-y-auto">
                @foreach($results as $index => $result)
                    <li class="group {{ $index > 0 ? 'border-t border-zinc-100 dark:border-zinc-800' : '' }}">
                        <a href="{{ $result['url'] }}" class="block cursor-pointer px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <div class="text-sm font-medium text-zinc-900 group-hover:text-lemme-accent dark:text-white">
                                <span x-html="
                                    (() => {
                                        const result = highlightedResults[{{ $index }}];
                                        if (result && result.matches && window.lemmeSearchInstance) {
                                            return window.lemmeSearchInstance.highlightMatches(
                                                '{{ addslashes($result['title']) }}',
                                                result.matches,
                                                'title'
                                            );
                                        }
                                        return '{{ addslashes($result['title']) }}';
                                    })()
                                "></span>
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-2xs whitespace-nowrap text-zinc-500">
                                <span>{{ $result['category'] }}</span>
                                @if(isset($result['score']))
                                    <span class="opacity-60">
                                        • {{ number_format((1 - $result['score']) * 100, 0) }}% match
                                    </span>
                                @endif
                            </div>
                            @if(strlen($search) > 0 && !empty($result['content']))
                                <div class="mt-1 text-xs text-zinc-600 dark:text-zinc-400 line-clamp-2">
                                    <span x-html="
                                        (() => {
                                            const result = highlightedResults[{{ $index }}];
                                            if (result && result.matches && window.lemmeSearchInstance) {
                                                return window.lemmeSearchInstance.highlightMatches(
                                                    '{{ addslashes(Str::limit($result['content'], 120)) }}',
                                                    result.matches,
                                                    'content'
                                                );
                                            }
                                            return '{{ addslashes(Str::limit($result['content'], 120)) }}';
                                        })()
                                    "></span>
                                </div>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @elseif (strlen(trim($search)) > 0)
            <div class="px-4 py-8 text-center text-sm text-zinc-500">
                <div class="mb-2">No results found for "{{ $search }}"</div>
                <div class="text-xs text-zinc-400">
                    Try different keywords or check spelling
                </div>
            </div>
        @endif
    </div>
</div>
