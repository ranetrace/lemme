<?php

namespace Ranetrace\Lemme\Views\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class NavGroup extends Component
{
    public function __construct(
        public array $group,
        public string $currentPageSlug,
    ) {}

    public function render(): View
    {
        return view('lemme::components.nav-group');
    }
}
