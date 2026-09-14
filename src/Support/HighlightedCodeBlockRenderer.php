<?php

namespace Ranetrace\Lemme\Support;

use InvalidArgumentException;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Renderer\Block\FencedCodeRenderer as BaseFencedCodeRenderer;
use League\CommonMark\Extension\CommonMark\Renderer\Block\IndentedCodeRenderer as BaseIndentedCodeRenderer;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use Spatie\CommonMarkShikiHighlighter\ShikiHighlighter;

/**
 * Renders a code block as a code block, whether or not Shiki could highlight it.
 *
 * The vendor renderer builds the same `<pre><code>` element this one does, hands
 * the `<code>` to Shiki, and then returns only the element's *contents*. That is
 * correct while Shiki answers, because a successful highlight brings its own
 * `<pre class="shiki">` wrapper; it loses the `pre` on every other path, and a
 * bare `<code>` renders inline, so a multi-line snippet collapses onto one line
 * and picks up the prose stylesheet's backtick decorations.
 *
 * Both paths fail in ordinary installs: Shiki throws on a language it does not
 * know (```env, ```blade), and it throws on every block when the web server
 * cannot reach a Node binary, which is the usual case under PHP-FPM, where the
 * PATH does not carry a version manager's directory.
 *
 * So this renderer returns the whole element rather than its contents, and only
 * steps aside for the highlighted markup that already is a block.
 */
class HighlightedCodeBlockRenderer implements NodeRendererInterface
{
    public function __construct(protected ShikiHighlighter $highlighter) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! $node instanceof FencedCode && ! $node instanceof IndentedCode) {
            throw new InvalidArgumentException('Expected a fenced or indented code block, got '.$node::class.'.');
        }

        $element = $this->renderElement($node, $childRenderer);

        $highlighted = $this->highlighter->highlight(
            (string) $element->getContents(),
            $node instanceof FencedCode ? $this->infoLine($node) : null,
        );

        // A successful highlight is already a block: Shiki returns its own
        // `<pre class="shiki">` with the theme's colours on it, so wrapping it
        // in this element's `pre` would nest one block inside another.
        if (str_starts_with(ltrim($highlighted), '<pre')) {
            return $highlighted;
        }

        // Every other path keeps the element the base renderer built, which
        // carries the `language-*` class a fenced block asked for.
        $element->setContents($highlighted);

        return (string) $element;
    }

    /**
     * The `<pre><code>` element CommonMark itself would have rendered.
     */
    protected function renderElement(FencedCode|IndentedCode $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        $baseRenderer = $node instanceof FencedCode
            ? new BaseFencedCodeRenderer
            : new BaseIndentedCodeRenderer;

        $element = $baseRenderer->render($node, $childRenderer);

        if (! $element instanceof HtmlElement) {
            throw new InvalidArgumentException('Expected the base code block renderer to return an HtmlElement.');
        }

        return $element;
    }

    /**
     * The fence's info string, which carries the language and may carry the
     * line directives the highlighter reads, as in `php{1,3}`. It is passed on
     * whole so those directives keep working; the highlighter splits it.
     */
    protected function infoLine(FencedCode $node): ?string
    {
        $infoWords = $node->getInfoWords();

        if ($infoWords === [] || $infoWords[0] === '') {
            return null;
        }

        return Xml::escape($infoWords[0]);
    }
}
