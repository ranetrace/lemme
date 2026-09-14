<?php

namespace Ranetrace\Lemme\Tests\Support;

use Ranetrace\Lemme\Support\MarkdownRenderer;

/**
 * Stands in for the renderer a host application swaps in through
 * `lemme.markdown.renderer`.
 *
 * It marks everything it renders, which is how a test can tell the configured
 * class really rendered the page instead of Lemme's own default.
 */
class StubMarkdownRenderer extends MarkdownRenderer
{
    public const MARKER = '<!-- rendered by the host application -->';

    /**
     * The same mark, as text rather than as a comment.
     *
     * The search index is the rendered HTML with its tags stripped, and
     * strip_tags takes an HTML comment with them, so a test asking whether the
     * index was built by this class needs a mark that survives that.
     */
    public const TEXT_MARKER = 'host-renderer-marker';

    public function toHtml(string $markdown): string
    {
        return self::MARKER.'<p>'.self::TEXT_MARKER.'</p>'.parent::toHtml($markdown);
    }
}
