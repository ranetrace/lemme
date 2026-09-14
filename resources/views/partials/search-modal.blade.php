<div x-show="searchModalOpen" x-cloak class="fixed inset-0 z-50" role="dialog" tabindex="-1" aria-modal="true">
    <!-- Backdrop/Overlay -->
    <div x-transition:enter="duration-300 ease-out" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100"
         x-transition:leave="duration-200 ease-in" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-zinc-400/25 backdrop-blur-xs dark:bg-black/40" 
         aria-hidden="true">
    </div>
    
    <!-- Modal Container -->
    <div class="fixed inset-0 overflow-y-auto px-4 py-4 sm:px-6 sm:py-20 md:py-32 lg:px-8 lg:py-[15vh]">
        <div x-transition:enter="duration-300 ease-out" 
             x-transition:enter-start="scale-95 opacity-0" 
             x-transition:enter-end="scale-100 opacity-100"
             x-transition:leave="duration-200 ease-in" 
             x-transition:leave-start="scale-100 opacity-100" 
             x-transition:leave-end="scale-95 opacity-0"
             @click.away="searchModalOpen = false"
             @click.stop
             @keydown.escape="searchModalOpen = false"
             class="mx-auto transform-gpu overflow-hidden rounded-lg bg-zinc-50 shadow-xl ring-1 ring-zinc-900/7.5 sm:max-w-xl dark:bg-zinc-900 dark:ring-zinc-800">
            {{-- The combobox roles used to sit on a wrapper around this form, with an
                 aria-expanded that never changed. They belong on the field itself, which
                 is where the search component now carries them. --}}
            <form action="" novalidate="" role="search">
                <livewire:lemme.search-component />
            </form>
        </div>
    </div>
</div>