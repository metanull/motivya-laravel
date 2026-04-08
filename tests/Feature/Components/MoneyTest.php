<?php

declare(strict_types=1);

use App\View\Components\Money;

describe('Money Blade component', function () {

    it('formats cents to Belgian EUR format', function () {
        $component = new Money(cents: 1250);

        expect($component->formatted)->toBe("€\u{00A0}12,50");
    });

    it('formats zero cents', function () {
        $component = new Money(cents: 0);

        expect($component->formatted)->toBe("€\u{00A0}0,00");
    });

    it('formats large amounts with dot as thousands separator', function () {
        $component = new Money(cents: 123456);

        expect($component->formatted)->toBe("€\u{00A0}1.234,56");
    });

    it('formats single digit cents correctly', function () {
        $component = new Money(cents: 5);

        expect($component->formatted)->toBe("€\u{00A0}0,05");
    });

    it('formats negative amounts', function () {
        $component = new Money(cents: -750);

        expect($component->formatted)->toBe("€\u{00A0}-7,50");
    });

    it('renders the component with correct markup', function () {
        $view = $this->blade('<x-money :cents="1250" />');

        $view->assertSee("€\u{00A0}12,50", false);
        $view->assertSee('whitespace-nowrap', false);
    });

    it('accepts additional html attributes', function () {
        $view = $this->blade('<x-money :cents="1250" class="text-green-600" />');

        $view->assertSee('text-green-600', false);
    });
});
