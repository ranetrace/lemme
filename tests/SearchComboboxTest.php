<?php

use Livewire\Livewire;
use Ranetrace\Lemme\Livewire\SearchComponent;
use Ranetrace\Lemme\Tests\Support\DocsFactory;

/**
 * Two results, in the shape the Fuse index hands back.
 *
 * @return array<int, array<string, mixed>>
 */
function searchResultsFixture(): array
{
    return [
        [
            'title' => 'Installation Guide',
            'category' => 'Guides',
            'url' => '/docs/installation',
            'content' => 'How to install the system',
            'score' => 0.1,
        ],
        [
            'title' => 'Configuration Guide',
            'category' => 'Guides',
            'url' => '/docs/configuration',
            'content' => 'How to configure the system',
            'score' => 0.2,
        ],
    ];
}

/**
 * The rendered markup as a queryable document.
 *
 * Alpine attribute names such as `x-on:keydown.arrow-down.prevent` are rejected by
 * the HTML parser, so parse errors are collected and dropped: the elements and
 * their standard attributes survive, which is what these tests read. The Alpine
 * wiring is asserted against the raw HTML instead.
 */
function searchDocument(string $html): DOMXPath
{
    $document = new DOMDocument;

    libxml_use_internal_errors(true);
    $document->loadHTML($html, LIBXML_NOERROR);
    libxml_clear_errors();
    libxml_use_internal_errors(false);

    return new DOMXPath($document);
}

it('puts the combobox role on the search field itself', function () {
    // The role used to sit on a div wrapping the form. A combobox is the element
    // that takes the typing and keeps the focus, and a wrapper is neither.
    $xpath = searchDocument(Livewire::test(SearchComponent::class)->html());

    $comboboxes = $xpath->query('//*[@role="combobox"]');

    expect($comboboxes)->toHaveCount(1)
        ->and($comboboxes->item(0)->tagName)->toBe('input')
        ->and($comboboxes->item(0)->getAttribute('id'))->toBe('lemme-search')
        ->and($comboboxes->item(0)->getAttribute('aria-autocomplete'))->toBe('list')
        ->and($comboboxes->item(0)->getAttribute('aria-haspopup'))->toBe('listbox');
});

it('points aria-controls at the id of the results listbox', function () {
    $xpath = searchDocument(
        Livewire::test(SearchComponent::class)
            ->call('handleSearchResults', searchResultsFixture())
            ->html()
    );

    $controls = $xpath->query('//*[@role="combobox"]')->item(0)->getAttribute('aria-controls');
    $listboxes = $xpath->query('//*[@role="listbox"]');

    expect($controls)->not->toBe('')
        ->and($listboxes)->toHaveCount(1)
        ->and($listboxes->item(0)->getAttribute('id'))->toBe($controls);
});

it('renders the listbox with nothing in it rather than dropping it, so aria-controls resolves', function () {
    // An aria-controls pointing at an element that is not on the page is a broken
    // reference, so the list stays and is hidden while there is nothing to show.
    $xpath = searchDocument(Livewire::test(SearchComponent::class)->html());

    $listboxes = $xpath->query('//*[@role="listbox"]');

    expect($listboxes)->toHaveCount(1)
        ->and($listboxes->item(0)->hasAttribute('hidden'))->toBeTrue()
        ->and($xpath->query('//*[@role="option"]'))->toHaveCount(0);
});

it('reports whether results are showing through aria-expanded', function () {
    $collapsed = searchDocument(Livewire::test(SearchComponent::class)->html());

    expect($collapsed->query('//*[@role="combobox"]')->item(0)->getAttribute('aria-expanded'))
        ->toBe('false');

    $expanded = searchDocument(
        Livewire::test(SearchComponent::class)
            ->call('handleSearchResults', searchResultsFixture())
            ->html()
    );

    expect($expanded->query('//*[@role="combobox"]')->item(0)->getAttribute('aria-expanded'))
        ->toBe('true')
        ->and($expanded->query('//*[@role="listbox"]')->item(0)->hasAttribute('hidden'))
        ->toBeFalse();
});

it('marks every result as an option with its own id and a selected state', function () {
    $xpath = searchDocument(
        Livewire::test(SearchComponent::class)
            ->call('handleSearchResults', searchResultsFixture())
            ->html()
    );

    $options = $xpath->query('//*[@role="listbox"]/*[@role="option"]');

    expect($options)->toHaveCount(2);

    $ids = [];
    $selectedStates = [];

    foreach ($options as $option) {
        $ids[] = $option->getAttribute('id');
        $selectedStates[] = $option->getAttribute('aria-selected');
    }

    // The ids are what aria-activedescendant points at, so each one has to be
    // there and each one has to be its own.
    expect($ids)->toBe(['lemme-search-result-0', 'lemme-search-result-1'])
        ->and($selectedStates)->toBe(['false', 'false']);
});

it('keeps the results out of the tab order', function () {
    // Focus stays in the field and the options are reached with the arrow keys, so
    // Tab leaves the widget instead of walking the results.
    $xpath = searchDocument(
        Livewire::test(SearchComponent::class)
            ->call('handleSearchResults', searchResultsFixture())
            ->html()
    );

    $links = $xpath->query('//*[@role="option"]//a');

    expect($links)->toHaveCount(2);

    foreach ($links as $link) {
        expect($link->getAttribute('tabindex'))->toBe('-1');
    }
});

it('binds the keys that walk the options to the field', function () {
    // The package ships no JavaScript test harness, so the key handling is asserted
    // where it is declared: on the field that keeps the focus.
    Livewire::test(SearchComponent::class)
        ->assertSeeHtml('x-on:keydown.arrow-down.prevent="moveActiveOption(1)"')
        ->assertSeeHtml('x-on:keydown.arrow-up.prevent="moveActiveOption(-1)"')
        ->assertSeeHtml('x-on:keydown.home.prevent="setActiveOption(0)"')
        ->assertSeeHtml('x-on:keydown.end.prevent="setActiveOption(optionElements().length - 1)"')
        ->assertSeeHtml('x-on:keydown.enter.prevent="openActiveOption()"')
        ->assertSeeHtml('x-bind:aria-activedescendant="activeDescendantId()"');
});

it('makes a hovered result the active option instead of a second highlight', function () {
    Livewire::test(SearchComponent::class)
        ->call('handleSearchResults', searchResultsFixture())
        ->assertSeeHtml('x-on:mouseenter="activeIndex = 0"')
        ->assertSeeHtml('x-on:mouseenter="activeIndex = 1"')
        // The highlight hangs off aria-selected, so pointer and keyboard cannot
        // light two different rows.
        ->assertSeeHtml('group-aria-selected:bg-zinc-50')
        ->assertDontSeeHtml('hover:bg-zinc-50');
});

it('exposes one combobox on a rendered documentation page', function () {
    // The page carries the whole modal, so this is where a second, hardcoded
    // combobox around the form would show up again.
    $docs = DocsFactory::make();
    config()->set('lemme.docs_directory', $docs->relativePath());
    config()->set('lemme.cache.enabled', false);
    config()->set('lemme.route_prefix', 'docs');
    config()->set('lemme.subdomain', null);
    $docs->markdown('page.md', 'My Page', 'Body copy.');

    try {
        $html = $this->get('/docs/page')->assertOk()->getContent();

        $xpath = searchDocument($html);
        $comboboxes = $xpath->query('//*[@role="combobox"]');

        expect($comboboxes)->toHaveCount(1)
            ->and($comboboxes->item(0)->tagName)->toBe('input')
            ->and($comboboxes->item(0)->getAttribute('aria-expanded'))->toBe('false');
    } finally {
        $docs->cleanup();
    }
});

it('escapes the result text it renders as HTML and leaves the highlighting to the search instance', function () {
    // Both result strips are rendered with x-html, so a title or an excerpt that is
    // markup would be parsed as markup. The highlighting itself happens in the
    // browser, against the indexed content the match indices were measured on: the
    // view used to interpolate a truncated copy of that content, which moved every
    // position under those indices and spliced tag fragments into the excerpt. The
    // package ships no JavaScript test harness, so the wiring is asserted where it
    // is declared.
    $results = [
        [
            'title' => '<img src=x onerror="alert(1)">',
            'category' => 'Guides',
            'url' => '/docs/escaping',
            'content' => 'An <script>alert(1)</script> excerpt.',
            'score' => 0.1,
        ],
    ];

    $html = Livewire::test(SearchComponent::class)
        ->set('search', 'alert')
        ->call('handleSearchResults', $results)
        ->html();

    expect($html)->toContain('x-html="titleHtml(0,')
        ->and($html)->toContain('x-html="excerptHtml(0,')
        ->and($html)->not->toContain('<img src=x')
        ->and($html)->not->toContain('<script>alert(1)</script>');
});
