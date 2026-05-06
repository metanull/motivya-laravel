<?php

declare(strict_types=1);

namespace App\Livewire\Coach;

use App\Enums\BookingStatus;
use App\Enums\CoachProfileStatus;
use App\Enums\SessionStatus;
use App\Models\CoachPayoutStatement;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use App\Services\AnomalyDetectorService;
use App\Services\SessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class Dashboard extends Component
{
    public string $tab = 'upcoming';

    /** Whether the onboarding checklist panel is expanded. */
    public bool $showChecklist = true;

    /**
     * At mount time, auto-collapse the checklist when every item is already done.
     */
    public function mount(): void
    {
        $allDone = collect($this->checklistItems())->every(fn (array $item): bool => $item['done']);

        if ($allDone) {
            $this->showChecklist = false;
        }
    }

    /** Toggle the onboarding checklist panel open / closed. */
    public function toggleChecklist(): void
    {
        $this->showChecklist = ! $this->showChecklist;
    }

    /**
     * Compute the six launch-critical onboarding checklist items.
     *
     * @return list<array{label: string, done: bool, url: string|null}>
     */
    public function checklistItems(): array
    {
        /** @var User $coach */
        $coach = auth()->user();

        /** @var CoachProfile|null $coachProfile */
        $coachProfile = $coach->coachProfile;

        // ── 1. Profile approved ──────────────────────────────────────────────
        $profileApproved = $coachProfile?->status === CoachProfileStatus::Approved;

        // ── 2. Profile complete ──────────────────────────────────────────────
        $profileComplete = $coachProfile !== null
            && is_array($coachProfile->specialties) && count($coachProfile->specialties) > 0
            && is_string($coachProfile->bio) && $coachProfile->bio !== ''
            && is_string($coachProfile->experience_level) && $coachProfile->experience_level !== ''
            && is_string($coachProfile->postal_code) && $coachProfile->postal_code !== ''
            && is_string($coachProfile->enterprise_number) && $coachProfile->enterprise_number !== '';

        // ── 3. VAT status captured (admin must set true or false; null = not yet reviewed) ─
        // is_vat_subject is nullable boolean: null means not yet set by admin.
        $vatCaptured = $coachProfile !== null && $coachProfile->is_vat_subject !== null;

        // ── 4. Stripe onboarding complete ────────────────────────────────────
        $stripeReady = $coachProfile?->stripe_onboarding_complete === true;

        // ── 5. At least one published future session ─────────────────────────
        $hasPublishedSession = SportSession::where('coach_id', $coach->id)
            ->where('status', SessionStatus::Published)
            ->where('date', '>=', now()->toDateString())
            ->exists();

        // ── 6. At least one session with a cover image ───────────────────────
        $hasCoverImage = SportSession::where('coach_id', $coach->id)
            ->whereNotNull('cover_image_id')
            ->exists();

        // Stripe onboard URL (continue if account already started)
        $stripeOnboardUrl = ($coachProfile?->stripe_account_id !== null && $coachProfile->stripe_account_id !== '')
            ? route('coach.stripe.refresh')
            : route('coach.stripe.onboard');

        return [
            [
                'label' => __('coach.onboarding_item_profile_approved'),
                'done' => $profileApproved,
                'url' => route('coach.profile.edit'),
            ],
            [
                'label' => __('coach.onboarding_item_profile_complete'),
                'done' => $profileComplete,
                'url' => route('coach.profile.edit'),
            ],
            [
                'label' => __('coach.onboarding_item_vat_captured'),
                'done' => $vatCaptured,
                'url' => route('coach.profile.edit'),
            ],
            [
                'label' => __('coach.onboarding_item_stripe_ready'),
                'done' => $stripeReady,
                'url' => $stripeOnboardUrl,
            ],
            [
                'label' => __('coach.onboarding_item_published_session'),
                'done' => $hasPublishedSession,
                'url' => route('coach.sessions.create'),
            ],
            [
                'label' => __('coach.onboarding_item_cover_image'),
                'done' => $hasCoverImage,
                'url' => route('coach.sessions.create'),
            ],
        ];
    }

    /**
     * Recent payout statements for the coach (last 12, latest first).
     *
     * @return Collection<int, CoachPayoutStatement>
     */
    #[Computed]
    public function payoutStatements(): Collection
    {
        /** @var User $coach */
        $coach = auth()->user();

        return CoachPayoutStatement::where('coach_id', $coach->id)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->limit(12)
            ->get();
    }

    public function publishSession(int $sessionId, SessionService $service): void
    {
        $session = SportSession::findOrFail($sessionId);
        Gate::authorize('update', $session);

        try {
            $service->publish($session);
            $this->dispatch('notify', type: 'success', message: __('sessions.published'));
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: collect($e->errors())->flatten()->first());
        }
    }

    public function cancelSession(int $sessionId, SessionService $service): void
    {
        $session = SportSession::findOrFail($sessionId);
        Gate::authorize('cancel', $session);

        $service->cancel($session);

        $this->dispatch('notify', type: 'success', message: __('sessions.cancelled'));
    }

    public function deleteSession(int $sessionId, SessionService $service): void
    {
        $session = SportSession::findOrFail($sessionId);
        Gate::authorize('delete', $session);

        $service->delete($session);

        $this->dispatch('notify', type: 'success', message: __('sessions.deleted'));
    }

    public function render(AnomalyDetectorService $anomalyDetector): View
    {
        /** @var User $coach */
        $coach = auth()->user();

        /** @var CoachProfile|null $coachProfile */
        $coachProfile = $coach->coachProfile;

        // Eager-load confirmed/pending booking counts on every session query (Story 5.3)
        $bookingCountScopes = [
            'bookings as confirmed_count' => fn ($q) => $q->where('status', BookingStatus::Confirmed->value),
            'bookings as pending_count' => fn ($q) => $q->where('status', BookingStatus::PendingPayment->value),
        ];

        $upcoming = SportSession::where('coach_id', $coach->id)
            ->whereIn('status', [SessionStatus::Published, SessionStatus::Confirmed])
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->withCount($bookingCountScopes)
            ->get();

        $drafts = SportSession::where('coach_id', $coach->id)
            ->where('status', SessionStatus::Draft)
            ->orderBy('date')
            ->orderBy('start_time')
            ->withCount($bookingCountScopes)
            ->get();

        $past = SportSession::where('coach_id', $coach->id)
            ->whereIn('status', [SessionStatus::Completed, SessionStatus::Cancelled])
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->limit(20)
            ->withCount($bookingCountScopes)
            ->get();

        // Stats
        $allSessions = SportSession::where('coach_id', $coach->id);

        $totalSessions = (clone $allSessions)->count();

        $sessionsThisMonth = (clone $allSessions)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->count();

        // Confirmed participants (Story 5.3: use confirmed paid bookings only)
        $totalConfirmedParticipants = (int) DB::table('bookings')
            ->join('sport_sessions', 'bookings.sport_session_id', '=', 'sport_sessions.id')
            ->where('sport_sessions.coach_id', $coach->id)
            ->where('bookings.status', BookingStatus::Confirmed->value)
            ->count();

        // Keep alias for backward compat
        $totalBookings = $totalConfirmedParticipants;

        // Pending payment holds (Story 5.3)
        $totalPendingPaymentHolds = (int) DB::table('bookings')
            ->join('sport_sessions', 'bookings.sport_session_id', '=', 'sport_sessions.id')
            ->where('sport_sessions.coach_id', $coach->id)
            ->where('bookings.status', BookingStatus::PendingPayment->value)
            ->count();

        // Avg fill rate based on confirmed paid bookings only (Story 5.3)
        $confirmedStatus = BookingStatus::Confirmed->value;
        $sessionStats = DB::table('sport_sessions')
            ->where('sport_sessions.coach_id', $coach->id)
            ->where('sport_sessions.max_participants', '>', 0)
            ->selectRaw(
                'sport_sessions.id, sport_sessions.max_participants, (SELECT COUNT(*) FROM bookings WHERE sport_session_id = sport_sessions.id AND status = ?) as confirmed_count',
                [$confirmedStatus],
            )
            ->get();

        $avgFillRate = $sessionStats->count() > 0
            ? $sessionStats->avg(fn (object $s): float => ($s->confirmed_count / $s->max_participants) * 100)
            : 0;
        $avgFillRate = (int) round((float) $avgFillRate);

        $totalRevenueCents = (int) DB::table('bookings')
            ->join('sport_sessions', 'bookings.sport_session_id', '=', 'sport_sessions.id')
            ->where('sport_sessions.coach_id', $coach->id)
            ->where('bookings.status', BookingStatus::Confirmed->value)
            ->sum('bookings.amount_paid');

        // Story 5.2: warn when coach has published/confirmed sessions but Stripe is not ready
        $publishedWithoutStripe = false;

        if ($coachProfile !== null && ! $coachProfile->isStripeReady()) {
            $publishedWithoutStripe = SportSession::where('coach_id', $coach->id)
                ->whereIn('status', [SessionStatus::Published, SessionStatus::Confirmed])
                ->exists();
        }

        // Story 5.2: Warn when any confirmed paid booking is missing stripe_payment_intent_id
        $hasMissingPaymentIntentBookings = $anomalyDetector->coachHasMissingPaymentIntents($coach);

        // Story 5.2: Current-month revenue breakdown
        $currentMonthRevenueCents = (int) DB::table('bookings')
            ->join('sport_sessions', 'bookings.sport_session_id', '=', 'sport_sessions.id')
            ->where('sport_sessions.coach_id', $coach->id)
            ->where('bookings.status', BookingStatus::Confirmed->value)
            ->whereMonth('bookings.created_at', now()->month)
            ->whereYear('bookings.created_at', now()->year)
            ->sum('bookings.amount_paid');

        $currentMonthRefundedCents = (int) DB::table('bookings')
            ->join('sport_sessions', 'bookings.sport_session_id', '=', 'sport_sessions.id')
            ->where('sport_sessions.coach_id', $coach->id)
            ->where('bookings.status', BookingStatus::Refunded->value)
            ->whereMonth('bookings.refunded_at', now()->month)
            ->whereYear('bookings.refunded_at', now()->year)
            ->sum('bookings.amount_paid');

        $checklistItems = $this->checklistItems();
        $allChecklistDone = collect($checklistItems)->every(fn (array $item): bool => $item['done']);

        return view('livewire.coach.dashboard', [
            'coachProfile' => $coachProfile,
            'upcoming' => $upcoming,
            'drafts' => $drafts,
            'past' => $past,
            'totalSessions' => $totalSessions,
            'sessionsThisMonth' => $sessionsThisMonth,
            'totalBookings' => $totalBookings,
            'totalConfirmedParticipants' => $totalConfirmedParticipants,
            'totalPendingPaymentHolds' => $totalPendingPaymentHolds,
            'avgFillRate' => $avgFillRate,
            'totalRevenueCents' => $totalRevenueCents,
            'publishedWithoutStripe' => $publishedWithoutStripe,
            'hasMissingPaymentIntentBookings' => $hasMissingPaymentIntentBookings,
            'currentMonthRevenueCents' => $currentMonthRevenueCents,
            'currentMonthRefundedCents' => $currentMonthRefundedCents,
            'checklistItems' => $checklistItems,
            'allChecklistDone' => $allChecklistDone,
        ])->title(__('coach.dashboard_title'));
    }
}
