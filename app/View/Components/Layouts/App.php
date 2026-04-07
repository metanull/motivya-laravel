<?php

declare(strict_types=1);

namespace App\View\Components\Layouts;

use Illuminate\View\Component;
use Illuminate\View\View;

final class App extends Component
{
    /**
     * Get the view that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
