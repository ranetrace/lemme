<?php

namespace Ranetrace\Lemme\Tests\Support;

use Illuminate\Foundation\Bootstrap\SetRequestForConsole;

/**
 * Boots the application with the documentation under `example.com/guides`.
 *
 * `routes/web.php` reads `lemme.route_prefix` while the routes file is loaded,
 * so the prefix has to be in place before the service provider boots; a
 * `config()->set()` in a test body would come too late. A prefix other than the
 * package default is used on purpose, so a url that still says `docs` is a
 * failure rather than a coincidence.
 */
trait ServesDocsOnRoutePrefix
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.url', 'http://example.com');
        $app['config']->set('lemme.subdomain', null);
        $app['config']->set('lemme.route_prefix', 'guides');
        $app['config']->set('lemme.cache.enabled', false);

        // See ServesDocsOnSubdomain: Testbench builds the console request from
        // `app.url` before defineEnvironment runs, so it is rebuilt here.
        $app->make(SetRequestForConsole::class)->bootstrap($app);
    }
}
