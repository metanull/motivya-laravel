<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Money extends Component
{
    public readonly string $formatted;

    /**
     * Create a new component instance.
     *
     * @param  int  $cents  Amount in EUR cents
     */
    public function __construct(public readonly int $cents)
    {
        $euros = $cents / 100;

        // Belgian format: comma decimal separator, dot thousands separator
        $this->formatted = '€'."\u{00A0}".number_format($euros, 2, ',', '.');
    }

    public function render(): View|Closure|string
    {
        return view('components.money');
    }
}
