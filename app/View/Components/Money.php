<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use NumberFormatter;

final class Money extends Component
{
    public readonly string $formatted;

    private const LOCALE_MAP = [
        'fr' => 'fr_BE',
        'en' => 'en_GB',
        'nl' => 'nl_BE',
    ];

    /**
     * Create a new component instance.
     *
     * @param  int  $cents  Amount in EUR cents
     * @param  string|null  $locale  Override locale (fr, en, nl); defaults to app locale
     */
    public function __construct(public readonly int $cents, ?string $locale = null)
    {
        $locale ??= app()->getLocale();
        $ietfLocale = self::LOCALE_MAP[$locale] ?? self::LOCALE_MAP['fr'];

        $formatter = new NumberFormatter($ietfLocale, NumberFormatter::CURRENCY);
        $this->formatted = $formatter->formatCurrency($cents / 100, 'EUR');
    }

    public function render(): View|Closure|string
    {
        return view('components.money');
    }
}
