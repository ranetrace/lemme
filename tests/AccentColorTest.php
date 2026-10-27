<?php

use Ranetrace\Lemme\Support\AccentColor;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

beforeEach(function () {
    $this->docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $this->docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    config()->set('lemme.route_prefix', 'docs');
    config()->set('lemme.subdomain', null);

    $this->docs->file('page.md', "---\ntitle: My Page\n---\n# My Page\n");
});

afterEach(function () {
    $this->docs?->cleanup();
});

it('emits no accent style by default', function () {
    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertSee('My Page');
    $response->assertDontSee('--color-lemme-accent', false);
});

it('sets the accent on :root from a single color, which covers dark mode too', function () {
    config()->set('lemme.accent_color', 'oklch(42% 0.070 170)');

    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertSee(':root { --color-lemme-accent: oklch(42% 0.070 170); }', false);
    $response->assertDontSee(':root.dark', false);
});

it('emits a light and a dark rule from the array form', function () {
    config()->set('lemme.accent_color', [
        'light' => 'oklch(42% 0.070 170)',
        'dark' => 'oklch(67% 0.105 170)',
    ]);

    $response = $this->get('/docs/page');

    $response->assertOk();
    $response->assertSeeInOrder([
        'vendor/lemme/app.css',
        ':root { --color-lemme-accent: oklch(42% 0.070 170); }',
        ':root.dark { --color-lemme-accent: oklch(67% 0.105 170); }',
    ], false);
});

it('emits both rules from the array form even when the values match', function () {
    expect(AccentColor::resolve(['light' => '#fff', 'dark' => '#fff'])->css())
        ->toBe(":root { --color-lemme-accent: #fff; }\n:root.dark { --color-lemme-accent: #fff; }");
});

it('accepts every supported color form', function (string $color) {
    expect(AccentColor::resolve($color)->light)->toBe($color);
})->with([
    '#abc',
    '#abcd',
    '#aabbcc',
    '#aabbccdd',
    '#AABBCC',
    'rgb(255 0 0)',
    'rgba(255, 0, 0, 0.5)',
    'hsl(120deg 50% 50% / 50%)',
    'hsla(120, 50%, 50%, .5)',
    'hwb(120 10% 10%)',
    'lab(50% 40 -20)',
    'lch(50% 40 120)',
    'oklab(0.5 0.1 -0.1)',
    'oklch(42% 0.070 170)',
    'color(display-p3 1 0 0)',
    'rgb(+1e2 0 0)',
    'RGB(255 0 0)',
    'OKLCH(42% 0.070 170)',
    'rebeccapurple',
    'RebeccaPurple',
]);

it('trims whitespace around a value before validating it', function (string $color) {
    expect(AccentColor::resolve($color)->light)->toBe('oklch(42% 0.070 170)');
})->with([
    'spaces' => '  oklch(42% 0.070 170)  ',
    'newlines' => "\noklch(42% 0.070 170)\n",
    'tabs' => "\toklch(42% 0.070 170)\t",
]);

it('trims each value of the array form', function () {
    $accent = AccentColor::resolve(['light' => ' #fff ', 'dark' => "#000\n"]);

    expect($accent->light)->toBe('#fff')
        ->and($accent->dark)->toBe('#000');
});

it('rejects a value that is not plainly a color', function (mixed $value, string $shown) {
    expect(fn () => AccentColor::resolve($value))->toThrow(
        InvalidArgumentException::class,
        "The configured lemme.accent_color [{$shown}]",
    );
})->with([
    'a rule break-out' => ['red;}body{display:none', 'red;}body{display:none'],
    'a closing style tag' => ['</style><script>alert(1)</script>', '</style><script>alert(1)</script>'],
    'url()' => ['url(https://x)', 'url(https://x)'],
    'var()' => ['var(--x)', 'var(--x)'],
    'a nested function' => ['rgb(calc(1) 2 3)', 'rgb(calc(1) 2 3)'],
    'expression()' => ['expression(alert(1))', 'expression(alert(1))'],
    'an empty string' => ['', ''],
    'only whitespace' => ['   ', '   '],
    'an integer' => [123, '123'],
    'a boolean' => [true, 'true'],
    'a list' => [['#fff', '#000'], '["#fff","#000"]'],
    'an array missing dark' => [['light' => '#fff'], '{"light":"#fff"}'],
    'an array missing light' => [['dark' => '#000'], '{"dark":"#000"}'],
    'an array with an extra key' => [['light' => '#fff', 'dark' => '#000', 'extra' => 'x'], '{"light":"#fff","dark":"#000","extra":"x"}'],
    'an empty array' => [[], '[]'],
]);

it('rejects an invalid value inside the array form by its key', function (array $value, string $message) {
    expect(fn () => AccentColor::resolve($value))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'light' => [['light' => 'red;}', 'dark' => '#000'], 'The configured lemme.accent_color.light [red;}]'],
    'dark' => [['light' => '#fff', 'dark' => 'var(--x)'], 'The configured lemme.accent_color.dark [var(--x)]'],
    'non-string dark' => [['light' => '#fff', 'dark' => 0], 'The configured lemme.accent_color.dark [0]'],
]);

it('rejects values that are close to a color but not one', function (string $value) {
    expect(fn () => AccentColor::resolve($value))->toThrow(InvalidArgumentException::class, 'lemme.accent_color');
})->with([
    'uppercase url()' => ['URL(https://x)'],
    'uppercase var()' => ['VAR(--x)'],
    'a smuggled closing parenthesis' => ['rgb(1 2 3))'],
    'a parenthesis smuggled mid-value' => ['rgb(1) 2 3)'],
    'a second declaration after a color' => ['rgb(1 2 3); color: red'],
    'a newline inside a value' => ["rgb(1\n2 3)"],
    'a newline between two words' => ["red\nblue"],
    'a tab inside a value' => ["rgb(1\t2 3)"],
    'a trailing newline then markup' => ["#fff\n</style>"],
    'a double-quoted value' => ['"#fff"'],
    'a single-quoted value' => ["'red'"],
    'a quote inside a function' => ['rgb("1" 2 3)'],
    'a backslash escape' => ['r\\65 d'],
    'a CSS comment' => ['red/**/'],
    'a cyrillic lookalike keyword' => ['rеd'],
    'a fullwidth parenthesis' => ['rgb（1 2 3)'],
    'a non-breaking space inside a function' => ["rgb(1\u{00A0}2 3)"],
    'an unknown function' => ['foo(1 2 3)'],
    'an empty function' => ['rgb()'],
    'a hex of the wrong length' => ['#abcde'],
    'a hex with a non-hex digit' => ['#ggg'],
    'a hex without its hash' => ['fff000'],
    'a keyword with a digit' => ['red1'],
    'a keyword with a hyphen' => ['-webkit-red'],
    'a null byte' => ["red\0blue"],
    'an important flag' => ['red !important'],
]);

it('fails the page rather than rendering it with a rejected accent', function () {
    $this->withoutExceptionHandling();
    config()->set('lemme.accent_color', 'red;}body{display:none');

    $this->get('/docs/page');
})->throws(InvalidArgumentException::class, 'lemme.accent_color [red;}body{display:none]');
