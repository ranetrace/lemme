<?php

namespace Ranetrace\Lemme\Views\Components;

use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

class MobileNavigation extends Component
{
    public function __construct(
        public Collection $navigation = new Collection,
        public string $currentPageSlug = '',
    ) {}

    public function render(): View
    {
        return view('lemme::components.mobile-navigation');
    }
}
