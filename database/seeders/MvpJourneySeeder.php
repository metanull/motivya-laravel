<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\BookingStatus;
use App\Enums\CoachPayoutStatementStatus;
use App\Enums\CoachProfileStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentAnomalyType;
use App\Enums\SessionLevel;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\CoachPayoutStatement;
use App\Models\CoachProfile;
use App\Models\Invoice;
use App\Models\PaymentAnomaly;
use App\Models\SportSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a realistic MVP journey scenario for manual QA smoke testing.
 *
 * Scenario overview:
 *  - Admin user (approves coaches, manages platform)
 *  - Coach 1 (Sophie): profile pending admin approval — used to test the approval flow
 *  - Coach 2 (Marc): approved + Stripe-ready, sessions in multiple Brussels postal codes
 *  - Athletes: Alice (confirmed bookings), Bob (confirmed + cancelled), Charlie (fresh tester),
 *              Diana (pending-payment booking for recovery testing)
 *  - Accountant: read-only financial dashboard access
 *  - Payout statements and payment anomalies for accountant/admin review
 *  - Suspended user and unverified user for admin user management testing
 *
 * All passwords: "password" (local dev only — never use in production).
 * All Stripe account IDs are placeholders; replace with real test IDs.
 *
 * ⚠  NEVER run this seeder in a production environment.
 *    It creates predictable demo credentials that are a security risk.
 */
final class MvpJourneySeeder extends Seeder
{
    /**
     * Seed the MVP journey scenario.
     */
    public function run(): void
    {
        // Production guard — never seed demo credentials in production.
        if (app()->isProduction()) {
            if ($this->command !== null) {
                $this->command->warn('⛔  MvpJourneySeeder: skipped in production environment.');
            }

            return;
        }

        $password = Hash::make('password');

        // ── Admin ─────────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@motivya.test'],
            [
                'name' => 'Admin User',
                'password' => $password,
                'role' => UserRole::Admin->value,
                'email_verified_at' => now(),
            ]
        );

        // ── Accountant ────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'accountant@motivya.test'],
            [
                'name' => 'Accountant User',
                'password' => $password,
                'role' => UserRole::Accountant->value,
                'email_verified_at' => now(),
            ]
        );

        // ── Coach 1 — Sophie (pending approval) ───────────────────────────────
        $sophie = User::firstOrCreate(
            ['email' => 'sophie.coach@motivya.test'],
            [
                'name' => 'Sophie Martin',
                'password' => $password,
                'role' => UserRole::Coach->value,
                'email_verified_at' => now(),
            ]
        );

        CoachProfile::firstOrCreate(
            ['user_id' => $sophie->id],
            [
                'status' => CoachProfileStatus::Pending,
                'specialties' => ['yoga', 'pilates'],
                'bio' => "Coach certifiée yoga et pilates avec 8 ans d'expérience à Bruxelles. Spécialisée dans les cours en plein air.",
                'experience_level' => 'expert',
                'postal_code' => '1050',
                'country' => 'BE',
                'enterprise_number' => '0123.456.789',
                'is_vat_subject' => false,
            ]
        );

        // ── Coach 2 — Marc (approved + Stripe-ready) ──────────────────────────
        $marc = User::firstOrCreate(
            ['email' => 'marc.coach@motivya.test'],
            [
                'name' => 'Marc Dupont',
                'password' => $password,
                'role' => UserRole::Coach->value,
                'email_verified_at' => now(),
            ]
        );

        CoachProfile::firstOrCreate(
            ['user_id' => $marc->id],
            [
                'status' => CoachProfileStatus::Approved,
                'specialties' => ['running', 'cardio'],
                'bio' => 'Coach running et cardio depuis 10 ans. Entraîneur certifié ADEPS. Sessions en plein air dans les parcs bruxellois.',
                'experience_level' => 'advanced',
                'postal_code' => '1000',
                'country' => 'BE',
                'enterprise_number' => '0987.654.321',
                'is_vat_subject' => true,
                // Use Stripe test account placeholder.
                // Replace with a real Stripe Express test account ID from your Stripe dashboard.
                'stripe_account_id' => 'acct_mvp_smoke_test',
                'stripe_onboarding_complete' => true,
                'verified_at' => now()->subDays(14),
            ]
        );

        // ── Athletes ──────────────────────────────────────────────────────────
        $alice = User::firstOrCreate(
            ['email' => 'alice@motivya.test'],
            [
                'name' => 'Alice Leroy',
                'password' => $password,
                'role' => UserRole::Athlete->value,
                'email_verified_at' => now(),
            ]
        );

        $bob = User::firstOrCreate(
            ['email' => 'bob@motivya.test'],
            [
                'name' => 'Bob Renard',
                'password' => $password,
                'role' => UserRole::Athlete->value,
                'email_verified_at' => now(),
            ]
        );

        // Charlie is the "fresh tester" — no bookings yet.
        User::firstOrCreate(
            ['email' => 'charlie@motivya.test'],
            [
                'name' => 'Charlie Dubois',
                'password' => $password,
                'role' => UserRole::Athlete->value,
                'email_verified_at' => now(),
            ]
        );

        // Diana has a pending-payment booking (for payment recovery testing #273).
        $diana = User::firstOrCreate(
            ['email' => 'diana@motivya.test'],
            [
                'name' => 'Diana Kohl',
                'password' => $password,
                'role' => UserRole::Athlete->value,
                'email_verified_at' => now(),
            ]
        );

        // ── Suspended user (for admin user management) ────────────────────────
        User::firstOrCreate(
            ['email' => 'suspended@motivya.test'],
            [
                'name' => 'Suspended User',
                'password' => $password,
                'role' => UserRole::Athlete->value,
                'email_verified_at' => now(),
                'suspended_at' => now()->subDays(3),
                'suspension_reason' => 'Demo: suspended account for admin testing.',
            ]
        );

        // ── Unverified email user (for admin user management) ─────────────────
        User::firstOrCreate(
            ['email' => 'unverified@motivya.test'],
            [
                'name' => 'Unverified User',
                'password' => $password,
                'role' => UserRole::Athlete->value,
                'email_verified_at' => null,
            ]
        );

        // ── Marc's published session near Cinquantenaire (postal 1000) ────────
        // Price: €20.00 per person — 2 bookings, needs 1 more to confirm
        $sessionDate = Carbon::now()->addDays(10)->format('Y-m-d');

        /** @var SportSession $activeSession */
        $activeSession = SportSession::firstOrCreate(
            [
                'coach_id' => $marc->id,
                'title' => 'Running Cinquantenaire — Cardio Débutant',
            ],
            [
                'activity_type' => ActivityType::Cardio->value,
                'level' => SessionLevel::Beginner->value,
                'description' => 'Séance de running débutant dans le Parc du Cinquantenaire. Rythme accessible, conseils techniques inclus.',
                'location' => 'Parc du Cinquantenaire, Bruxelles',
                'postal_code' => '1000',
                'latitude' => '50.8390',
                'longitude' => '4.3860',
                'date' => $sessionDate,
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'price_per_person' => 2000,
                'min_participants' => 3,
                'max_participants' => 10,
                'current_participants' => 2,
                'status' => SessionStatus::Published->value,
            ]
        );

        // Alice's confirmed booking
        Booking::firstOrCreate(
            [
                'sport_session_id' => $activeSession->id,
                'athlete_id' => $alice->id,
            ],
            [
                'status' => BookingStatus::Confirmed->value,
                'amount_paid' => 2000,
                'stripe_payment_intent_id' => 'pi_mvp_smoke_alice',
            ]
        );

        // Bob's confirmed booking
        Booking::firstOrCreate(
            [
                'sport_session_id' => $activeSession->id,
                'athlete_id' => $bob->id,
            ],
            [
                'status' => BookingStatus::Confirmed->value,
                'amount_paid' => 2000,
                'stripe_payment_intent_id' => 'pi_mvp_smoke_bob',
            ]
        );

        // Diana's pending-payment booking (payment window expires in 20 min — for recovery flow)
        Booking::firstOrCreate(
            [
                'sport_session_id' => $activeSession->id,
                'athlete_id' => $diana->id,
            ],
            [
                'status' => BookingStatus::PendingPayment->value,
                'amount_paid' => 0,
                'stripe_payment_intent_id' => 'pi_mvp_smoke_diana_pending',
                'stripe_checkout_session_id' => 'cs_mvp_smoke_diana',
                'payment_expires_at' => now()->addMinutes(20),
            ]
        );

        // ── Marc's session in Laeken (postal 1020) — confirmed ────────────────
        $laekenDate = Carbon::now()->addDays(7)->format('Y-m-d');

        /** @var SportSession $laekenSession */
        $laekenSession = SportSession::firstOrCreate(
            [
                'coach_id' => $marc->id,
                'title' => 'Running Laeken — Cardio Intermédiaire',
            ],
            [
                'activity_type' => ActivityType::Running->value,
                'level' => SessionLevel::Intermediate->value,
                'description' => 'Séance de running intermédiaire dans le Parc de Laeken. Intervalles et endurance.',
                'location' => 'Parc de Laeken, Bruxelles',
                'postal_code' => '1020',
                'latitude' => '50.8930',
                'longitude' => '4.3570',
                'date' => $laekenDate,
                'start_time' => '07:30:00',
                'end_time' => '08:30:00',
                'price_per_person' => 2500,
                'min_participants' => 2,
                'max_participants' => 8,
                'current_participants' => 3,
                'status' => SessionStatus::Confirmed->value,
            ]
        );

        Booking::firstOrCreate(
            [
                'sport_session_id' => $laekenSession->id,
                'athlete_id' => $alice->id,
            ],
            [
                'status' => BookingStatus::Confirmed->value,
                'amount_paid' => 2500,
                'stripe_payment_intent_id' => 'pi_mvp_smoke_laeken_alice',
            ]
        );

        // ── Marc's session in Ixelles (postal 1050) — published ───────────────
        $ixellesDate = Carbon::now()->addDays(14)->format('Y-m-d');

        SportSession::firstOrCreate(
            [
                'coach_id' => $marc->id,
                'title' => 'Yoga Bois de la Cambre — Stretching',
            ],
            [
                'activity_type' => ActivityType::Yoga->value,
                'level' => SessionLevel::Beginner->value,
                'description' => 'Séance de yoga matinal dans le Bois de la Cambre. Tous niveaux bienvenus.',
                'location' => 'Bois de la Cambre, Ixelles',
                'postal_code' => '1050',
                'latitude' => '50.8130',
                'longitude' => '4.3840',
                'date' => $ixellesDate,
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'price_per_person' => 1500,
                'min_participants' => 3,
                'max_participants' => 12,
                'current_participants' => 1,
                'status' => SessionStatus::Published->value,
            ]
        );

        // ── Marc's cancelled session (Bob booked, then it was cancelled) ──────
        $cancelledDate = Carbon::now()->subDays(5)->format('Y-m-d');

        /** @var SportSession $cancelledSession */
        $cancelledSession = SportSession::firstOrCreate(
            [
                'coach_id' => $marc->id,
                'title' => 'Running Scharbeek — Piste de vitesse',
            ],
            [
                'activity_type' => ActivityType::Running->value,
                'level' => SessionLevel::Advanced->value,
                'description' => 'Séance de running avancée à Scharbeek. Piste de vitesse.',
                'location' => 'Parc Josaphat, Scharbeek',
                'postal_code' => '1030',
                'latitude' => '50.8650',
                'longitude' => '4.3810',
                'date' => $cancelledDate,
                'start_time' => '06:30:00',
                'end_time' => '07:30:00',
                'price_per_person' => 3000,
                'min_participants' => 5,
                'max_participants' => 15,
                'current_participants' => 1,
                'status' => SessionStatus::Cancelled->value,
            ]
        );

        Booking::firstOrCreate(
            [
                'sport_session_id' => $cancelledSession->id,
                'athlete_id' => $bob->id,
            ],
            [
                'status' => BookingStatus::Refunded->value,
                'amount_paid' => 3000,
                'stripe_payment_intent_id' => 'pi_mvp_smoke_scharbeek_bob',
            ]
        );

        // ── Marc's previous completed session + draft invoice ─────────────────
        $completedDate = Carbon::now()->subDays(30)->format('Y-m-d');

        /** @var SportSession $completedSession */
        $completedSession = SportSession::firstOrCreate(
            [
                'coach_id' => $marc->id,
                'title' => 'Running Etterbeek — Cardio Avancé',
            ],
            [
                'activity_type' => ActivityType::Running->value,
                'level' => SessionLevel::Advanced->value,
                'description' => 'Séance de running avancée à Etterbeek. Intervalles intensifs.',
                'location' => 'Parc du Cinquantenaire, Etterbeek',
                'postal_code' => '1040',
                'latitude' => '50.8370',
                'longitude' => '4.3920',
                'date' => $completedDate,
                'start_time' => '07:30:00',
                'end_time' => '08:30:00',
                'price_per_person' => 2500,
                'min_participants' => 3,
                'max_participants' => 8,
                'current_participants' => 5,
                'status' => SessionStatus::Completed->value,
            ]
        );

        // Draft invoice for the completed session (covers the previous billing month).
        // Note: these values are pre-computed inline to give the invoice realistic-looking
        // seed data without requiring the full service stack during seeding. The actual
        // invoice generation in production uses the service layer (InvoiceService).
        $billingStart = Carbon::now()->subMonth()->startOfMonth();
        $billingEnd = Carbon::now()->subMonth()->endOfMonth();

        // 5 participants × €25.00 = €125.00 TTC
        $revenueTtc = 12500;
        $revenueHtva = (int) round($revenueTtc / 1.21);
        // Freemium plan: 30% commission on HTVA
        $commissionAmount = (int) round($revenueHtva * 30 / 100);
        $coachPayout = $revenueHtva - $commissionAmount;
        $vatAmount = (int) round($revenueHtva * 21 / 100);
        $stripeFee = (int) round($revenueTtc * 15 / 1000);

        Invoice::firstOrCreate(
            [
                'coach_id' => $marc->id,
                'sport_session_id' => $completedSession->id,
            ],
            [
                'type' => InvoiceType::Invoice->value,
                'billing_period_start' => $billingStart->format('Y-m-d'),
                'billing_period_end' => $billingEnd->format('Y-m-d'),
                'revenue_ttc' => $revenueTtc,
                'revenue_htva' => $revenueHtva,
                'vat_amount' => $vatAmount,
                'stripe_fee' => $stripeFee,
                'subscription_fee' => 0,
                'commission_amount' => $commissionAmount,
                'coach_payout' => $coachPayout,
                'platform_margin' => $commissionAmount,
                'plan_applied' => 'freemium',
                'tax_category_code' => 'S',
                'status' => InvoiceStatus::Draft->value,
            ]
        );

        // ── Payout statement for Marc (previous month, draft) ─────────────────
        $prevMonth = Carbon::now()->subMonth();

        CoachPayoutStatement::firstOrCreate(
            [
                'coach_id' => $marc->id,
                'period_month' => $prevMonth->month,
                'period_year' => $prevMonth->year,
            ],
            [
                'status' => CoachPayoutStatementStatus::Draft->value,
                'sessions_count' => 1,
                'paid_bookings_count' => 5,
                'revenue_ttc' => $revenueTtc,
                'revenue_htva' => $revenueHtva,
                'vat_amount' => $vatAmount,
                'payment_fees' => $stripeFee,
                'subscription_tier' => 'freemium',
                'commission_rate' => 30,
                'commission_amount' => $commissionAmount,
                'coach_payout' => $coachPayout,
                'is_vat_subject' => true,
            ]
        );

        // ── Payment anomaly (open, for admin/accountant anomaly queue) ─────────
        PaymentAnomaly::firstOrCreate(
            [
                'anomaly_type' => PaymentAnomalyType::CompletedSessionWithoutInvoice->value,
                'related_coach_id' => $marc->id,
            ],
            [
                'anomalous_model_type' => SportSession::class,
                'anomalous_model_id' => $completedSession->id,
                'related_session_id' => $completedSession->id,
                'resolution_status' => 'open',
                'description' => 'Demo anomaly: completed session without a finalized invoice. Used for smoke testing the anomaly queue.',
                'recommended_action' => 'Generate and finalise the invoice via the accountant dashboard.',
            ]
        );

        if ($this->command !== null) {
            $this->command->info('✅ MvpJourneySeeder: scenario created successfully.');
            $this->command->info('');
            $this->command->info('  Test accounts (password: "password"):');
            $this->command->info('    admin@motivya.test              — Admin');
            $this->command->info('    accountant@motivya.test         — Accountant');
            $this->command->info('    sophie.coach@motivya.test       — Coach (pending approval)');
            $this->command->info('    marc.coach@motivya.test         — Coach (approved + Stripe-ready)');
            $this->command->info('    alice@motivya.test              — Athlete (confirmed bookings)');
            $this->command->info('    bob@motivya.test                — Athlete (confirmed booking + refund)');
            $this->command->info('    charlie@motivya.test            — Athlete (no bookings — use for journey test)');
            $this->command->info('    diana@motivya.test              — Athlete (pending-payment booking)');
            $this->command->info('    suspended@motivya.test          — Athlete (suspended account)');
            $this->command->info('    unverified@motivya.test         — Athlete (unverified email)');
            $this->command->info('');
            $this->command->info('  Sessions across Brussels postal codes: 1000, 1020, 1030, 1040, 1050');
            $this->command->info('  ⚠  Replace stripe_account_id "acct_mvp_smoke_test" with a real Stripe test Express account ID.');
            $this->command->info('  📖 See doc/MVP-Smoke-Test.md for the full manual QA checklist.');
        }
    }
}
