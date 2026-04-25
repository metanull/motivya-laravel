<?php

declare(strict_types=1);

use App\DataTransferObjects\PayoutBreakdown;
use App\Models\CoachProfile;
use App\Services\PayoutService;
use App\Services\VatService;

describe('PayoutService', function () {

    beforeEach(function () {
        $this->service = new PayoutService(new VatService());
        $this->vatSubjectCoach = new CoachProfile(['is_vat_subject' => true]);
        $this->nonSubjectCoach = new CoachProfile(['is_vat_subject' => false]);
    });

    describe('calculatePayout', function () {

        it('returns a PayoutBreakdown instance', function () {
            $result = $this->service->calculatePayout($this->vatSubjectCoach, 30000, 450);

            expect($result)->toBeInstanceOf(PayoutBreakdown::class);
        });

        it('populates revenue_ttc and revenue_htva correctly', function () {
            // 30000 TTC → htva = intdiv(30000*100+60, 121) = 24793
            $result = $this->service->calculatePayout($this->vatSubjectCoach, 30000, 450);

            expect($result->revenue_ttc)->toBe(30000);
            expect($result->revenue_htva)->toBe(24793);
        });

        it('populates stripe_fee from the input', function () {
            $result = $this->service->calculatePayout($this->vatSubjectCoach, 30000, 450);

            expect($result->stripe_fee)->toBe(450);
        });

        /**
         * Jean Freemium — January (doc/UseCases.md example).
         *
         * 25 payments × 12€ = 300€ TTC = 30000 cents
         * Stripe fees: 1.5% × 30000 = 450 cents
         *
         * HTVA-based calculation:
         *   revenue_htva = intdiv(30000*100+60, 121) = 24793
         *   Freemium(30%): commission=7438, payout=17355, net=17355-450-0    = 16905
         *   Active(20%):   commission=4959, payout=19834, net=19834-450-3900 = 15484
         *   Premium(10%):  commission=2479, payout=22314, net=22314-450-7900 = 13964
         *   → Best plan: Freemium (net 16905)
         */
        it('Jean Freemium January — picks Freemium for low revenue (300 EUR TTC)', function () {
            $result = $this->service->calculatePayout($this->vatSubjectCoach, 30000, 450);

            expect($result->applied_plan)->toBe('freemium');
            expect($result->commission_rate)->toBe(30);
            expect($result->subscription_fee)->toBe(0);
            expect($result->commission_amount)->toBe(7438);
            expect($result->coach_payout)->toBe(16905);
            expect($result->platform_margin)->toBe(7438);
        });

        /**
         * Jean Freemium — February (doc/UseCases.md example).
         *
         * 48 payments × 13€ = 624€ TTC = 62400 cents
         * Stripe fees: 1.5% × 62400 = 936 cents
         *
         * HTVA-based calculation (from vat-calculations.instructions.md example 3):
         *   revenue_htva = 51570
         *   Freemium: net = 36099 - 936 - 0    = 35163
         *   Active:   net = 41256 - 936 - 3900  = 36420
         *   Premium:  net = 46413 - 936 - 7900  = 37577
         *   → Best plan: Premium (net 37577)
         */
        it('Jean Freemium February — picks Premium for higher revenue (624 EUR TTC)', function () {
            $result = $this->service->calculatePayout($this->vatSubjectCoach, 62400, 936);

            expect($result->applied_plan)->toBe('premium');
            expect($result->revenue_htva)->toBe(51570);
            expect($result->commission_rate)->toBe(10);
            expect($result->commission_amount)->toBe(5157);
            expect($result->coach_payout)->toBe(37577);
        });

        /**
         * Marie Active — January (doc/UseCases.md example).
         *
         * 25 payments × 12€ = 300€ TTC = 30000 cents, fees=450
         * Freemium net: 16905 > Active net: 15484 → Freemium stays better.
         */
        it('Marie Active January — stays Freemium when volume is low (300 EUR TTC)', function () {
            $result = $this->service->calculatePayout($this->vatSubjectCoach, 30000, 450);

            expect($result->applied_plan)->toBe('freemium');
            expect($result->coach_payout)->toBeGreaterThan(0);
        });

        /**
         * Marie Active — February (doc/UseCases.md example).
         *
         * 48 payments × 13€ = 624€ TTC = 62400 cents, fees=936
         * Active net (36420) > Freemium net (35163) → Active or better is chosen.
         * Premium net (37577) is best overall.
         */
        it('Marie Active February — Active beats Freemium for higher volume (624 EUR TTC)', function () {
            $result = $this->service->calculatePayout($this->vatSubjectCoach, 62400, 936);

            // Active (36420) and Premium (37577) both beat Freemium (35163)
            expect($result->coach_payout)->toBeGreaterThan(35163);
            expect($result->applied_plan)->not->toBe('freemium');
        });

        /**
         * Loïc Premium — March (doc/UseCases.md example).
         *
         * 160 payments × 11€ = 1760€ TTC = 176000 cents
         * Stripe fees: 1.5% × 176000 = 2640 cents
         *
         * HTVA-based calculation:
         *   revenue_htva = intdiv(176000*100+60, 121) = 145455
         *   Freemium(30%): commission=43637, payout=101818, net=101818-2640-0    = 99178
         *   Active(20%):   commission=29091, payout=116364, net=116364-2640-3900 = 109824
         *   Premium(10%):  commission=14546, payout=130909, net=130909-2640-7900 = 120369
         *   → Best plan: Premium (net 120369)
         */
        it('Loïc Premium March — picks Premium for high revenue (1760 EUR TTC)', function () {
            $result = $this->service->calculatePayout($this->vatSubjectCoach, 176000, 2640);

            expect($result->applied_plan)->toBe('premium');
            expect($result->revenue_htva)->toBe(145455);
            expect($result->commission_rate)->toBe(10);
            expect($result->commission_amount)->toBe(14546);
            expect($result->coach_payout)->toBe(120369);
        });

        it('preserves the same platform_margin for subject and non-subject coaches at identical revenue', function () {
            // The HTVA formula ensures Motivya's margin is the same regardless of VAT status
            $subjectResult = $this->service->calculatePayout($this->vatSubjectCoach, 10000, 0);
            $nonSubjectResult = $this->service->calculatePayout($this->nonSubjectCoach, 10000, 0);

            expect($subjectResult->platform_margin)->toBe($nonSubjectResult->platform_margin);
            expect($subjectResult->commission_amount)->toBe($nonSubjectResult->commission_amount);
            expect($subjectResult->applied_plan)->toBe($nonSubjectResult->applied_plan);
        });

        it('the platform_margin equals the commission_amount', function () {
            $result = $this->service->calculatePayout($this->vatSubjectCoach, 30000, 450);

            expect($result->platform_margin)->toBe($result->commission_amount);
        });

        it('handles zero revenue and zero fees', function () {
            $result = $this->service->calculatePayout($this->vatSubjectCoach, 0, 0);

            expect($result->revenue_ttc)->toBe(0);
            expect($result->revenue_htva)->toBe(0);
            expect($result->coach_payout)->toBe(0);
            expect($result->applied_plan)->toBe('freemium');
        });

    });

});
