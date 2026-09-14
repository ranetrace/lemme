<div x-cloak
     x-show="mobileNavOpen"
     @keydown.escape.window="mobileNavOpen = false"
     class="fixed inset-0 top-14 z-50 lg:hidden" role="dialog"
     tabindex="-1"
     aria-modal="true"
     x-bind:aria-hidden="!mobileNavOpen">

    <!-- backdrop -->
    <div class="fixed inset-0 top-14 bg-zinc-400/20 backdrop-blur-xs dark:bg-black/40"
         aria-hidden="true"
         @click="mobileNavOpen = false"
         x-show="mobileNavOpen"
    ></div>

    <!-- panel -->
    <div>
        <div class="fixed top-14 bottom-0 left-0 w-full overflow-y-auto bg-white px-4 pt-6 pb-4 shadow-lg ring-1 shadow-zinc-900/10 ring-zinc-900/7.5 min-[416px]:max-w-sm sm:px-6 sm:pb-10 dark:bg-zinc-900 dark:ring-zinc-800"
             x-show="mobileNavOpen"
             x-transition:enter="transform transition ease-out duration-100"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in duration-75"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">
            {{-- The same navigation the sidebar renders at desktop width, so it carries
                 the same label. The dialog around it is aria-hidden while it is closed,
                 so a screen reader is never offered both at once. --}}
            <nav aria-label="Documentation">
                <ul role="list" class="space-y-1">
                    @foreach ($navigation as $item)
                        @if ($item['type'] === 'page')
                            <x-lemme::nav-link
                                :href="$item['url']"
                                :title="$item['title']"
                                :active="$currentPageSlug === $item['slug']"
                                x-on:click="mobileNavOpen = false"
                            />
                        @elseif ($item['type'] === 'group')
                            <x-lemme::nav-group :group="$item" :current-page-slug="$currentPageSlug" />
                        @endif
                    @endforeach
                </ul>
            </nav>
        </div>
    </div>
</div>
