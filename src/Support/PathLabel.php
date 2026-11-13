<?php

namespace Ranetrace\Lemme\Support;

use Illuminate\Support\Str;

/**
 * The label a directory or file name stands for: a group title in the
 * navigation, a category in the search results, and the title of a page that
 * names none in its front matter.
 *
 * The name is the only place a writer can spell that label, so its casing is
 * kept as written and only the first letter is raised. `3_PHP-SDK` reads
 * "PHP SDK" and `1_getting-started` reads "Getting started"; lowercasing the
 * name and capitalising every word would turn both into title case and the
 * acronyms into "Php Sdk".
 */
class PathLabel
{
    public static function for(string $name): string
    {
        $withoutOrderPrefix = (string) preg_replace('/^\d+[-_]/', '', $name);

        return Str::ucfirst(str_replace(['-', '_'], ' ', $withoutOrderPrefix));
    }
}
