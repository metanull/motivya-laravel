<?php

declare(strict_types=1);

use App\Enums\ActivityType;

describe('ActivityType', function () {

    it('has the correct backed string values', function () {
        expect(ActivityType::Yoga->value)->toBe('yoga');
        expect(ActivityType::Strength->value)->toBe('strength');
        expect(ActivityType::Running->value)->toBe('running');
        expect(ActivityType::Cardio->value)->toBe('cardio');
        expect(ActivityType::Pilates->value)->toBe('pilates');
        expect(ActivityType::Outdoor->value)->toBe('outdoor');
        expect(ActivityType::Boxing->value)->toBe('boxing');
        expect(ActivityType::Dance->value)->toBe('dance');
        expect(ActivityType::Padel->value)->toBe('padel');
        expect(ActivityType::Tennis->value)->toBe('tennis');
    });

    it('can be created from a string value', function () {
        expect(ActivityType::from('yoga'))->toBe(ActivityType::Yoga);
        expect(ActivityType::from('strength'))->toBe(ActivityType::Strength);
        expect(ActivityType::from('running'))->toBe(ActivityType::Running);
        expect(ActivityType::from('boxing'))->toBe(ActivityType::Boxing);
        expect(ActivityType::from('padel'))->toBe(ActivityType::Padel);
        expect(ActivityType::from('tennis'))->toBe(ActivityType::Tennis);
    });

    it('lists all ten cases', function () {
        expect(ActivityType::cases())->toHaveCount(10);
    });

    it('returns a localized label for each case', function () {
        app()->setLocale('en');

        expect(ActivityType::Yoga->label())->toBe('Yoga');
        expect(ActivityType::Strength->label())->toBe('Strength');
        expect(ActivityType::Running->label())->toBe('Running');
        expect(ActivityType::Cardio->label())->toBe('Cardio');
        expect(ActivityType::Pilates->label())->toBe('Pilates');
        expect(ActivityType::Outdoor->label())->toBe('Outdoor');
        expect(ActivityType::Boxing->label())->toBe('Boxing');
        expect(ActivityType::Dance->label())->toBe('Dance');
        expect(ActivityType::Padel->label())->toBe('Padel');
        expect(ActivityType::Tennis->label())->toBe('Tennis');
    });

    it('returns French labels when locale is fr', function () {
        app()->setLocale('fr');

        expect(ActivityType::Yoga->label())->toBe('Yoga');
        expect(ActivityType::Strength->label())->toBe('Musculation');
        expect(ActivityType::Running->label())->toBe('Course à pied');
        expect(ActivityType::Boxing->label())->toBe('Boxe');
        expect(ActivityType::Dance->label())->toBe('Danse');
    });

});
