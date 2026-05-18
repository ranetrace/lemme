# Changelog

All notable changes to `ranetrace/lemme` will be documented in this file.

## Unreleased

### Fixed
- Docs search no longer fails to initialize when Livewire boots before the deferred search bundle. The search instance is now created synchronously and the Livewire data request is order-independent, so `lemmeSearchInstance not available` no longer occurs on a fresh full page load.

## v3.0.4 - 2026-05-18

### Added
- Markdown tables (and the rest of GitHub Flavored Markdown) render out of the box. The `GithubFlavoredMarkdownExtension` is enabled by default.
- New `lemme.markdown.extensions`, `lemme.markdown.highlight_theme`, and `lemme.markdown.commonmark_options` config keys for customizing CommonMark extensions, the Shiki theme(s), and CommonMark options without forking. See the "Customizing Markdown Rendering" section in the README.

### Changed
- Lemme now uses a dedicated `Spatie\LaravelMarkdown\MarkdownRenderer` instance per render instead of mutating the container-bound one. Rendering documentation no longer leaks Lemme's extensions or options into the host application's `MarkdownRenderer` (e.g. for blog posts, comments, glossary content).
