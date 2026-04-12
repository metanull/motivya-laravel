<?php

declare(strict_types=1);

use App\Enums\BookingStatus;

describe('BookingStatus', function () {

    it('has the correct backed string values', function () {
        expect(BookingStatus::PendingPayment->value)->toBe('pending_payment');
        expect(BookingStatus::Confirmed->value)->toBe('confirmed');
        expect(BookingStatus::Cancelled->value)->toBe('cancelled');
        expect(BookingStatus::Refunded->value)->toBe('refunded');
    });

    it('can be created from a string value', function () {
        expect(BookingStatus::from('pending_payment'))->toBe(BookingStatus::PendingPayment);
        expect(BookingStatus::from('confirmed'))->toBe(BookingStatus::Confirmed);
        expect(BookingStatus::from('cancelled'))->toBe(BookingStatus::Cancelled);
        expect(BookingStatus::from('refunded'))->toBe(BookingStatus::Refunded);
    });

    it('lists all four cases', function () {
        expect(BookingStatus::cases())->toHaveCount(4);
    });

    it('returns a localized label for each case', function () {
        app()->setLocale('en');

        foreach (BookingStatus::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    });

});
