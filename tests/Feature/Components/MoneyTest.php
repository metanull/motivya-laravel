<?php

declare(strict_types=1);

use App\View\Components\Money;
use Illuminate\Support\Facades\App;

describe('Money Blade component', function () {

    describe('French (fr_BE) locale', function () {
        beforeEach(fn () => App::setLocale('fr'));

        it('formats cents with comma decimal and euro suffix', function () {
            $component = new Money(cents: 1250);

            // fr_BE: "12,50 €" (NBSP before €)
            expect($component->formatted)->toBe("12,50\u{00A0}€");
        });

        it('formats zero cents', function () {
            $component = new Money(cents: 0);

            expect($component->formatted)->toBe("0,00\u{00A0}€");
        });

        it('formats large amounts with narrow no-break space as thousands separator', function () {
            $component = new Money(cents: 123456);

            // fr_BE uses narrow NBSP (\u202F) as thousands separator
            expect($component->formatted)->toBe("1\u{202F}234,56\u{00A0}€");
        });

        it('formats single digit cents', function () {
            $component = new Money(cents: 5);

            expect($component->formatted)->toBe("0,05\u{00A0}€");
        });

        it('formats negative amounts', function () {
            $component = new Money(cents: -750);

            expect($component->formatted)->toBe("-7,50\u{00A0}€");
        });
    });

    describe('English (en_GB) locale', function () {
        beforeEach(fn () => App::setLocale('en'));

        it('formats cents with dot decimal and euro prefix', function () {
            $component = new Money(cents: 1250);

            // en_GB: "€12.50"
            expect($component->formatted)->toBe('€12.50');
        });

        it('formats large amounts with comma thousands separator', function () {
            $component = new Money(cents: 123456);

            expect($component->formatted)->toBe('€1,234.56');
        });

        it('formats negative amounts', function () {
            $component = new Money(cents: -750);

            expect($component->formatted)->toBe('-€7.50');
        });
    });

    describe('Dutch (nl_BE) locale', function () {
        beforeEach(fn () => App::setLocale('nl'));

        it('formats cents with comma decimal and euro prefix with space', function () {
            $component = new Money(cents: 1250);

            // nl_BE: "€ 12,50" (NBSP after €)
            expect($component->formatted)->toBe("€\u{00A0}12,50");
        });

        it('formats large amounts with dot thousands separator', function () {
            $component = new Money(cents: 123456);

            expect($component->formatted)->toBe("€\u{00A0}1.234,56");
        });

        it('formats negative amounts', function () {
            $component = new Money(cents: -750);

            expect($component->formatted)->toBe("€\u{00A0}-7,50");
        });
    });

    describe('locale override', function () {
        it('uses explicit locale instead of app locale', function () {
            App::setLocale('fr');

            $component = new Money(cents: 1250, locale: 'en');

            expect($component->formatted)->toBe('€12.50');
        });
    });

    describe('Blade rendering', function () {
        it('renders the component with correct markup', function () {
            App::setLocale('nl');
            $view = $this->blade('<x-money :cents="1250" />');

            $view->assertSee("€\u{00A0}12,50", false);
            $view->assertSee('whitespace-nowrap', false);
        });

        it('accepts additional html attributes', function () {
            $view = $this->blade('<x-money :cents="1250" class="text-green-600" />');

            $view->assertSee('text-green-600', false);
        });
    });
});
