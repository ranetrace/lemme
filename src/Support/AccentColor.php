<?php

namespace Ranetrace\Lemme\Support;

use InvalidArgumentException;

/**
 * The accent color configured under `lemme.accent_color`, checked and split
 * into the value for each theme.
 *
 * The value is printed inside a `<style>` element, so it is a CSS injection
 * surface: anything that is not plainly a color is rejected by name rather
 * than escaped. A functional color may not nest parentheses, which keeps out
 * `url()`, `var()` and `calc()` along with anything that would close the rule.
 */
class AccentColor
{
    private const HEX = '/\A#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})\z/i';

    private const FUNCTIONAL = '/\A(?:rgba?|hsla?|hwb|lab|lch|oklab|oklch|color)\([0-9a-z.%,\/+\- ]+\)\z/i';

    private const KEYWORD = '/\A[a-z]+\z/i';

    /**
     * A null dark value means the light value covers both themes, since the
     * `:root` rule applies under `.dark` as well.
     */
    public function __construct(
        public readonly string $light,
        public readonly ?string $dark = null,
    ) {}

    /**
     * Resolve `lemme.accent_color`, or null when none is configured and the
     * shipped accent stays.
     */
    public static function fromConfig(): ?self
    {
        return self::resolve(config('lemme.accent_color'));
    }

    public static function resolve(mixed $value): ?self
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $keys = array_keys($value);
            sort($keys);

            if ($keys !== ['dark', 'light']) {
                throw self::rejected($value, 'must be a CSS color string or an array with exactly the keys [light] and [dark]');
            }

            return new self(
                self::color($value['light'], 'accent_color.light'),
                self::color($value['dark'], 'accent_color.dark'),
            );
        }

        return new self(self::color($value, 'accent_color'));
    }

    /**
     * The declarations for the page's `<style>` element.
     */
    public function css(): string
    {
        $css = ":root { --color-lemme-accent: {$this->light}; }";

        if ($this->dark !== null) {
            $css .= "\n:root.dark { --color-lemme-accent: {$this->dark}; }";
        }

        return $css;
    }

    private static function color(mixed $value, string $key): string
    {
        if (! is_string($value)) {
            throw self::rejected($value, 'must be a CSS color string', $key);
        }

        $color = trim($value);

        foreach ([self::HEX, self::FUNCTIONAL, self::KEYWORD] as $pattern) {
            if (preg_match($pattern, $color) === 1) {
                return $color;
            }
        }

        throw self::rejected($value, 'must be a hex color, a functional color without nested functions, or a named color', $key);
    }

    private static function rejected(mixed $value, string $reason, string $key = 'accent_color'): InvalidArgumentException
    {
        $shown = is_string($value) ? $value : (string) json_encode($value);

        return new InvalidArgumentException("The configured lemme.{$key} [{$shown}] {$reason}.");
    }
}
