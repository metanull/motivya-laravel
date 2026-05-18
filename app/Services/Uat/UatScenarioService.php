<?php

declare(strict_types=1);

namespace App\Services\Uat;

use App\Enums\ActivityType;
use App\Enums\BookingStatus;
use App\Enums\CoachProfileStatus;
use App\Enums\PaymentAnomalyType;
use App\Enums\SessionLevel;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Events\BookingRefunded;
use App\Events\SessionConfirmed;
use App\Models\AdminRefundAudit;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\Invoice;
use App\Models\PaymentAnomaly;
use App\Models\SportSession;
use App\Models\UatMailCapture;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CoachPayoutStatementService;
use App\Services\InvoiceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Stripe\Account as StripeAccount;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;
use Stripe\Refund as StripeRefund;
use Stripe\Stripe;

final class UatScenarioService
{
    /** @var list<User> */
    private array $coaches = [];

    /** @var list<User> */
    private array $athletes = [];

    /** @var list<SportSession> */
    private array $sessions = [];

    /** @var list<Booking> */
    private array $refundableBookings = [];

    /** @var list<string> */
    private array $connectedAccountIds = [];

    public function __construct(
        private readonly BookingService $bookingService,
        private readonly InvoiceService $invoiceService,
        private readonly CoachPayoutStatementService $payoutStatementService,
    ) {}

    /**
     * @param  array{run_id: string, coaches: int, athletes: int, sessions_per_coach: int, window_days: int, payments: string, failed_payment_rate: int, exceptional_refunds: int, fresh: bool, confirm_stripe: bool}  $options
     * @return array<string, int|string>
     */
    public function play(array $options): array
    {
        $this->guard($options);
        $this->configureRuntime($options['run_id']);

        if ($options['fresh']) {
            $this->deletePreviousScenarioData();
        }

        if ($options['payments'] === 'stripe') {
            $this->prepareStripeAccounts($options['coaches']);
        }

        $admin = $this->operator('admin', UserRole::Admin);
        $accountant = $this->operator('accountant', UserRole::Accountant);

        $this->createCoaches($options['coaches']);
        $this->createAthletes($options['athletes']);
        $this->createSessions($options['sessions_per_coach'], $options['window_days']);
        $this->bookSessions($options['payments'], $options['failed_payment_rate']);
        $exceptionalRefunds = $this->refundExceptionalBookings($admin, $options['payments'], $options['exceptional_refunds']);
        $this->generateFinancials($accountant);
        $this->createAnomaly();

        return [
            'run_id' => $options['run_id'],
            'coaches' => count($this->coaches),
            'athletes' => count($this->athletes),
            'sessions' => count($this->sessions),
            'bookings' => Booking::whereHas('athlete', fn ($query) => $query->where('email', 'like', 'uat+athlete%'))->count(),
            'exceptional_refunds' => $exceptionalRefunds,
            'captured_mails' => UatMailCapture::where('run_id', $options['run_id'])->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function guard(array $options): void
    {
        if (! app()->environment('uat')) {
            throw new RuntimeException('uat:play-scenario may only run when APP_ENV=uat.');
        }

        if ($options['payments'] !== 'simulated' && $options['payments'] !== 'stripe') {
            throw new RuntimeException('--payments must be simulated or stripe.');
        }

        if ($options['payments'] === 'stripe') {
            $secret = config('services.stripe.secret');

            if (! $options['confirm_stripe']) {
                throw new RuntimeException('Stripe mode requires --confirm-stripe.');
            }

            if (! is_string($secret) || ! str_starts_with($secret, 'sk_test_')) {
                throw new RuntimeException('Stripe mode requires STRIPE_SECRET to be a test-mode key.');
            }
        }
    }

    private function configureRuntime(string $runId): void
    {
        config([
            'uat.current_run_id' => $runId,
            'mail.default' => 'array',
            'mail.uat_capture.enabled' => true,
            'queue.default' => 'sync',
        ]);
    }

    private function deletePreviousScenarioData(): void
    {
        DB::transaction(function (): void {
            UatMailCapture::where('run_id', 'like', 'uat_%')->delete();
            PaymentAnomaly::where('description', 'like', 'UAT scenario:%', 'and')->delete();
            AdminRefundAudit::where('reason', 'like', 'UAT scenario:%', 'and')->delete();
            Invoice::whereHas('coach', fn ($query) => $query->where('email', 'like', 'uat+coach%'))->delete();
            SportSession::where('title', 'like', 'UAT Scenario %', 'and')->delete();
            CoachProfile::whereHas('user', fn ($query) => $query->where('email', 'like', 'uat+coach%'))->delete();
            User::where('email', 'like', 'uat+%', 'and')->delete();
        });
    }

    private function operator(string $slug, UserRole $role): User
    {
        return User::updateOrCreate(
            ['email' => "uat+{$slug}@motivya.test"],
            [
                'name' => 'UAT '.ucfirst($slug),
                'password' => Hash::make('password'),
                'role' => $role,
                'email_verified_at' => now(),
                'locale' => 'fr',
            ],
        );
    }

    private function createCoaches(int $count): void
    {
        $activities = ActivityType::cases();

        for ($index = 1; $index <= $count; $index++) {
            $coach = User::updateOrCreate(
                ['email' => sprintf('uat+coach%02d@motivya.test', $index)],
                [
                    'name' => sprintf('UAT Coach %02d', $index),
                    'password' => Hash::make('password'),
                    'role' => UserRole::Coach,
                    'email_verified_at' => now(),
                    'locale' => ['fr', 'en', 'nl'][$index % 3],
                ],
            );

            CoachProfile::updateOrCreate(
                ['user_id' => $coach->id],
                [
                    'status' => CoachProfileStatus::Approved,
                    'specialties' => [$activities[$index % count($activities)]->value],
                    'bio' => sprintf('UAT coach profile %02d generated for scenario testing.', $index),
                    'experience_level' => ['beginner', 'intermediate', 'advanced', 'expert'][$index % 4],
                    'postal_code' => ['1000', '1020', '1030', '1040', '1050'][$index % 5],
                    'country' => 'BE',
                    'enterprise_number' => sprintf('08%02d.%03d.%03d', $index, 100 + $index, 200 + $index),
                    'is_vat_subject' => $index % 2 === 0,
                    'stripe_account_id' => $this->connectedAccountForCoach($index),
                    'stripe_onboarding_complete' => true,
                    'verified_at' => now()->subDays(20),
                ],
            );

            $this->coaches[] = $coach->fresh('coachProfile');
        }
    }

    private function createAthletes(int $count): void
    {
        for ($index = 1; $index <= $count; $index++) {
            $this->athletes[] = User::updateOrCreate(
                ['email' => sprintf('uat+athlete%02d@motivya.test', $index)],
                [
                    'name' => sprintf('UAT Athlete %02d', $index),
                    'password' => Hash::make('password'),
                    'role' => UserRole::Athlete,
                    'email_verified_at' => now(),
                    'locale' => ['fr', 'en', 'nl'][$index % 3],
                ],
            );
        }
    }

    private function createSessions(int $sessionsPerCoach, int $windowDays): void
    {
        $activities = ActivityType::cases();
        $levels = SessionLevel::cases();
        $locations = [
            ['Parc du Cinquantenaire, Bruxelles', '1000', 50.8390000, 4.3860000],
            ['Parc de Laeken, Bruxelles', '1020', 50.8930000, 4.3570000],
            ['Parc Josaphat, Schaerbeek', '1030', 50.8650000, 4.3810000],
            ['Parc Léopold, Etterbeek', '1040', 50.8380000, 4.3810000],
            ['Bois de la Cambre, Ixelles', '1050', 50.8130000, 4.3840000],
        ];
        $sequence = 0;
        $totalSessions = max(1, count($this->coaches) * $sessionsPerCoach);

        foreach ($this->coaches as $coach) {
            for ($index = 1; $index <= $sessionsPerCoach; $index++) {
                $sequence++;
                $offset = -$windowDays + (($sequence - 1) * (2 * $windowDays) / max(1, $totalSessions - 1));
                $location = $locations[$sequence % count($locations)];

                $this->sessions[] = SportSession::create([
                    'coach_id' => $coach->id,
                    'activity_type' => $activities[$sequence % count($activities)]->value,
                    'level' => $levels[$sequence % count($levels)]->value,
                    'title' => sprintf('UAT Scenario %s Session %02d', config('uat.current_run_id'), $sequence),
                    'description' => 'Generated UAT scenario session with realistic booking/payment outcomes.',
                    'location' => $location[0],
                    'postal_code' => $location[1],
                    'latitude' => $location[2],
                    'longitude' => $location[3],
                    'date' => Carbon::today()->addDays((int) round($offset))->toDateString(),
                    'start_time' => sprintf('%02d:00:00', 8 + ($sequence % 10)),
                    'end_time' => sprintf('%02d:00:00', 9 + ($sequence % 10)),
                    'price_per_person' => 1500 + (($sequence % 7) * 500),
                    'min_participants' => $sequence % 5 === 0 ? 5 : 3,
                    'max_participants' => 8,
                    'current_participants' => 0,
                    'status' => SessionStatus::Published,
                ]);
            }
        }
    }

    private function bookSessions(string $mode, int $failedPaymentRate): void
    {
        $bookingIndex = 0;
        $failedEvery = max(1, (int) round(100 / max(1, $failedPaymentRate)));

        foreach ($this->sessions as $sessionIndex => $session) {
            $targetBookings = $sessionIndex % 5 === 0 ? 2 : 4;

            for ($slot = 0; $slot < $targetBookings; $slot++) {
                $athlete = $this->athletes[(($sessionIndex * 7) + $slot) % count($this->athletes)];
                $booking = $this->bookingService->book($session, $athlete);
                $bookingIndex++;

                if ($bookingIndex % $failedEvery === 0) {
                    $this->failBooking($booking, $mode);
                } else {
                    $this->confirmBooking($booking, $mode);
                }
            }

            $this->finalizeSession($session->fresh(), $mode, $sessionIndex);
        }
    }

    private function confirmBooking(Booking $booking, string $mode): void
    {
        $booking->loadMissing('sportSession');

        $booking->forceFill([
            'status' => BookingStatus::Confirmed,
            'amount_paid' => $booking->sportSession->price_per_person,
            'stripe_payment_intent_id' => $mode === 'stripe' ? $this->createStripePaymentIntent($booking) : 'pi_sim_'.config('uat.current_run_id').'_'.$booking->id,
            'stripe_checkout_session_id' => 'cs_'.$mode.'_'.config('uat.current_run_id').'_'.$booking->id,
        ])->save();

        $this->refundableBookings[] = $booking->fresh();
        BookingCreated::dispatch($booking->id);
    }

    private function failBooking(Booking $booking, string $mode): void
    {
        $booking->loadMissing('sportSession');
        $session = $booking->sportSession;

        $booking->forceFill([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'stripe_payment_intent_id' => $mode === 'stripe' ? $this->createFailedStripePaymentIntent($booking) : 'pi_sim_failed_'.config('uat.current_run_id').'_'.$booking->id,
            'stripe_checkout_session_id' => 'cs_failed_'.config('uat.current_run_id').'_'.$booking->id,
        ])->save();

        $session->forceFill(['current_participants' => max(0, $session->current_participants - 1)])->save();
        BookingCancelled::dispatch($booking->id, 'payment_failed', false);
    }

    private function finalizeSession(SportSession $session, string $mode, int $sessionIndex): void
    {
        $confirmedCount = $session->bookings()->where('status', BookingStatus::Confirmed)->count();

        if ($confirmedCount >= $session->min_participants) {
            $session->forceFill(['status' => SessionStatus::Confirmed])->save();
            SessionConfirmed::dispatch($session->id);
        }

        if ($session->date->lt(Carbon::today()) && $session->status === SessionStatus::Confirmed) {
            $session->forceFill(['status' => SessionStatus::Completed])->save();
        }

        if ($sessionIndex % 5 === 0) {
            $session->forceFill(['status' => SessionStatus::Cancelled])->save();

            foreach ($session->bookings()->where('status', BookingStatus::Confirmed)->get() as $booking) {
                $this->refundBooking($booking, $mode, 'UAT scenario: below minimum threshold');
            }
        }
    }

    private function refundExceptionalBookings(User $admin, string $mode, int $target): int
    {
        $count = 0;

        foreach ($this->refundableBookings as $booking) {
            if ($count >= $target) {
                return $count;
            }

            if ($booking->fresh()?->status !== BookingStatus::Confirmed) {
                continue;
            }

            $this->refundBooking($booking, $mode, 'UAT scenario: exceptional admin refund', $admin);
            $count++;
        }

        return $count;
    }

    private function refundBooking(Booking $booking, string $mode, string $reason, ?User $admin = null): void
    {
        $booking = $booking->fresh();

        if ($booking === null || $booking->status === BookingStatus::Refunded) {
            return;
        }

        $refundId = 're_sim_'.config('uat.current_run_id').'_'.$booking->id;

        if ($mode === 'stripe' && is_string($booking->stripe_payment_intent_id) && str_starts_with($booking->stripe_payment_intent_id, 'pi_')) {
            $refund = StripeRefund::create(['payment_intent' => $booking->stripe_payment_intent_id]);
            $refundId = is_string($refund->id ?? null) ? $refund->id : $refundId;
        }

        $booking->forceFill([
            'status' => BookingStatus::Refunded,
            'cancelled_at' => $booking->cancelled_at ?? now(),
            'refunded_at' => now(),
        ])->save();

        AdminRefundAudit::create([
            'admin_id' => $admin?->id,
            'booking_id' => $booking->id,
            'refund_amount' => $booking->amount_paid,
            'reason' => $reason,
            'stripe_refund_id' => $refundId,
            'status' => 'succeeded',
        ]);

        BookingRefunded::dispatch($booking->id);
    }

    private function generateFinancials(User $accountant): void
    {
        foreach ($this->sessions as $session) {
            $session = $session->fresh();

            if ($session !== null && $session->status === SessionStatus::Completed) {
                $this->invoiceService->generateForCompletedSession($session);
            }
        }

        foreach ($this->coaches as $coach) {
            foreach ([Carbon::now()->subMonth(), Carbon::now()] as $month) {
                try {
                    $statement = $this->payoutStatementService->generateForCoach($coach->fresh('coachProfile'), (int) $month->year, (int) $month->month);

                    if ($statement->paid_bookings_count > 0 && $statement->status->value === 'draft') {
                        $this->payoutStatementService->requestPayout($statement);
                        $this->payoutStatementService->markInvoiceSubmitted($statement->fresh());
                        $this->payoutStatementService->approve($statement->fresh(), $accountant);
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
        }
    }

    private function createAnomaly(): void
    {
        $session = collect($this->sessions)->first(fn (SportSession $session): bool => $session->fresh()->status === SessionStatus::Completed);

        if (! $session instanceof SportSession) {
            return;
        }

        PaymentAnomaly::firstOrCreate(
            [
                'anomaly_type' => PaymentAnomalyType::CompletedSessionWithoutInvoice->value,
                'related_session_id' => $session->id,
            ],
            [
                'anomalous_model_type' => SportSession::class,
                'anomalous_model_id' => $session->id,
                'related_coach_id' => $session->coach_id,
                'resolution_status' => 'open',
                'description' => 'UAT scenario: sample anomaly for accountant/admin review.',
                'recommended_action' => 'Review generated session and invoice state.',
            ],
        );
    }

    private function createStripePaymentIntent(Booking $booking): string
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));

        $paymentIntent = PaymentIntent::create([
            'amount' => $booking->sportSession->price_per_person,
            'currency' => 'eur',
            'payment_method' => 'pm_card_visa',
            'confirm' => true,
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
            'metadata' => [
                'run_id' => (string) config('uat.current_run_id'),
                'booking_id' => (string) $booking->id,
                'session_id' => (string) $booking->sport_session_id,
                'athlete_id' => (string) $booking->athlete_id,
            ],
        ]);

        return (string) $paymentIntent->id;
    }

    private function createFailedStripePaymentIntent(Booking $booking): string
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));

        try {
            PaymentIntent::create([
                'amount' => $booking->sportSession->price_per_person,
                'currency' => 'eur',
                'payment_method' => 'pm_card_chargeDeclined',
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
                'metadata' => [
                    'run_id' => (string) config('uat.current_run_id'),
                    'booking_id' => (string) $booking->id,
                ],
            ]);
        } catch (CardException $exception) {
            $id = $exception->getError()->payment_intent?->id;

            if (is_string($id) && $id !== '') {
                return $id;
            }
        }

        return 'pi_stripe_failed_missing_'.config('uat.current_run_id').'_'.$booking->id;
    }

    private function prepareStripeAccounts(int $count): void
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));

        $configured = config('services.stripe.manual_tests.connected_account_id');

        if (is_string($configured) && str_starts_with($configured, 'acct_')) {
            $this->connectedAccountIds[] = $configured;
        }

        foreach (StripeAccount::all(['limit' => 100])->data as $account) {
            if (is_string($account->id ?? null) && str_starts_with($account->id, 'acct_')) {
                $this->connectedAccountIds[] = $account->id;
            }
        }

        $this->connectedAccountIds = array_values(array_unique($this->connectedAccountIds));

        while (count($this->connectedAccountIds) < $count) {
            $account = StripeAccount::create([
                'type' => 'express',
                'country' => 'BE',
                'email' => sprintf('uat-connect-%02d@motivya.test', count($this->connectedAccountIds) + 1),
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_type' => 'individual',
                'metadata' => [
                    'run_id' => (string) config('uat.current_run_id'),
                    'created_by' => 'uat_play_scenario',
                ],
            ]);

            $this->connectedAccountIds[] = (string) $account->id;
        }
    }

    private function connectedAccountForCoach(int $coachIndex): string
    {
        if ($this->connectedAccountIds !== []) {
            return $this->connectedAccountIds[($coachIndex - 1) % count($this->connectedAccountIds)];
        }

        return sprintf('acct_sim_uat_%02d', $coachIndex);
    }
}
