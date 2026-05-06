<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports full database dumps for coaches, sessions, and payments as CSV.
 *
 * Uses the same OpenSpout infrastructure as FinancialExportService.
 * All monetary amounts are exported in EUR (divided by 100 from stored integer cents).
 */
final class DatabaseExportService
{
    /**
     * Export all coaches (User with role=coach + CoachProfile) as a streamed CSV download.
     */
    public function exportCoaches(): StreamedResponse
    {
        $coaches = User::query()
            ->with('coachProfile')
            ->where('role', UserRole::Coach)
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($coaches): void {
            $writer = new CsvWriter;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($this->coachHeaders()));

            foreach ($coaches as $coach) {
                $writer->addRow(Row::fromValues($this->coachRow($coach)));
            }

            $writer->close();
        }, $this->filename('coaches', 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Export all sport sessions as a streamed CSV download.
     */
    public function exportSessions(): StreamedResponse
    {
        $sessions = SportSession::query()
            ->with('coach')
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->streamDownload(function () use ($sessions): void {
            $writer = new CsvWriter;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($this->sessionHeaders()));

            foreach ($sessions as $session) {
                $writer->addRow(Row::fromValues($this->sessionRow($session)));
            }

            $writer->close();
        }, $this->filename('sessions', 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Export all bookings/payments as a streamed CSV download.
     */
    public function exportPayments(): StreamedResponse
    {
        $bookings = Booking::query()
            ->with(['sportSession', 'athlete'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->streamDownload(function () use ($bookings): void {
            $writer = new CsvWriter;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($this->paymentHeaders()));

            foreach ($bookings as $booking) {
                $writer->addRow(Row::fromValues($this->paymentRow($booking)));
            }

            $writer->close();
        }, $this->filename('payments', 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Column headers for the coaches export.
     *
     * @return list<string>
     */
    private function coachHeaders(): array
    {
        return [
            'id',
            'name',
            'email',
            'profile_status',
            'specialties',
            'experience_level',
            'postal_code',
            'country',
            'formatted_address',
            'locality',
            'latitude',
            'longitude',
            'geocoding_provider',
            'geocoded_at',
            'enterprise_number',
            'is_vat_subject',
            'stripe_onboarding_complete',
            'verified_at',
            'created_at',
        ];
    }

    /**
     * Map a single coach User to an export row.
     *
     * @return list<string|int|null>
     */
    private function coachRow(User $coach): array
    {
        $profile = $coach->coachProfile;

        return [
            $coach->id,
            $coach->name,
            $coach->email,
            $profile?->status->value ?? '',
            $profile !== null ? implode(', ', is_array($profile->specialties) ? $profile->specialties : []) : '',
            $profile?->experience_level ?? '',
            $profile?->postal_code ?? '',
            $profile?->country ?? '',
            $profile?->formatted_address ?? '',
            $profile?->locality ?? '',
            $profile?->latitude ?? '',
            $profile?->longitude ?? '',
            $profile?->geocoding_provider ?? '',
            $profile?->geocoded_at?->format('Y-m-d H:i:s') ?? '',
            $profile?->enterprise_number ?? '',
            $profile !== null ? ($profile->is_vat_subject ? '1' : '0') : '',
            $profile !== null ? ($profile->stripe_onboarding_complete ? '1' : '0') : '',
            $profile?->verified_at?->format('Y-m-d H:i:s') ?? '',
            $coach->created_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    /**
     * Column headers for the sessions export.
     *
     * @return list<string>
     */
    private function sessionHeaders(): array
    {
        return [
            'id',
            'coach_id',
            'coach_name',
            'activity_type',
            'level',
            'title',
            'location',
            'postal_code',
            'formatted_address',
            'locality',
            'geocoding_provider',
            'geocoded_at',
            'date',
            'start_time',
            'end_time',
            'price_per_person_eur',
            'min_participants',
            'max_participants',
            'current_participants',
            'status',
            'created_at',
        ];
    }

    /**
     * Map a single SportSession to an export row.
     *
     * @return list<string|int|float|null>
     */
    private function sessionRow(SportSession $session): array
    {
        return [
            $session->id,
            $session->coach_id,
            $session->coach?->name ?? '',
            $session->activity_type->value,
            $session->level->value,
            $session->title,
            $session->location,
            $session->postal_code,
            $session->formatted_address ?? '',
            $session->locality ?? '',
            $session->geocoding_provider ?? '',
            $session->geocoded_at?->format('Y-m-d H:i:s') ?? '',
            $session->date?->format('Y-m-d') ?? '',
            $session->start_time,
            $session->end_time,
            $this->toEur($session->price_per_person),
            $session->min_participants,
            $session->max_participants,
            $session->current_participants,
            $session->status->value,
            $session->created_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    /**
     * Column headers for the payments (bookings) export.
     *
     * @return list<string>
     */
    private function paymentHeaders(): array
    {
        return [
            'id',
            'sport_session_id',
            'session_title',
            'athlete_id',
            'athlete_name',
            'athlete_email',
            'status',
            'amount_paid_eur',
            'stripe_payment_intent_id',
            'created_at',
            'cancelled_at',
            'refunded_at',
        ];
    }

    /**
     * Map a single Booking to an export row.
     *
     * @return list<string|int|float|null>
     */
    private function paymentRow(Booking $booking): array
    {
        return [
            $booking->id,
            $booking->sport_session_id,
            $booking->sportSession?->title ?? '',
            $booking->athlete_id,
            $booking->athlete?->name ?? '',
            $booking->athlete?->email ?? '',
            $booking->status->value,
            $this->toEur($booking->amount_paid),
            $booking->stripe_payment_intent_id ?? '',
            $booking->created_at?->format('Y-m-d H:i:s') ?? '',
            $booking->cancelled_at?->format('Y-m-d H:i:s') ?? '',
            $booking->refunded_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    /**
     * Convert an integer cent amount to a decimal EUR value.
     */
    private function toEur(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /**
     * Generate a timestamped filename for the export.
     */
    private function filename(string $type, string $extension): string
    {
        return $type.'_export_'.now()->format('Y-m-d').'.'.$extension;
    }
}
