<?php

namespace Ranetrace\Lemme\Support;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use Spatie\CommonMarkShikiHighlighter\ShikiHighlighter;
use Spatie\LaravelMarkdown\MarkdownRenderer as BaseMarkdownRenderer;
use Spatie\ShikiPhp\Shiki;

/**
 * The renderer every documentation page is rendered through.
 *
 * It exists for one reason: to keep the `pre` element on code blocks Shiki
 * cannot highlight. See HighlightedCodeBlockRenderer for what the vendor
 * renderer does instead and why that reaches the page as inline text.
 *
 * A host application that highlights its own way points
 * `lemme.markdown.renderer` at its own subclass.
 */
class MarkdownRenderer extends BaseMarkdownRenderer
{
    /**
     * Spatie's highlight extension registers its code renderers at priority 10,
     * so these sit above it and take every code block. The extension itself is
     * left registered: the parent decides whether highlighting is on at all,
     * and this class does not second-guess it.
     */
    protected function configureCommonMarkEnvironment(EnvironmentBuilderInterface $environment): void
    {
        parent::configureCommonMarkEnvironment($environment);

        if (! $this->highlightCode) {
            return;
        }

        $codeBlockRenderer = new HighlightedCodeBlockRenderer(
            new ShikiHighlighter(new Shiki($this->highlightTheme)),
        );

        $environment
            ->addRenderer(FencedCode::class, $codeBlockRenderer, 20)
            ->addRenderer(IndentedCode::class, $codeBlockRenderer, 20);
    }
}
