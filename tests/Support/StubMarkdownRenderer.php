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

    public function toHtml(string $markdown): string
    {
        return self::MARKER.parent::toHtml($markdown);
    }
}
