<!DOCTYPE html>
<html lang="en" class="antialiased bg-white dark:bg-zinc-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page['title'] }} - {{ $siteTitle }}</title>
    <meta name="description" content="{{ $siteDescription }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tailwind CSS 4 (compiled) -->
    <link rel="stylesheet" href="{{ asset('vendor/lemme/app.css') }}">

    <!-- Fuse.js Search -->
    <script src="{{ asset('vendor/lemme/search.js') }}" defer></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>

    <script>!function () {
            try {
                var d = document.documentElement, c = d.classList;
                c.remove('light', 'dark');
                var e = localStorage.getItem('theme');
                if ('system' === e || (!e && true)) {
                    var t = '(prefers-color-scheme: dark)', m = window.matchMedia(t);
                    if (m.media !== t || m.matches) {
                        d.style.colorScheme = 'dark';
                        c.add('dark')
                    } else {
                        d.style.colorScheme = 'light';
                        c.add('light')
                    }
                } else if (e) {
                    c.add(e || '')
                }
                if (e === 'light' || e === 'dark') d.style.colorScheme = e
            } catch (e) {
            }
        }()</script>
</head>
<body x-data="{ searchModalOpen: false, mobileNavOpen: false }"
      @keydown.cmd.k.prevent="searchModalOpen = true"
      @keydown.ctrl.k.prevent="searchModalOpen = true">

<!-- Top Navigation -->
<div class="fixed inset-x-0 top-0 z-10 border-b border-gray-950/5 dark:border-white/10">
    <div class="flex h-14 items-center justify-between gap-8 px-4 sm:px-6 bg-white dark:bg-zinc-900">
        <div class="flex items-center gap-5">
            <!-- Toggle Button for Mobile Navigation -->
            <button x-on:click="mobileNavOpen = !mobileNavOpen"
                    type="button"
                    class="lg:hidden relative flex size-6 items-center justify-center rounded-md transition hover:bg-zinc-900/5 dark:hover:bg-white/5"
                    aria-label="Toggle navigation">
                <span class="absolute size-12 pointer-fine:hidden"></span>
                <svg x-show="!mobileNavOpen" class="w-2.5 stroke-zinc-900 dark:stroke-white" viewBox="0 0 10 9" fill="none" stroke-linecap="round" aria-hidden="true"><path d="M.5 1h9M.5 8h9M.5 4.5h9"></path></svg>
                <svg x-show="mobileNavOpen" x-cloak class="w-2.5 stroke-zinc-900 dark:stroke-white" viewBox="0 0 10 9" fill="none" stroke-linecap="round" aria-hidden="true"><path d="m1.5 1 7 7M8.5 1l-7 7"></path></svg>
            </button>

            <!-- Logo -->
            <a class="flex-shrink-0" aria-label="Home" href="{{ route('lemme.home') }}">
                @php($logo = config('lemme.logo'))
                @switch($logo['type'] ?? 'view')
                    @case('image')
                        @if(!empty($logo['image']))
                            <img src="{{ \Illuminate\Support\Str::startsWith($logo['image'], ['http://', 'https://', '/']) ? $logo['image'] : asset($logo['image']) }}"
                                 alt="{{ $logo['alt'] ?? 'Logo' }}" class="{{ $logo['classes'] ?? 'h-5' }}"/>
                        @else
                            @include('lemme::partials.logo')
                        @endif
                        @break
                    @case('text')
                        @if(!empty($logo['text']))
                            <span class="block font-semibold {{ $logo['classes'] ?? '' }}">{{ $logo['text'] }}</span>
                        @else
                            @include('lemme::partials.logo')
                        @endif
                        @break
                    @case('component')
                        @if(!empty($logo['component']))
                            <x-dynamic-component :component="$logo['component']" />
                        @else
                            @include('lemme::partials.logo')
                        @endif
                        @break
                    @case('view')
                    @default
                        @include($logo['view'] ?? 'lemme::partials.logo')
                @endswitch
            </a>
        </div>

        <!-- Search Bar -->
        <div class="hidden lg:block lg:max-w-md lg:flex-auto">
            <button type="button" @click="searchModalOpen = true"
                    class="hidden h-8 w-full items-center gap-2 rounded-lg bg-white pr-3 pl-2 text-sm text-zinc-500 ring-1 ring-zinc-900/10 hover:ring-zinc-900/20 lg:flex dark:bg-white/5 dark:text-zinc-400 dark:ring-white/10 dark:ring-inset dark:hover:ring-white/20">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true" class="h-5 w-5 stroke-current">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12.01 12a4.25 4.25 0 1 0-6.02-6 4.25 4.25 0 0 0 6.02 6Zm0 0 3.24 3.25"></path>
                </svg>
                Find something...
                <kbd class="ml-auto text-2xs text-zinc-400 dark:text-zinc-500">
                    @if ($isMac)
                        <kbd class="font-sans">⌘K</kbd>
                    @else
                        <kbd class="font-sans">Ctrl K</kbd>
                    @endif
                </kbd>
            </button>
        </div>
        <!-- Theme Switcher -->
        <div class="flex items-center gap-6">
            <button @click="searchModalOpen = true" type="button"
                    class="relative flex size-6 items-center justify-center rounded-md transition hover:bg-zinc-900/5 lg:hidden dark:hover:bg-white/5"
                    aria-label="Find something...">
                <span class="absolute size-12 pointer-fine:hidden"></span>
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"
                     class="h-5 w-5 stroke-zinc-900 dark:stroke-white">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12.01 12a4.25 4.25 0 1 0-6.02-6 4.25 4.25 0 0 0 6.02 6Zm0 0 3.24 3.25"></path>
                </svg>
            </button>
            <button type="button"
                    onclick="document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');"
                    class="flex size-6 items-center justify-center rounded-md transition hover:bg-zinc-900/5 dark:hover:bg-white/5"
                    aria-label="Switch to dark theme"><span class="absolute size-12 pointer-fine:hidden"></span>
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true" class="h-5 w-5 stroke-zinc-900 dark:hidden">
                    <path d="M12.5 10a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"></path>
                    <path stroke-linecap="round"
                          d="M10 5.5v-1M13.182 6.818l.707-.707M14.5 10h1M13.182 13.182l.707.707M10 15.5v-1M6.11 13.889l.708-.707M4.5 10h1M6.11 6.111l.708.707"></path>
                </svg>
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true" class="hidden h-5 w-5 stroke-white dark:block">
                    <path d="M15.224 11.724a5.5 5.5 0 0 1-6.949-6.949 5.5 5.5 0 1 0 6.949 6.949Z"></path>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Main Grid Layout -->
<div class="grid min-h-screen pt-14 lg:grid-cols-[300px_1fr] xl:grid-cols-[300px_1fr_350px]">
    <!-- Sidebar Navigation -->
    <div class="max-lg:hidden border-r border-zinc-900/10 dark:border-white/10">
        <div class="sticky top-14 h-[calc(100vh-3.5rem)] overflow-y-auto p-6">
            <nav>
                <ul class="space-y-1">
                    @foreach ($navigation as $item)
                        @if ($item['type'] === 'page')
                            <x-lemme::nav-link
                                    :href="$item['url']"
                                    :title="$item['title']"
                                    :show-active-indicator="false"
                                    :active="$page['slug'] === $item['slug']"
                            />
                        @elseif ($item['type'] === 'group')
                            <x-lemme::nav-group :group="$item" :current-page-slug="$page['slug']"/>
                        @endif
                    @endforeach
                </ul>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="mx-auto w-full max-w-2xl lg:max-w-3xl">
        <div class="px-4 pt-10 pb-24 sm:px-6 xl:pr-0 prose dark:prose-invert">
            {!! $html !!}
        </div>
    </div>

    <!-- Table of Contents -->
    <div class="max-xl:hidden">
        <div class="sticky top-14 max-h-[calc(100svh-3.5rem)] overflow-x-visible px-6 pt-10 pb-24">
            <h3 class="text-xs font-semibold text-zinc-900 dark:text-white">On this page</h3>
            <x-lemme::table-of-contents-navigation class="mt-3" data-toc="true">
                <ul class="space-y-1">
                    @if (isset($page['headings']) && count($page['headings']) > 0)
                        @foreach($page['headings'] as $heading)
                            <x-lemme::nav-link
                                    :href="'#' . $heading['id']"
                                    :title="$heading['text']"
                                    :active="false"
                                    :class="$heading['class'] ?? ''"
                                    :is-reactive="true"
                            />
                        @endforeach
                    @endif
                </ul>
            </x-lemme::table-of-contents-navigation>
        </div>
    </div>
</div>

<!-- modals -->
@include('lemme::partials.search-modal')
<x-lemme::mobile-navigation :navigation="$navigation" :current-page-slug="$page['slug']" />

<script>
    // IntersectionObserver driven Table of Contents highlighting
    document.addEventListener('DOMContentLoaded', () => {
        const toc = document.querySelector('nav[data-toc]');
        if (!toc) return;

        const controls = Array.from(toc.querySelectorAll('[data-slot=control]'));
        if (!controls.length) return;

        // Build heading map (id -> element + control li)
        const headings = controls.map(control => {
            const link = control.querySelector('[data-slot=link]');
            if (!link) return null;
            const href = link.getAttribute('href') || '';
            if (!href.startsWith('#')) return null;
            const id = href.slice(1);
            const el = document.getElementById(id);
            if (!el) return null;
            return {id, el, control};
        }).filter(Boolean);

        if (!headings.length) return;

        let currentActive = controls.find(c => c.querySelector('[data-slot=link][aria-current="page"]')) || null;
        let suppressUntil = 0; // timestamp until which auto activation is suppressed (after clicks)
        let lockedToHash = false; // when true, initial hash-based activation should not be overridden yet
        let initialScrollY = window.scrollY || window.pageYOffset;
        const releaseDistance = 32; // px user must scroll before releasing hash lock

        (function initHashLock() {
            const hash = window.location.hash;
            if (!hash) return;
            const id = hash.slice(1);
            if (!id) return;
            if (document.getElementById(id)) {
                lockedToHash = true; // table-of-contents-navigation handled activation already
            }
        })();
        const state = new Map(); // id -> {isIntersecting, top}

        function activate(control) {
            if (!control || currentActive === control) return;
            controls.forEach(c => {
                c.dispatchEvent(new CustomEvent(c === control ? 'link:active' : 'link:inactive', {bubbles: false}));
            });
            currentActive = control;
        }

        function pickActive() {
            if (lockedToHash) return; // Respect initial explicit hash selection
            if (Date.now() < suppressUntil) return; // Temporary suppression window after click
            // Prefer headings currently intersecting; pick the one closest to top (smallest positive or largest negative top)
            const intersecting = headings.filter(h => state.get(h.id)?.isIntersecting);
            let candidate = null;
            if (intersecting.length) {
                intersecting.sort((a, b) => (state.get(a.id).top) - (state.get(b.id).top));
                candidate = intersecting[0];
            } else {
                // Fallback: heading whose top is just above current scroll position
                const scrollY = window.scrollY || window.pageYOffset;
                const viewportOffset = 16; // small padding
                const before = headings.filter(h => (h.el.getBoundingClientRect().top + scrollY) <= scrollY + viewportOffset);
                if (before.length) {
                    before.sort((a, b) => (a.el.offsetTop - b.el.offsetTop));
                    candidate = before[before.length - 1];
                } else {
                    candidate = headings[0];
                }
            }
            if (candidate) activate(candidate.control);
        }

        // Observer to track when headings enter/leave; rootMargin raises earlier activation
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                state.set(entry.target.id, {isIntersecting: entry.isIntersecting, top: entry.boundingClientRect.top});
            });
            // Use rAF to batch DOM updates
            if (!pickActive._scheduled) {
                pickActive._scheduled = true;
                requestAnimationFrame(() => {
                    pickActive();
                    pickActive._scheduled = false;
                });
            }
        }, {
            root: null,
            // When the heading crosses 30% from the top we treat it as active (bottom margin pushes trigger earlier)
            rootMargin: '0px 0px -70% 0px',
            threshold: [0, 1]
        });

        headings.forEach(h => observer.observe(h.el));

        // Release the hash lock once user scrolls meaningfully
        window.addEventListener('scroll', () => {
            if (lockedToHash) {
                const delta = Math.abs((window.scrollY || window.pageYOffset) - initialScrollY);
                if (delta > releaseDistance) {
                    lockedToHash = false;
                    // Force recompute immediately after lock release
                    pickActive();
                }
            }
        }, {passive: true});

        // User interaction: clicking a TOC link should lock the active state and stop auto updates
        controls.forEach(control => {
            const link = control.querySelector('[data-slot=link]');
            if (!link) return;
            link.addEventListener('click', () => {
                // Keep clicked active; suppress auto-updates for a short period while the browser scrolls to anchor
                suppressUntil = Date.now() + 800; // ms
            }, {passive: true});
        });
    });
</script>

@livewireScripts
</body>
</html>
