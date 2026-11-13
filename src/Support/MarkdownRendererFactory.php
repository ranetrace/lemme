<?php

namespace Ranetrace\Lemme\Support;

use InvalidArgumentException;
use Spatie\LaravelMarkdown\MarkdownRenderer as BaseMarkdownRenderer;

/**
 * Builds the renderer Lemme reads Markdown through.
 *
 * There is one of these because a page and the search index pointing at it have
 * to be the same text. The index used to resolve spatie's container binding,
 * which the host application configures through its own `config/markdown.php`,
 * while the page went through the class named by `lemme.markdown.renderer` with
 * Lemme's extensions: two renderers over one document, so a phrase could be on
 * the page and missing from the index, or found in the index and absent from the
 * page the reader is then sent to. The binding is a `bind` rather than a
 * `singleton`, so nothing leaked between the two, but nothing kept them equal
 * either.
 *
 * Owning the instance is also what keeps Lemme's extensions and options out of
 * the host application's own renderer.
 */
class MarkdownRendererFactory
{
    /**
     * A table cannot scroll itself: one wider than the page widens the page. So
     * every table sits in a container that scrolls sideways in its place.
     *
     * The container takes over the table's vertical margin, which would otherwise
     * stop collapsing with the paragraphs around it (28px is the prose default for
     * a table). It can take focus and is a named region, because a scroll area a
     * keyboard cannot reach hides its far columns from anyone without a pointer.
     *
     * The classes are compiled into the shipped stylesheet from this file.
     */
    protected const array TABLE_SCROLL_CONTAINER = [
        'enabled' => true,
        'tag' => 'div',
        'attributes' => [
            'class' => 'my-7 overflow-x-auto [&>table]:my-0',
            'tabindex' => '0',
            'role' => 'region',
            'aria-label' => 'Table',
        ],
    ];

    /**
     * Build the renderer named by `lemme.markdown.renderer`.
     *
     * Naming the class is what lets a host application render documentation the
     * way the rest of its site renders Markdown. The default keeps code blocks
     * inside their `pre` element; see Lemme's MarkdownRenderer.
     */
    public function make(): BaseMarkdownRenderer
    {
        $extensions = array_map(
            fn (string $class): object => new $class,
            (array) config('lemme.markdown.extensions', []),
        );

        $rendererClass = (string) config('lemme.markdown.renderer', MarkdownRenderer::class);

        // Rejecting the class here names it. Accepting it would fail further
        // in, on a missing method, halfway through rendering someone's page.
        if (! is_a($rendererClass, BaseMarkdownRenderer::class, allow_string: true)) {
            throw new InvalidArgumentException(
                "The configured lemme.markdown.renderer [{$rendererClass}] must extend [".BaseMarkdownRenderer::class.'].'
            );
        }

        $renderer = new $rendererClass(
            commonmarkOptions: $this->commonmarkOptions(),
            highlightTheme: config('lemme.markdown.highlight_theme', 'github-light'),
            cacheStoreName: false,
            renderAnchors: false,
        );

        foreach ($extensions as $extension) {
            $renderer->addExtension($extension);
        }

        return $renderer;
    }

    /**
     * The configured CommonMark options, with the table scroll container added.
     *
     * The container is set here and not in the package's config file because an
     * application that published the config, or restates the `markdown` block,
     * would never receive a new default from it. A `table.wrap` the application
     * sets itself is kept whole.
     *
     * @return array<string, mixed>
     */
    protected function commonmarkOptions(): array
    {
        $options = (array) config('lemme.markdown.commonmark_options', []);

        $options['table']['wrap'] ??= self::TABLE_SCROLL_CONTAINER;

        return $options;
    }
}
