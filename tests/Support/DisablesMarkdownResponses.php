<?php

namespace Ranetrace\Lemme\Tests\Support;

/**
 * Boots the application with Markdown responses turned off.
 *
 * The `.md` twin routes are registered while the routes file is loaded, so the
 * feature flag has to be set before the service provider boots; a runtime
 * `config()->set()` inside a test body would come too late to keep the routes
 * out of the router. Applied with `uses()` at the top of a test file.
 */
trait DisablesMarkdownResponses
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('lemme.markdown.enabled', false);
    }
}
