<?php

namespace Ranetrace\Lemme\Tests\Support;

use Illuminate\Foundation\Bootstrap\SetRequestForConsole;

/**
 * Boots the application with the documentation under `/docs` on
 * `example.com` only, while the application itself lives on
 * `app.example.com`, the shape of a site that serves an app host and a public
 * host from one install.
 *
 * `routes/web.php` reads `lemme.domain` while the routes file is loaded, so it
 * has to be set before the service provider boots, as in ServesDocsOnSubdomain.
 */
trait ServesDocsOnDomain
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.url', 'http://app.example.com');
        $app['config']->set('lemme.subdomain', null);
        $app['config']->set('lemme.route_prefix', 'docs');
        $app['config']->set('lemme.domain', 'example.com');
        $app['config']->set('lemme.cache.enabled', false);
        $app['config']->set('lemme.markdown.enabled', true);

        // See ServesDocsOnSubdomain: Testbench builds the console request from
        // `app.url` before defineEnvironment runs, so it is rebuilt here.
        $app->make(SetRequestForConsole::class)->bootstrap($app);
    }
}
