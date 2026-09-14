<div x-init="$watch('searchModalOpen', value => { if (value) { activeIndex = -1; setTimeout(() => $refs.searchInput.focus(), 100) } })"
     x-data="{
         highlightedResults: [],

         // Which option the combobox points at, as an index into the rendered list.
         // Minus one means none, which is the state the field opens in and returns to
         // on every keystroke: a new query is a new result set.
         activeIndex: -1,

         optionElements() {
             return this.$refs.results ? Array.from(this.$refs.results.querySelectorAll('[role=option]')) : [];
         },

         activeDescendantId() {
             return this.activeIndex === -1 ? null : 'lemme-search-result-' + this.activeIndex;
         },

         // The arrow keys wrap: past the last result is the first one again, and up from
         // the first is the last. The list holds five results at most, so wrapping costs
         // nothing in orientation and saves the walk back.
         moveActiveOption(step) {
             const count = this.optionElements().length;

             if (count === 0) {
                 return;
             }

             const next = this.activeIndex === -1
                 ? (step === 1 ? 0 : count - 1)
                 : (this.activeIndex + step + count) % count;

             this.setActiveOption(next);
         },

         setActiveOption(index) {
             const options = this.optionElements();

             if (options.length === 0) {
                 this.activeIndex = -1;

                 return;
             }

             this.activeIndex = Math.max(0, Math.min(index, options.length - 1));

             // The list scrolls once it is full, so the option the arrow keys just
             // reached has to be brought into the box. 'nearest' leaves the list alone
             // when the option is already showing.
             this.$nextTick(() => {
                 const option = this.optionElements()[this.activeIndex];

                 if (option) {
                     option.scrollIntoView({ block: 'nearest' });
                 }
             });
         },

         openActiveOption() {
             const option = this.optionElements()[this.activeIndex];
             const link = option ? option.querySelector('a') : null;

             if (link) {
                 link.click();
             }
         },
     }"
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
             activeIndex = -1;
             $wire.call('handleSearchResults', results);
         } else {
             console.error('lemmeSearchInstance not available for search');
         }
     ">
    @php($hasResults = count($results) > 0)
    <div class="group relative flex h-12">
        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true" class="pointer-events-none absolute top-0 left-3 h-full w-5 stroke-zinc-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12.01 12a4.25 4.25 0 1 0-6.02-6 4.25 4.25 0 0 0 6.02 6Zm0 0 3.24 3.25"></path>
        </svg>
        {{-- The field has no visible label: the magnifying glass beside it is decorative
             and the placeholder disappears on the first keystroke. This names it without
             taking any room. --}}
        <label for="lemme-search" class="sr-only">Search the documentation</label>
        {{-- The combobox is this input, not a wrapper around it: the role belongs on the
             element that takes the typing and keeps the focus. The field owns the results
             list through aria-controls, says whether that list is showing through
             aria-expanded, and names the option the arrow keys are on through
             aria-activedescendant, which is how a screen reader reads a result out while
             the caret never leaves the field. --}}
        <input
            id="lemme-search"
            wire:model.live.debounce.300ms="search"
            x-ref="searchInput"
            role="combobox"
            aria-autocomplete="list"
            aria-haspopup="listbox"
            aria-controls="lemme-search-results"
            aria-expanded="{{ $hasResults ? 'true' : 'false' }}"
            x-bind:aria-activedescendant="activeDescendantId()"
            x-on:input="activeIndex = -1"
            x-on:keydown.arrow-down.prevent="moveActiveOption(1)"
            x-on:keydown.arrow-up.prevent="moveActiveOption(-1)"
            x-on:keydown.home.prevent="setActiveOption(0)"
            x-on:keydown.end.prevent="setActiveOption(optionElements().length - 1)"
            {{-- Enter is swallowed either way: with an option active it follows that
                 result, and with none it still beats the form around the field, which
                 would otherwise submit and reload the page out from under the modal.
                 Escape is left to bubble to the panel, which closes the modal. --}}
            x-on:keydown.enter.prevent="openActiveOption()"
            {{-- The outline is drawn inside the box rather than around it: the field is
                 full bleed at the top of the panel, and the panel clips whatever crosses
                 its rounded edge, so an outline sitting outside the box would be cut off
                 on three sides. --}}
            class="flex-auto appearance-none bg-transparent pl-10 text-zinc-900 placeholder:text-zinc-500 focus:w-full focus:flex-none focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-lemme-accent sm:text-sm dark:text-white [&::-webkit-search-cancel-button]:hidden [&::-webkit-search-decoration]:hidden [&::-webkit-search-results-button]:hidden [&::-webkit-search-results-decoration]:hidden pr-4"
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
        {{-- The listbox is rendered on every pass, empty and hidden when there is nothing
             to show, because aria-controls on the field has to resolve to an element at
             all times. --}}
        <ul id="lemme-search-results"
            role="listbox"
            aria-label="Search results"
            x-ref="results"
            class="max-h-80 overflow-y-auto"
            {{ $hasResults ? '' : 'hidden' }}>
            @foreach($results as $index => $result)
                {{-- aria-selected marks the active option and paints it, so the keyboard
                     and the pointer cannot end up lighting two different rows: hovering a
                     result makes it the active option rather than a second highlight. --}}
                <li id="lemme-search-result-{{ $index }}"
                    role="option"
                    aria-selected="false"
                    x-bind:aria-selected="activeIndex === {{ $index }}"
                    x-on:mouseenter="activeIndex = {{ $index }}"
                    class="group {{ $index > 0 ? 'border-t border-zinc-100 dark:border-zinc-800' : '' }}">
                    {{-- Out of the tab order on purpose. Inside a combobox the focus stays
                         in the field and the options are reached with the arrow keys, so
                         Tab leaves the widget instead of walking the results. Clicking
                         still works, and Enter on the active option clicks this link. --}}
                    <a href="{{ $result['url'] }}" tabindex="-1" class="block cursor-pointer px-4 py-3 group-aria-selected:bg-zinc-50 dark:group-aria-selected:bg-zinc-800/50">
                        <div class="text-sm font-medium text-zinc-900 group-aria-selected:text-lemme-accent dark:text-white">
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

        @if (! $hasResults && strlen(trim($search)) > 0)
            <div class="px-4 py-8 text-center text-sm text-zinc-500">
                <div class="mb-2">No results found for "{{ $search }}"</div>
                <div class="text-xs text-zinc-400">
                    Try different keywords or check spelling
                </div>
            </div>
        @endif
    </div>
</div>
