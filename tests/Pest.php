<?php

use Ranetrace\Lemme\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest Configuration
|--------------------------------------------------------------------------
|
| This file registers the base TestCase for all tests. Individual tests can
| still leverage $this just like in PHPUnit. Additional expectations or
| helpers can be added here later.
|
*/

uses(TestCase::class)->in(__DIR__);

// Optional: compact printer for cleaner CI output (can be toggled off locally)
if (env('CI')) {
    pest()->printer()->compact();
}
