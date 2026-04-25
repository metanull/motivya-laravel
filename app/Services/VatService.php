<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CoachProfile;

final class VatService
{
    /**
     * Returns the applicable VAT rate as a percentage integer.
     *
     * VAT-subject coaches are taxed at the standard Belgian rate of 21%.
     * Non-subject coaches operate under the franchise regime (art. 56bis CTVA) at 0%.
     */
    public function getVatRate(CoachProfile $coach): int
    {
        return $coach->is_vat_subject ? 21 : 0;
    }

    /**
     * Returns the PEPPOL tax category code for the coach.
     *
     * 'S' = Standard rate (21%) for VAT-subject coaches.
     * 'E' = Exempt (franchise regime) for non-subject coaches.
     */
    public function getTaxCategoryCode(CoachProfile $coach): string
    {
        return $coach->is_vat_subject ? 'S' : 'E';
    }

    /**
     * Calculates the VAT amount in cents for the given HTVA amount.
     *
     * Uses half-up rounding to the nearest cent.
     */
    public function calculateVat(int $amountHtva, CoachProfile $coach): int
    {
        $rate = $this->getVatRate($coach);

        return (int) round($amountHtva * $rate / 100);
    }

    /**
     * Converts a TTC (tax-inclusive) amount to HTVA (tax-exclusive) in cents.
     *
     * Uses the Belgian standard VAT rate of 21%.
     * Applies half-up rounding: intdiv(ttc * 100 + 60, 121).
     */
    public function toHtva(int $amountTtc): int
    {
        return intdiv($amountTtc * 100 + 60, 121);
    }
}
