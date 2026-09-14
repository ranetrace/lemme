<?php

namespace Ranetrace\Lemme\Tests\Support;

use Illuminate\Foundation\Bootstrap\SetRequestForConsole;

/**
 * Boots the application with neither a subdomain nor a route prefix configured.
 *
 * This is the third layout in `routes/web.php`: with both settings empty the
 * documentation falls back to `docs` on the application's own host, and the
 * urls have to follow that fallback rather than the empty prefix.
 */
trait ServesDocsOnDefaultPrefix
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.url', 'http://example.com');
        $app['config']->set('lemme.subdomain', null);
        $app['config']->set('lemme.route_prefix', null);
        $app['config']->set('lemme.cache.enabled', false);

        // See ServesDocsOnSubdomain: Testbench builds the console request from
        // `app.url` before defineEnvironment runs, so it is rebuilt here.
        $app->make(SetRequestForConsole::class)->bootstrap($app);
    }
}
