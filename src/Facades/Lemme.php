<?php

namespace Ranetrace\Lemme\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ranetrace\Lemme\Lemme
 */
class Lemme extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ranetrace\Lemme\Lemme::class;
    }
}
