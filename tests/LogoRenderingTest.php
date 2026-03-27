<?php

use Illuminate\Support\Facades\View;

it('renders logo using component type when configured', function () {
    // Mock a simple component
    View::addNamespace('test', __DIR__.'/stubs/views');
    
    config()->set('lemme.logo', [
        'type' => 'component',
        'component' => 'test::logo-component',
        'view' => 'lemme::partials.logo',
        'image' => null,
        'text' => null,
        'alt' => 'Logo',
        'classes' => 'h-6 text-black dark:text-white',
    ]);

    $logo = config('lemme.logo');
    
    expect($logo['type'])->toBe('component');
    expect($logo['component'])->toBe('test::logo-component');
});

it('falls back to default view when component is empty', function () {
    config()->set('lemme.logo', [
        'type' => 'component',
        'component' => '', // empty component
        'view' => 'lemme::partials.logo',
        'image' => null,
        'text' => null,
        'alt' => 'Logo',
        'classes' => 'h-6 text-black dark:text-white',
    ]);

    $logo = config('lemme.logo');
    
    expect($logo['type'])->toBe('component');
    expect($logo['component'])->toBe('');
});

it('supports component configuration via environment variable', function () {
    config()->set('lemme.logo.component', 'my-custom.logo-component');
    
    expect(config('lemme.logo.component'))->toBe('my-custom.logo-component');
});