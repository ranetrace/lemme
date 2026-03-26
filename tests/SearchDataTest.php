<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

it('builds searchable content from markdown', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);

    $docs->file('search.md', <<<'MD'
```php
echo "Hi";
```

# Heading

**Bold** _Italic_ `Code` [Link](https://example.com)
MD);

    $data = Lemme::getSearchData();
    $entry = collect($data)->firstWhere('slug', 'search');
    expect($entry)->not->toBeNull()
        ->and($entry['content'])->toContain('Heading')
        ->and($entry['content'])->toContain('Bold')
        ->and($entry['content'])->toContain('Code')
        ->and($entry['content'])->not->toContain('[');
});
