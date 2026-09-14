<?php

namespace Ranetrace\Lemme\Tests\Support;

use Illuminate\Foundation\Bootstrap\SetRequestForConsole;

/**
 * Boots the application with the documentation on `docs.example.com`.
 *
 * `routes/web.php` reads `lemme.subdomain` while the routes file is loaded, and
 * the domain it builds reads `app.url` at the same moment, so both have to be in
 * place before the service provider boots; a `config()->set()` in a test body
 * would come too late. Applied with `uses()` at the top of a test file, the way
 * DisablesMarkdownResponses is.
 */
trait ServesDocsOnSubdomain
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.url', 'http://example.com');
        $app['config']->set('lemme.subdomain', 'docs');
        $app['config']->set('lemme.route_prefix', null);
        $app['config']->set('lemme.cache.enabled', false);

        // Testbench builds the console request from `app.url` before it calls
        // defineEnvironment, so the request still points at the default host and
        // every generated url would take its root from there. Rebuilding it here
        // is what makes the test the situation it describes: an index built by an
        // artisan command, where the only host the application knows is `app.url`.
        $app->make(SetRequestForConsole::class)->bootstrap($app);
    }
}
