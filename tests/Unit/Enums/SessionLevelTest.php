<?php

declare(strict_types=1);

use App\Enums\SessionLevel;

describe('SessionLevel', function () {

    it('has the correct backed string values', function () {
        expect(SessionLevel::Beginner->value)->toBe('beginner');
        expect(SessionLevel::Intermediate->value)->toBe('intermediate');
        expect(SessionLevel::Advanced->value)->toBe('advanced');
    });

    it('can be created from a string value', function () {
        expect(SessionLevel::from('beginner'))->toBe(SessionLevel::Beginner);
        expect(SessionLevel::from('intermediate'))->toBe(SessionLevel::Intermediate);
        expect(SessionLevel::from('advanced'))->toBe(SessionLevel::Advanced);
    });

    it('lists all three cases', function () {
        expect(SessionLevel::cases())->toHaveCount(3);
    });

    it('returns a localized label for each case', function () {
        app()->setLocale('en');

        expect(SessionLevel::Beginner->label())->toBe('Beginner');
        expect(SessionLevel::Intermediate->label())->toBe('Intermediate');
        expect(SessionLevel::Advanced->label())->toBe('Advanced');
    });

    it('returns French labels when locale is fr', function () {
        app()->setLocale('fr');

        expect(SessionLevel::Beginner->label())->toBe('Débutant');
        expect(SessionLevel::Intermediate->label())->toBe('Intermédiaire');
        expect(SessionLevel::Advanced->label())->toBe('Avancé');
    });

});
