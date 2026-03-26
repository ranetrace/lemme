<?php

namespace Ranetrace\Lemme\Views\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class NavLink extends Component
{
    public function __construct(
        public string $href,
        public string $title,
        public bool $active = false,
        public bool $showActiveIndicator = true,
        public bool $isReactive = false,
    ) {}

    public function render(): View
    {
        return view('lemme::components.nav-link');
    }
}
