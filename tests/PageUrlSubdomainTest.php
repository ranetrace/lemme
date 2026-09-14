<?php

use Ranetrace\Lemme\Facades\Lemme;
use Ranetrace\Lemme\Tests\Support\DocsFactory;
use Ranetrace\Lemme\Tests\Support\ServesDocsOnSubdomain;

uses(ServesDocsOnSubdomain::class);

it('links a page on the documentation subdomain', function () {
    // The urls used to be built with `url()`, which only ever knows the
    // application's own host, so a subdomain install sent every navigation link
    // and every search result to `example.com/getting-started`, where nothing
    // serves documentation.
    expect(Lemme::getPageUrl('getting-started'))->toBe('http://docs.example.com/getting-started');
});

it('links the home page to the home route rather than to an empty slug', function () {
    // `index.md` has the empty string for a slug, and the docs root is its own
    // route, so the home page is not the page route with nothing in it.
    expect(Lemme::getPageUrl(''))->toBe('http://docs.example.com');
});

it('keeps the slashes in a nested slug unencoded', function () {
    // Slugs from a frontmatter `slug:` carry their directories, and a `%2F` in
    // the path matches no route at all.
    expect(Lemme::getPageUrl('getting-started/install'))->toBe('http://docs.example.com/getting-started/install');
});

it('indexes search results on the documentation subdomain', function () {
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());

    $docs->markdown('installation.md', 'Installation', 'How to install the package.');

    try {
        $entry = collect(Lemme::getSearchData())->firstWhere('slug', 'installation');

        expect($entry['url'])->toBe('http://docs.example.com/installation');
    } finally {
        $docs->cleanup();
    }
});
