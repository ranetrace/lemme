<?php

namespace Ranetrace\Lemme\Views\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class TableOfContentsNavigation extends Component
{
    public function __construct() {}

    public function render(): View
    {
        return view('lemme::components.table-of-contents-navigation');
    }
}
