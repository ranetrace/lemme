# Changelog

All notable changes to `ranetrace/lemme` will be documented in this file.

## Unreleased

### Added
- Every documentation page now has a Markdown twin at its own URL: append `.md` to any page URL (`/docs/getting-started.md`) to get the raw Markdown source, with the same `Content-Type: text/markdown` and `X-Markdown-Tokens` headers the `Accept: text/markdown` mode already returned. Agents and tools that cannot set request headers can now fetch documentation directly. The docs home page is served at `/docs/index.md`, since its own slug is empty. HTML pages advertise their twin with a `<link rel="alternate" type="text/markdown">` tag in the `<head>`. The twin routes follow the existing `lemme.markdown.enabled` flag, so setting `LEMME_MARKDOWN_ENABLED=false` leaves both the routes and the link tag out.
- New `lemme.markdown.renderer` config key naming the class Lemme builds per render, so an application that highlights code its own way can render its documentation the same way. It defaults to Lemme's own `Ranetrace\Lemme\Support\MarkdownRenderer` (see the fix below) and accepts any class extending `Spatie\LaravelMarkdown\MarkdownRenderer` that keeps that constructor's signature, since the theme, extensions and CommonMark options are passed as named arguments. A class that does not extend it throws an `InvalidArgumentException` naming the configured class, rather than failing later inside a page render. Documented under "Customizing Markdown Rendering" in the README.

### Fixed
- The docs layout now has the landmarks a screen reader and a keyboard navigate by. The page content sits in a `<main id="main-content">`, which is new: there was no main landmark at all, so "skip to the content" had nothing to skip to. A skip link to it is the first focusable element in the `<body>`, hidden until it takes focus, which spares a keyboard visitor the navigation toggle, the logo, the search and the theme switcher on every page. Each of the three `<nav>` elements now carries a name: the sidebar and the mobile panel are labelled "Documentation", since they are one navigation the layout renders at two widths and only one of them is exposed at a time, and the table of contents is labelled "On this page". Unlabelled they were announced as three identical navigation regions.
- The docs search field is now named and shows where the focus is. It had no label, no `aria-label` and no `id`, so it was announced as an unnamed edit field; it now carries `id="lemme-search"` and a visually hidden label reading "Search the documentation". Its `outline-hidden` suppressed the focus outline with nothing in its place, leaving a keyboard visitor no way to see where they were, and is replaced by a `focus-visible` outline in the accent colour, drawn inside the box because the panel clips whatever crosses its rounded edge. The results list is now named "Search results", and its `role="listbox"` is dropped: a listbox promises arrow key navigation and an active option reported through `aria-activedescendant`, and the results are plain links reached with Tab, so the role took the links out of the reading a screen reader gives them and handed nothing back. Wiring the full combobox pattern is the fix for that, and is a separate change.
- Fenced and indented code blocks now keep their `<pre>` element on every path, so a multi-line snippet stays a block instead of collapsing onto one line as inline text. Spatie's renderer hands the `<code>` element to Shiki and returns the result in place of the whole `<pre>`, which is correct only while Shiki answers, because a highlighted block brings its own `<pre class="shiki">` wrapper. Shiki throws on a language it does not know, such as ` ```env `, and it throws on every block at once when the web server cannot reach a Node binary, which is the usual case under PHP-FPM, where the PATH does not carry a version manager's directory; in both cases what reached the page was a bare `<code>`, which browsers render inline and the prose stylesheet decorates with literal backticks. Lemme now renders code blocks through its own `MarkdownRenderer`, which registers a code block renderer above Spatie's and returns the whole element, stepping aside only for highlighted markup that already is a block. This replaces the pattern match over the finished HTML added earlier in this cycle, which could only recognise blocks that carried a `language-*` class and so left a fence with no language, and every indented block, still inline. Rendered pages are cached by their own modified time, so a site with `lemme.cache.enabled` on keeps serving the old markup for pages nobody has edited: run `php artisan lemme:clear` after upgrading.

## v3.0.6 - 2026-05-19

### Fixed
- The "On this page" table of contents no longer shows phantom entries for `#`-prefixed lines (e.g. shell comments) inside fenced or indented code blocks. Headings are now collected from the CommonMark AST using the same parser that renders the page, so code-block content can never leak into the TOC and anchor `id`s stay aligned with the rendered headings by construction.

## v3.0.5 - 2026-05-18

### Added
- Configurable favicon for the docs layout via a new `lemme.favicon` config block (`LEMME_FAVICON_*` env vars), mirroring the existing `logo` pattern. Supports `type=file` (`<link rel="icon">` plus optional apple-touch-icon) and `type=view` (render your own Blade partial in `<head>` for a full modern set). Opt-in: the default `type=none` emits nothing, leaving the docs `<head>` byte-identical to before — no change for existing installs on upgrade. See the "Favicon Customization" section in the README.

### Fixed
- Docs search no longer fails to initialize when Livewire boots before the deferred search bundle. The search instance is now created synchronously and the Livewire data request is order-independent, so `lemmeSearchInstance not available` no longer occurs on a fresh full page load.

## v3.0.4 - 2026-05-18

### Added
- Markdown tables (and the rest of GitHub Flavored Markdown) render out of the box. The `GithubFlavoredMarkdownExtension` is enabled by default.
- New `lemme.markdown.extensions`, `lemme.markdown.highlight_theme`, and `lemme.markdown.commonmark_options` config keys for customizing CommonMark extensions, the Shiki theme(s), and CommonMark options without forking. See the "Customizing Markdown Rendering" section in the README.

### Changed
- Lemme now uses a dedicated `Spatie\LaravelMarkdown\MarkdownRenderer` instance per render instead of mutating the container-bound one. Rendering documentation no longer leaks Lemme's extensions or options into the host application's `MarkdownRenderer` (e.g. for blog posts, comments, glossary content).
