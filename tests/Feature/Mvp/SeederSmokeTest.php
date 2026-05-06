<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\CoachPayoutStatementStatus;
use App\Enums\CoachProfileStatus;
use App\Enums\PaymentAnomalyType;
use App\Enums\UserRole;
use App\Models\ActivityImage;
use App\Models\Booking;
use App\Models\CoachPayoutStatement;
use App\Models\CoachProfile;
use App\Models\Invoice;
use App\Models\PaymentAnomaly;
use App\Models\SportSession;
use App\Models\User;
use Database\Seeders\MvpJourneySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function ensureMvpSeederStorageSymlink(): bool
{
    $linkPath = public_path('storage');

    if (is_link($linkPath)) {
        return false;
    }

    $target = storage_path('app/public');

    if (! is_dir($target)) {
        mkdir($target, 0755, true);
    }

    symlink($target, $linkPath);

    return true;
}

describe('MvpJourneySeeder', function () {

    beforeEach(function (): void {
        $GLOBALS['mvpSeederStorageSymlinkCreated'] = ensureMvpSeederStorageSymlink();
    });

    afterEach(function (): void {
        if (($GLOBALS['mvpSeederStorageSymlinkCreated'] ?? false) && is_link(public_path('storage'))) {
            unlink(public_path('storage'));
        }
    });

    it('creates all expected demo users', function () {
        $this->seed(MvpJourneySeeder::class);

        $emails = [
            'admin@motivya.test',
            'accountant@motivya.test',
            'sophie.coach@motivya.test',
            'marc.coach@motivya.test',
            'alice@motivya.test',
            'bob@motivya.test',
            'charlie@motivya.test',
            'diana@motivya.test',
            'suspended@motivya.test',
            'unverified@motivya.test',
        ];

        foreach ($emails as $email) {
            expect(User::where('email', $email)->exists())->toBeTrue("Expected user {$email} not found");
        }
    });

    it('assigns correct roles', function () {
        $this->seed(MvpJourneySeeder::class);

        expect(User::where('email', 'admin@motivya.test')->first()->role)->toBe(UserRole::Admin);
        expect(User::where('email', 'accountant@motivya.test')->first()->role)->toBe(UserRole::Accountant);
        expect(User::where('email', 'sophie.coach@motivya.test')->first()->role)->toBe(UserRole::Coach);
        expect(User::where('email', 'marc.coach@motivya.test')->first()->role)->toBe(UserRole::Coach);
        expect(User::where('email', 'alice@motivya.test')->first()->role)->toBe(UserRole::Athlete);
    });

    it('creates a pending coach profile for Sophie', function () {
        $this->seed(MvpJourneySeeder::class);

        $sophie = User::where('email', 'sophie.coach@motivya.test')->first();
        $profile = CoachProfile::where('user_id', $sophie->id)->first();

        expect($profile)->not->toBeNull();
        expect($profile->status)->toBe(CoachProfileStatus::Pending);
    });

    it('creates an approved Stripe-ready profile for Marc', function () {
        $this->seed(MvpJourneySeeder::class);

        $marc = User::where('email', 'marc.coach@motivya.test')->first();
        $profile = CoachProfile::where('user_id', $marc->id)->first();

        expect($profile)->not->toBeNull();
        expect($profile->status)->toBe(CoachProfileStatus::Approved);
        expect($profile->stripe_account_id)->toBe('acct_mvp_smoke_test');
        expect($profile->stripe_onboarding_complete)->toBeTrue();
    });

    it('creates sessions across multiple Brussels postal codes', function () {
        $this->seed(MvpJourneySeeder::class);

        $postalCodes = SportSession::pluck('postal_code')->unique()->sort()->values()->all();

        expect($postalCodes)->toContain('1000');
        expect($postalCodes)->toContain('1020');
        expect($postalCodes)->toContain('1050');
    });

    it('creates sessions with coordinates and public activity images', function () {
        $this->seed(MvpJourneySeeder::class);

        expect(SportSession::whereNull('latitude')->orWhereNull('longitude')->count())->toBe(0);
        expect(SportSession::whereNull('cover_image_id')->count())->toBe(0);
        expect(ActivityImage::count())->toBeGreaterThanOrEqual(3);

        ActivityImage::all()->each(function (ActivityImage $image): void {
            expect(Storage::disk('public')->exists($image->path))->toBeTrue();
            expect(file_exists(public_path('storage/'.ltrim($image->path, '/'))))->toBeTrue();
        });
    });

    it('creates coherent payment fields for non-anomalous paid bookings', function () {
        $this->seed(MvpJourneySeeder::class);

        $anomalousBookingIds = PaymentAnomaly::where(
            'anomaly_type',
            PaymentAnomalyType::ConfirmedBookingMissingPayment->value,
        )->pluck('related_booking_id');

        $bookings = Booking::whereIn('status', [
            BookingStatus::Confirmed->value,
            BookingStatus::Refunded->value,
        ])->whereNotIn('id', $anomalousBookingIds)->get();

        expect($bookings)->not->toBeEmpty();

        $bookings->each(function (Booking $booking): void {
            expect($booking->amount_paid)->toBeGreaterThan(0);
            expect($booking->stripe_checkout_session_id)->not->toBeNull();
            expect($booking->stripe_payment_intent_id)->not->toBeNull();

            if ($booking->status === BookingStatus::Refunded) {
                expect($booking->refunded_at)->not->toBeNull();
            }
        });
    });

    it('creates a labelled anomalous booking for accountant and admin smoke checks', function () {
        $this->seed(MvpJourneySeeder::class);

        $anomaly = PaymentAnomaly::where(
            'anomaly_type',
            PaymentAnomalyType::ConfirmedBookingMissingPayment->value,
        )->first();

        expect($anomaly)->not->toBeNull();
        expect($anomaly->booking)->not->toBeNull();
        expect($anomaly->booking->status)->toBe(BookingStatus::Confirmed);
        expect($anomaly->booking->amount_paid)->toBe(0);
        expect($anomaly->description)->toContain('Demo anomaly');
    });

    it('fails clearly when public storage is not linked', function () {
        if (is_link(public_path('storage'))) {
            unlink(public_path('storage'));
        }

        $GLOBALS['mvpSeederStorageSymlinkCreated'] = false;

        expect(fn () => $this->seed(MvpJourneySeeder::class))
            ->toThrow(RuntimeException::class, 'MvpJourneySeeder requires public/storage to be linked');
    });

    it('renders discovery map and image-backed session cards', function () {
        $this->seed(MvpJourneySeeder::class);

        $this->get(route('sessions.index'))
            ->assertOk()
            ->assertSee('id="session-map"', false)
            ->assertSee('sessionMap(', false)
            ->assertSee('activity-images/mvp-cardio.svg', false);
    });

    it('renders detail map and a coordinate-based directions link', function () {
        $this->seed(MvpJourneySeeder::class);

        $session = SportSession::where('title', 'Running Cinquantenaire — Cardio Débutant')->firstOrFail();

        $this->get(route('sessions.show', $session))
            ->assertOk()
            ->assertSee('id="detail-map"', false)
            ->assertSee('sessionMap(', false)
            ->assertSee('destination=50.8390000,4.3860000', false)
            ->assertSee('activity-images/mvp-cardio.svg', false);
    });

    it('creates a suspended user', function () {
        $this->seed(MvpJourneySeeder::class);

        $suspended = User::where('email', 'suspended@motivya.test')->first();

        expect($suspended->suspended_at)->not->toBeNull();
        expect($suspended->suspension_reason)->not->toBeEmpty();
    });

    it('creates a user with unverified email', function () {
        $this->seed(MvpJourneySeeder::class);

        $unverified = User::where('email', 'unverified@motivya.test')->first();

        expect($unverified->email_verified_at)->toBeNull();
    });

    it('creates a pending-payment booking for Diana', function () {
        $this->seed(MvpJourneySeeder::class);

        $diana = User::where('email', 'diana@motivya.test')->first();
        $booking = Booking::where('athlete_id', $diana->id)->first();

        expect($booking)->not->toBeNull();
        expect($booking->status)->toBe(BookingStatus::PendingPayment);
        expect($booking->payment_expires_at)->not->toBeNull();
    });

    it('creates a draft invoice for the completed session', function () {
        $this->seed(MvpJourneySeeder::class);

        $marc = User::where('email', 'marc.coach@motivya.test')->first();

        expect(Invoice::where('coach_id', $marc->id)->exists())->toBeTrue();
    });

    it('creates a payout statement for Marc', function () {
        $this->seed(MvpJourneySeeder::class);

        $marc = User::where('email', 'marc.coach@motivya.test')->first();
        $statement = CoachPayoutStatement::where('coach_id', $marc->id)->first();

        expect($statement)->not->toBeNull();
        expect($statement->status)->toBe(CoachPayoutStatementStatus::Draft);
    });

    it('creates at least one open payment anomaly', function () {
        $this->seed(MvpJourneySeeder::class);

        expect(PaymentAnomaly::where('resolution_status', 'open')->exists())->toBeTrue();
    });

    it('is idempotent — running twice does not duplicate data', function () {
        $this->seed(MvpJourneySeeder::class);
        $this->seed(MvpJourneySeeder::class);

        expect(User::where('email', 'admin@motivya.test')->count())->toBe(1);
        expect(User::where('email', 'marc.coach@motivya.test')->count())->toBe(1);
    });

    it('skips seeding in production environment', function () {
        // Temporarily override app environment to production.
        $originalEnv = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        try {
            $before = User::count();
            // Directly instantiate seeder to avoid artisan output mocking issues.
            (new MvpJourneySeeder)->run();
            $after = User::count();
            expect($after)->toBe($before);
        } finally {
            app()->detectEnvironment(fn () => $originalEnv);
        }
    })->group('production-guard');

});
