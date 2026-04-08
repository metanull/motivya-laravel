<?php

declare(strict_types=1);

use App\Enums\SessionStatus;

describe('SessionStatus', function () {

    it('has the correct backed string values', function () {
        expect(SessionStatus::Draft->value)->toBe('draft');
        expect(SessionStatus::Published->value)->toBe('published');
        expect(SessionStatus::Confirmed->value)->toBe('confirmed');
        expect(SessionStatus::Completed->value)->toBe('completed');
        expect(SessionStatus::Cancelled->value)->toBe('cancelled');
    });

    it('can be created from a string value', function () {
        expect(SessionStatus::from('draft'))->toBe(SessionStatus::Draft);
        expect(SessionStatus::from('published'))->toBe(SessionStatus::Published);
        expect(SessionStatus::from('confirmed'))->toBe(SessionStatus::Confirmed);
        expect(SessionStatus::from('completed'))->toBe(SessionStatus::Completed);
        expect(SessionStatus::from('cancelled'))->toBe(SessionStatus::Cancelled);
    });

    it('lists all five cases', function () {
        expect(SessionStatus::cases())->toHaveCount(5);
    });

    it('returns a localized label for each case', function () {
        app()->setLocale('en');

        expect(SessionStatus::Draft->label())->toBe('Draft');
        expect(SessionStatus::Published->label())->toBe('Published');
        expect(SessionStatus::Confirmed->label())->toBe('Confirmed');
        expect(SessionStatus::Completed->label())->toBe('Completed');
        expect(SessionStatus::Cancelled->label())->toBe('Cancelled');
    });

    it('returns French labels when locale is fr', function () {
        app()->setLocale('fr');

        expect(SessionStatus::Draft->label())->toBe('Brouillon');
        expect(SessionStatus::Published->label())->toBe('Publiée');
        expect(SessionStatus::Confirmed->label())->toBe('Confirmée');
        expect(SessionStatus::Completed->label())->toBe('Terminée');
        expect(SessionStatus::Cancelled->label())->toBe('Annulée');
    });

});
