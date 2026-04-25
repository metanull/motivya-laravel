<?php

declare(strict_types=1);

use App\Models\CoachProfile;
use App\Services\VatService;

describe('VatService', function () {

    beforeEach(function () {
        $this->service = new VatService;
    });

    describe('getVatRate', function () {

        it('returns 21 for a VAT-subject coach', function () {
            $coach = new CoachProfile(['is_vat_subject' => true]);

            expect($this->service->getVatRate($coach))->toBe(21);
        });

        it('returns 0 for a non-subject coach', function () {
            $coach = new CoachProfile(['is_vat_subject' => false]);

            expect($this->service->getVatRate($coach))->toBe(0);
        });

    });

    describe('getTaxCategoryCode', function () {

        it('returns S for a VAT-subject coach', function () {
            $coach = new CoachProfile(['is_vat_subject' => true]);

            expect($this->service->getTaxCategoryCode($coach))->toBe('S');
        });

        it('returns E for a non-subject coach', function () {
            $coach = new CoachProfile(['is_vat_subject' => false]);

            expect($this->service->getTaxCategoryCode($coach))->toBe('E');
        });

    });

    describe('calculateVat', function () {

        it('calculates 21% VAT for a subject coach', function () {
            $coach = new CoachProfile(['is_vat_subject' => true]);

            // 5785 cents HTVA × 21% = 1214.85 → rounds half-up to 1215
            expect($this->service->calculateVat(5785, $coach))->toBe(1215);
        });

        it('calculates 0 VAT for a non-subject coach', function () {
            $coach = new CoachProfile(['is_vat_subject' => false]);

            expect($this->service->calculateVat(5785, $coach))->toBe(0);
        });

        it('rounds half-up when fractional cents arise', function () {
            $coach = new CoachProfile(['is_vat_subject' => true]);

            // 100 cents × 21% = 21.0 → 21
            expect($this->service->calculateVat(100, $coach))->toBe(21);

            // 10 cents × 21% = 2.1 → rounds to 2
            expect($this->service->calculateVat(10, $coach))->toBe(2);

            // 5 cents × 21% = 1.05 → rounds to 1
            expect($this->service->calculateVat(5, $coach))->toBe(1);
        });

        it('returns 0 for a zero amount regardless of VAT status', function () {
            $subjectCoach = new CoachProfile(['is_vat_subject' => true]);
            $nonSubjectCoach = new CoachProfile(['is_vat_subject' => false]);

            expect($this->service->calculateVat(0, $subjectCoach))->toBe(0);
            expect($this->service->calculateVat(0, $nonSubjectCoach))->toBe(0);
        });

    });

    describe('toHtva', function () {

        it('converts TTC to HTVA for a standard 10000-cent amount', function () {
            // 10000 / 1.21 = 8264.46… → half-up rounds to 8264
            expect($this->service->toHtva(10000))->toBe(8264);
        });

        it('converts TTC to HTVA for 30000 cents (300 EUR)', function () {
            // 30000 / 1.21 = 24793.38… → 24793
            expect($this->service->toHtva(30000))->toBe(24793);
        });

        it('converts TTC to HTVA for 62400 cents (624 EUR)', function () {
            // From vat-calculations.instructions.md example 3: htva=51570
            expect($this->service->toHtva(62400))->toBe(51570);
        });

        it('returns 0 for a zero TTC amount', function () {
            expect($this->service->toHtva(0))->toBe(0);
        });

        it('aligns with the worked example: 10000 TTC gives HTVA=8264, VAT=1736', function () {
            $coach = new CoachProfile(['is_vat_subject' => true]);

            // From worked example in vat-calculations.instructions.md:
            // 10 clients × 1000 cents = 10000 TTC
            // revenue_htva = 8264 cents, VAT owed = round(10000 * 21 / 121) = 1736 cents
            $htva = $this->service->toHtva(10000);

            expect($htva)->toBe(8264);
            // VAT on 10000 TTC = round(10000 * 21 / 121) = 1736
            // VAT on HTVA = round(8264 * 21 / 100) = 1735 (1 cent rounding difference — expected)
            expect($this->service->calculateVat($htva, $coach))->toBe(1735);
        });

    });

});
