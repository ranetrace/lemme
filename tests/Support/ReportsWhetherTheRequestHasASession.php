<?php

namespace Ranetrace\Lemme\Tests\Support;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stands in for a middleware an application appended to its `web` group.
 */
class ReportsWhetherTheRequestHasASession
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Added-By-The-Application', $request->hasSession() ? 'with a session' : 'without a session');

        return $response;
    }
}
